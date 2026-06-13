<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SharedRecipeValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_recipe_rejects_invalid_size_temp_and_long_remark(): void
    {
        [$sender, $recipient] = $this->friendPair();
        $product = $this->createProduct();

        $this->actingAs($sender)
            ->postJson('/api/recipes', [
                'recipient_id' => $recipient->id,
                'product_id' => $product->id,
                'name' => 'Bad Recipe',
                'size' => 'Huge',
                'temp' => 'Lava',
                'remark' => str_repeat('x', 1001),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['size', 'temp', 'remark']);
    }

    public function test_shared_recipe_rejects_addon_from_another_product(): void
    {
        [$sender, $recipient] = $this->friendPair();
        $product = $this->createProduct('Latte');
        $otherProduct = $this->createProduct('Mocha');
        $addon = $this->createAddon($otherProduct, 'Caramel Syrup');

        $this->actingAs($sender)
            ->postJson('/api/recipes', [
                'recipient_id' => $recipient->id,
                'product_id' => $product->id,
                'name' => 'Latte Mix',
                'size' => 'Regular',
                'temp' => 'Hot',
                'addons' => [$addon->name],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('addons');
    }

    public function test_shared_recipe_accepts_valid_payload(): void
    {
        [$sender, $recipient] = $this->friendPair();
        $product = $this->createProduct();
        $addon = $this->createAddon($product, 'Extra Shot');

        $this->actingAs($sender)
            ->postJson('/api/recipes', [
                'recipient_id' => $recipient->id,
                'product_id' => $product->id,
                'name' => 'Morning Latte',
                'size' => 'Regular',
                'temp' => 'Hot',
                'addons' => [$addon->name],
                'remark' => 'Less sweet.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('shared_recipes', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'product_id' => $product->id,
            'name' => 'Morning Latte',
        ]);
    }

    private function friendPair(): array
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        DB::table('friendships')->insert([
            'user_id' => $sender->id,
            'friend_id' => $recipient->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$sender, $recipient];
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
