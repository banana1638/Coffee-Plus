<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminTwoFactorService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly AdminTwoFactorService $twoFactorService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function show(Request $request)
    {
        if (! $this->pendingAdmin($request)) {
            return redirect()->route('admin.login');
        }

        return view('admin.two-factor-challenge');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:32']]);
        $admin = $this->pendingAdmin($request);

        if (! $admin || ! $this->twoFactorService->verify($admin, $validated['code'])) {
            return back()->withErrors(['code' => 'The authentication code is invalid or expired.']);
        }

        $remember = (bool) $request->session()->pull('admin.two_factor_remember', false);
        $request->session()->forget(['admin.two_factor_id', 'admin.two_factor_started_at']);
        Auth::guard('admin')->login($admin, $remember);
        $request->session()->regenerate();
        $this->auditLogService->record('admin.login.two_factor', null, null, [
            'email' => $admin->email,
        ], $admin, $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    private function pendingAdmin(Request $request): ?Admin
    {
        $startedAt = (int) $request->session()->get('admin.two_factor_started_at', 0);

        if (! $startedAt || now()->timestamp - $startedAt > 300) {
            $request->session()->forget([
                'admin.two_factor_id',
                'admin.two_factor_remember',
                'admin.two_factor_started_at',
            ]);

            return null;
        }

        return Admin::find($request->session()->get('admin.two_factor_id'));
    }
}
