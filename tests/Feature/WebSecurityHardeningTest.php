<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_cart_rejects_invalid_options(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $menu = Menu::create(['name' => 'Coffee']);
        $product = new Product();
        $product->menu_id = $menu->id;
        $product->name = 'Latte';
        $product->price = 10.00;
        $product->save();

        $this->actingAs($user)
            ->postJson(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 1,
                'size' => 'Bucket',
                'temp' => 'Boiling',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['size', 'temp']);
    }

    public function test_web_refill_rejects_out_of_range_amount_before_gateway_call(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->from(route('tangki.index'))
            ->post(route('tangki.refill'), ['amount' => 500.001])
            ->assertRedirect(route('tangki.index'))
            ->assertSessionHasErrors('amount');
    }

    public function test_global_security_headers_are_present_and_hsts_requires_https(): void
    {
        config(['security.hsts.enabled' => true]);

        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_telescope_is_scoped_to_admin_owner_access(): void
    {
        $manager = new Admin();
        $manager->role = 'manager';

        $owner = new Admin();
        $owner->role = 'owner';

        $this->assertSame('admin/telescope', config('telescope.path'));
        $this->assertContains('auth:admin', config('telescope.middleware'));
        $this->assertContains('admin.permission:telescope.view', config('telescope.middleware'));
        $this->assertFalse($manager->canPerform('telescope.view'));
        $this->assertTrue($owner->canPerform('telescope.view'));
        $this->assertSame(168, config('telescope.prune_hours'));
    }
}
