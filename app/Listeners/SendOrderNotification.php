<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderNotification implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(OrderPlaced $event): void
    {
        $event->user->notify(new OrderPlacedNotification($event->order));
    }
}
