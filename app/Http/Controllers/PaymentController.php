<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\PaymentEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\CheckoutServiceInterface;
use App\Services\CartSnapshotService;
use App\Services\Payment\PaymentHandlerFactory;

class PaymentController extends Controller
{
    private PaymentGatewayInterface $gateway;
    private PaymentHandlerFactory $handlerFactory;
    private CartSnapshotService $cartSnapshotService;
    private CheckoutServiceInterface $checkoutService;

    public function __construct(
        PaymentGatewayInterface $gateway,
        PaymentHandlerFactory $handlerFactory,
        CartSnapshotService $cartSnapshotService,
        CheckoutServiceInterface $checkoutService,
    ) {
        $this->gateway = $gateway;
        $this->handlerFactory = $handlerFactory;
        $this->cartSnapshotService = $cartSnapshotService;
        $this->checkoutService = $checkoutService;
    }

    public function checkout(Request $request)
    {
        $user = Auth::user();
        $cartItems = CartItem::where('user_id', $user->id)->with('product')->get();
        $useOzIds = $request->input('use_oz', []);
        $couponCode = $request->input('coupon_code');
        $pickupTime = $request->input('pickup_time');

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $snapshot = $this->cartSnapshotService->createFromCart($user, $useOzIds, $couponCode, $pickupTime);

        $items = [];
        foreach ($snapshot->items_json as $item) {
            if ($item['paid_with_oz']) {
                continue;
            }

            $items[] = [
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => $item['product_name'],
                        'description' => "{$item['size']}, {$item['temp']}" . (!empty($item['addons']) ? ", +" . implode(', ', $item['addons']) : ""),
                    ],
                    'unit_amount' => (int) $item['unit_price_cents'],
                ],
                'quantity' => (int) $item['quantity'],
            ];
        }

        // Zero-cash orders do not need an external payment session.
        if (empty($items) || $snapshot->final_amount_cents === 0) {
            $order = $this->checkoutService->processCheckout($user, $useOzIds, $couponCode, $pickupTime);

            return redirect()->route('tangki.transactions')
                ->with('success', 'Enjoy your coffee! Order #' . $order->bill_id . ' placed.');
        }

        if ($snapshot->discount_cents > 0) {
            $items = [[
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => 'Coffee Plus Order',
                        'description' => 'Coupon discount applied',
                    ],
                    'unit_amount' => $snapshot->final_amount_cents,
                ],
                'quantity' => 1,
            ]];
        }

        $metadata = [
            'type' => 'checkout',
            'user_id' => $user->id,
            'cart_snapshot_id' => $snapshot->id,
            'coupon_code' => $couponCode,
            'pickup_time' => $pickupTime,
            'use_oz' => json_encode($useOzIds),
        ];
        $payment = $this->gateway->createCheckout($user, $items, $metadata);

        PaymentEvent::recordPending(
            $user,
            $payment->sessionId,
            'checkout',
            $snapshot->final_amount_cents,
            $metadata,
        );

        return Redirect::away($payment->redirectUrl);
    }

    public function success(Request $request)
    {
        return redirect()->route('dashboard')
            ->with('success', 'Payment received. We are confirming it with Stripe.');
    }
}
