<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONSTRAINT = 'wallet_ledger_integrity_check';

    public function up(): void
    {
        $invalidRowsExist = DB::table('wallet_ledger')
            ->where(function ($query) {
                $query->where('amount_cents', '<=', 0)
                    ->orWhere('balance_before_cents', '<', 0)
                    ->orWhere('balance_after_cents', '<', 0)
                    ->orWhereNotIn('direction', ['credit', 'debit'])
                    ->orWhereRaw("(direction = 'credit' AND balance_after_cents <> balance_before_cents + amount_cents)")
                    ->orWhereRaw("(direction = 'debit' AND balance_after_cents <> balance_before_cents - amount_cents)");
            })
            ->exists();

        if ($invalidRowsExist) {
            throw new RuntimeException('Invalid wallet ledger rows must be repaired before adding integrity constraints.');
        }

        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            return;
        }

        DB::statement(sprintf(
            "ALTER TABLE wallet_ledger ADD CONSTRAINT %s CHECK (amount_cents > 0 AND balance_before_cents >= 0 AND balance_after_cents >= 0 AND ((direction = 'credit' AND balance_after_cents = balance_before_cents + amount_cents) OR (direction = 'debit' AND balance_after_cents = balance_before_cents - amount_cents)))",
            self::CONSTRAINT,
        ));
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE wallet_ledger DROP CONSTRAINT '.self::CONSTRAINT);
        }

        if ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE wallet_ledger DROP CHECK '.self::CONSTRAINT);
            } catch (Throwable) {
                DB::statement('ALTER TABLE wallet_ledger DROP CONSTRAINT '.self::CONSTRAINT);
            }
        }
    }
};
