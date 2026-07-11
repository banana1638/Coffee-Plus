<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Services\ProductQueryService;

class ProductController extends Controller
{
    public function __construct(private readonly ProductQueryService $productQueryService)
    {
    }

    public function show($id)
    {
        $product = $this->productQueryService->detail((int) $id);

        $options = config('coffee.options');

        return response()->json([
            'product' => new ProductResource($product),
            'options' => $options,
        ]);
    }
}
