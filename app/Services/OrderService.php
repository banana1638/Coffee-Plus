<?php

namespace App\Services;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly OrderStateMachine $orderStateMachine
    )
    {
    }

    public function cancel(Order $order, User $user): Order
    {
        return DB::transaction(function () use ($order, $user) {
            $lockedOrder = Order::where('id', $order->id)
                ->with(['items', 'user'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->user_id !== $user->id) {
                throw new OrderException('You are not allowed to cancel this order.', 403);
            }

            if ($lockedOrder->status !== Order::STATUS_PENDING) {
                throw new OrderException('Only pending orders can be cancelled.');
            }

            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $cashRefundCents = (int) ($lockedOrder->final_amount_cents ?? Money::toCents($lockedOrder->final_amount));
            $cashRefund = Money::fromCents($cashRefundCents);
            $ozRefund = (int) $lockedOrder->oz_used;
            $rewardOz = $this->calculateCashRewardOz($lockedOrder);

            if ($cashRefund > 0) {
                $this->ledgerService->credit(
                    $lockedUser,
                    $cashRefundCents,
                    'refund',
                    $lockedOrder->bill_id,
                    "refund:cash:{$lockedOrder->bill_id}",
                    'Cancelled order refund: RM ' . number_format($cashRefund, 2)
                );
                $this->recordTransaction(
                    $lockedUser->id,
                    $lockedOrder->bill_id,
                    0,
                    'refund',
                    'Cancelled order refund: RM ' . number_format($cashRefund, 2)
                );
            }

            if ($ozRefund > 0) {
                $lockedUser->increment('tangki_oz', $ozRefund);
                $this->recordTransaction(
                    $lockedUser->id,
                    $lockedOrder->bill_id,
                    $ozRefund,
                    'refund',
                    "Cancelled order OZ refund: {$ozRefund} OZ"
                );
            }

            if ($rewardOz > 0) {
                $reversedRewardOz = min((int) $lockedUser->fresh()->tangki_oz, $rewardOz);

                if ($reversedRewardOz > 0) {
                    $lockedUser->decrement('tangki_oz', $reversedRewardOz);
                    $this->recordTransaction(
                        $lockedUser->id,
                        $lockedOrder->bill_id,
                        -$reversedRewardOz,
                        'refund',
                        "Cancelled order reward reversal: {$reversedRewardOz} OZ"
                    );
                }
            }

            $this->reverseReferralReward($lockedOrder);
            $this->restoreStock($lockedOrder);

            $this->orderStateMachine->transition($lockedOrder, Order::STATUS_CANCELLED);

            return $lockedOrder->fresh(['items.product']);
        });
    }

    public function complete(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::where('id', $order->id)
                ->with('user')
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status === Order::STATUS_CANCELLED) {
                throw new OrderException('Cancelled orders cannot be completed.');
            }

            if ($lockedOrder->status === Order::STATUS_COMPLETED) {
                return $lockedOrder;
            }

            if ($lockedOrder->status !== Order::STATUS_READY) {
                throw new OrderException('Only ready for pickup orders can be completed.');
            }

            $this->orderStateMachine->transition($lockedOrder, Order::STATUS_COMPLETED);

            return $lockedOrder->fresh(['items.product', 'user']);
        });
    }

    public function completeByPickupCode(string $pickupCode): Order
    {
        $normalizedCode = strtoupper(trim($pickupCode));
        $order = Order::where('pickup_code', $normalizedCode)->first();

        if (!$order) {
            throw new OrderException('Pickup code not found.', 404);
        }

        return $this->complete($order);
    }

    public function advanceStatus(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::where('id', $order->id)
                ->with(['items.product', 'user'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status === Order::STATUS_CANCELLED) {
                throw new OrderException('Cancelled orders cannot be updated.');
            }

            $nextStatus = $lockedOrder->nextStatus();
            if (!$nextStatus) {
                throw new OrderException('Order status cannot be advanced.');
            }

            $this->orderStateMachine->transition($lockedOrder, $nextStatus);

            return $lockedOrder->fresh(['items.product', 'user']);
        });
    }

    private function calculateCashRewardOz(Order $order): int
    {
        return $order->items
            ->filter(fn ($item) => (int) $item->oz_at_time === 0)
            ->sum(fn ($item) => (int) (((int) ($item->price_at_time_cents ?? Money::toCents($item->price_at_time)) * (int) $item->quantity) / 2));
    }

    private function reverseReferralReward(Order $order): void
    {
        $referralReward = Transaction::where('bill_id', $order->bill_id)
            ->where('description', 'like', 'Referral Reward:%')
            ->first();

        if (!$referralReward) {
            return;
        }

        $referrer = User::where('id', $referralReward->user_id)->lockForUpdate()->first();
        if (!$referrer) {
            return;
        }

        $referralCashReversalCents = min($this->ledgerService->balanceCents($referrer), 500);
        if ($referralCashReversalCents > 0) {
            $this->ledgerService->debit(
                $referrer,
                $referralCashReversalCents,
                'refund',
                $order->bill_id,
                "refund:referral_cash:{$order->bill_id}",
                'Cancelled order referral cash reward reversal'
            );
        }
        $reversedOz = min((int) $referrer->tangki_oz, 50);

        if ($reversedOz > 0) {
            $referrer->decrement('tangki_oz', $reversedOz);
        }

        $this->recordTransaction(
            $referrer->id,
            $order->bill_id,
            -$reversedOz,
            'refund',
            'Cancelled order referral reward reversal'
        );

        User::where('id', $order->user_id)->update(['referral_rewarded' => false]);
    }

    private function recordTransaction(
        int $userId,
        string $billId,
        int $ozDelta,
        string $type,
        string $description
    ): void {
        $transaction = new Transaction();
        $transaction->user_id = $userId;
        $transaction->bill_id = $billId;
        $transaction->oz_delta = $ozDelta;
        $transaction->type = $type;
        $transaction->description = $description;
        $transaction->save();
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            Product::where('id', $item->product_id)
                ->where('track_stock', true)
                ->increment('stock', (int) $item->quantity);
        }
    }
}
