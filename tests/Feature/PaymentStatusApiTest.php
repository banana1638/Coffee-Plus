<?php

namespace Tests\Feature;

use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStatusApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_payment_status(): void
    {
        $user = User::factory()->create();

        PaymentEvent::create([
            'provider' => 'stripe',
            'event_id' => 'evt_status_1',
            'session_id' => 'cs_status_1',
            'user_id' => $user->id,
            'type' => 'checkout.session.completed',
            'amount_cents' => 5000,
            'currency' => 'myr',
            'status' => 'processed',
            'payload_json' => ['id' => 'evt_status_1'],
            'processed_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/payments/cs_status_1/status')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.session_id', 'cs_status_1')
            ->assertJsonPath('data.provider', 'stripe')
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.amount_cents', 5000)
            ->assertJsonPath('data.currency', 'myr');
    }

    public function test_unknown_payment_status_returns_pending(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/payments/cs_not_processed_yet/status')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.session_id', 'cs_not_processed_yet')
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_user_cannot_view_another_users_payment_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        PaymentEvent::create([
            'provider' => 'stripe',
            'event_id' => 'evt_status_other',
            'session_id' => 'cs_status_other',
            'user_id' => $otherUser->id,
            'type' => 'checkout.session.completed',
            'amount_cents' => 5000,
            'currency' => 'myr',
            'status' => 'processed',
            'payload_json' => ['id' => 'evt_status_other'],
            'processed_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/payments/cs_status_other/status')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.session_id', 'cs_status_other')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonMissingPath('data.amount_cents');
    }

    public function test_payment_status_requires_authentication(): void
    {
        $this->getJson('/api/payments/cs_status_1/status')
            ->assertUnauthorized();
    }
}
