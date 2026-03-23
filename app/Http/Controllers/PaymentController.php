<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\PaymentHandlerFactory;

class PaymentController extends Controller
{
    private PaymentGatewayInterface $gateway;
    private PaymentHandlerFactory $handlerFactory;

    public function __construct(
        PaymentGatewayInterface $gateway,
        PaymentHandlerFactory $handlerFactory
    ) {
        $this->gateway = $gateway;
        $this->handlerFactory = $handlerFactory;
    }

    public function checkout(Request $request)
    {
        $user = Auth::user();
        $cartItems = CartItem::where('user_id', $user->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $items = [];
        foreach ($cartItems as $item) {
            $items[] = [
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => $item->product->name,
                        'description' => "{$item->size}, {$item->temp}" . ($item->addons ? ", +{$item->addons}" : ""),
                    ],
                    'unit_amount' => (int) ($item->unit_price * 100),
                ],
                'quantity' => $item->quantity,
            ];
        }

        $url = $this->gateway->createCheckoutUrl($user, $items, [
            'type' => 'checkout',
            'user_id' => $user->id,
        ]);

        return Redirect::away($url);
    }

    public function success(Request $request)
    {
        $sessionId = $request->input('session_id');

        $result = $this->gateway->getSessionData($sessionId);

        if (!$result->isSuccess()) {
            return redirect()->route('cart.index')->with('error', 'Payment failed.');
        }

        $user = Auth::user();

        // Ensure user is not null (route is protected by auth)
        if (!$user) {
            abort(403);
        }

        $this->handlerFactory->make($result->getType())->handle($result, $user);

        return redirect()->route('dashboard')->with('success', 'Payment processed!');
    }
}
