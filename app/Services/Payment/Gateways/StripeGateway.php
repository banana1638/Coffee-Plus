<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DataTransferObjects\PaymentInitiation;
use App\DataTransferObjects\PaymentResult;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * @param  array<int, array{price_data: array{currency: string, product_data: array{name: string}, unit_amount: int}, quantity: int}>  $items
     * @param  array<string, mixed>  $metadata
     */
    public function createCheckout(User $user, array $items, array $metadata): PaymentInitiation
    {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $items,
            'metadata' => $metadata,
            'mode' => 'payment',
            'success_url' => route('stripe.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url' => route('cart.index'),
        ]);

        return new PaymentInitiation($session->id, $session->url);
    }

    public function getSessionData(string $sessionId): PaymentResult
    {
        $session = Session::retrieve($sessionId);

        return new PaymentResult(
            status: $session->payment_status === 'paid' ? 'success' : 'failed',
            amount: $session->amount_total / 100,
            metadata: $session->metadata->toArray(),
            platformRef: $session->id,
            currency: $session->currency,
        );
    }
}
