<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(float $price = 10.00): Product
    {
        $menu = new Menu();
        $menu->name = 'Coffee';
        $menu->save();

        $product = new Product();
        $product->menu_id = $menu->id;
        $product->name = 'Latte';
        $product->price = $price;
        $product->save();

        return $product;
    }

    private function addCartItem(User $user, Product $product, float $unitPrice = 10.00): CartItem
    {
        $cartItem = new CartItem();
        $cartItem->user_id = $user->id;
        $cartItem->product_id = $product->id;
        $cartItem->quantity = 1;
        $cartItem->size = 'Regular';
        $cartItem->temp = 'Hot';
        $cartItem->addons = [];
        $cartItem->unit_price = $unitPrice;
        $cartItem->save();

        return $cartItem;
    }

    public function test_checkout_with_empty_cart_returns_business_error(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);

        $response = $this->actingAs($user)->postJson('/api/checkout');

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Your cart is empty.',
            ]);
    }

    public function test_refill_order_does_not_block_first_product_order_referral_reward(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create([
            'referrer_id' => $referrer->id,
            'tangki_balance' => 20.00,
        ]);
        $product = $this->createProduct();

        $refillOrder = new Order();
        $refillOrder->user_id = $user->id;
        $refillOrder->bill_id = 'REFILL-1';
        $refillOrder->subtotal = 20.00;
        $refillOrder->final_amount = 20.00;
        $refillOrder->status = 'completed';
        $refillOrder->save();

        $this->addCartItem($user, $product);

        $this->actingAs($user)->postJson('/api/checkout')->assertStatus(200);

        $referrer->refresh();
        $this->assertEquals('5.00', $referrer->tangki_balance);
        $this->assertSame(50, $referrer->tangki_oz);
    }

    public function test_referral_reward_is_not_repeated_after_existing_product_order(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create([
            'referrer_id' => $referrer->id,
            'tangki_balance' => 20.00,
        ]);
        $product = $this->createProduct();

        $previousOrder = new Order();
        $previousOrder->user_id = $user->id;
        $previousOrder->bill_id = 'CP-PREVIOUS';
        $previousOrder->subtotal = 10.00;
        $previousOrder->final_amount = 10.00;
        $previousOrder->status = 'completed';
        $previousOrder->save();

        $previousItem = new OrderItem();
        $previousItem->order_id = $previousOrder->id;
        $previousItem->product_id = $product->id;
        $previousItem->quantity = 1;
        $previousItem->price = 10.00;
        $previousItem->price_at_time = 10.00;
        $previousItem->oz_at_time = 0;
        $previousItem->options = [];
        $previousItem->save();

        $this->addCartItem($user, $product);

        $this->actingAs($user)->postJson('/api/checkout')->assertStatus(200);

        $referrer->refresh();
        $this->assertEquals('0.00', $referrer->tangki_balance);
        $this->assertSame(0, $referrer->tangki_oz);
    }

    public function test_coupon_at_usage_limit_is_not_applied_or_incremented(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $coupon = new Coupon();
        $coupon->code = 'USEDUP';
        $coupon->type = 'fixed';
        $coupon->value = 5.00;
        $coupon->usage_limit = 1;
        $coupon->used_count = 1;
        $coupon->save();

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'coupon_code' => 'USEDUP',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.final_amount', 10);

        $coupon->refresh();
        $this->assertSame(1, $coupon->used_count);
    }
}
