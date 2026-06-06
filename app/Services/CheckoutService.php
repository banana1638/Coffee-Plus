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
use Illuminate\Support\Str;

class CheckoutService implements CheckoutServiceInterface
{
    protected TangkiServiceInterface $tangkiService;

    public function __construct(TangkiServiceInterface $tangkiService)
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

                $totalCashToPay = 0;
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

                    $unitPrice = $item->unit_price;
                    $quantity = $item->quantity;
                    $itemTotal = $unitPrice * $quantity;

                    $isRedeem = in_array($item->id, $useOzIds);

                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                    $orderItem->product_id = $item->product_id;
                    $orderItem->quantity = $quantity;
                    $orderItem->options = [
                        'size' => $item->size,
                        'temp' => $item->temp,
                        'addons' => $item->addons
                    ];
                    $orderItem->price = $item->product->price;

                    // OZ redemption
                    if ($isRedeem) {
                        $ozNeeded = (int) ($itemTotal * 100);
                        $totalOzToDrain += $ozNeeded;
                        $orderItem->oz_at_time = $ozNeeded;
                        $orderItem->price_at_time = 0;
                    }

                    else {
                        $totalCashToPay += $itemTotal;
                        $orderItem->oz_at_time = 0;
                        $orderItem->price_at_time = $unitPrice;

                        // Reward OZ only for cash payments.
                        $totalRewardOz += (int) (($itemTotal * 100) / 2);
                    }

                    $orderItem->save();
                }

                // Coupon discount
                $discount = 0;
                if ($couponCode && $totalCashToPay > 0) {
                    $coupon = Coupon::where('code', $couponCode)->first();
                    if ($coupon && $coupon->isValid()) {
                        $discount = $coupon->calculateDiscount($totalCashToPay);
                        if (!$coupon->redeemForOrder($user, $order, (int) round($discount * 100))) {
                            $discount = 0;
                        }
                    }
                }

                // Update order totals
                $order->subtotal = $totalCashToPay + ($totalOzToDrain / 100);
                $order->final_amount = max(0, $totalCashToPay - $discount);
                $order->oz_used = $totalOzToDrain;
                $order->pickup_time = $pickupTime;
                $order->save();

                // Listeners update balances, rewards, cart cleanup, and notifications.
                event(new OrderPlaced(
                    $order,
                    $user,
                    $useOzIds,
                    $totalCashToPay,
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
            $order->subtotal = $lockedSnapshot->subtotal_cents / 100;
            $order->final_amount = $lockedSnapshot->final_amount_cents / 100;
            $order->oz_used = $lockedSnapshot->oz_used;
            $order->pickup_time = $lockedSnapshot->pickup_time;
            $order->save();

            $totalRewardOz = 0;

            foreach ($lockedSnapshot->items_json as $item) {
                $this->deductStock((int) $item['product_id'], (int) $item['quantity']);

                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item['product_id'];
                $orderItem->quantity = $item['quantity'];
                $orderItem->options = [
                    'size' => $item['size'],
                    'temp' => $item['temp'],
                    'addons' => $item['addons'],
                ];
                $orderItem->price = $item['product_price_cents'] / 100;
                $orderItem->price_at_time = $item['paid_with_oz'] ? 0 : ($item['unit_price_cents'] / 100);
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
