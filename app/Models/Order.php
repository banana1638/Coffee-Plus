<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;


    protected $attributes = [
        'oz_used' => 0,
        'status' => 'pending',
    ];

    protected $casts = [
        'pickup_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function canBeCancelled(): bool
    {
        return $this->status === 'pending';
    }

    public function getPickupQrPayloadAttribute(): ?string
    {
        if (!$this->pickup_code) {
            return null;
        }

        return "COFFEEPLUS|{$this->bill_id}|{$this->pickup_code}";
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }
}
