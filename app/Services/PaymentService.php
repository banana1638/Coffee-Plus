<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe checkout session for a refill.
     */
    public function createRefillSession(int $userId, float $amount): Session
    {
        return Session::create([
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
                'user_id' => $userId,
                'amount' => $amount,
            ]
        ]);
    }
}
