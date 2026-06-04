<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): Product
    {
        $menu = new Menu();
        $menu->name = 'Coffee';
        $menu->save();

        $product = new Product();
        $product->menu_id = $menu->id;
        $product->name = 'Latte';
        $product->price = 10.00;
        $product->save();

        return $product;
    }

    private function createOrderWithProduct(User $user, Product $product, string $status): Order
    {
        $order = new Order();
        $order->user_id = $user->id;
        $order->bill_id = 'CP-API-REVIEW-' . strtoupper(substr(md5((string) microtime(true)), 0, 6));
        $order->subtotal = 10.00;
        $order->final_amount = 10.00;
        $order->status = $status;
        $order->save();

        $item = new OrderItem();
        $item->order_id = $order->id;
        $item->product_id = $product->id;
        $item->quantity = 1;
        $item->price = 10.00;
        $item->price_at_time = 10.00;
        $item->oz_at_time = 0;
        $item->options = [];
        $item->save();

        return $order;
    }

    public function test_anyone_can_list_product_reviews(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $order = $this->createOrderWithProduct($user, $product, Order::STATUS_COMPLETED);

        $review = new ProductReview();
        $review->user_id = $user->id;
        $review->product_id = $product->id;
        $review->order_id = $order->id;
        $review->rating = 5;
        $review->comment = 'Great';
        $review->save();

        $this->getJson("/api/products/{$product->id}/reviews")
            ->assertStatus(200)
            ->assertJsonPath('data.average_rating', 5)
            ->assertJsonPath('data.reviews.data.0.comment', 'Great');
    }

    public function test_user_can_create_review_via_api(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $order = $this->createOrderWithProduct($user, $product, Order::STATUS_COMPLETED);

        $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/reviews", [
                'product_id' => $product->id,
                'rating' => 4,
                'comment' => 'Nice',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.review.rating', 4);

        $this->assertDatabaseHas('product_reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 4,
        ]);
    }

    public function test_user_cannot_review_pending_order_via_api(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $order = $this->createOrderWithProduct($user, $product, Order::STATUS_PENDING);

        $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/reviews", [
                'product_id' => $product->id,
                'rating' => 4,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only completed orders can be reviewed.');
    }
}
