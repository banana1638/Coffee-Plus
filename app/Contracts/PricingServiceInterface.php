<?php

namespace App\Contracts;

use App\Models\Product;

interface PricingServiceInterface
{
    /**
     * Calculate final unit price for a product based on size and addons.
     *
     * @param Product $product
     * @param string $size
     * @param array $selectedAddons
     * @return float
     */
    public function calculateUnitPrice(Product $product, string $size, array $selectedAddons): float;
}
