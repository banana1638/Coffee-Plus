<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Exceptions\CheckoutException;
use App\Contracts\TangkiServiceInterface;

class DeductUserBalance
{
    protected TangkiServiceInterface $tangkiService;

    public function __construct(TangkiServiceInterface $tangkiService)
    {
        $this->tangkiService = $tangkiService;
    }

    public function handle(OrderPlaced $event): void
    {
        if ($event->totalOzToDrain > 0) {
            $description = "OZ Redeem (Order: {$event->order->bill_id})";
            $drained = $this->tangkiService->drainOz($event->user, $event->totalOzToDrain, $event->order->bill_id, $description);
            if (!$drained) {
                throw new CheckoutException(
                    "Insufficient OZ Balance. Required: " . number_format($event->totalOzToDrain)
                );
            }
        }

        if ($event->totalCashToPay > 0) {
            $description = "Cash Reward (Order: {$event->order->bill_id})";
            $deducted = $this->tangkiService->deductBalanceAndRewardOz($event->user, $event->totalCashToPay, 0, $event->order->bill_id, $description);
            if (!$deducted) {
                throw new CheckoutException(
                    "Insufficient Balance. Required: RM " . number_format($event->totalCashToPay, 2)
                );
            }
        }
    }
}
