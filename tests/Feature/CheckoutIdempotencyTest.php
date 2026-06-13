<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_checkout_requires_idempotency_key(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);

        $this->actingAs($user)
            ->postJson('/api/checkout')
            ->assertStatus(422)
            ->assertJsonValidationErrors('idempotency_key');
    }

    public function test_repeated_checkout_with_same_idempotency_key_returns_existing_order(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $this->addCartItem($user, $this->createProduct());
        $key = 'checkout-key-123456789';

        $first = $this->apiCheckout($user, [], $key);
        $first->assertStatus(200);

        $second = $this->apiCheckout($user, [], $key);
        $second->assertStatus(200);

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, Order::count());
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_checkout_accepts_body_idempotency_key_for_legacy_clients(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $this->addCartItem($user, $this->createProduct());

        $this->actingAs($user)
            ->postJson('/api/checkout', [
                'idempotency_key' => 'legacy-body-key-123456',
            ])
            ->assertStatus(200);

        $this->assertSame(1, Order::count());
    }

    public function test_same_idempotency_key_with_different_payload_is_rejected(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $this->addCartItem($user, $this->createProduct());
        $key = 'checkout-key-different-payload';

        $this->apiCheckout($user, [], $key)->assertStatus(200);

        $this->apiCheckout($user, ['coupon_code' => 'DIFFERENT'], $key)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Idempotency key request payload does not match the original request.');
    }

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

    private function addCartItem(User $user, Product $product): CartItem
    {
        $cartItem = new CartItem();
        $cartItem->user_id = $user->id;
        $cartItem->product_id = $product->id;
        $cartItem->quantity = 1;
        $cartItem->size = 'Regular';
        $cartItem->temp = 'Hot';
        $cartItem->addons = [];
        $cartItem->unit_price = 10.00;
        $cartItem->save();

        return $cartItem;
    }
}
