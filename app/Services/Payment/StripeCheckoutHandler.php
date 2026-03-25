<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCompletionHandler;
use App\Services\CheckoutService;
use App\Models\User;
use App\DataTransferObjects\PaymentResult;

class StripeCheckoutHandler implements PaymentCompletionHandler
{
    protected $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function handle(PaymentResult $result, User $user)
    {
        $metadata = $result->metadata;
        $useOzIds = [];
        
        if (isset($metadata['use_oz'])) {
            $useOzIds = json_decode($metadata['use_oz'], true) ?? [];
        }

        return $this->checkoutService->processCheckout($user, $useOzIds);
    }
}