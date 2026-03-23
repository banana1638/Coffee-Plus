<?php

namespace App\Services\Payment;

use App\Services\TangkiService;
use App\Models\User;
use App\Contracts\PaymentCompletionHandler;
use App\DataTransferObjects\PaymentResult;

class RefillHandler implements PaymentCompletionHandler
{
    public function handle(PaymentResult $result, User $user)
    {
        $amount = (float) $result->metadata['amount'];
        $billId = 'TOPUP-' . strtoupper(uniqid());

        return TangkiService::refill($user, $amount, $billId);
    }
}
