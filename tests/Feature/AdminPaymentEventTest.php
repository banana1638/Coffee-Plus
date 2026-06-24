<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_payment_events(): void
    {
        $admin = $this->createAdmin('owner');
        $user = User::factory()->create(['email' => 'payer@example.test']);
        $event = $this->createPaymentEvent($user, 'failed');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.payment-events.index', ['status' => 'failed']))
            ->assertOk()
            ->assertSee($event->session_id)
            ->assertSee('payer@example.test')
            ->assertSee('failed');
    }

    public function test_owner_can_view_payment_event_detail(): void
    {
        $admin = $this->createAdmin('owner');
        $user = User::factory()->create();
        $event = $this->createPaymentEvent($user, 'ignored');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.payment-events.show', $event))
            ->assertOk()
            ->assertSee($event->event_id)
            ->assertSee($event->session_id)
            ->assertSee('checkout.session.completed');
    }

    public function test_manager_cannot_view_payment_events(): void
    {
        $admin = $this->createAdmin('manager');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.payment-events.index'))
            ->assertForbidden();
    }

    private function createPaymentEvent(User $user, string $status): PaymentEvent
    {
        return PaymentEvent::create([
            'provider' => 'stripe',
            'event_id' => 'evt_admin_' . $status,
            'session_id' => 'cs_admin_' . $status,
            'user_id' => $user->id,
            'type' => 'checkout.session.completed',
            'amount_cents' => 2500,
            'currency' => 'myr',
            'status' => $status,
            'payload_json' => [
                'id' => 'evt_admin_' . $status,
                'type' => 'checkout.session.completed',
            ],
            'processed_at' => $status === 'processed' ? now() : null,
        ]);
    }

    private function createAdmin(string $role): Admin
    {
        $admin = new Admin();
        $admin->name = ucfirst($role);
        $admin->email = $role . '-payment-events@example.test';
        $admin->password = 'password';
        $admin->role = $role;
        $admin->save();

        return $admin;
    }
}
