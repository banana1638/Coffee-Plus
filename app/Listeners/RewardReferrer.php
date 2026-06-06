<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\User;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\LedgerService;

class RewardReferrer
{
    public function __construct(private readonly LedgerService $ledgerService)
    {
    }

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
                $this->ledgerService->credit(
                    $referrer,
                    500,
                    'referral_reward',
                    $event->order->bill_id,
                    "referral_reward:{$event->order->bill_id}:{$referrer->id}",
                    "Referral Reward: referred user {$user->name} placed first order"
                );
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
