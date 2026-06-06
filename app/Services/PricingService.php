<?php

namespace App\Services;

use App\Contracts\PricingServiceInterface;
use App\Models\Product;
use App\Support\Money;

class PricingService implements PricingServiceInterface
{
    /**
     * Calculate final unit price for a product based on size and addons.
     */
    public function calculateUnitPrice(Product $product, string $size, array $selectedAddons): float
    {
        return Money::fromCents($this->calculateUnitPriceCents($product, $size, $selectedAddons));
    }

    public function calculateUnitPriceCents(Product $product, string $size, array $selectedAddons): int
    {
        $coffeeConfig = config('coffee.options');

        $sizeExtraCents = Money::toCents(collect($coffeeConfig['sizes'])
            ->firstWhere('name', $size)['extra'] ?? 0);

        $addonsTotal = $product->addons()
            ->whereIn('name', $selectedAddons)
            ->get()
            ->sum(fn ($addon) => (int) ($addon->price_cents ?? Money::toCents($addon->price)));

        return (int) ($product->price_cents ?? Money::toCents($product->price)) + $sizeExtraCents + (int) $addonsTotal;
    }
}
