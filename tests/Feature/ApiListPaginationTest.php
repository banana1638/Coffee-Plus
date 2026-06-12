<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Product;
use App\Models\SharedRecipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiListPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_api_is_paginated(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'test.notification',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode(['message' => "Notification {$i}"]),
                'created_at' => now()->subMinutes($i),
                'updated_at' => now()->subMinutes($i),
            ]);
        }

        $this->actingAs($user)
            ->getJson('/api/profile/notifications?per_page=2')
            ->assertStatus(200)
            ->assertJsonCount(2, 'notifications')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_shared_recipes_api_is_paginated(): void
    {
        $recipient = User::factory()->create();
        $sender = User::factory()->create();
        $product = $this->createProduct();

        for ($i = 0; $i < 3; $i++) {
            $recipe = new SharedRecipe();
            $recipe->sender_id = $sender->id;
            $recipe->recipient_id = $recipient->id;
            $recipe->product_id = $product->id;
            $recipe->name = "Recipe {$i}";
            $recipe->size = 'Regular';
            $recipe->temp = 'Hot';
            $recipe->addons = [];
            $recipe->save();
        }

        $this->actingAs($recipient)
            ->getJson('/api/recipes?per_page=2')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2);
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
