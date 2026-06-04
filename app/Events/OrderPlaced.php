<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public Order $order;
    public User $user;
    public array $useOzIds;
    public float $totalCashToPay;
    public int $totalOzToDrain;
    public int $totalRewardOz;

    public function __construct(
        Order $order,
        User $user,
        array $useOzIds,
        float $totalCashToPay,
        int $totalOzToDrain,
        int $totalRewardOz
    ) {
        $this->order = $order;
        $this->user = $user;
        $this->useOzIds = $useOzIds;
        $this->totalCashToPay = $totalCashToPay;
        $this->totalOzToDrain = $totalOzToDrain;
        $this->totalRewardOz = $totalRewardOz;
    }
}
