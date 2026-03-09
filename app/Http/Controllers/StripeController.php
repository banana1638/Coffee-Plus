<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StripeController extends Controller
{
    public function checkout(Request $request)
    {
        $user = Auth::user();
        $cartItems = CartItem::where('user_id', $user->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return back()->with('error', 'Your cart is empty.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $this->buildLineItems($cartItems),
            'mode' => 'payment',
            'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('cart.index'),
            'customer_email' => $user->email,
            'metadata' => [
                'user_id' => $user->id,
            ]
        ]);

        return redirect($session->url);
    }

    /**
     * Map cart items to Stripe line items.
     * Separated to reduce function complexity for IDE type inference.
     */
    private function buildLineItems($cartItems): array
    {
        return $cartItems->map(function ($item) {
            return [
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => $item->product->name . " ({$item->size})",
                        'images' => [$item->product->image_url],
                    ],
                    'unit_amount' => (int) ($item->unit_price * 100),
                ],
                'quantity' => $item->quantity,
            ];
        })->toArray();
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        if (!$sessionId) {
            return redirect()->route('dashboard');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $session = Session::retrieve($sessionId);

        if ($session->payment_status === 'paid') {
            $user = Auth::user();

            // Handle Refill
            if (isset($session->metadata->type) && $session->metadata->type === 'refill') {
                $amount = (float) $session->metadata->amount;
                $ozToInject = (int) ($amount * 10);
                $billId = 'TOPUP-' . strtoupper(uniqid());

                DB::transaction(function () use ($user, $amount, $ozToInject, $billId) {
                    $user->increment('tangki_balance', $amount);
                    $user->increment('tangki_oz', $ozToInject);

                    $order = new Order();
                    $order->user_id = $user->id;
                    $order->bill_id = $billId;
                    $order->subtotal = $amount;
                    $order->oz_used = 0;
                    $order->final_amount = $amount;
                    $order->status = 'completed';
                    $order->save();

                    $transaction = new Transaction();
                    $transaction->user_id = $user->id;
                    $transaction->bill_id = $billId;
                    $transaction->oz_delta = $ozToInject;
                    $transaction->type = 'refill';
                    $transaction->description = "Refilled RM" . number_format($amount, 2) . " (Earned {$ozToInject} OZ)";
                    $transaction->save();
                });

                return redirect()->route('user.tangki')->with('success', 'Refill successful!');
            }

            // Handle Cart Checkout (Existing Logic)
            $cartItems = CartItem::where('user_id', $user->id)->with('product')->get();

            if ($cartItems->isEmpty()) {
                return redirect()->route('dashboard')->with('success', 'Payment successful, but cart was already cleared.');
            }

            $order = DB::transaction(function () use ($user, $cartItems, $session) {
                $order = new Order();
                $order->user_id = $user->id;
                $order->bill_id = 'ST-' . strtoupper(uniqid());
                $order->status = 'pending';
                $order->subtotal = $session->amount_total / 100;
                $order->final_amount = $session->amount_total / 100;
                $order->oz_used = 0;
                $order->save();

                foreach ($cartItems as $item) {
                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                    $orderItem->product_id = $item->product_id;
                    $orderItem->quantity = $item->quantity;
                    $orderItem->options = [
                        'size' => $item->size,
                        'temp' => $item->temp,
                        'addons' => $item->addons
                    ];
                    $orderItem->price_at_time = $item->unit_price;
                    $orderItem->oz_at_time = 0;
                    $orderItem->save();
                }

                // Reward OZ for cash purchase (Stripe)
                $rewardOz = (int) ($session->amount_total / 2);
                $user->tangki_oz += $rewardOz;
                $user->save();

                CartItem::where('user_id', $user->id)->delete();

                return $order;
            });

            return redirect()->route('tangki.transactions')
                ->with('success', 'Payment successful! Order #' . $order->bill_id . ' placed.');
        }

        return redirect()->route('cart.index')->with('error', 'Payment failed or was cancelled.');
    }
}
