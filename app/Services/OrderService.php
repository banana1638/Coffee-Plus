<?php

namespace App\Services;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Support\Facades\DB;

class OrderService
{
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

            if ($lockedOrder->status !== 'pending') {
                throw new OrderException('Only pending orders can be cancelled.');
            }

            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $cashRefund = (float) $lockedOrder->final_amount;
            $ozRefund = (int) $lockedOrder->oz_used;
            $rewardOz = $this->calculateCashRewardOz($lockedOrder);

            if ($cashRefund > 0) {
                $lockedUser->increment('tangki_balance', $cashRefund);
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

            $lockedOrder->status = 'cancelled';
            $lockedOrder->cancelled_at = now();
            $lockedOrder->save();

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

            if ($lockedOrder->status === 'cancelled') {
                throw new OrderException('Cancelled orders cannot be completed.');
            }

            if ($lockedOrder->status === 'completed') {
                return $lockedOrder;
            }

            $lockedOrder->status = 'completed';
            $lockedOrder->completed_at = now();
            $lockedOrder->save();
            $lockedOrder->user->notify(new OrderCompletedNotification($lockedOrder));

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

    private function calculateCashRewardOz(Order $order): int
    {
        return $order->items
            ->filter(fn ($item) => (int) $item->oz_at_time === 0)
            ->sum(fn ($item) => (int) (((float) $item->price_at_time * (int) $item->quantity * 100) / 2));
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

        $referrer->decrement('tangki_balance', min((float) $referrer->tangki_balance, 5.00));
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
}
