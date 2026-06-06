<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Product;
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
}
