<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Contracts\TangkiServiceInterface;

class RewardUserOz
{
    protected TangkiServiceInterface $tangkiService;

    public function __construct(TangkiServiceInterface $tangkiService)
    {
        $this->tangkiService = $tangkiService;
    }

    public function handle(OrderPlaced $event): void
    {
        if ($event->totalRewardOz > 0) {
            $description = "Cash Reward (Order: {$event->order->bill_id})";
            $this->tangkiService->deductBalanceAndRewardOz($event->user, 0.00, $event->totalRewardOz, $event->order->bill_id, $description);
        }
    }
}
