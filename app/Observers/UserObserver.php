<?php

namespace App\Observers;

use App\Models\User;

/**
 * UserObserver
 *
 * Keeps blind index columns in sync whenever encrypted fields change.
 *
 * How blind indexes work:
 *   - We store HMAC-SHA256(lowercase(trim($plaintext)), BLIND_INDEX_KEY)
 *   - Two identical values always produce the same hash → WHERE queries work
 *   - The hash cannot be reversed to the original value (one-way)
 *   - Uses a separate key (BLIND_INDEX_KEY) so a compromised APP_KEY doesn't
 *     also expose the search index.
 *
 * NOTE: The blind index is computed from the *plaintext* value, which the
 * User model exposes via its mutators (setPhoneAttribute / setAddressAttribute)
 * before/alongside the encrypted cast write. The Observer fires on `saving`,
 * where we read the raw ciphertext from getDirty() and recompute.
 *
 * Because Laravel's `encrypted` cast runs BEFORE the observer `saving` event,
 * we store the plaintext transiently in a protected property on the model and
 * read from there in the observer.
 */
class UserObserver
{
    /**
     * HMAC salt — use a dedicated env key for defence-in-depth.
     */
    public static function salt(): string
    {
        // Strip "base64:" prefix if present (same format as APP_KEY)
        $key = env('BLIND_INDEX_KEY', config('app.key', ''));
        return str_replace('base64:', '', $key);
    }

    /**
     * Compute a deterministic blind index for a plaintext value.
     */
    public static function makeIndex(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }
        return hash_hmac('sha256', mb_strtolower(trim($plaintext)), static::salt());
    }

    /**
     * Called before every INSERT or UPDATE.
     * We read the pre-encryption plaintext stored by the model mutators.
     */
    public function saving(User $user): void
    {
        // The User model stores plaintext transiently in $_pendingPlaintext
        // before the cast encrypts the value. We pick it up here.
        $pending = $user->getPendingPlaintext();

        if (array_key_exists('phone', $pending)) {
            $user->phone_index = static::makeIndex($pending['phone']);
        }

        if (array_key_exists('address', $pending)) {
            $user->address_index = static::makeIndex($pending['address']);
        }

        // Clear after use
        $user->clearPendingPlaintext();
    }
}
