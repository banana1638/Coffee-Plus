<?php

namespace App\Services\Payment;

use App\Contracts\TangkiServiceInterface;
use App\Models\User;
use App\Contracts\PaymentCompletionHandler;
use App\DataTransferObjects\PaymentResult;

class RefillHandler implements PaymentCompletionHandler
{
    protected TangkiServiceInterface $tangkiService;

    public function __construct(TangkiServiceInterface $tangkiService)
    {
        $this->tangkiService = $tangkiService;
    }

    public function handle(PaymentResult $result, User $user)
    {
        // Use the actual Stripe payment amount, NOT the user-controlled metadata
        $paidAmount = $result->amount;
        $metaAmount = (float) ($result->metadata['amount'] ?? 0);

        // Cross-validate: allow 0.01 floating-point tolerance
        if (abs($paidAmount - $metaAmount) > 0.01) {
            report(new \RuntimeException(
                "Refill amount mismatch: paid={$paidAmount} meta={$metaAmount} user={$user->id}"
            ));
            throw new \RuntimeException('Payment amount mismatch. Refill aborted.');
        }

        $billId = 'TOPUP-' . strtoupper(uniqid());

        return $this->tangkiService->refillBalance($user, $paidAmount, $billId);
    }
}
