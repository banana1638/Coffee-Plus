<?php

namespace App\Services;

use App\Contracts\PricingServiceInterface;
use App\Models\Product;

class PricingService implements PricingServiceInterface
{
    /**
     * Calculate final unit price for a product based on size and addons.
     */
    public function calculateUnitPrice(Product $product, string $size, array $selectedAddons): float
    {
        $coffeeConfig = config('coffee.options');

        $sizeExtra = collect($coffeeConfig['sizes'])
            ->firstWhere('name', $size)['extra'] ?? 0;

        $addonsTotal = $product->addons()
            ->whereIn('name', $selectedAddons)
            ->sum('price');

        return $product->price + $sizeExtra + $addonsTotal;
    }
}
