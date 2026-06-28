<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiProviderContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_contract_contains_token_and_user(): void
    {
        $user = User::factory()->create([
            'email' => 'contract-login@example.test',
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Contract Phone',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'access_token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'balance',
                    'balance_cents',
                    'oz',
                ],
            ])
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('token_type', 'Bearer');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Contract Phone',
        ]);
    }

    public function test_dashboard_guest_contract_contains_menus_options_and_guest_user(): void
    {
        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'menus',
                'allCategoryNames',
                'options',
                'search',
                'category',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'balance',
                    'oz',
                ],
            ])
            ->assertJsonPath('user.name', 'Guest');
    }

    public function test_cart_contract_contains_cart_items_wrapper(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'cartItems',
                ],
            ]);
    }

    public function test_coupon_validate_contract_contains_discount_fields(): void
    {
        $user = User::factory()->create();

        $coupon = new Coupon;
        $coupon->code = 'CONTRACT5';
        $coupon->type = 'fixed';
        $coupon->value = 5.00;
        $coupon->usage_limit = 10;
        $coupon->used_count = 0;
        $coupon->save();

        $this->actingAs($user)
            ->getJson('/api/coupons/validate?code=CONTRACT5&subtotal=20')
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'valid',
                    'code',
                    'type',
                    'value',
                    'discount',
                    'final_amount',
                    'message',
                ],
            ])
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.discount', 5);
    }

    public function test_order_detail_contract_contains_money_and_item_snapshot_fields(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);

        $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'bill_id',
                    'status',
                    'subtotal',
                    'subtotal_cents',
                    'final_amount',
                    'final_amount_cents',
                    'coupon_code',
                    'coupon_discount',
                    'discount_cents',
                    'items' => [
                        '*' => [
                            'product_name',
                            'quantity',
                            'price_at_time',
                            'price_at_time_cents',
                            'oz_at_time',
                            'customizations' => [
                                'size',
                                'temp',
                                'addons',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_payment_status_contract_returns_pending_for_unknown_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/payments/cs_contract_pending/status')
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'session_id',
                    'status',
                ],
            ])
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_tangki_contract_contains_user_and_transactions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/tangki')
            ->assertOk()
            ->assertJsonStructure([
                'transactions',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'balance',
                    'balance_cents',
                    'oz',
                ],
            ]);
    }

    private function createOrder(User $user): Order
    {
        $menu = new Menu;
        $menu->name = 'Coffee';
        $menu->save();

        $product = new Product;
        $product->menu_id = $menu->id;
        $product->name = 'Contract Latte';
        $product->price = 10.00;
        $product->save();

        $order = new Order;
        $order->user_id = $user->id;
        $order->bill_id = 'CP-CONTRACT';
        $order->subtotal = 10.00;
        $order->final_amount = 10.00;
        $order->status = Order::STATUS_PENDING;
        $order->save();

        $item = new OrderItem;
        $item->order_id = $order->id;
        $item->product_id = $product->id;
        $item->product_name = $product->name;
        $item->quantity = 1;
        $item->price = 10.00;
        $item->price_at_time = 10.00;
        $item->oz_at_time = 0;
        $item->options = [
            'size' => 'Regular',
            'temp' => 'Hot',
            'addons' => [],
        ];
        $item->save();

        return $order;
    }
}
