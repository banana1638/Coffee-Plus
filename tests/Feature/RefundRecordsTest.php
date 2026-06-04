<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundRecordsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): Admin
    {
        $admin = new Admin();
        $admin->name = 'Owner';
        $admin->email = 'refund-owner@example.test';
        $admin->password = 'password';
        $admin->role = 'owner';
        $admin->save();

        return $admin;
    }

    public function test_admin_can_view_refund_records(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();

        $order = new Order();
        $order->user_id = $user->id;
        $order->bill_id = 'CP-REFUND';
        $order->subtotal = 10.00;
        $order->final_amount = 10.00;
        $order->status = Order::STATUS_CANCELLED;
        $order->save();

        $refund = new Transaction();
        $refund->user_id = $user->id;
        $refund->bill_id = $order->bill_id;
        $refund->oz_delta = 1000;
        $refund->type = 'refund';
        $refund->description = 'Cancelled order OZ refund: 1000 OZ';
        $refund->save();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.refunds'))
            ->assertStatus(200)
            ->assertSee('CP-REFUND')
            ->assertSee('Cancelled order OZ refund');
    }

    public function test_admin_can_search_refund_records_by_bill_id(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();

        foreach (['CP-MATCH', 'CP-OTHER'] as $billId) {
            $refund = new Transaction();
            $refund->user_id = $user->id;
            $refund->bill_id = $billId;
            $refund->oz_delta = 0;
            $refund->type = 'refund';
            $refund->description = "Refund {$billId}";
            $refund->save();
        }

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.refunds', ['search_id' => 'MATCH']))
            ->assertStatus(200)
            ->assertSee('CP-MATCH')
            ->assertDontSee('CP-OTHER');
    }
}
