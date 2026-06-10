<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'price_cents',
        'price_at_time',
        'price_at_time_cents',
        'options',
        'oz_at_time',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    protected $attributes = [
        'oz_at_time' => 0,
        'price' => 0.00,
    ];

    protected function price(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => [
                'price' => $value,
                'price_cents' => Money::toCents($value),
            ],
        );
    }

    protected function priceAtTime(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => [
                'price_at_time' => $value,
                'price_at_time_cents' => Money::toCents($value),
            ],
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
