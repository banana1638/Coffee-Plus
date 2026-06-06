<?php

namespace App\Services;

use App\Exceptions\OrderException;
use App\Models\Order;

class OrderStateMachine
{
    private const TRANSITIONS = [
        Order::STATUS_PENDING => [
            Order::STATUS_PREPARING,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_PREPARING => [
            Order::STATUS_READY,
        ],
        Order::STATUS_READY => [
            Order::STATUS_COMPLETED,
        ],
    ];

    public function transition(Order $order, string $targetStatus): Order
    {
        if ($order->status === $targetStatus) {
            return $order;
        }

        $allowedTargets = self::TRANSITIONS[$order->status] ?? [];

        if (!in_array($targetStatus, $allowedTargets, true)) {
            throw new OrderException("Order cannot transition from {$order->status} to {$targetStatus}.");
        }

        $order->status = $targetStatus;

        if ($targetStatus === Order::STATUS_COMPLETED) {
            $order->completed_at = now();
        }

        if ($targetStatus === Order::STATUS_CANCELLED) {
            $order->cancelled_at = now();
        }

        $order->save();

        return $order;
    }
}
