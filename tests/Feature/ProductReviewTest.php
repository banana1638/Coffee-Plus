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

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): Product
    {
        $menu = new Menu;
        $menu->name = 'Coffee';
        $menu->save();

        $product = new Product;
        $product->menu_id = $menu->id;
        $product->name = 'Latte';
        $product->price = 10.00;
        $product->save();

        return $product;
    }

    private function createOrderWithProduct(User $user, Product $product, string $status): Order
    {
        $order = new Order;
        $order->user_id = $user->id;
        $order->bill_id = 'CP-REVIEW-'.strtoupper(substr(md5((string) microtime(true)), 0, 6));
        $order->subtotal = 10.00;
        $order->final_amount = 10.00;
        $order->status = $status;
        $order->save();

        $item = new OrderItem;
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

    public function test_user_can_review_product_from_completed_order(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $order = $this->createOrderWithProduct($user, $product, Order::STATUS_COMPLETED);

        $this->actingAs($user)
            ->post(route('orders.reviews.store', $order), [
                'product_id' => $product->id,
                'rating' => 5,
                'comment' => 'Excellent coffee',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Excellent coffee',
        ]);
    }

    public function test_user_cannot_review_pending_order(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $order = $this->createOrderWithProduct($user, $product, Order::STATUS_PENDING);

        $this->actingAs($user)
            ->post(route('orders.reviews.store', $order), [
                'product_id' => $product->id,
                'rating' => 4,
            ])
            ->assertSessionHas('error', 'Only completed orders can be reviewed.');

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_user_cannot_review_same_product_twice_for_same_order(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $order = $this->createOrderWithProduct($user, $product, Order::STATUS_COMPLETED);

        $review = new ProductReview;
        $review->user_id = $user->id;
        $review->product_id = $product->id;
        $review->order_id = $order->id;
        $review->rating = 5;
        $review->save();

        $this->actingAs($user)
            ->post(route('orders.reviews.store', $order), [
                'product_id' => $product->id,
                'rating' => 3,
            ])
            ->assertSessionHas('error', 'You have already reviewed this product for this order.');

        $this->assertDatabaseCount('product_reviews', 1);
    }

    public function test_product_detail_shows_average_review_score(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $order = $this->createOrderWithProduct($user, $product, Order::STATUS_COMPLETED);

        $review = new ProductReview;
        $review->user_id = $user->id;
        $review->product_id = $product->id;
        $review->order_id = $order->id;
        $review->rating = 4;
        $review->comment = 'Smooth';
        $review->save();

        $this->actingAs($user)
            ->get(route('product.detail', $product->id))
            ->assertStatus(200)
            ->assertSee('4.0 / 5')
            ->assertSee('Smooth');
    }

    public function test_web_product_detail_keeps_only_five_reviews_in_memory(): void
    {
        $product = $this->createProduct();

        for ($number = 1; $number <= 6; $number++) {
            $user = User::factory()->create();
            $order = $this->createOrderWithProduct($user, $product, Order::STATUS_COMPLETED);
            $review = new ProductReview;
            $review->user_id = $user->id;
            $review->product_id = $product->id;
            $review->order_id = $order->id;
            $review->rating = 4;
            $review->save();
            $review->forceFill(['created_at' => now()->addSeconds($number)])->saveQuietly();
        }

        $response = $this->actingAs($user)->get(route('product.detail', $product->id))->assertOk();
        $loadedProduct = $response->viewData('product');

        $this->assertSame(6, $loadedProduct->reviews_count);
        $this->assertCount(5, $loadedProduct->reviews);
    }
}
