<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\CartItem;
use App\Models\CartSnapshot;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartSnapshotService
{
    public function createFromCart(User $user, array $useOzIds, ?string $couponCode, ?string $pickupTime): CartSnapshot
    {
        return DB::transaction(function () use ($user, $useOzIds, $couponCode, $pickupTime) {
            $cartItems = CartItem::where('user_id', $user->id)
                ->with('product')
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw new CheckoutException('Your cart is empty.');
            }

            $items = [];
            $subtotalCents = 0;
            $cashSubtotalCents = 0;
            $ozUsed = 0;

            foreach ($cartItems as $item) {
                $unitPriceCents = (int) round(((float) $item->unit_price) * 100);
                $lineTotalCents = $unitPriceCents * (int) $item->quantity;
                $paidWithOz = in_array($item->id, $useOzIds);

                $subtotalCents += $lineTotalCents;

                if ($paidWithOz) {
                    $ozUsed += $lineTotalCents;
                } else {
                    $cashSubtotalCents += $lineTotalCents;
                }

                $items[] = [
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_price_cents' => (int) round(((float) $item->product->price) * 100),
                    'quantity' => (int) $item->quantity,
                    'size' => $item->size,
                    'temp' => $item->temp,
                    'addons' => $item->addons ?? [],
                    'unit_price_cents' => $unitPriceCents,
                    'paid_with_oz' => $paidWithOz,
                ];
            }

            $discountCents = 0;
            if ($couponCode && $cashSubtotalCents > 0) {
                $coupon = Coupon::where('code', $couponCode)->first();
                if ($coupon && $coupon->isValid()) {
                    $discountCents = (int) round($coupon->calculateDiscount($cashSubtotalCents / 100) * 100);
                }
            }

            $snapshot = new CartSnapshot();
            $snapshot->user_id = $user->id;
            $snapshot->items_json = $items;
            $snapshot->subtotal_cents = $subtotalCents;
            $snapshot->discount_cents = $discountCents;
            $snapshot->oz_used = $ozUsed;
            $snapshot->final_amount_cents = max(0, $cashSubtotalCents - $discountCents);
            $snapshot->coupon_code = $couponCode ? strtoupper($couponCode) : null;
            $snapshot->pickup_time = $pickupTime;
            $snapshot->status = CartSnapshot::STATUS_PENDING;
            $snapshot->expires_at = now()->addMinutes(30);
            $snapshot->save();

            return $snapshot;
        });
    }
}
