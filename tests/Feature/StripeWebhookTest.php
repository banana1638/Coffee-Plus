<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return "t={$timestamp},v1={$signature}";
    }

    private function checkoutCompletedPayload(User $user, string $eventId, string $sessionId): string
    {
        return json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'object' => 'checkout.session',
                    'amount_total' => 1000,
                    'currency' => 'myr',
                    'mode' => 'payment',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'type' => 'refill',
                        'user_id' => (string) $user->id,
                        'amount' => '10.00',
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES);
    }

    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        config(['services.stripe.webhook' => 'whsec_test']);

        $payload = json_encode(['id' => 'evt_bad', 'type' => 'checkout.session.completed']);

        $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=1,v1=bad',
        ], $payload)->assertStatus(400);
    }

    public function test_stripe_webhook_processes_checkout_session_once(): void
    {
        config(['services.stripe.webhook' => 'whsec_test']);

        $user = User::factory()->create();
        $payload = $this->checkoutCompletedPayload($user, 'evt_1', 'cs_test_once');
        $signature = $this->stripeSignature($payload, 'whsec_test');

        $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload)->assertStatus(200);

        $duplicatePayload = $this->checkoutCompletedPayload($user, 'evt_2', 'cs_test_once');
        $duplicateSignature = $this->stripeSignature($duplicatePayload, 'whsec_test');

        $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $duplicateSignature,
        ], $duplicatePayload)->assertStatus(200)
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, Order::where('bill_id', 'like', 'TOPUP-%')->count());
        $this->assertSame(1, Transaction::where('type', 'refill')->count());
        $this->assertSame(1, PaymentEvent::where('status', 'processed')->count());
    }

    public function test_stripe_webhook_transitions_pending_payment_to_processed(): void
    {
        config(['services.stripe.webhook' => 'whsec_test']);

        $user = User::factory()->create();
        PaymentEvent::recordPending($user, 'cs_pending_once', 'refill', 1000, [
            'type' => 'refill',
            'user_id' => $user->id,
            'amount' => '10.00',
        ]);

        $payload = $this->checkoutCompletedPayload($user, 'evt_pending_once', 'cs_pending_once');
        $signature = $this->stripeSignature($payload, 'whsec_test');

        $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload)->assertOk();

        $this->assertDatabaseHas('payment_events', [
            'event_id' => 'evt_pending_once',
            'session_id' => 'cs_pending_once',
            'user_id' => $user->id,
            'type' => 'refill',
            'amount_cents' => 1000,
            'status' => 'processed',
        ]);
        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_stripe_webhook_rejects_payment_that_does_not_match_pending_amount(): void
    {
        config(['services.stripe.webhook' => 'whsec_test']);

        $user = User::factory()->create();
        PaymentEvent::recordPending($user, 'cs_amount_mismatch', 'refill', 2000, [
            'type' => 'refill',
            'user_id' => $user->id,
            'amount' => '20.00',
        ]);

        $payload = $this->checkoutCompletedPayload($user, 'evt_amount_mismatch', 'cs_amount_mismatch');
        $signature = $this->stripeSignature($payload, 'whsec_test');

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->call('POST', '/api/stripe/webhook', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ], $payload)->assertStatus(500);
        }

        $this->assertDatabaseHas('payment_events', [
            'session_id' => 'cs_amount_mismatch',
            'amount_cents' => 2000,
            'status' => 'failed',
        ]);
        $this->assertSame(0, Transaction::where('type', 'refill')->count());
    }

    public function test_stripe_webhook_ignores_unpaid_session(): void
    {
        config(['services.stripe.webhook' => 'whsec_test']);

        $user = User::factory()->create();
        $payload = $this->checkoutCompletedPayload($user, 'evt_unpaid', 'cs_unpaid');
        $payload = str_replace('"payment_status":"paid"', '"payment_status":"unpaid"', $payload);
        $signature = $this->stripeSignature($payload, 'whsec_test');

        $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload)->assertOk()
            ->assertJsonPath('status', 'ignored');

        $this->assertSame(0, Transaction::where('type', 'refill')->count());
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_stripe_webhook_ignores_wrong_currency(): void
    {
        config(['services.stripe.webhook' => 'whsec_test']);

        $user = User::factory()->create();
        $payload = $this->checkoutCompletedPayload($user, 'evt_usd', 'cs_usd');
        $payload = str_replace('"currency":"myr"', '"currency":"usd"', $payload);
        $signature = $this->stripeSignature($payload, 'whsec_test');

        $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload)->assertOk()
            ->assertJsonPath('status', 'ignored');

        $this->assertSame(0, Transaction::where('type', 'refill')->count());
    }

    public function test_stripe_webhook_ignores_missing_user_safely(): void
    {
        config(['services.stripe.webhook' => 'whsec_test']);

        $user = User::factory()->make(['id' => 99999]);
        $payload = $this->checkoutCompletedPayload($user, 'evt_missing_user', 'cs_missing_user');
        $signature = $this->stripeSignature($payload, 'whsec_test');

        $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload)->assertOk();

        $this->assertSame(0, Transaction::where('type', 'refill')->count());
        $this->assertDatabaseHas('payment_events', [
            'event_id' => 'evt_missing_user',
            'session_id' => 'cs_missing_user',
            'status' => 'ignored',
        ]);
    }

    public function test_stripe_success_redirect_does_not_process_payment_business_logic(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('stripe.success', ['session_id' => 'cs_fake']))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('transactions', 0);
    }
}
