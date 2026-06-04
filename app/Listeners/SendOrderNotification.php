<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Notifications\OrderPlacedNotification;

class SendOrderNotification
{
    public function handle(OrderPlaced $event): void
    {
        $event->user->notify(new OrderPlacedNotification($event->order));
    }
}
