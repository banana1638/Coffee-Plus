<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    public function show($id)
    {
        $product = Product::query()
            ->with([
                'reviews' => fn ($query) => $query
                    ->with('user:id,name')
                    ->latest()
                    ->limit(5),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->findOrFail($id);

        $options = config('coffee.options');

        return response()->json([
            'product' => new ProductResource($product),
            'options' => $options,
        ]);
    }
}
