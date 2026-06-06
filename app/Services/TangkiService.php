<?php

namespace App\Services;

use App\Contracts\TangkiServiceInterface;
use App\Models\User;
use App\Models\Order;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class TangkiService implements TangkiServiceInterface
{
    public function __construct(private readonly LedgerService $ledgerService)
    {
    }

    /**
     * Refill user's account balance and reward them with OZ.
     */
    public function refillBalance(User $user, float $amount, string $billId): bool
    {
        $amountCents = Money::toCents($amount);
        $ozToInject = (int) ($amount * 10);

        DB::transaction(function () use ($user, $amount, $amountCents, $ozToInject, $billId) {
            $this->ledgerService->credit(
                $user,
                $amountCents,
                'stripe_refill',
                $billId,
                "stripe_refill:{$billId}",
                'Refilled RM' . number_format($amount, 2)
            );
            $user->increment('tangki_oz', $ozToInject);

            $order = new Order();
            $order->user_id = $user->id;
            $order->bill_id = $billId;
            $order->subtotal = $amount;
            $order->subtotal_cents = $amountCents;
            $order->oz_used = 0;
            $order->final_amount = $amount;
            $order->final_amount_cents = $amountCents;
            $order->status = 'completed';
            $order->save();

            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->bill_id = $billId;
            $transaction->oz_delta = $ozToInject;
            $transaction->type = 'refill';
            $transaction->description = "Refilled RM" . number_format($amount, 2) . " (Earned {$ozToInject} OZ)";
            $transaction->save();
        });

        return true;
    }

    /**
     * Drain user's OZ balance.
     */
    public function drainOz(User $user, int $ozAmount, string $billId, string $description = 'Redeemed items'): bool
    {
        return DB::transaction(function () use ($user, $ozAmount, $billId, $description) {
            $userObj = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$userObj || $userObj->tangki_oz < $ozAmount) {
                return false;
            }

            $userObj->decrement('tangki_oz', $ozAmount);

            $transaction = new Transaction();
            $transaction->user_id = $userObj->id;
            $transaction->bill_id = $billId;
            $transaction->oz_delta = -$ozAmount;
            $transaction->type = 'drain';
            $transaction->description = $description;
            $transaction->save();

            return true;
        });
    }

    /**
     * Deduct user's cash balance and reward them with OZ.
     */
    public function deductBalanceAndRewardOz(User $user, float $amount, int $rewardOz, string $billId, string $description): bool
    {
        return DB::transaction(function () use ($user, $amount, $rewardOz, $billId, $description) {
            $userObj = User::where('id', $user->id)->lockForUpdate()->first();
            $amountCents = Money::toCents($amount);
            $balanceCents = (int) ($userObj?->tangki_balance_cents ?? Money::toCents($userObj?->tangki_balance));

            if (!$userObj || $balanceCents < $amountCents) {
                return false;
            }

            if ($amountCents > 0) {
                $ledger = $this->ledgerService->debit(
                    $userObj,
                    $amountCents,
                    'order_payment',
                    $billId,
                    "order_payment:{$billId}",
                    $description
                );

                if (!$ledger) {
                    return false;
                }
            }

            if ($rewardOz > 0) {
                $userObj->increment('tangki_oz', $rewardOz);

                $transaction = new Transaction();
                $transaction->user_id = $userObj->id;
                $transaction->bill_id = $billId;
                $transaction->oz_delta = $rewardOz;
                $transaction->type = 'refill';
                $transaction->description = $description;
                $transaction->save();
            }

            return true;
        });
    }
}
