<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Admin;
use App\Models\Coupon;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $response = $this->apiCheckout($user);

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

        $this->apiCheckout($user)->assertStatus(200);

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

        $this->apiCheckout($user)->assertStatus(200);

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

        $response = $this->apiCheckout($user, [
            'coupon_code' => 'USEDUP',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.final_amount', 10);

        $coupon->refresh();
        $this->assertSame(1, $coupon->used_count);
    }

    public function test_coupon_redemption_is_recorded_and_user_cannot_reuse_coupon(): void
    {
        $user = User::factory()->create(['tangki_balance' => 40.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $coupon = new Coupon();
        $coupon->code = 'ONCE5';
        $coupon->type = 'fixed';
        $coupon->value = 5.00;
        $coupon->usage_limit = 10;
        $coupon->used_count = 0;
        $coupon->save();

        $first = $this->apiCheckout($user, [
            'coupon_code' => 'ONCE5',
        ]);
        $first->assertStatus(200)
            ->assertJsonPath('data.final_amount', 5)
            ->assertJsonPath('data.final_amount_cents', 500)
            ->assertJsonPath('data.coupon_code', 'ONCE5')
            ->assertJsonPath('data.discount_cents', 500)
            ->assertJsonPath('data.coupon_discount', 5);

        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $first->json('data.id'),
            'discount_cents' => 500,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $first->json('data.id'),
            'coupon_code' => 'ONCE5',
            'discount_cents' => 500,
        ]);

        $this->addCartItem($user, $product);

        $second = $this->apiCheckout($user, [
            'coupon_code' => 'ONCE5',
        ]);
        $second->assertStatus(200)
            ->assertJsonPath('data.final_amount', 10)
            ->assertJsonPath('data.final_amount_cents', 1000);

        $coupon->refresh();
        $this->assertSame(1, $coupon->used_count);
        $this->assertDatabaseCount('coupon_redemptions', 1);
    }

    public function test_order_item_keeps_product_name_snapshot_after_product_changes(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $checkout = $this->apiCheckout($user);
        $checkout->assertStatus(200)
            ->assertJsonPath('data.items.0.product_name', 'Latte');

        $product->name = 'Renamed Latte';
        $product->save();

        $this->actingAs($user)
            ->getJson('/api/orders/' . $checkout->json('data.id'))
            ->assertOk()
            ->assertJsonPath('data.items.0.product_name', 'Latte');

        $this->assertDatabaseHas('order_items', [
            'order_id' => $checkout->json('data.id'),
            'product_name' => 'Latte',
        ]);
    }

    public function test_checkout_uses_final_discounted_amount_for_balance_check(): void
    {
        $user = User::factory()->create(['tangki_balance' => 7.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $coupon = new Coupon();
        $coupon->code = 'FINAL5';
        $coupon->type = 'fixed';
        $coupon->value = 5.00;
        $coupon->usage_limit = 10;
        $coupon->used_count = 0;
        $coupon->save();

        $this->apiCheckout($user, [
            'coupon_code' => 'FINAL5',
        ])->assertStatus(200)
            ->assertJsonPath('data.final_amount', 5)
            ->assertJsonPath('data.final_amount_cents', 500);

        $user->refresh();
        $this->assertEquals('2.00', $user->tangki_balance);
        $this->assertSame(200, $user->tangki_balance_cents);
    }

    public function test_checkout_accepts_legacy_balance_when_cents_cache_is_stale(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'tangki_balance' => 20.00,
                'tangki_balance_cents' => 0,
            ]);

        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $this->apiCheckout($user->fresh())->assertStatus(200)
            ->assertJsonPath('data.final_amount', 10)
            ->assertJsonPath('data.final_amount_cents', 1000);

        $user->refresh();
        $this->assertEquals('10.00', $user->tangki_balance);
        $this->assertSame(1000, $user->tangki_balance_cents);
    }

    public function test_checkout_rejects_when_tracked_stock_is_insufficient(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $product->stock = 0;
        $product->track_stock = true;
        $product->save();
        $this->addCartItem($user, $product);

        $this->apiCheckout($user)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Insufficient stock for Latte.');
    }

    public function test_cancelled_pending_order_restores_tracked_stock(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $product->stock = 1;
        $product->track_stock = true;
        $product->save();
        $this->addCartItem($user, $product);

        $checkout = $this->apiCheckout($user);
        $checkout->assertStatus(200);

        $product->refresh();
        $this->assertSame(0, $product->stock);

        $this->actingAs($user)->postJson('/api/orders/' . $checkout->json('data.id') . '/cancel')
            ->assertStatus(200);

        $product->refresh();
        $this->assertSame(1, $product->stock);
    }

    public function test_user_can_cancel_pending_cash_order_and_receive_tangki_refund(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $checkout = $this->apiCheckout($user);
        $checkout->assertStatus(200);

        $orderId = $checkout->json('data.id');

        $user->refresh();
        $this->assertEquals('10.00', $user->tangki_balance);
        $this->assertSame(500, $user->tangki_oz);

        $response = $this->actingAs($user)->postJson("/api/orders/{$orderId}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.can_cancel', false);

        $user->refresh();
        $this->assertEquals('20.00', $user->tangki_balance);
        $this->assertSame(0, $user->tangki_oz);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'refund',
            'oz_delta' => 0,
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'refund',
            'oz_delta' => -500,
        ]);
    }

    public function test_user_can_cancel_pending_oz_order_and_receive_oz_refund(): void
    {
        $user = User::factory()->create([
            'tangki_balance' => 0.00,
            'tangki_oz' => 1000,
        ]);
        $product = $this->createProduct();
        $cartItem = $this->addCartItem($user, $product);

        $checkout = $this->apiCheckout($user, [
            'use_oz' => [$cartItem->id],
        ]);
        $checkout->assertStatus(200);

        $user->refresh();
        $this->assertSame(0, $user->tangki_oz);

        $response = $this->actingAs($user)->postJson('/api/orders/' . $checkout->json('data.id') . '/cancel');

        $response->assertStatus(200);

        $user->refresh();
        $this->assertSame(1000, $user->tangki_oz);
    }

    public function test_completed_order_cannot_be_cancelled(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $checkout = $this->apiCheckout($user);
        $checkout->assertStatus(200);

        $order = Order::findOrFail($checkout->json('data.id'));
        $order->status = 'completed';
        $order->save();

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Only pending orders can be cancelled.');
    }

    public function test_checkout_generates_pickup_code_and_qr_payload(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $response = $this->apiCheckout($user);

        $response->assertStatus(200);

        $pickupCode = $response->json('data.pickup_code');
        $this->assertNotEmpty($pickupCode);
        $this->assertStringStartsWith('PU', $pickupCode);
        $this->assertSame(
            'COFFEEPLUS|' . $response->json('data.bill_id') . '|' . $pickupCode,
            $response->json('data.pickup_qr_payload')
        );
    }

    public function test_admin_can_complete_order_by_pickup_code(): void
    {
        $admin = new Admin();
        $admin->name = 'Staff';
        $admin->email = 'staff@example.test';
        $admin->password = 'password';
        $admin->role = 'staff';
        $admin->save();

        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $checkout = $this->apiCheckout($user);
        $checkout->assertStatus(200);

        $pickupCode = $checkout->json('data.pickup_code');
        $order = Order::findOrFail($checkout->json('data.id'));
        $order->status = Order::STATUS_READY;
        $order->save();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.orders.complete-by-code'), [
            'pickup_code' => strtolower($pickupCode),
        ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_admin_can_advance_order_status_flow(): void
    {
        $admin = new Admin();
        $admin->name = 'Staff';
        $admin->email = 'flow@example.test';
        $admin->password = 'password';
        $admin->role = 'staff';
        $admin->save();

        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $checkout = $this->apiCheckout($user);
        $checkout->assertStatus(200)
            ->assertJsonPath('data.status', Order::STATUS_PENDING)
            ->assertJsonPath('data.next_status', Order::STATUS_PREPARING);

        $order = Order::findOrFail($checkout->json('data.id'));

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.orders.advance-status', $order))
            ->assertSessionHas('success');
        $order->refresh();
        $this->assertSame(Order::STATUS_PREPARING, $order->status);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.orders.advance-status', $order))
            ->assertSessionHas('success');
        $order->refresh();
        $this->assertSame(Order::STATUS_READY, $order->status);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.orders.advance-status', $order))
            ->assertSessionHas('success');
        $order->refresh();
        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_pickup_code_cannot_complete_order_before_ready_status(): void
    {
        $admin = new Admin();
        $admin->name = 'Staff';
        $admin->email = 'not-ready@example.test';
        $admin->password = 'password';
        $admin->role = 'staff';
        $admin->save();

        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $checkout = $this->apiCheckout($user);
        $checkout->assertStatus(200);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.orders.complete-by-code'), [
            'pickup_code' => $checkout->json('data.pickup_code'),
        ]);

        $response->assertSessionHas('error', 'Only ready for pickup orders can be completed.');
    }
}
