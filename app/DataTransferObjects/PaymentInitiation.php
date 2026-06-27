<?php

namespace App\DataTransferObjects;

class PaymentInitiation
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $redirectUrl,
    ) {
    }
}
