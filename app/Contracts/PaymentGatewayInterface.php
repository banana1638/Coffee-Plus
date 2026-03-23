<?php

namespace App\Contracts;

use App\Models\User;

use App\DataTransferObjects\PaymentResult;

interface PaymentGatewayInterface
{
    public function createCheckoutUrl(User $user, array $items, array $metadata): string;

    public function getSessionData(string $sessionId): PaymentResult;
}
