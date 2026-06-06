<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCompletionHandler;
use App\Services\CheckoutService;
use App\Models\CartSnapshot;
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

        if (isset($metadata['cart_snapshot_id'])) {
            $snapshot = CartSnapshot::findOrFail((int) $metadata['cart_snapshot_id']);
            $paidAmountCents = (int) round($result->amount * 100);

            if ($paidAmountCents !== (int) $snapshot->final_amount_cents) {
                throw new \RuntimeException('Stripe payment amount does not match cart snapshot.');
            }

            return $this->checkoutService->processCartSnapshot(
                $user,
                $snapshot,
                $result->platformRef ?? ('SNAPSHOT-' . $snapshot->id)
            );
        }

        return $this->checkoutService->processCheckout(
            $user,
            $useOzIds,
            $metadata['coupon_code'] ?? null,
            $metadata['pickup_time'] ?? null
        );
    }
}
