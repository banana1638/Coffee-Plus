<?php

namespace Tests\Feature;

use App\Contracts\PaymentGatewayInterface;
use App\DataTransferObjects\PaymentResult;
use App\DataTransferObjects\PaymentInitiation;
use App\Models\CartSnapshot;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\Payment\StripeCheckoutHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripeMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_checkout_metadata_includes_coupon_pickup_and_use_oz(): void
    {
        $user = User::factory()->create();
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

        $cashItem = new CartItem();
        $cashItem->user_id = $user->id;
        $cashItem->product_id = $product->id;
        $cashItem->quantity = 1;
        $cashItem->size = 'Large';
        $cashItem->temp = 'Hot';
        $cashItem->addons = [];
        $cashItem->unit_price = 12.00;
        $cashItem->save();

        Coupon::create([
            'code' => 'SAVE5',
            'type' => 'fixed',
            'value' => 5.00,
            'usage_limit' => 10,
            'used_count' => 0,
        ]);

        $gateway = new class implements PaymentGatewayInterface {
            public array $metadata = [];
            public array $items = [];

            public function createCheckout(User $user, array $items, array $metadata): PaymentInitiation
            {
                $this->items = $items;
                $this->metadata = $metadata;

                return new PaymentInitiation('cs_checkout_pending', 'https://stripe.test/checkout');
            }

            public function getSessionData(string $sessionId): PaymentResult
            {
                return new PaymentResult('failed', 0, []);
            }
        };

        $this->app->instance(PaymentGatewayInterface::class, $gateway);

        $this->actingAs($user)
            ->post(route('stripe.checkout'), [
                'coupon_code' => 'SAVE5',
                'pickup_time' => '2026-06-06 15:00:00',
                'use_oz' => [$cartItem->id],
            ])
            ->assertRedirect('https://stripe.test/checkout');

        $this->assertSame('checkout', $gateway->metadata['type']);
        $this->assertSame($user->id, $gateway->metadata['user_id']);
        $this->assertNotEmpty($gateway->metadata['cart_snapshot_id']);
        $this->assertSame('SAVE5', $gateway->metadata['coupon_code']);
        $this->assertSame('2026-06-06 15:00:00', $gateway->metadata['pickup_time']);
        $this->assertSame(json_encode([$cartItem->id]), $gateway->metadata['use_oz']);
        $this->assertSame(700, $gateway->items[0]['price_data']['unit_amount']);
        $this->assertSame(1, $gateway->items[0]['quantity']);
        $this->assertDatabaseHas('cart_snapshots', [
            'id' => $gateway->metadata['cart_snapshot_id'],
            'user_id' => $user->id,
            'discount_cents' => 500,
            'final_amount_cents' => 700,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'session_id' => 'cs_checkout_pending',
            'user_id' => $user->id,
            'type' => 'checkout',
            'amount_cents' => 700,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_checkout_handler_processes_cart_snapshot(): void
    {
        $user = User::factory()->create();
        $snapshot = new CartSnapshot();
        $snapshot->user_id = $user->id;
        $snapshot->items_json = [];
        $snapshot->subtotal_cents = 1200;
        $snapshot->discount_cents = 0;
        $snapshot->oz_used = 0;
        $snapshot->final_amount_cents = 1200;
        $snapshot->status = CartSnapshot::STATUS_PENDING;
        $snapshot->expires_at = now()->addMinutes(30);
        $snapshot->save();

        $checkoutService = Mockery::mock(CheckoutService::class);
        $checkoutService->shouldReceive('processCartSnapshot')
            ->once()
            ->withArgs(fn ($passedUser, $passedSnapshot, $sessionId) =>
                $passedUser->is($user)
                && $passedSnapshot->is($snapshot)
                && $sessionId === 'cs_snapshot'
            )
            ->andReturn(new Order());

        $handler = new StripeCheckoutHandler($checkoutService);

        $handler->handle(new PaymentResult('success', 12.00, [
            'type' => 'checkout',
            'cart_snapshot_id' => $snapshot->id,
        ], 'cs_snapshot'), $user);
    }

    public function test_fully_discounted_checkout_completes_without_stripe(): void
    {
        $user = User::factory()->create(['tangki_balance' => 0]);
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

        Coupon::create([
            'code' => 'FREE10',
            'type' => 'fixed',
            'value' => 10.00,
            'usage_limit' => 1,
            'used_count' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('stripe.checkout'), [
                'coupon_code' => 'FREE10',
                'pickup_time' => '2026-06-27 15:00:00',
            ])
            ->assertRedirect(route('tangki.transactions'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'coupon_code' => 'FREE10',
            'discount_cents' => 1000,
            'final_amount_cents' => 0,
            'pickup_time' => '2026-06-27 15:00:00',
        ]);
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_stripe_checkout_handler_rejects_snapshot_amount_mismatch(): void
    {
        $user = User::factory()->create();
        $snapshot = new CartSnapshot();
        $snapshot->user_id = $user->id;
        $snapshot->items_json = [];
        $snapshot->subtotal_cents = 1200;
        $snapshot->discount_cents = 0;
        $snapshot->oz_used = 0;
        $snapshot->final_amount_cents = 1200;
        $snapshot->status = CartSnapshot::STATUS_PENDING;
        $snapshot->expires_at = now()->addMinutes(30);
        $snapshot->save();

        $handler = new StripeCheckoutHandler(Mockery::mock(CheckoutService::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stripe payment amount does not match cart snapshot.');

        $handler->handle(new PaymentResult('success', 10.00, [
            'type' => 'checkout',
            'cart_snapshot_id' => $snapshot->id,
        ], 'cs_snapshot'), $user);
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
