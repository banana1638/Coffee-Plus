<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_check_fails_production_when_debug_is_enabled(): void
    {
        config([
            'app.debug' => true,
            'app.key' => 'base64:test',
            'services.stripe.key' => 'pk_test',
            'services.stripe.secret' => 'sk_test',
            'services.stripe.webhook' => 'whsec_test',
            'telescope.enabled' => false,
        ]);

        $this->artisan('coffee:security-check --production')
            ->expectsOutputToContain('APP_DEBUG must be false in production.')
            ->assertExitCode(1);
    }

    public function test_security_check_passes_when_required_production_config_is_present(): void
    {
        config([
            'app.debug' => false,
            'app.key' => 'base64:test',
            'services.stripe.key' => 'pk_test',
            'services.stripe.secret' => 'sk_test',
            'services.stripe.webhook' => 'whsec_test',
            'broadcasting.default' => 'null',
            'telescope.enabled' => false,
            'cors.allowed_origins' => ['https://coffee-plus.example'],
            'cors.allowed_origins_patterns' => [],
        ]);

        $this->artisan('coffee:security-check --production')
            ->expectsOutputToContain('Coffee-Plus backend security check passed.')
            ->assertExitCode(0);
    }

    public function test_security_check_rejects_wildcard_cors_in_production(): void
    {
        config([
            'app.debug' => false,
            'app.key' => 'base64:test',
            'services.stripe.key' => 'pk_test',
            'services.stripe.secret' => 'sk_test',
            'services.stripe.webhook' => 'whsec_test',
            'broadcasting.default' => 'null',
            'telescope.enabled' => false,
            'cors.allowed_origins' => ['*'],
            'cors.allowed_origins_patterns' => [],
        ]);

        $this->artisan('coffee:security-check --production')
            ->expectsOutputToContain('CORS wildcard origins are not allowed in production.')
            ->assertExitCode(1);
    }
}
