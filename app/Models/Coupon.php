<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $casts = [
        'expires_at' => 'datetime',
        'value' => 'decimal:2',
    ];

    /**
     * Check if the coupon is valid for use.
     */
    public function isValid(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount amount for a given subtotal.
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'percent') {
            return round($subtotal * ($this->value / 100), 2);
        }

        // Fixed discount, capped at subtotal
        return min($this->value, $subtotal);
    }

    /**
     * Increment usage counter.
     */
    public function markUsed(): void
    {
        $this->increment('used_count');
    }
}
