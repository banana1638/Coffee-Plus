<?php

namespace Tests\Feature;

use App\DataTransferObjects\PaymentResult;
use App\Models\CartItem;
use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use App\Services\CartSnapshotService;
use App\Services\Payment\StripeCheckoutHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_checkout_uses_snapshot_even_if_cart_changes_after_session_creation(): void
    {
        $user = User::factory()->create(['tangki_balance' => 0.00]);
        $product = $this->createProduct();

        $cartItem = new CartItem();
        $cartItem->user_id = $user->id;
        $cartItem->product_id = $product->id;
        $cartItem->quantity = 1;
        $cartItem->size = 'Regular';
        $cartItem->temp = 'Hot';
        $cartItem->addons = [];
        $cartItem->unit_price = 10.00;
        $cartItem->save();

        $snapshot = app(CartSnapshotService::class)->createFromCart($user, [], null, null);

        $cartItem->quantity = 3;
        $cartItem->unit_price = 99.00;
        $cartItem->save();

        app(StripeCheckoutHandler::class)->handle(new PaymentResult('success', 10.00, [
            'type' => 'checkout',
            'cart_snapshot_id' => $snapshot->id,
        ], 'cs_snapshot_cart_change'), $user);

        $this->assertDatabaseHas('orders', [
            'bill_id' => 'CP-CS_SNAPSHOT_CART_CHANGE',
            'subtotal' => 10.00,
            'final_amount' => 10.00,
        ]);

        $this->assertDatabaseHas('order_items', [
            'quantity' => 1,
            'price_at_time' => 10.00,
        ]);
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
