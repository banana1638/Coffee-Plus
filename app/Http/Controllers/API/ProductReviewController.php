<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    use ApiResponse;

    public function index(Product $product)
    {
        $reviews = $product->reviews()
            ->with('user')
            ->latest()
            ->paginate(15);

        return $this->success([
            'average_rating' => $product->average_rating,
            'reviews_count' => $product->reviews_count,
            'reviews' => $reviews->through(fn ($review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user_name' => $review->user?->name,
                'created_at' => $review->created_at->format('Y-m-d'),
            ]),
        ]);
    }

    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($order->user_id !== $request->user()->id) {
            return $this->error('Order not found.', 404);
        }

        if ($order->status !== Order::STATUS_COMPLETED) {
            return $this->error('Only completed orders can be reviewed.', 422);
        }

        $productId = (int) $validated['product_id'];
        if (!$order->items()->where('product_id', $productId)->exists()) {
            return $this->error('This product is not part of the order.', 422);
        }

        $exists = ProductReview::where('user_id', $request->user()->id)
            ->where('order_id', $order->id)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            return $this->error('You have already reviewed this product for this order.', 409);
        }

        $review = new ProductReview();
        $review->user_id = $request->user()->id;
        $review->order_id = $order->id;
        $review->product_id = $productId;
        $review->rating = (int) $validated['rating'];
        $review->comment = $validated['comment'] ?? null;
        $review->save();

        return $this->success([
            'review' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at->format('Y-m-d'),
            ],
        ], 'Review submitted.', 201);
    }
}
