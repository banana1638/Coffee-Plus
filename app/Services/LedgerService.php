<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletLedger;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    public function credit(
        User $user,
        int $amountCents,
        string $sourceType,
        ?string $sourceId,
        string $idempotencyKey,
        string $description,
        ?int $createdBy = null
    ): WalletLedger {
        $this->assertPositiveAmount($amountCents);

        return DB::transaction(function () use ($user, $amountCents, $sourceType, $sourceId, $idempotencyKey, $description, $createdBy) {
            $existing = WalletLedger::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
            $beforeCents = $this->balanceCents($lockedUser);
            $afterCents = $beforeCents + $amountCents;

            $lockedUser->forceFill([
                'tangki_balance' => Money::fromCents($afterCents),
                'tangki_balance_cents' => $afterCents,
            ])->save();

            return WalletLedger::create([
                'user_id' => $lockedUser->id,
                'direction' => 'credit',
                'amount_cents' => $amountCents,
                'balance_before_cents' => $beforeCents,
                'balance_after_cents' => $afterCents,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function debit(
        User $user,
        int $amountCents,
        string $sourceType,
        ?string $sourceId,
        string $idempotencyKey,
        string $description,
        ?int $createdBy = null
    ): ?WalletLedger {
        $this->assertPositiveAmount($amountCents);

        return DB::transaction(function () use ($user, $amountCents, $sourceType, $sourceId, $idempotencyKey, $description, $createdBy) {
            $existing = WalletLedger::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
            $beforeCents = $this->balanceCents($lockedUser);

            if ($beforeCents < $amountCents) {
                return null;
            }

            $afterCents = $beforeCents - $amountCents;
            $lockedUser->forceFill([
                'tangki_balance' => Money::fromCents($afterCents),
                'tangki_balance_cents' => $afterCents,
            ])->save();

            return WalletLedger::create([
                'user_id' => $lockedUser->id,
                'direction' => 'debit',
                'amount_cents' => $amountCents,
                'balance_before_cents' => $beforeCents,
                'balance_after_cents' => $afterCents,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function balanceCents(User $user): int
    {
        $legacyBalanceCents = Money::toCents($user->tangki_balance);
        $storedBalanceCents = $user->tangki_balance_cents;

        if ($storedBalanceCents === null) {
            return $legacyBalanceCents;
        }

        return max((int) $storedBalanceCents, $legacyBalanceCents);
    }

    private function assertPositiveAmount(int $amountCents): void
    {
        if ($amountCents <= 0) {
            throw new \InvalidArgumentException('Wallet ledger amount must be greater than zero.');
        }
    }
}
