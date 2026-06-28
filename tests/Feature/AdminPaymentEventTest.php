<?php

namespace Tests\Feature;

use App\Contracts\PaymentGatewayInterface;
use App\DataTransferObjects\PaymentInitiation;
use App\DataTransferObjects\PaymentResult;
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

    public function test_owner_can_retry_server_owned_failed_payment(): void
    {
        $admin = $this->createAdmin('owner');
        $user = User::factory()->create();
        $event = PaymentEvent::recordPending($user, 'cs_retry_paid', 'refill', 2500, [
            'type' => 'refill',
            'user_id' => $user->id,
            'amount' => '25.00',
        ]);
        $event->update(['status' => PaymentEvent::STATUS_FAILED]);
        $this->bindPaymentResult(new PaymentResult(
            'success',
            25.00,
            ['type' => 'refill', 'user_id' => $user->id, 'amount' => '25.00'],
            'cs_retry_paid',
            'myr',
        ));

        $this->actingAs($admin, 'admin')
            ->post(route('admin.payment-events.retry', $event))
            ->assertRedirect(route('admin.payment-events.show', $event))
            ->assertSessionHas('success');

        $event->refresh();
        $user->refresh();
        $this->assertSame(PaymentEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame(1, $event->retry_attempts);
        $this->assertEquals('25.00', $user->tangki_balance);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'payment.retry.succeeded',
            'target_id' => $event->id,
        ]);
    }

    public function test_retry_does_not_credit_wallet_when_stripe_is_not_paid(): void
    {
        $admin = $this->createAdmin('owner');
        $user = User::factory()->create();
        $event = PaymentEvent::recordPending($user, 'cs_retry_unpaid', 'refill', 2500);
        $event->update(['status' => PaymentEvent::STATUS_FAILED]);
        $this->bindPaymentResult(new PaymentResult(
            'failed',
            25.00,
            ['type' => 'refill', 'user_id' => $user->id],
            'cs_retry_unpaid',
            'myr',
        ));

        $this->actingAs($admin, 'admin')
            ->post(route('admin.payment-events.retry', $event))
            ->assertSessionHas('error');

        $event->refresh();
        $user->refresh();
        $this->assertSame(PaymentEvent::STATUS_FAILED, $event->status);
        $this->assertSame(1, $event->retry_attempts);
        $this->assertSame('Stripe does not report this payment as paid.', $event->last_error);
        $this->assertEquals('0.00', $user->tangki_balance);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'payment.retry.failed',
            'target_id' => $event->id,
        ]);
    }

    public function test_manager_cannot_retry_payment_event(): void
    {
        $admin = $this->createAdmin('manager');
        $user = User::factory()->create();
        $event = PaymentEvent::recordPending($user, 'cs_retry_forbidden', 'refill', 2500);
        $event->update(['status' => PaymentEvent::STATUS_FAILED]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.payment-events.retry', $event))
            ->assertForbidden();
    }

    private function createPaymentEvent(User $user, string $status): PaymentEvent
    {
        return PaymentEvent::create([
            'provider' => 'stripe',
            'event_id' => 'evt_admin_'.$status,
            'session_id' => 'cs_admin_'.$status,
            'user_id' => $user->id,
            'type' => 'checkout.session.completed',
            'amount_cents' => 2500,
            'currency' => 'myr',
            'status' => $status,
            'payload_json' => [
                'id' => 'evt_admin_'.$status,
                'type' => 'checkout.session.completed',
            ],
            'processed_at' => $status === 'processed' ? now() : null,
        ]);
    }

    private function createAdmin(string $role): Admin
    {
        $admin = new Admin;
        $admin->name = ucfirst($role);
        $admin->email = $role.'-payment-events@example.test';
        $admin->password = 'password';
        $admin->role = $role;
        $admin->save();

        return $admin;
    }

    private function bindPaymentResult(PaymentResult $result): void
    {
        $this->app->instance(PaymentGatewayInterface::class, new class($result) implements PaymentGatewayInterface
        {
            public function __construct(private readonly PaymentResult $result) {}

            public function createCheckout(User $user, array $items, array $metadata): PaymentInitiation
            {
                throw new \RuntimeException('Not used by this test.');
            }

            public function getSessionData(string $sessionId): PaymentResult
            {
                return $this->result;
            }
        });
    }
}
