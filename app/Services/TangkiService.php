<?php

namespace App\Services;

class TangkiService {
    public static function drain($user, $ozAmount, $billId) {
        if ($user->tangki_oz < $ozAmount) return false;
        
        $user->decrement('tangki_oz', $ozAmount);
        $user->transactions()->create([
            'bill_id' => $billId,
            'oz_delta' => -$ozAmount,
            'type' => 'drain',
            'description' => 'Redeemed items'
        ]);
        return true;
    }

    /**
     * Handle account balance refill.
     */
    public static function refill($user, $amount, $billId)
    {
        $ozToInject = (int) ($amount * 10);

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $amount, $ozToInject, $billId) {
            $user->increment('tangki_balance', $amount);
            $user->increment('tangki_oz', $ozToInject);

            $order = new \App\Models\Order();
            $order->user_id = $user->id;
            $order->bill_id = $billId;
            $order->subtotal = $amount;
            $order->oz_used = 0;
            $order->final_amount = $amount;
            $order->status = 'completed';
            $order->save();

            $transaction = new \App\Models\Transaction();
            $transaction->user_id = $user->id;
            $transaction->bill_id = $billId;
            $transaction->oz_delta = $ozToInject;
            $transaction->type = 'refill';
            $transaction->description = "Refilled RM" . number_format($amount, 2) . " (Earned {$ozToInject} OZ)";
            $transaction->save();
        });

        return true;
    }
}