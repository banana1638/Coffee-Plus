<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class RealtimeBusinessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly string $title,
        private readonly string $message,
        private readonly array $action,
        private readonly array $data,
        private readonly string $occurredAt,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    public function broadcastType(): string
    {
        return $this->event;
    }

    public function eventName(): string
    {
        return $this->event;
    }

    public function payload(): array
    {
        return [
            'notification_id' => $this->id,
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'occurred_at' => $this->occurredAt,
            'action' => $this->action,
            'data' => $this->data,
        ];
    }
}
