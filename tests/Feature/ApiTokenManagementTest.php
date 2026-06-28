<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_device_tokens_and_identify_current_token(): void
    {
        $user = User::factory()->create();
        $service = app(ApiTokenService::class);
        $service->issue($user, 'Old Phone');
        $current = $service->issue($user, 'Current Phone');

        $this->withToken($current->plainTextToken)
            ->getJson('/api/tokens')
            ->assertOk()
            ->assertJsonPath('data.tokens.0.device_name', 'Current Phone')
            ->assertJsonPath('data.tokens.0.is_current', true)
            ->assertJsonPath('data.tokens.1.device_name', 'Old Phone')
            ->assertJsonPath('data.tokens.1.is_current', false)
            ->assertJsonMissingPath('data.tokens.0.token');
    }

    public function test_user_can_revoke_owned_device_but_not_another_users_token(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $service = app(ApiTokenService::class);
        $current = $service->issue($user, 'Current Phone');
        $old = $service->issue($user, 'Old Phone');
        $foreign = $service->issue($otherUser, 'Other Phone');

        $this->withToken($current->plainTextToken)
            ->deleteJson('/api/tokens/'.$old->accessToken->id)
            ->assertOk()
            ->assertJsonPath('data.revoked_current', false);

        $this->withToken($current->plainTextToken)
            ->deleteJson('/api/tokens/'.$foreign->accessToken->id)
            ->assertNotFound();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $old->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $foreign->accessToken->id]);
    }

    public function test_user_can_revoke_all_device_tokens(): void
    {
        $user = User::factory()->create();
        $service = app(ApiTokenService::class);
        $service->issue($user, 'Tablet');
        $current = $service->issue($user, 'Phone');

        $this->withToken($current->plainTextToken)
            ->deleteJson('/api/tokens')
            ->assertOk()
            ->assertJsonPath('data.revoked_count', 2);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_issuing_tokens_enforces_per_user_limit(): void
    {
        config(['sanctum.max_tokens_per_user' => 3]);
        $user = User::factory()->create();
        $service = app(ApiTokenService::class);

        $first = $service->issue($user, 'Device 1');
        $service->issue($user, 'Device 2');
        $service->issue($user, 'Device 3');
        $service->issue($user, 'Device 4');

        $this->assertSame(3, $user->tokens()->count());
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $first->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Device 4']);
    }

    public function test_password_change_revokes_other_tokens_and_preserves_current_device_name(): void
    {
        $user = User::factory()->create();
        $service = app(ApiTokenService::class);
        $service->issue($user, 'Old Tablet');
        $current = $service->issue($user, 'Current Phone');

        $response = $this->withToken($current->plainTextToken)
            ->postJson('/api/profile/password', [
                'current_password' => 'password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertOk()
            ->assertJsonStructure(['access_token']);

        $this->assertNotSame($current->plainTextToken, $response->json('access_token'));
        $this->assertSame(1, $user->tokens()->count());
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Current Phone',
        ]);
    }
}
