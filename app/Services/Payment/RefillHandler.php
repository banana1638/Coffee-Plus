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
        $amount = (float) $result->metadata['amount'];
        $billId = 'TOPUP-' . strtoupper(uniqid());

        return $this->tangkiService->refillBalance($user, $amount, $billId);
    }
}
