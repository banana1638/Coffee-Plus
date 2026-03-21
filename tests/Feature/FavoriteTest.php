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

    public function test_user_can_list_favorites()
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
        $user = User::factory()->create();
        $menu = new Menu();
        $menu->name = 'Coffee';
        $menu->save();

        $product = new Product();
        $product->menu_id = $menu->id;
        $product->name = 'Latte';
        $product->price = 10.00;
        $product->save();

        $response = $this->actingAs($user)->postJson('/api/favorites', [
            'product_id' => $product->id,
            'size' => 'Regular',
            'temp' => 'Hot',
            'addons' => ['Extra Shot'],
            'remark' => 'Make it strong'
        ]);

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
    }

    public function test_user_can_remove_favorite()
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
