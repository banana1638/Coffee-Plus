<?php

namespace App\Http\Controllers;

use App\Services\ProductQueryService;

class ProductController extends Controller
{
    public function __construct(private readonly ProductQueryService $productQueryService)
    {
    }

    public function show(int $id)
    {
        $product = $this->productQueryService->detail((int) $id);

        $options = config('coffee.options');

        return view('user.products.detail', compact('product', 'options'));
    }
}
