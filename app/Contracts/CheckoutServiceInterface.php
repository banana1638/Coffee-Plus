<?php

namespace App\Contracts;

use App\Models\User;
use App\Models\Order;

interface CheckoutServiceInterface
{
    /**
     * Process checkout for the user's cart.
     *
     * @param User $user
     * @param array $useOzIds Cart item IDs that are paid using OZ
     * @return Order
     * @throws \Exception
     */
    public function processCheckout(User $user, array $useOzIds): Order;
}
