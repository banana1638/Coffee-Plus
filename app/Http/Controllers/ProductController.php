<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show($id)
    {
        $product = Product::with('addons')->findOrFail($id);

        $options = config('coffee.options');

        return view('user.products.detail', compact('product', 'options'));
    }
}