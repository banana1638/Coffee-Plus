<?php

namespace App\Exceptions;

use RuntimeException;

class OrderException extends RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode = 422)
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
