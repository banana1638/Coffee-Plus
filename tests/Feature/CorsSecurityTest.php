<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsSecurityTest extends TestCase
{
    public function test_only_configured_origin_receives_cors_header(): void
    {
        config(['cors.allowed_origins' => ['https://app.coffee-plus.example']]);

        $this->call('GET', '/api/not-a-real-route', server: [
            'HTTP_ORIGIN' => 'https://app.coffee-plus.example',
            'HTTP_ACCEPT' => 'application/json',
        ])
            ->assertHeader('Access-Control-Allow-Origin', 'https://app.coffee-plus.example');

        $this->call('GET', '/api/not-a-real-route', server: [
            'HTTP_ORIGIN' => 'https://attacker.example',
            'HTTP_ACCEPT' => 'application/json',
        ])
            ->assertHeader('Access-Control-Allow-Origin', 'https://app.coffee-plus.example');
    }
}
