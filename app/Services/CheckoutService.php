<?php

namespace App\Services;

use App\Contracts\CheckoutServiceInterface;
use App\Contracts\TangkiServiceInterface;
use App\Exceptions\CheckoutException;
use App\Models\{CartItem, CartSnapshot, Coupon, Order, OrderItem, User};
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Events\OrderPlaced;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Str;

class CheckoutService implements CheckoutServiceInterface
{
    protected TangkiServiceInterface $tangkiService;

    public function __construct(
        TangkiServiceInterface $tangkiService,
        private readonly CartPricingService $cartPricingService,
    )
    {
        $this->tangkiService = $tangkiService;
    }

    /**
     * Process checkout
     */
    public function processCheckout(User $user, array $useOzIds, ?string $couponCode = null, ?string $pickupTime = null): Order
    {
        $lock = Cache::lock('checkout_user_' . $user->id, 60);

        try {
            $lock->block(5);
        } catch (LockTimeoutException) {
            throw new CheckoutException('Another checkout operation is in progress. Please try again.', 409);
        }

        try {
            $cartItems = CartItem::where('user_id', $user->id)
                ->with('product')
                ->get();

            if ($cartItems->isEmpty()) {
                throw new CheckoutException('Your cart is empty.');
            }

            return DB::transaction(function () use ($user, $cartItems, $useOzIds, $couponCode, $pickupTime) {

                $totalCashToPayCents = 0;
                $totalOzToDrain = 0;
                $totalRewardOz = 0;

                $order = new Order();
                $order->user_id = $user->id;
                $order->bill_id = 'CP-' . strtoupper(uniqid());
                $order->pickup_code = $this->generatePickupCode();
                $order->status = 'pending';
                $order->subtotal = 0;
                $order->final_amount = 0;
                $order->oz_used = 0;
                $order->save();

                foreach ($cartItems as $item) {
                    $this->deductStock($item->product_id, (int) $item->quantity);

                    $unitPriceCents = $this->cartPricingService->refreshUnitPrice($item);
                    $unitPrice = Money::fromCents($unitPriceCents);
                    $quantity = $item->quantity;
                    $itemTotalCents = $unitPriceCents * $quantity;

                    $isRedeem = in_array($item->id, $useOzIds);

                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                    $orderItem->product_id = $item->product_id;
                    $orderItem->product_name = $item->product->name;
                    $orderItem->quantity = $quantity;
                    $orderItem->options = [
                        'size' => $item->size,
                        'temp' => $item->temp,
                        'addons' => $item->addons
                    ];
                    $orderItem->price = Money::fromCents((int) ($item->product->price_cents ?? Money::toCents($item->product->price)));
                    $orderItem->price_cents = (int) ($item->product->price_cents ?? Money::toCents($item->product->price));

                    // OZ redemption
                    if ($isRedeem) {
                        $ozNeeded = $itemTotalCents;
                        $totalOzToDrain += $ozNeeded;
                        $orderItem->oz_at_time = $ozNeeded;
                        $orderItem->price_at_time = 0;
                        $orderItem->price_at_time_cents = 0;
                    }

                    else {
                        $totalCashToPayCents += $itemTotalCents;
                        $orderItem->oz_at_time = 0;
                        $orderItem->price_at_time = $unitPrice;
                        $orderItem->price_at_time_cents = $unitPriceCents;

                        // Reward OZ only for cash payments.
                        $totalRewardOz += (int) ($itemTotalCents / 2);
                    }

                    $orderItem->save();
                }

                // Coupon discount
                $discountCents = 0;
                if ($couponCode && $totalCashToPayCents > 0) {
                    $coupon = Coupon::where('code', $couponCode)->first();
                    if ($coupon && $coupon->isValid()) {
                        $discountCents = $coupon->calculateDiscountCents($totalCashToPayCents);
                        if (!$coupon->redeemForOrder($user, $order, $discountCents)) {
                            $discountCents = 0;
                        }
                    }
                }

                // Update order totals
                $order->subtotal = Money::fromCents($totalCashToPayCents + $totalOzToDrain);
                $order->subtotal_cents = $totalCashToPayCents + $totalOzToDrain;
                $order->final_amount = Money::fromCents(max(0, $totalCashToPayCents - $discountCents));
                $order->final_amount_cents = max(0, $totalCashToPayCents - $discountCents);
                $order->coupon_code = $discountCents > 0 ? strtoupper((string) $couponCode) : null;
                $order->discount_cents = $discountCents;
                $order->oz_used = $totalOzToDrain;
                $order->pickup_time = $pickupTime;
                $order->save();

                // Listeners update balances, rewards, cart cleanup, and notifications.
                event(new OrderPlaced(
                    $order,
                    $user,
                    $useOzIds,
                    Money::fromCents($totalCashToPayCents - $discountCents),
                    $totalOzToDrain,
                    $totalRewardOz
                ));

                return $order;
            });
        } finally {
            $lock->release();
        }
    }

