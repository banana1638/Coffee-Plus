<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_more_than_five_failed_admin_login_attempts_are_throttled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('admin.login.post'), [
                'email' => 'missing@example.test',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $this->post(route('admin.login.post'), [
            'email' => 'missing@example.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_admin_login_failure_message_does_not_reveal_account_existence(): void
    {
        $admin = new Admin();
        $admin->name = 'Owner';
        $admin->email = 'owner@example.test';
        $admin->password = 'password';
        $admin->role = 'owner';
        $admin->save();

        $existing = $this->post(route('admin.login.post'), [
            'email' => 'owner@example.test',
            'password' => 'wrong-password',
        ]);

        $missing = $this->post(route('admin.login.post'), [
            'email' => 'missing@example.test',
            'password' => 'wrong-password',
        ]);

        $existing->assertSessionHasErrors([
            'email' => 'These credentials do not match our records.',
        ]);
        $missing->assertSessionHasErrors([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    public function test_successful_admin_login_still_works(): void
    {
        $admin = new Admin();
        $admin->name = 'Owner';
        $admin->email = 'owner-login@example.test';
        $admin->password = 'password';
        $admin->role = 'owner';
        $admin->save();

        $this->post(route('admin.login.post'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }
}
