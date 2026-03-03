<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show($id)
    {
        $product = Product::findOrFail($id);

        $options = config('coffee.options');

        return view('products.detail', compact('product', 'options'));
    }
}