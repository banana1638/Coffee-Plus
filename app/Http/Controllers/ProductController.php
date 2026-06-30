<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show($id)
    {
        $product = Product::query()
            ->with([
                'addons',
                'reviews' => fn ($query) => $query
                    ->with('user:id,name')
                    ->latest()
                    ->limit(5),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->findOrFail($id);

        $options = config('coffee.options');

        return view('user.products.detail', compact('product', 'options'));
    }
}
