<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_paginates_pending_orders(): void
    {
        $admin = new Admin;
        $admin->name = 'Staff';
        $admin->email = 'pagination-staff@example.test';
        $admin->password = 'password';
        $admin->role = 'staff';
        $admin->save();

        $user = User::factory()->create();

        for ($number = 1; $number <= 12; $number++) {
            $order = new Order;
            $order->user_id = $user->id;
            $order->bill_id = sprintf('CP-PENDING-%02d', $number);
            $order->subtotal = 10.00;
            $order->final_amount = 10.00;
            $order->status = Order::STATUS_PENDING;
            $order->save();
        }

        $firstPage = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Queue: 12');
        $firstPageOrders = $firstPage->viewData('pendingOrders');
        $this->assertSame(12, $firstPageOrders->total());
        $this->assertSame(10, $firstPageOrders->count());
        $this->assertSame('CP-PENDING-01', $firstPageOrders->first()->bill_id);
        $this->assertSame('CP-PENDING-10', $firstPageOrders->last()->bill_id);

        $secondPage = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['page' => 2]))
            ->assertOk();
        $secondPageOrders = $secondPage->viewData('pendingOrders');
        $this->assertSame(2, $secondPageOrders->count());
        $this->assertSame('CP-PENDING-11', $secondPageOrders->first()->bill_id);
        $this->assertSame('CP-PENDING-12', $secondPageOrders->last()->bill_id);
    }
}
