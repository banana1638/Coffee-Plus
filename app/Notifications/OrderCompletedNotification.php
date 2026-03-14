<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class OrderCompletedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'order_id' => $this->order->id,
            'message' => 'Your order #' . $this->order->id . ' has been completed. Please proceed to the counter to collect it!',
        ]);
    }

    public function broadcastOn()
    {
        return ['private-App.Models.User.' . $this->order->user_id];
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'bill_id' => $this->order->bill_id,
            'message' => 'Your order has been completed. Please proceed to the counter to collect it!',
            'completed_at' => now()->toDateTimeString(),
        ];
    }
}