<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EncryptExistingUsers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:encrypt-existing-users
                            {--dry-run : Preview what would be migrated without saving}
                            {--chunk=100 : Number of users to process per batch}';

    /**
     * The console command description.
     */
    protected $description = 'Encrypt phone and address fields for existing users and rebuild blind indexes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun   = $this->option('dry-run');
        $chunk    = (int) $this->option('chunk');
        $failed   = [];
        $migrated = 0;
        $skipped  = 0;

        $this->info($dryRun ? '🔍 Dry run — no changes will be saved.' : '🔐 Encrypting existing user data...');
        $this->newLine();

        User::query()->chunk($chunk, function ($users) use ($dryRun, &$failed, &$migrated, &$skipped) {
            foreach ($users as $user) {
                try {
                    $needsMigration = false;

                    // Detect if phone is plaintext (not yet an encrypted ciphertext payload)
                    // A Laravel encrypted cast value always starts with 'eyJ' (base64 of '{"iv":...')
                    $rawPhone   = $user->getRawOriginal('phone');
                    $rawAddress = $user->getRawOriginal('address');

                    $phoneNeedsEncryption   = $rawPhone   && !str_starts_with($rawPhone,   'eyJ');
                    $addressNeedsEncryption = $rawAddress && !str_starts_with($rawAddress, 'eyJ');

                    if ($phoneNeedsEncryption || $addressNeedsEncryption || !$user->phone_index) {
                        $needsMigration = true;
                    }

                    if (!$needsMigration) {
                        $skipped++;
                        continue;
                    }

                    $this->line("  User #{$user->id} ({$user->email})");

                    if (!$dryRun) {
                        // Re-assigning triggers mutator → marks dirty → observer rebuilds index
                        if ($phoneNeedsEncryption) {
                            $user->phone = $rawPhone;  // mutator stores plaintext, cast will encrypt
                        } elseif ($rawPhone) {
                            // Already encrypted — just rebuild the blind index from decrypted value
                            $user->phone_index = UserObserver::makeIndex($user->phone);
                        }

                        if ($addressNeedsEncryption) {
                            $user->address = $rawAddress;
                        } elseif ($rawAddress) {
                            $user->address_index = UserObserver::makeIndex($user->address);
                        }

                        // Suppress observer double-run for already-encrypted values
                        $user->saveQuietly();
                    }

                    $migrated++;
                } catch (\Throwable $e) {
                    $failed[] = $user->id;
                    Log::error("EncryptExistingUsers: failed for user #{$user->id}", [
                        'error' => $e->getMessage(),
                    ]);
                    $this->warn("  ⚠ Failed for user #{$user->id}: {$e->getMessage()}");
                }
            }
        });

        $this->newLine();
        $this->info("✅ Migrated : {$migrated}");
        $this->info("⏭  Skipped  : {$skipped} (already encrypted)");

        if (!empty($failed)) {
            $this->error('❌ Failed user IDs: ' . implode(', ', $failed));
            $this->error('   Check storage/logs/laravel.log for details.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
