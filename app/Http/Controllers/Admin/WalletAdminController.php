<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\LedgerService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WalletAdminController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:5000'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $admin = $request->user('admin');
        $user = User::findOrFail($validated['user_id']);
        $amountCents = Money::toCents($validated['amount']);
        $direction = $validated['direction'];
        $reason = trim($validated['reason']);
        $idempotencyKey = 'admin_adjustment:' . Str::uuid();

        DB::transaction(function () use ($user, $admin, $amountCents, $direction, $reason, $idempotencyKey) {
            $beforeCents = $this->ledgerService->balanceCents($user);
            $sourceId = 'admin:' . $admin->id;
            $description = "Admin wallet {$direction}: {$reason}";

            $ledger = $direction === 'credit'
                ? $this->ledgerService->credit($user, $amountCents, 'admin_adjustment', $sourceId, $idempotencyKey, $description)
                : $this->ledgerService->debit($user, $amountCents, 'admin_adjustment', $sourceId, $idempotencyKey, $description);

            if (!$ledger) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient wallet balance for this adjustment.',
                ]);
            }

            $user->refresh();

            $this->auditLogService->record(
                'wallet.adjust',
                $user,
                [
                    'balance_cents' => $beforeCents,
                ],
                [
                    'balance_cents' => $this->ledgerService->balanceCents($user),
                    'direction' => $direction,
                    'amount_cents' => $amountCents,
                    'reason' => $reason,
                    'ledger_id' => $ledger->id,
                ],
                $admin
            );
        });

        return back()->with('success', 'Wallet adjustment recorded.');
    }
}
