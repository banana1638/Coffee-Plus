<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use OTPHP\TOTP;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_confirm_two_factor_setup(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin')->get(route('admin.two-factor.show'))->assertOk();
        $secret = session('admin.two_factor_setup_secret');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.two-factor.confirm'), [
                'code' => TOTP::create($secret)->now(),
            ])
            ->assertRedirect(route('admin.two-factor.show'))
            ->assertSessionHas('recovery_codes');

        $admin->refresh();
        $this->assertTrue($admin->hasTwoFactorAuthentication());
        $this->assertCount(8, $admin->two_factor_recovery_codes);
        $this->assertNotSame($secret, $admin->getRawOriginal('two_factor_secret'));
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'admin.two_factor.enabled',
        ]);
    }

    public function test_password_login_requires_valid_totp_before_authentication(): void
    {
        $secret = TOTP::create()->getSecret();
        $admin = $this->createAdmin($secret);

        $this->post(route('admin.login.post'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.two-factor.challenge'));
        $this->assertGuest('admin');

        $this->post(route('admin.two-factor.verify'), [
            'code' => TOTP::create($secret)->now(),
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'admin.login.two_factor',
        ]);
    }

    public function test_invalid_totp_does_not_authenticate_admin(): void
    {
        $admin = $this->createAdmin(TOTP::create()->getSecret());

        $this->post(route('admin.login.post'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->post(route('admin.two-factor.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest('admin');
    }

    public function test_recovery_code_is_consumed_after_login(): void
    {
        $secret = TOTP::create()->getSecret();
        $admin = $this->createAdmin($secret, ['ABCDE-12345']);

        $this->post(route('admin.login.post'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $this->post(route('admin.two-factor.verify'), ['code' => 'abcde-12345'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertSame([], $admin->fresh()->two_factor_recovery_codes);
    }

    public function test_totp_code_cannot_be_replayed(): void
    {
        $secret = TOTP::create()->getSecret();
        $admin = $this->createAdmin($secret);
        $code = TOTP::create($secret)->now();

        $this->post(route('admin.login.post'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $this->post(route('admin.two-factor.verify'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.logout'));
        $this->post(route('admin.login.post'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $this->post(route('admin.two-factor.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest('admin');
    }

    private function createAdmin(?string $secret = null, array $recoveryCodes = []): Admin
    {
        $admin = new Admin;
        $admin->name = 'Owner';
        $admin->email = 'two-factor-owner@example.test';
        $admin->password = 'password';
        $admin->role = 'owner';

        if ($secret) {
            $admin->two_factor_secret = $secret;
            $admin->two_factor_recovery_codes = array_map(
                fn (string $code) => Hash::make($code),
                $recoveryCodes,
            );
            $admin->two_factor_confirmed_at = now();
        }

        $admin->save();

        return $admin;
    }
}
