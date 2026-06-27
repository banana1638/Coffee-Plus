<?php

namespace App\Contracts;

use App\DataTransferObjects\PaymentInitiation;
use App\DataTransferObjects\PaymentResult;
use App\Models\User;

interface PaymentGatewayInterface
{
    public function createCheckout(User $user, array $items, array $metadata): PaymentInitiation;

    public function getSessionData(string $sessionId): PaymentResult;
}
