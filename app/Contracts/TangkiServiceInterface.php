<?php

namespace App\Contracts;

use App\Models\User;

interface TangkiServiceInterface
{
    /**
     * Refill user's account balance and reward them with OZ.
     */
    public function refillBalance(User $user, float $amount, string $billId): bool;

    /**
     * Drain user's OZ balance.
     */
    public function drainOz(User $user, int $ozAmount, string $billId, string $description = 'Redeemed items'): bool;

    /**
     * Deduct user's cash balance and reward them with OZ.
     */
    public function deductBalanceAndRewardOz(User $user, float $amount, int $rewardOz, string $billId, string $description): bool;
}
