<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_validate_fixed_coupon(): void
    {
        $user = User::factory()->create();

        $coupon = new Coupon();
        $coupon->code = 'SAVE5';
        $coupon->type = 'fixed';
        $coupon->value = 5.00;
        $coupon->used_count = 0;
        $coupon->save();

        $this->actingAs($user)
            ->getJson('/api/coupons/validate?code=save5&subtotal=12.00')
            ->assertStatus(200)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.discount', 5)
            ->assertJsonPath('data.final_amount', 7);
    }

    public function test_user_can_validate_percent_coupon(): void
    {
        $user = User::factory()->create();

        $coupon = new Coupon();
        $coupon->code = 'TENOFF';
        $coupon->type = 'percent';
        $coupon->value = 10.00;
        $coupon->used_count = 0;
        $coupon->save();

        $this->actingAs($user)
            ->getJson('/api/coupons/validate?code=TENOFF&subtotal=50.00')
            ->assertStatus(200)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.discount', 5)
            ->assertJsonPath('data.final_amount', 45);
    }

    public function test_invalid_coupon_returns_valid_false(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/coupons/validate?code=NOPE&subtotal=12.00')
            ->assertStatus(200)
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.final_amount', 12);
    }
}
