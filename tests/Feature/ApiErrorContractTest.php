<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_validation_errors_have_standard_shape(): void
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
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => [
                    'quantity',
                ],
            ]);
    }

    public function test_api_authentication_errors_have_standard_shape(): void
    {
        $this->getJson('/api/cart')
            ->assertUnauthorized()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_api_not_found_errors_have_standard_shape(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/not-a-real-route')
            ->assertNotFound()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Not found.');
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
