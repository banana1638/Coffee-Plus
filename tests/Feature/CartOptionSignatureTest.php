<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Support\AddonsSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartOptionSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_add_merges_items_with_same_sorted_addons_signature(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        app(CartService::class)->add($user, $product->id, 1, 'Regular', 'Hot', [
            'Vanilla Syrup',
            'Extra Shot',
        ]);

        $item = app(CartService::class)->add($user, $product->id, 2, 'Regular', 'Hot', [
            'Extra Shot',
            'Vanilla Syrup',
        ]);

        $item->refresh();

        $this->assertSame(3, $item->quantity);
        $this->assertSame(AddonsSignature::from(['Extra Shot', 'Vanilla Syrup']), $item->addons_signature);
        $this->assertDatabaseCount('cart_items', 1);
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