    private function generatePickupCode(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = 'PU' . strtoupper(Str::random(6));

            if (!Order::where('pickup_code', $code)->exists()) {
                return $code;
            }
        }

        throw new CheckoutException('Unable to generate pickup code. Please try again.');
    }

    public function processCartSnapshot(User $user, CartSnapshot $snapshot, string $paymentSessionId): Order
    {
        if ($snapshot->user_id !== $user->id) {
            throw new CheckoutException('Cart snapshot does not belong to this user.', 403);
        }

        return DB::transaction(function () use ($user, $snapshot, $paymentSessionId) {
            $lockedSnapshot = CartSnapshot::where('id', $snapshot->id)->lockForUpdate()->firstOrFail();

            if ($lockedSnapshot->status === CartSnapshot::STATUS_PROCESSED) {
                $existingOrder = Order::where('payment_session_id', $paymentSessionId)->first()
                    ?? Order::where('bill_id', 'CP-' . strtoupper($paymentSessionId))->first();
                if ($existingOrder) {
                    return $existingOrder;
                }

                throw new CheckoutException('Cart snapshot has already been processed.', 409);
            }

            if ($lockedSnapshot->expires_at->isPast()) {
                $lockedSnapshot->status = CartSnapshot::STATUS_EXPIRED;
                $lockedSnapshot->save();

                throw new CheckoutException('Cart snapshot has expired.');
            }

            $order = new Order();
            $order->user_id = $user->id;
            $order->bill_id = 'CP-' . strtoupper($paymentSessionId);
            $order->payment_session_id = $paymentSessionId;
            $order->cart_snapshot_id = $lockedSnapshot->id;
            $order->pickup_code = $this->generatePickupCode();
            $order->status = Order::STATUS_PENDING;
            $order->subtotal = Money::fromCents($lockedSnapshot->subtotal_cents);
            $order->subtotal_cents = $lockedSnapshot->subtotal_cents;
            $order->final_amount = Money::fromCents($lockedSnapshot->final_amount_cents);
            $order->final_amount_cents = $lockedSnapshot->final_amount_cents;
            $order->oz_used = $lockedSnapshot->oz_used;
            $order->pickup_time = $lockedSnapshot->pickup_time;
            $order->save();

            $totalRewardOz = 0;

            foreach ($lockedSnapshot->items_json as $item) {
                $this->deductStock((int) $item['product_id'], (int) $item['quantity']);

                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item['product_id'];
                $orderItem->product_name = $item['product_name'] ?? null;
                $orderItem->quantity = $item['quantity'];
                $orderItem->options = [
                    'size' => $item['size'],
                    'temp' => $item['temp'],
                    'addons' => $item['addons'],
                ];
                $orderItem->price = Money::fromCents($item['product_price_cents']);
                $orderItem->price_cents = $item['product_price_cents'];
                $orderItem->price_at_time = $item['paid_with_oz'] ? 0 : Money::fromCents($item['unit_price_cents']);
                $orderItem->price_at_time_cents = $item['paid_with_oz'] ? 0 : $item['unit_price_cents'];
                $orderItem->oz_at_time = $item['paid_with_oz'] ? ($item['unit_price_cents'] * $item['quantity']) : 0;
                $orderItem->save();

                if (!$item['paid_with_oz']) {
                    $totalRewardOz += (int) (($item['unit_price_cents'] * $item['quantity']) / 2);
                }
            }

            if ($lockedSnapshot->coupon_code && $lockedSnapshot->discount_cents > 0) {
                $coupon = Coupon::where('code', $lockedSnapshot->coupon_code)->first();
                if ($coupon && !$coupon->redeemForOrder($user, $order, $lockedSnapshot->discount_cents)) {
                    throw new CheckoutException('Coupon can no longer be redeemed.');
                }
            }

            $order->coupon_code = $lockedSnapshot->discount_cents > 0 ? $lockedSnapshot->coupon_code : null;
            $order->discount_cents = $lockedSnapshot->discount_cents;
            $order->save();

            $lockedSnapshot->status = CartSnapshot::STATUS_PROCESSED;
            $lockedSnapshot->save();

            event(new OrderPlaced(
                $order,
                $user,
                [],
                0,
                $lockedSnapshot->oz_used,
                $totalRewardOz
            ));

            return $order;
        });
    }

    private function deductStock(int $productId, int $quantity): void
    {
        $product = Product::where('id', $productId)->firstOrFail();

        if (!$product->track_stock) {
            return;
        }

        $updated = Product::where('id', $productId)
            ->where('track_stock', true)
            ->where('stock', '>=', $quantity)
            ->update([
                'stock' => DB::raw('stock - ' . $quantity),
            ]);

        if ($updated !== 1) {
            throw new CheckoutException("Insufficient stock for {$product->name}.");
        }
    }
}
