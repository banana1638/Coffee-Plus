<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Coupon extends Model
{
    protected $casts = [
        'expires_at' => 'datetime',
        'value' => 'decimal:2',
    ];

    protected function value(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => [
                'value' => $value,
                'value_cents' => $this->type === 'fixed' ? Money::toCents($value) : null,
            ],
        );
    }

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
        return Money::fromCents($this->calculateDiscountCents(Money::toCents($subtotal)));
    }

    public function calculateDiscountCents(int $subtotalCents): int
    {
        if ($this->type === 'percent') {
            return (int) round($subtotalCents * (((float) $this->value) / 100));
        }

        return min((int) ($this->value_cents ?? Money::toCents($this->value)), $subtotalCents);
    }

    /**
     * Increment usage counter.
     */
    public function markUsed(): void
    {
        $this->increment('used_count');
    }

    public function redeemForOrder(User $user, Order $order, int $discountCents): bool
    {
        if ($discountCents <= 0 || CouponRedemption::where('coupon_id', $this->id)->where('user_id', $user->id)->exists()) {
            return false;
        }

        $updated = self::where('id', $this->id)
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->update([
                'used_count' => DB::raw('used_count + 1'),
            ]);

        if ($updated !== 1) {
            return false;
        }

        CouponRedemption::create([
            'coupon_id' => $this->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'discount_cents' => $discountCents,
        ]);

        $this->refresh();

        return true;
    }

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }
}
