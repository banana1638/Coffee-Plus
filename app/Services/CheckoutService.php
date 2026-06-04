<?php

namespace App\Services;

use App\Contracts\CheckoutServiceInterface;
use App\Contracts\TangkiServiceInterface;
use App\Exceptions\CheckoutException;
use App\Models\{CartItem, Coupon, Order, OrderItem, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Events\OrderPlaced;
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
        $lock = Cache::lock('checkout_user_' . $user->id, 10);
        if (!$lock->get()) {
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
                    $coupon = Coupon::where('code', $couponCode)->lockForUpdate()->first();
                    if ($coupon && $coupon->isValid()) {
                        $discount = $coupon->calculateDiscount($totalCashToPay);
                        $coupon->markUsed();
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
        do {
            $code = 'PU' . strtoupper(Str::random(6));
        } while (Order::where('pickup_code', $code)->exists());

        return $code;
    }
}
