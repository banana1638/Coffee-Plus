<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    //

    protected function apiCheckout(User $user, array $payload = [], ?string $idempotencyKey = null): TestResponse
    {
        return $this->actingAs($user)
            ->withHeader('Idempotency-Key', $idempotencyKey ?? (string) Str::uuid())
            ->postJson('/api/checkout', $payload);
    }
}
