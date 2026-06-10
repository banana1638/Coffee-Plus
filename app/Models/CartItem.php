<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'addons',
        'unit_price',
        'unit_price_cents',
        'special_instructions',
    ];

    protected $casts = [
        'addons' => 'array',
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    protected function unitPrice(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => [
                'unit_price' => $value,
                'unit_price_cents' => Money::toCents($value),
            ],
        );
    }

    public function getRequiredOzAttribute()
    {
        return ($this->unit_price_cents ?? Money::toCents($this->unit_price)) * $this->quantity;
    }
}
