<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Contracts\PaymentGatewayInterface;
use App\Services\CartSnapshotService;
use App\Services\Payment\PaymentHandlerFactory;

class PaymentController extends Controller
{
    private PaymentGatewayInterface $gateway;
    private PaymentHandlerFactory $handlerFactory;
    private CartSnapshotService $cartSnapshotService;

    public function __construct(
        PaymentGatewayInterface $gateway,
        PaymentHandlerFactory $handlerFactory,
        CartSnapshotService $cartSnapshotService
    ) {
        $this->gateway = $gateway;
        $this->handlerFactory = $handlerFactory;
        $this->cartSnapshotService = $cartSnapshotService;
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

        // If everything is fully paid by OZ, just route to normal checkout directly
        if (empty($items)) {
            return app(\App\Http\Controllers\OrderController::class)->checkout($request);
        }

        $url = $this->gateway->createCheckoutUrl($user, $items, [
            'type' => 'checkout',
            'user_id' => $user->id,
            'cart_snapshot_id' => $snapshot->id,
            'coupon_code' => $couponCode,
            'pickup_time' => $pickupTime,
            'use_oz' => json_encode($useOzIds),
        ]);

        return Redirect::away($url);
    }

    public function success(Request $request)
    {
        return redirect()->route('dashboard')
            ->with('success', 'Payment received. We are confirming it with Stripe.');
    }
}
