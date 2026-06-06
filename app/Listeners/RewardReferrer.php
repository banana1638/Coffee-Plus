<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\User;
use App\Models\Order;
use App\Models\Transaction;

class RewardReferrer
{
    public function handle(OrderPlaced $event): void
    {
        $user = $event->user;

        $completedProductOrderCount = Order::where('user_id', $user->id)
            ->whereHas('items')
            ->count();

        if ($user->referrer_id && $completedProductOrderCount === 1) {
            $markedForReward = User::where('id', $user->id)
                ->where('referral_rewarded', false)
                ->update(['referral_rewarded' => true]);

            if ($markedForReward !== 1) {
                return;
            }

            $referrer = User::find($user->referrer_id);
            if ($referrer) {
                $referrer->increment('tangki_balance', 5.00);
                $referrer->increment('tangki_oz', 50);

                $transaction = new Transaction();
                $transaction->user_id = $referrer->id;
                $transaction->bill_id = $event->order->bill_id;
                $transaction->oz_delta = 50;
                $transaction->type = 'refill';
                $transaction->description = "Referral Reward: referred user {$user->name} placed first order (Earned RM 5.00 & 50 OZ)";
                $transaction->save();
            }
        }
    }
}
