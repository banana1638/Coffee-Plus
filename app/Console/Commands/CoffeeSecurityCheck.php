<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CoffeeSecurityCheck extends Command
{
    protected $signature = 'coffee:security-check {--production : Enforce production security requirements}';

    protected $description = 'Check Coffee-Plus backend security-sensitive production configuration.';

    public function handle(): int
    {
        $enforceProduction = $this->option('production') || app()->environment('production');
        $failures = [];
        $warnings = [];

        if ($enforceProduction && config('app.debug')) {
            $failures[] = 'APP_DEBUG must be false in production.';
        }

        if (!config('app.key')) {
            $failures[] = 'APP_KEY is missing.';
        }

        foreach (['key', 'secret', 'webhook'] as $stripeKey) {
            if (!config("services.stripe.{$stripeKey}")) {
                $failures[] = "Stripe {$stripeKey} is missing.";
            }
        }

        if (config('broadcasting.default') === 'reverb') {
            foreach (['key', 'secret', 'app_id'] as $reverbKey) {
                if (!config("broadcasting.connections.reverb.{$reverbKey}")) {
                    $failures[] = "Reverb {$reverbKey} is missing while BROADCAST_CONNECTION=reverb.";
                }
            }
        }

        if ($enforceProduction && config('telescope.enabled', false)) {
            $telescopeMiddleware = config('telescope.middleware', []);

            if (config('telescope.path') !== 'admin/telescope'
                || ! in_array('auth:admin', $telescopeMiddleware, true)
                || ! in_array('admin.permission:telescope.view', $telescopeMiddleware, true)) {
                $failures[] = 'Enabled Telescope must use /admin/telescope with admin authentication and permission middleware.';
            }

            if ((int) config('telescope.prune_hours', 0) < 24) {
                $failures[] = 'Enabled Telescope must retain at least 24 hours and have scheduled pruning configured.';
            }
        }

        if (!config('filesystems.disks.public.url')) {
            $warnings[] = 'Public filesystem URL is missing.';
        }

        if (!File::exists(public_path('storage'))) {
            $warnings[] = 'Public storage link was not found at public/storage.';
        }

        $corsOrigins = config('cors.allowed_origins', []);
        $corsOriginPatterns = config('cors.allowed_origins_patterns', []);

        if ($enforceProduction && $corsOrigins === [] && $corsOriginPatterns === []) {
            $failures[] = 'CORS must define at least one allowed origin in production.';
        }

        if ($enforceProduction && in_array('*', $corsOrigins, true)) {
            $failures[] = 'CORS wildcard origins are not allowed in production.';
        }

        foreach ($failures as $failure) {
            $this->error($failure);
        }

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        if ($failures !== []) {
            $this->error('Coffee-Plus backend security check failed.');

            return self::FAILURE;
        }

        $this->info('Coffee-Plus backend security check passed.');

        return self::SUCCESS;
    }
}
