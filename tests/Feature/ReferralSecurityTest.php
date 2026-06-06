<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Listeners\RewardReferrer;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_registration_accepts_uuid_referral_code(): void
    {
        $referrer = User::factory()->create();

        $this->post('/register', [
            'name' => 'New User',
            'email' => 'new@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'ref' => $referrer->referral_code,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.test',
            'referrer_id' => $referrer->id,
            'referred_by' => $referrer->id,
        ]);
    }

    public function test_web_registration_rejects_legacy_base64_user_id_referral(): void
    {
        $referrer = User::factory()->create();

        $this->from('/register')
            ->post('/register', [
                'name' => 'New User',
                'email' => 'legacy@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'ref' => base64_encode((string) $referrer->id),
            ])
            ->assertSessionHasErrors('ref');

        $this->assertDatabaseMissing('users', [
            'email' => 'legacy@example.test',
        ]);
    }

    public function test_api_registration_accepts_uuid_referral_code(): void
    {
        $referrer = User::factory()->create();

        $this->postJson('/api/register', [
            'name' => 'Api User',
            'email' => 'api-ref@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'ref' => $referrer->referral_code,
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'api-ref@example.test',
            'referrer_id' => $referrer->id,
            'referred_by' => $referrer->id,
        ]);
    }

    public function test_referral_reward_is_idempotent(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create([
            'referrer_id' => $referrer->id,
            'referred_by' => $referrer->id,
        ]);
        $product = $this->createProduct();
        $order = $this->createOrderWithProduct($user, $product);

        $listener = app(RewardReferrer::class);
        $event = new OrderPlaced($order, $user, [], 10.00, 0, 500);

        $listener->handle($event);
        $listener->handle($event);

        $referrer->refresh();
        $user->refresh();

        $this->assertEquals('5.00', $referrer->tangki_balance);
        $this->assertSame(50, $referrer->tangki_oz);
        $this->assertTrue((bool) $user->referral_rewarded);
        $this->assertDatabaseCount('transactions', 1);
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

    private function createOrderWithProduct(User $user, Product $product): Order
    {
        $order = new Order();
        $order->user_id = $user->id;
        $order->bill_id = 'CP-REF-IDEMPOTENT';
        $order->subtotal = 10.00;
        $order->final_amount = 10.00;
        $order->status = Order::STATUS_PENDING;
        $order->save();

        $item = new OrderItem();
        $item->order_id = $order->id;
        $item->product_id = $product->id;
        $item->quantity = 1;
        $item->price = 10.00;
        $item->price_at_time = 10.00;
        $item->oz_at_time = 0;
        $item->options = [];
        $item->save();

        return $order;
    }
}
