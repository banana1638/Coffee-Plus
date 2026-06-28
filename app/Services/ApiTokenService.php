<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class ApiTokenService
{
    public function issue(User $user, ?string $deviceName = null): NewAccessToken
    {
        $name = trim((string) $deviceName) ?: 'Coffee-Plus-App';
        $maxTokens = max(1, (int) config('sanctum.max_tokens_per_user', 10));
        $tokensToRemove = max(0, $user->tokens()->count() - ($maxTokens - 1));

        if ($tokensToRemove > 0) {
            $tokenIds = $user->tokens()->orderBy('id')->limit($tokensToRemove)->pluck('id');
            $user->tokens()->whereIn('id', $tokenIds)->delete();
        }

        $expirationMinutes = config('sanctum.expiration');
        $expiresAt = $expirationMinutes ? now()->addMinutes((int) $expirationMinutes) : null;

        return $user->createToken($name, ['*'], $expiresAt);
    }
}
