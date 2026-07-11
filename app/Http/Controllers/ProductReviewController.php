<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductReview;
use App\Http\Requests\StoreProductReviewRequest;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function store(StoreProductReviewRequest $request, Order $order)
    {
        $validated = $request->validated();

        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->status !== Order::STATUS_COMPLETED) {
            return back()->with('error', 'Only completed orders can be reviewed.');
        }

        $productId = (int) $validated['product_id'];
        $orderHasProduct = $order->items()->where('product_id', $productId)->exists();

        if (!$orderHasProduct) {
            return back()->with('error', 'This product is not part of the order.');
        }

        $exists = ProductReview::where('user_id', Auth::id())
            ->where('order_id', $order->id)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            return back()->with('error', 'You have already reviewed this product for this order.');
        }

        $review = new ProductReview();
        $review->user_id = Auth::id();
        $review->order_id = $order->id;
        $review->product_id = $productId;
        $review->rating = (int) $validated['rating'];
        $review->comment = $validated['comment'] ?? null;
        $review->save();

        return back()->with('success', 'Review submitted.');
    }
}
