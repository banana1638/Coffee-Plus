<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ProductAddon extends Model
{
    protected function price(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => [
                'price' => $value,
                'price_cents' => Money::toCents($value),
            ],
        );
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
