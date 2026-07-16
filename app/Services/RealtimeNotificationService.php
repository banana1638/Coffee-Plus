<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Notifications\RealtimeBusinessNotification;
use Throwable;

class RealtimeNotificationService
{
    public function orderAccepted(Order $order, ?User $user = null): void
    {
        $this->sendOrderNotification(
            $order,
            'order.accepted',
            'Order accepted',
            "Order {$order->bill_id} has been accepted.",
            $user,
        );
    }

    public function orderStatusChanged(Order $order): void
    {
        $notification = match ($order->status) {
            Order::STATUS_PREPARING => [
                'order.preparing',
                'Coffee is being prepared',
                "Order {$order->bill_id} is now being prepared.",
            ],
            Order::STATUS_READY => [
                'order.ready_for_pickup',
                'Order ready for pickup',
                "Order {$order->bill_id} is ready for pickup.",
            ],
            Order::STATUS_COMPLETED => [
                'order.completed',
                'Order completed',
                "Order {$order->bill_id} has been completed.",
            ],
            Order::STATUS_CANCELLED => [
                'order.cancelled',
                'Order cancelled',
                "Order {$order->bill_id} has been cancelled.",
            ],
            default => null,
        };

        if ($notification === null) {
            return;
        }

        $this->sendOrderNotification($order, ...$notification);
    }

    public function pickupReminder(Order $order): void
    {
        $this->sendOrderNotification(
            $order,
            'order.pickup_reminder',
            'Pickup time approaching',
            "Order {$order->bill_id} is ready. Please collect it soon.",
        );
    }

    public function paymentProcessed(PaymentEvent $paymentEvent, mixed $handlerResult = null): void
    {
        $user = $paymentEvent->user;
        if (! $user) {
            return;
        }

        if ($paymentEvent->type === 'refill') {
            $this->send(
                $user,
                'wallet.refill_succeeded',
                'Tangki refill complete',
                'RM'.number_format($paymentEvent->amount_cents / 100, 2).' has been added to your Tangki.',
                ['type' => 'tangki'],
                $this->paymentData($paymentEvent),
            );

            return;
        }

        $order = $handlerResult instanceof Order ? $handlerResult : null;
        $action = $order
            ? ['type' => 'order_detail', 'order_id' => $order->id, 'bill_id' => $order->bill_id]
            : ['type' => 'notifications'];

        $this->send(
            $user,
            'payment.checkout_succeeded',
            'Payment confirmed',
            $order
                ? "Payment for order {$order->bill_id} has been confirmed."
                : 'Your Stripe payment has been confirmed.',
            $action,
            array_filter([
                ...$this->paymentData($paymentEvent),
                'order_id' => $order?->id,
                'bill_id' => $order?->bill_id,
            ], fn ($value) => $value !== null),
        );
    }

    public function paymentFailed(PaymentEvent $paymentEvent): void
    {
        $user = $paymentEvent->user;
        if (! $user) {
            return;
        }

        $isRefill = $paymentEvent->type === 'refill';

        $this->send(
            $user,
            $isRefill ? 'wallet.refill_failed' : 'payment.checkout_failed',
            $isRefill ? 'Tangki refill failed' : 'Payment was not completed',
            $isRefill
                ? 'Your Tangki balance was not changed. Please try the refill again.'
                : 'Your order payment was not completed. Please try again.',
            ['type' => $isRefill ? 'tangki' : 'notifications'],
            $this->paymentData($paymentEvent),
        );
    }

    private function sendOrderNotification(
        Order $order,
        string $event,
        string $title,
        string $message,
        ?User $user = null,
    ): void {
        $user ??= $order->user;
        if (! $user) {
            return;
        }

        $this->send(
            $user,
            $event,
            $title,
            $message,
            ['type' => 'order_detail', 'order_id' => $order->id, 'bill_id' => $order->bill_id],
            array_filter([
                'order_id' => $order->id,
                'bill_id' => $order->bill_id,
                'order_status' => $order->status,
                'pickup_code' => $order->pickup_code,
                'pickup_time' => $order->pickup_time?->toIso8601String(),
            ], fn ($value) => $value !== null),
        );
    }

    private function paymentData(PaymentEvent $paymentEvent): array
    {
        return array_filter([
            'session_id' => $paymentEvent->session_id,
            'payment_status' => $paymentEvent->status,
            'payment_type' => $paymentEvent->type,
            'amount_cents' => $paymentEvent->amount_cents,
            'currency' => strtolower((string) $paymentEvent->currency),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function send(
        User $user,
        string $event,
        string $title,
        string $message,
        array $action,
        array $data,
    ): void {
        try {
            $user->notify(new RealtimeBusinessNotification(
                event: $event,
                title: $title,
                message: $message,
                action: $action,
                data: $data,
                occurredAt: now()->toIso8601String(),
            ));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
