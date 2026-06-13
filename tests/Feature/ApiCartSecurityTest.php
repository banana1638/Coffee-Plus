<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCartSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_add_rejects_quantity_above_limit(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $this->actingAs($user)
            ->postJson('/api/cart/add', [
                'product_id' => $product->id,
                'quantity' => 100,
                'size' => 'Regular',
                'temp' => 'Hot',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }

    public function test_cart_update_rejects_zero_quantity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/cart/update', [
                'product_id' => $this->createProduct()->id,
                'quantity' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }

    public function test_cart_add_rejects_invalid_size_and_temp(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $this->actingAs($user)
            ->postJson('/api/cart/add', [
                'product_id' => $product->id,
                'quantity' => 1,
                'size' => 'Bucket',
                'temp' => 'Boiling',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['size', 'temp']);
    }

    public function test_cart_add_rejects_addon_from_another_product(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $otherProduct = $this->createProduct('Mocha');
        $addon = $this->createAddon($otherProduct, 'Vanilla Syrup');

        $this->actingAs($user)
            ->postJson('/api/cart/add', [
                'product_id' => $product->id,
                'quantity' => 1,
                'size' => 'Regular',
                'temp' => 'Hot',
                'addons' => [$addon->name],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('addons');
    }

    public function test_cart_add_accepts_valid_product_addon(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $addon = $this->createAddon($product, 'Extra Shot');

        $this->actingAs($user)
            ->postJson('/api/cart/add', [
                'product_id' => $product->id,
                'quantity' => 1,
                'size' => 'Regular',
                'temp' => 'Hot',
                'addons' => [$addon->name],
            ])
            ->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    private function createProduct(string $name = 'Latte'): Product
    {
        $menu = new Menu();
        $menu->name = 'Coffee';
        $menu->save();

        $product = new Product();
        $product->menu_id = $menu->id;
        $product->name = $name;
        $product->price = 10.00;
        $product->save();

        return $product;
    }

    private function createAddon(Product $product, string $name): ProductAddon
    {
        $addon = new ProductAddon();
        $addon->product_id = $product->id;
        $addon->name = $name;
        $addon->price = 1.50;
        $addon->save();

        return $addon;
    }
}
