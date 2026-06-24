<?php

namespace App\Support;

use App\Models\ProductAddon;

class ProductAddonSelection
{
    public static function normalize(mixed $addons): array
    {
        return array_values(array_unique(is_array($addons) ? $addons : []));
    }

    public static function belongsToProduct(int $productId, array $addons): bool
    {
        if ($productId <= 0 || $addons === []) {
            return true;
        }

        $validAddons = ProductAddon::where('product_id', $productId)
            ->whereIn('name', $addons)
            ->count();

        return $validAddons === count($addons);
    }
}
