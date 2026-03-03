<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;

class ProductController extends Controller
{
    public function show($id)
    {
        $product = Product::findOrFail($id);

        $options = config('coffee.options');

        return response()->json([
            'product' => new ProductResource($product),
            'options' => $options,
        ]);
    }
}