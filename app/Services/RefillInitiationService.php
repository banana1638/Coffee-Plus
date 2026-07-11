<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\DataTransferObjects\PaymentInitiation;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Support\Money;

class RefillInitiationService
{
    public function __construct(private readonly PaymentGatewayInterface $gateway)
    {
    }

    public function initiate(User $user, int $amountCents): PaymentInitiation
    {
        if ($amountCents < 500 || $amountCents > 50000) {
            throw new \InvalidArgumentException('Refill amount must be between RM5 and RM500.');
        }

        $amount = Money::fromCents($amountCents);
        $metadata = [
            'type' => 'refill',
            'user_id' => $user->id,
            'amount' => number_format($amount, 2, '.', ''),
        ];

        $payment = $this->gateway->createCheckout($user, [[
            'price_data' => [
                'currency' => 'myr',
                'product_data' => [
                    'name' => 'Tangki Balance Refill',
                    'description' => 'Refill RM'.number_format($amount, 2),
                ],
                'unit_amount' => $amountCents,
            ],
            'quantity' => 1,
        ]], $metadata);

        PaymentEvent::recordPending(
            $user,
            $payment->sessionId,
            'refill',
            $amountCents,
            $metadata,
        );

        return $payment;
    }
}
