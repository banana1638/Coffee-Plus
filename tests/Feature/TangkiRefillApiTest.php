<?php

namespace Tests\Feature;

use App\Contracts\PaymentGatewayInterface;
use App\DataTransferObjects\PaymentResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TangkiRefillApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tangki_refill_returns_stripe_redirect_contract(): void
    {
        $this->app->instance(PaymentGatewayInterface::class, new class implements PaymentGatewayInterface {
            public array $metadata = [];

            public function createCheckoutUrl($user, array $items, array $metadata = []): string
            {
                $this->metadata = $metadata;

                return 'https://checkout.stripe.test/session';
            }

            public function getSessionData(string $sessionId): PaymentResult
            {
                return new PaymentResult('failed', 0, []);
            }
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/tangki/refill', ['amount' => 50])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', 'https://checkout.stripe.test/session')
            ->assertJsonPath('message', 'Redirect to Stripe checkout.');

        $user->refresh();
        $this->assertEquals('0.00', $user->tangki_balance);
    }
}
