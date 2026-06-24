<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWalletAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_credit_user_wallet_with_ledger_and_audit_log(): void
    {
        $admin = $this->createAdmin('owner');
        $user = User::factory()->create(['tangki_balance' => 0.00, 'tangki_balance_cents' => 0]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.dashboard'))
            ->post(route('admin.wallet.adjust'), [
                'user_id' => $user->id,
                'direction' => 'credit',
                'amount' => 25.50,
                'reason' => 'Customer support goodwill adjustment',
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success', 'Wallet adjustment recorded.');

        $user->refresh();
        $this->assertSame(2550, $user->tangki_balance_cents);
        $this->assertEquals('25.50', $user->tangki_balance);

        $this->assertDatabaseHas('wallet_ledger', [
            'user_id' => $user->id,
            'direction' => 'credit',
            'amount_cents' => 2550,
            'source_type' => 'admin_adjustment',
            'source_id' => 'admin:' . $admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'wallet.adjust',
            'target_type' => User::class,
            'target_id' => $user->id,
        ]);
    }

    public function test_owner_cannot_debit_more_than_user_wallet_balance(): void
    {
        $admin = $this->createAdmin('owner');
        $user = User::factory()->create(['tangki_balance' => 10.00, 'tangki_balance_cents' => 1000]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.dashboard'))
            ->post(route('admin.wallet.adjust'), [
                'user_id' => $user->id,
                'direction' => 'debit',
                'amount' => 25.50,
                'reason' => 'Reverse duplicated manual adjustment',
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasErrors('amount');

        $user->refresh();
        $this->assertSame(1000, $user->tangki_balance_cents);
        $this->assertDatabaseMissing('wallet_ledger', [
            'user_id' => $user->id,
            'direction' => 'debit',
            'amount_cents' => 2550,
            'source_type' => 'admin_adjustment',
        ]);
    }

    public function test_manager_cannot_adjust_wallet(): void
    {
        $admin = $this->createAdmin('manager');
        $user = User::factory()->create(['tangki_balance' => 0.00, 'tangki_balance_cents' => 0]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.wallet.adjust'), [
                'user_id' => $user->id,
                'direction' => 'credit',
                'amount' => 25.50,
                'reason' => 'Customer support goodwill adjustment',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('wallet_ledger', 0);
    }

    private function createAdmin(string $role): Admin
    {
        $admin = new Admin();
        $admin->name = ucfirst($role);
        $admin->email = $role . '-wallet@example.test';
        $admin->password = 'password';
        $admin->role = $role;
        $admin->save();

        return $admin;
    }
}
