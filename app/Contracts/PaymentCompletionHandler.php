<?php

namespace App\Contracts;

use App\Models\User;
use App\DataTransferObjects\PaymentResult;

interface PaymentCompletionHandler
{
    public function handle(PaymentResult $result, User $user);
}