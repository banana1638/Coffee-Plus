<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Coupon;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_permission_cannot_access_protected_route(): void
    {
        $staff = $this->createAdmin('staff');

        $this->actingAs($staff, 'admin')
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_admin_with_permission_can_access_protected_route(): void
    {
        $manager = $this->createAdmin('manager', 'manager@example.test');

        $this->actingAs($manager, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk();
    }

    public function test_product_deletion_requires_product_delete_permission(): void
    {
        $staff = $this->createAdmin('staff');
        $product = $this->createProduct();

        $this->actingAs($staff, 'admin')
            ->delete(route('admin.products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_coupon_update_requires_coupon_update_permission(): void
    {
        $staff = $this->createAdmin('staff');
        $coupon = $this->createCoupon();

        $this->actingAs($staff, 'admin')
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'NEWCODE',
                'type' => 'fixed',
                'value' => 20,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'SAVE10',
        ]);
    }

    public function test_order_status_update_requires_order_status_update_permission(): void
    {
        $viewer = $this->createAdmin('viewer', 'viewer@example.test');
        $order = $this->createOrder();

        $this->actingAs($viewer, 'admin')
            ->patch(route('admin.orders.advance-status', $order))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PENDING,
        ]);
    }

    public function test_report_export_requires_report_export_permission(): void
    {
        $staff = $this->createAdmin('staff');

        $this->actingAs($staff, 'admin')
            ->get(route('admin.orders.export.download', [
                'type' => 'date',
                'date' => now()->toDateString(),
            ]))
            ->assertForbidden();
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

    private function createCoupon(): Coupon
    {
        $coupon = new Coupon();
        $coupon->code = 'SAVE10';
        $coupon->type = 'fixed';
        $coupon->value = 10.00;
        $coupon->used_count = 0;
        $coupon->save();

        return $coupon;
    }

    private function createOrder(): Order
    {
        $order = new Order();
        $order->user_id = User::factory()->create()->id;
        $order->bill_id = 'CP-PERMISSION';
        $order->subtotal = 10.00;
        $order->final_amount = 10.00;
        $order->status = Order::STATUS_PENDING;
        $order->save();

        return $order;
    }
}
