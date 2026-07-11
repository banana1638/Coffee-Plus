<?php

namespace App\Services;

use App\Models\Product;

class ProductQueryService
{
    public function detail(int $productId): Product
    {
        return Product::query()
            ->with([
                'addons:id,product_id,name,price,price_cents',
                'reviews' => fn ($query) => $query
                    ->with('user:id,name')
                    ->latest()
                    ->limit(5),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->findOrFail($productId);
    }
}
