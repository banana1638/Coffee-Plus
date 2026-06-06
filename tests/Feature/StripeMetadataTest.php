<?php

namespace Tests\Feature;

use App\Contracts\PaymentGatewayInterface;
use App\DataTransferObjects\PaymentResult;
use App\Models\CartSnapshot;
use App\Models\CartItem;
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

        $gateway = new class implements PaymentGatewayInterface {
            public array $metadata = [];

            public function createCheckoutUrl(User $user, array $items, array $metadata): string
            {
                $this->metadata = $metadata;

                return 'https://stripe.test/checkout';
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
        $this->assertDatabaseHas('cart_snapshots', [
            'id' => $gateway->metadata['cart_snapshot_id'],
            'user_id' => $user->id,
            'final_amount_cents' => 1200,
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
