<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_create_coupon(): void
    {
        $staff = $this->createAdmin('staff');

        $this->actingAs($staff, 'admin')
            ->post(route('admin.coupons.store'), [
                'code' => 'STAFFNO',
                'type' => 'fixed',
                'value' => 5,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('coupons', [
            'code' => 'STAFFNO',
        ]);
    }

    public function test_staff_navigation_hides_unpermitted_admin_sections(): void
    {
        $staff = $this->createAdmin('staff');

        $this->actingAs($staff, 'admin')
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('Dashboard')
            ->assertSee('Live Orders')
            ->assertDontSee('Products')
            ->assertDontSee('Coupons')
            ->assertDontSee('Analytics')
            ->assertDontSee('Manage Products');
    }

    public function test_manager_navigation_shows_permitted_admin_sections(): void
    {
        $manager = $this->createAdmin('manager', 'manager@example.test');

        $this->actingAs($manager, 'admin')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.owner.dashboard'));

        $this->actingAs($manager, 'admin')
            ->get(route('admin.owner.dashboard'))
            ->assertStatus(200)
            ->assertSee('Analytics')
            ->assertSee('Products')
            ->assertSee('Coupons')
            ->assertSee('Orders');
    }

    public function test_staff_cannot_see_coupon_management_actions(): void
    {
        $staff = $this->createAdmin('staff');

        $this->actingAs($staff, 'admin')
            ->get(route('admin.coupons.index'))
            ->assertForbidden();
    }

    public function test_staff_orders_page_hides_report_export_action(): void
    {
        $staff = $this->createAdmin('staff');
        $this->createOrder(Order::STATUS_PENDING);

        $this->actingAs($staff, 'admin')
            ->get(route('admin.orders.index'))
            ->assertStatus(200)
            ->assertSee('Verify')
            ->assertDontSee('Export Center');
    }

    public function test_order_status_update_creates_audit_log(): void
    {
        $staff = $this->createAdmin('staff');
        $order = $this->createOrder(Order::STATUS_PENDING);

        $this->actingAs($staff, 'admin')
            ->patch(route('admin.orders.advance-status', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'order.status.update',
            'target_type' => Order::class,
            'target_id' => $order->id,
        ]);
    }

    public function test_admin_login_creates_audit_log(): void
    {
        $admin = $this->createAdmin('owner', 'login-owner@example.test');

        $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'admin.login',
        ]);
    }

    private function createAdmin(string $role, string $email = 'admin@example.test'): Admin
    {
        $admin = new Admin();
        $admin->name = ucfirst($role);
        $admin->email = $email;
        $admin->password = 'password';
        $admin->role = $role;
        $admin->save();

        return $admin;
    }

    private function createOrder(string $status): Order
    {
        $order = new Order();
        $order->user_id = User::factory()->create()->id;
        $order->bill_id = 'CP-SECURITY';
        $order->subtotal = 10.00;
        $order->final_amount = 10.00;
        $order->status = $status;
        $order->save();

        return $order;
    }
}
