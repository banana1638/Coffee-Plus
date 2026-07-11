<?php

namespace App\Services;

use App\Contracts\PricingServiceInterface;
use App\Exceptions\CheckoutException;
use App\Models\CartItem;
use App\Models\Product;
use App\Support\ProductAddonSelection;

class CartPricingService
{
    public function __construct(private readonly PricingServiceInterface $pricingService)
    {
    }

    public function refreshUnitPrice(CartItem $cartItem): int
    {
        $product = Product::find($cartItem->product_id);

        if (! $product) {
            throw new CheckoutException('A product in your cart is no longer available.');
        }

        $sizes = collect(config('coffee.options.sizes', []))->pluck('name')->all();
        $temps = config('coffee.options.temps', []);
        $addons = ProductAddonSelection::normalize($cartItem->addons ?? []);

        if (! in_array($cartItem->size, $sizes, true) || ! in_array($cartItem->temp, $temps, true)) {
            throw new CheckoutException('A product in your cart has invalid size or temperature options.');
        }

        if (! ProductAddonSelection::belongsToProduct($product->id, $addons)) {
            throw new CheckoutException('A product in your cart has invalid add-ons.');
        }

        $unitPriceCents = $this->pricingService->calculateUnitPriceCents(
            $product,
            $cartItem->size,
            $addons,
        );

        if ($unitPriceCents <= 0) {
            throw new CheckoutException('A product in your cart has an invalid price.');
        }

        $cartItem->addons = $addons;
        $cartItem->unit_price_cents = $unitPriceCents;
        $cartItem->unit_price = $unitPriceCents / 100;
        $cartItem->setRelation('product', $product);
        $cartItem->save();

        return $unitPriceCents;
    }
}
