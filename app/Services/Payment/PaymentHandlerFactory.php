<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCompletionHandler;
use Exception;

class PaymentHandlerFactory
{
    private array $handlers;

    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    /**
     * Register a new payment completion handler dynamically (OCP compliance).
     */
    public function registerHandler(string $type, string $handlerClass): void
    {
        $this->handlers[$type] = $handlerClass;
    }

    /**
     * Resolve the completion handler instance by type.
     *
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
