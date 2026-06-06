<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\IdempotencyKey;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

class IdempotencyService
{
    public function run(User $user, string $key, string $route, string $requestHash, Closure $callback): array
    {
        return DB::transaction(function () use ($user, $key, $route, $requestHash, $callback) {
            $record = IdempotencyKey::where('key', $key)->lockForUpdate()->first();

            if (!$record) {
                $record = IdempotencyKey::create([
                    'user_id' => $user->id,
                    'key' => $key,
                    'route' => $route,
                    'request_hash' => $requestHash,
                    'status' => IdempotencyKey::STATUS_PROCESSING,
                    'locked_until' => now()->addMinute(),
                ]);
            } else {
                if ((int) $record->user_id !== (int) $user->id || $record->route !== $route) {
                    throw new CheckoutException('Idempotency key is already used for another request.', 409);
                }

                if (!hash_equals($record->request_hash, $requestHash)) {
                    throw new CheckoutException('Idempotency key request payload does not match the original request.', 409);
                }

                if ($record->status === IdempotencyKey::STATUS_COMPLETED && $record->response_json) {
                    return $record->response_json;
                }

                $record->locked_until = now()->addMinute();
                $record->save();
            }

            $response = $callback();

            $record->response_json = $response;
            $record->status = IdempotencyKey::STATUS_COMPLETED;
            $record->locked_until = null;
            $record->save();

            return $response;
        }, 5);
    }

    public static function hashPayload(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
