<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\CartItem;

class ClearUserCart
{
    public function handle(OrderPlaced $event): void
    {
        CartItem::where('user_id', $event->user->id)->delete();
    }
}
