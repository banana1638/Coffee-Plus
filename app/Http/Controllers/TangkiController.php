<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class TangkiController extends Controller
{
    public function index()
    {
        $transactions = Auth::user()->transactions()->latest()->take(5)->get();
        return view('user.tangki.index', compact('transactions'));
    }

    public function refill(Request $request)
    {
        $user = Auth::user();
        $amount = floatval($request->input('amount'));

        if ($amount <= 0) {
            return back()->with('error', 'Invalid amount.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'myr',
                        'product_data' => [
                            'name' => 'Tangki Refill',
                            'description' => "Refill RM" . number_format($amount, 2),
                        ],
                        'unit_amount' => (int) ($amount * 100),
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('tangki.index'),
            'metadata' => [
                'type' => 'refill',
                'user_id' => $user->id,
                'amount' => $amount,
            ]
        ]);

        return redirect($session->url);
    }
}