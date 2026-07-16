<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\RealtimeNotificationService;

class SendOrderNotification
{
    public function __construct(private readonly RealtimeNotificationService $notificationService) {}

    public function handle(OrderPlaced $event): void
    {
        $this->notificationService->orderAccepted($event->order, $event->user);
    }
}
