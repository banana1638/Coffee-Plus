<?php

namespace App\Services;

use App\Contracts\TangkiServiceInterface;
use App\Models\User;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TangkiService implements TangkiServiceInterface
{
    /**
     * Refill user's account balance and reward them with OZ.
     */
    public function refillBalance(User $user, float $amount, string $billId): bool
    {
        $ozToInject = (int) ($amount * 10);

        DB::transaction(function () use ($user, $amount, $ozToInject, $billId) {
            $user->increment('tangki_balance', $amount);
            $user->increment('tangki_oz', $ozToInject);

            $order = new Order();
            $order->user_id = $user->id;
            $order->bill_id = $billId;
            $order->subtotal = $amount;
            $order->oz_used = 0;
            $order->final_amount = $amount;
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
        if ($user->tangki_oz < $ozAmount) {
            return false;
        }

        $user->decrement('tangki_oz', $ozAmount);

        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->bill_id = $billId;
        $transaction->oz_delta = -$ozAmount;
        $transaction->type = 'drain';
        $transaction->description = $description;
        $transaction->save();

        return true;
    }

    /**
     * Deduct user's cash balance and reward them with OZ.
     */
    public function deductBalanceAndRewardOz(User $user, float $amount, int $rewardOz, string $billId, string $description): bool
    {
        if ($user->tangki_balance < $amount) {
            return false;
        }

        $user->decrement('tangki_balance', $amount);
        if ($rewardOz > 0) {
            $user->increment('tangki_oz', $rewardOz);

            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->bill_id = $billId;
            $transaction->oz_delta = $rewardOz;
            $transaction->type = 'refill';
            $transaction->description = $description;
            $transaction->save();
        }

        return true;
    }
}