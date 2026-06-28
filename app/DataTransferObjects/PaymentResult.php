<?php

namespace App\DataTransferObjects;

class PaymentResult
{
    public function __construct(
        public readonly string $status,
        public readonly float $amount,
        public readonly array $metadata,
        public readonly ?string $platformRef = null,
        public readonly ?string $currency = null,
    ) {}

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function getType(): string
    {
        return $this->metadata['type'] ?? 'checkout';
    }
}
