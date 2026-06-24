<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiOrderTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(User $user, string $billId = 'CP-API-ORDER'): Order
    {
        $menu = new Menu();
        $menu->name = 'Coffee';
        $menu->save();

        $product = new Product();
        $product->menu_id = $menu->id;
        $product->name = 'Latte';
        $product->price = 10.00;
        $product->save();

        $order = new Order();
        $order->user_id = $user->id;
        $order->bill_id = $billId;
        $order->subtotal = 10.00;
        $order->final_amount = 10.00;
        $order->status = Order::STATUS_PENDING;
        $order->save();

        $item = new OrderItem();
        $item->order_id = $order->id;
        $item->product_id = $product->id;
        $item->product_name = 'Latte';
        $item->quantity = 1;
        $item->price = 10.00;
        $item->price_at_time = 10.00;
        $item->oz_at_time = 0;
        $item->options = [];
        $item->save();

        return $order;
    }

    public function test_user_can_list_their_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createOrder($user, 'CP-MINE');
        $this->createOrder($otherUser, 'CP-OTHER');

        $this->actingAs($user)
            ->getJson('/api/orders')
            ->assertStatus(200)
            ->assertJsonPath('data.orders.0.bill_id', 'CP-MINE')
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonMissing(['bill_id' => 'CP-OTHER']);
    }

    public function test_user_can_view_their_order_detail(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'CP-DETAIL');

        $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.bill_id', 'CP-DETAIL')
            ->assertJsonPath('data.items.0.product_name', 'Latte');
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = $this->createOrder($otherUser, 'CP-PRIVATE');

        $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}")
            ->assertStatus(404);
    }

    public function test_user_cannot_cancel_another_users_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = $this->createOrder($otherUser, 'CP-CANCEL-PRIVATE');

        $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertStatus(404);
    }
}
