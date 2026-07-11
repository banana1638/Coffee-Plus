<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\TangkiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_credit_is_idempotent(): void
    {
        $user = User::factory()->create(['tangki_balance' => 0.00]);
        $ledger = app(LedgerService::class);

        $ledger->credit($user, 500, 'admin_adjustment', 'ADJ-1', 'same-key', 'Adjustment');
        $ledger->credit($user, 500, 'admin_adjustment', 'ADJ-1', 'same-key', 'Adjustment');

        $user->refresh();
        $this->assertEquals('5.00', $user->tangki_balance);
        $this->assertDatabaseCount('wallet_ledger', 1);
    }

    public function test_ledger_rejects_non_positive_amounts(): void
    {
        $user = User::factory()->create(['tangki_balance' => 10.00]);
        $ledger = app(LedgerService::class);

        foreach ([0, -100] as $amountCents) {
            try {
                $ledger->credit($user, $amountCents, 'test', null, 'invalid-'.$amountCents, 'Invalid');
                $this->fail('Expected non-positive ledger amount to be rejected.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertSame('Wallet ledger amount must be greater than zero.', $exception->getMessage());
            }
        }

        $this->assertDatabaseCount('wallet_ledger', 0);
        $this->assertSame(1000, $user->fresh()->tangki_balance_cents);
    }

    public function test_refill_generates_cash_ledger_entry(): void
    {
        $user = User::factory()->create(['tangki_balance' => 0.00]);

        app(TangkiService::class)->refillBalance($user, 10.00, 'TOPUP-LEDGER');

        $user->refresh();
        $this->assertEquals('10.00', $user->tangki_balance);
        $this->assertDatabaseHas('wallet_ledger', [
            'user_id' => $user->id,
            'direction' => 'credit',
            'amount_cents' => 1000,
            'source_type' => 'stripe_refill',
            'source_id' => 'TOPUP-LEDGER',
            'idempotency_key' => 'stripe_refill:TOPUP-LEDGER',
        ]);
    }

    public function test_checkout_deducts_cash_balance_through_ledger(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $this->apiCheckout($user)->assertStatus(200);

        $user->refresh();
        $this->assertEquals('10.00', $user->tangki_balance);
        $this->assertDatabaseHas('wallet_ledger', [
            'user_id' => $user->id,
            'direction' => 'debit',
            'amount_cents' => 1000,
            'source_type' => 'order_payment',
        ]);
    }

    public function test_cancelled_order_refunds_cash_through_ledger(): void
    {
        $user = User::factory()->create(['tangki_balance' => 20.00]);
        $product = $this->createProduct();
        $this->addCartItem($user, $product);

        $checkout = $this->apiCheckout($user);
        $checkout->assertStatus(200);

        $this->actingAs($user)->postJson('/api/orders/' . $checkout->json('data.id') . '/cancel')
            ->assertStatus(200);

        $this->assertDatabaseHas('wallet_ledger', [
            'user_id' => $user->id,
            'direction' => 'credit',
            'amount_cents' => 1000,
            'source_type' => 'refund',
        ]);
    }

    public function test_cash_balance_cents_are_synced_with_ledger(): void
    {
        $user = User::factory()->create(['tangki_balance' => 0.00]);

        app(TangkiService::class)->refillBalance($user, 12.34, 'TOPUP-CENTS');

        $user->refresh();
        $this->assertSame(1234, $user->tangki_balance_cents);
        $this->assertEquals('12.34', $user->tangki_balance);
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

    private function addCartItem(User $user, Product $product): void
    {
        $cartItem = new CartItem();
        $cartItem->user_id = $user->id;
        $cartItem->product_id = $product->id;
        $cartItem->quantity = 1;
        $cartItem->size = 'Regular';
        $cartItem->temp = 'Hot';
        $cartItem->addons = [];
        $cartItem->unit_price = 10.00;
        $cartItem->save();
    }
}
