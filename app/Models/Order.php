<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bill_id',
        'pickup_code',
        'status',
        'subtotal',
        'subtotal_cents',
        'final_amount',
        'final_amount_cents',
        'payment_method',
        'pickup_time',
        'oz_used',
        'cancelled_at',
        'completed_at',
        'coupon_code',
        'discount_cents',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready_for_pickup';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FLOW = [
        self::STATUS_PENDING,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_COMPLETED,
    ];

    protected $attributes = [
        'oz_used' => 0,
        'status' => self::STATUS_PENDING,
    ];

    protected $casts = [
        'pickup_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected function subtotal(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => [
                'subtotal' => $value,
                'subtotal_cents' => Money::toCents($value),
            ],
        );
    }

    protected function finalAmount(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => [
                'final_amount' => $value,
                'final_amount_cents' => Money::toCents($value),
            ],
        );
    }

    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function nextStatus(): ?string
    {
        $currentIndex = array_search($this->status, self::STATUS_FLOW, true);

        if ($currentIndex === false) {
            return null;
        }

        return self::STATUS_FLOW[$currentIndex + 1] ?? null;
    }

    public function canAdvanceStatus(): bool
    {
        return $this->nextStatus() !== null;
    }

    public function statusStep(): int
    {
        $currentIndex = array_search($this->status, self::STATUS_FLOW, true);

        return $currentIndex === false ? 0 : $currentIndex + 1;
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

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }
}
