<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCompletionHandler;
use App\Services\Payment\RefillHandler;
use App\Services\Payment\StripeCheckoutHandler;
use Exception;

class PaymentHandlerFactory
{
    private array $handlers = [
        'refill' => RefillHandler::class,
        'checkout' => StripeCheckoutHandler::class,
    ];

    /**
     * @param string $type
     * @return PaymentCompletionHandler
     * @throws Exception
     */
    public function make(string $type): PaymentCompletionHandler
    {
        if (!isset($this->handlers[$type])) {
            throw new Exception("Unsupported payment type: {$type}");
        }

        return app($this->handlers[$type]);
    }
}
