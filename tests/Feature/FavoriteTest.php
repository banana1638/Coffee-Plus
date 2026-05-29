<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Menu;
use App\Models\Favorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): array
    {
        $user = User::factory()->create();
        $menu = new Menu();
        $menu->name = 'Coffee';
        $menu->save();

        $product = new Product();
        $product->menu_id = $menu->id;
        $product->name = 'Latte';
        $product->price = 10.00;
        $product->save();

        return [$user, $product];
    }

    public function test_user_can_list_favorites()
    {
        [$user, $product] = $this->createProduct();

        $favorite = new Favorite();
        $favorite->user_id = $user->id;
        $favorite->product_id = $product->id;
        $favorite->size = 'Regular';
        $favorite->temp = 'Hot';
        $favorite->addons = [];
        $favorite->remark = 'No sugar';
        $favorite->save();

        $response = $this->actingAs($user)->getJson('/api/favorites');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_user_can_add_favorite()
    {
        [$user, $product] = $this->createProduct();

        // First POST — should add the favorite (201)
        $response = $this->actingAs($user)->postJson('/api/favorites', [
            'product_id' => $product->id,
            'size' => 'Regular',
            'temp' => 'Hot',
            'addons' => ['Extra Shot'],
            'remark' => 'Make it strong'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'size' => 'Regular'
        ]);

        // Second POST with same data — should conflict (409)
        $duplicate = $this->actingAs($user)->postJson('/api/favorites', [
            'product_id' => $product->id,
            'size' => 'Regular',
            'temp' => 'Hot',
            'addons' => ['Extra Shot'],
            'remark' => 'Make it strong'
        ]);

        $duplicate->assertStatus(409);
    }

    public function test_user_can_remove_favorite()
    {
        [$user, $product] = $this->createProduct();

        $favorite = new Favorite();
        $favorite->user_id = $user->id;
        $favorite->product_id = $product->id;
        $favorite->size = 'Regular';
        $favorite->temp = 'Hot';
        $favorite->addons = [];
        $favorite->remark = '';
        $favorite->save();

        $response = $this->actingAs($user)->deleteJson("/api/favorites/{$favorite->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('favorites', ['id' => $favorite->id]);
    }
}
