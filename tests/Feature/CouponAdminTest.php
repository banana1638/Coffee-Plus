<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponAdminTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): Admin
    {
        $admin = new Admin();
        $admin->name = 'Owner';
        $admin->email = 'owner@example.test';
        $admin->password = 'password';
        $admin->role = 'owner';
        $admin->save();

        return $admin;
    }

    public function test_admin_can_view_coupon_index(): void
    {
        $admin = $this->createAdmin();

        $coupon = new Coupon();
        $coupon->code = 'SAVE10';
        $coupon->type = 'fixed';
        $coupon->value = 10.00;
        $coupon->used_count = 0;
        $coupon->save();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.index'))
            ->assertStatus(200)
            ->assertSee('SAVE10');
    }

    public function test_admin_can_create_coupon(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.store'), [
                'code' => 'new15',
                'type' => 'percent',
                'value' => 15,
                'usage_limit' => 20,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'code' => 'NEW15',
            'type' => 'percent',
            'used_count' => 0,
            'usage_limit' => 20,
        ]);
    }

    public function test_admin_can_update_coupon(): void
    {
        $admin = $this->createAdmin();

        $coupon = new Coupon();
        $coupon->code = 'SAVE10';
        $coupon->type = 'fixed';
        $coupon->value = 10.00;
        $coupon->used_count = 0;
        $coupon->save();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'SAVE20',
                'type' => 'fixed',
                'value' => 20,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'SAVE20',
            'value' => 20,
        ]);
    }

    public function test_admin_can_delete_coupon(): void
    {
        $admin = $this->createAdmin();

        $coupon = new Coupon();
        $coupon->code = 'SAVE10';
        $coupon->type = 'fixed';
        $coupon->value = 10.00;
        $coupon->used_count = 0;
        $coupon->save();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.coupons.destroy', $coupon))
            ->assertRedirect();

        $this->assertDatabaseMissing('coupons', [
            'id' => $coupon->id,
        ]);
    }

    public function test_percent_coupon_cannot_exceed_one_hundred_percent(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.coupons.create'))
            ->post(route('admin.coupons.store'), [
                'code' => 'FREEPLUS',
                'type' => 'percent',
                'value' => 101,
            ])
            ->assertRedirect(route('admin.coupons.create'))
            ->assertSessionHasErrors('value');

        $this->assertDatabaseMissing('coupons', ['code' => 'FREEPLUS']);
    }
}
