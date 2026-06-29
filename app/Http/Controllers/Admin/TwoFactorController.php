<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminTwoFactorService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    private const SETUP_SECRET_KEY = 'admin.two_factor_setup_secret';

    public function __construct(
        private readonly AdminTwoFactorService $twoFactorService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function show(Request $request)
    {
        $admin = $request->user('admin');
        $totp = null;

        if (! $admin->hasTwoFactorAuthentication()) {
            $secret = $request->session()->get(self::SETUP_SECRET_KEY);
            $totp = $this->twoFactorService->createTotp($admin, $secret);
            $request->session()->put(self::SETUP_SECRET_KEY, $totp->getSecret());
        }

        return view('admin.security.two-factor', compact('admin', 'totp'));
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'string', 'size:6']]);
        $admin = $request->user('admin');
        $secret = $request->session()->get(self::SETUP_SECRET_KEY);

        if (! $secret || ! $this->twoFactorService->verifySecret($secret, $validated['code'])) {
            return back()->withErrors(['code' => 'The authentication code is invalid.']);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
        $admin->two_factor_secret = $secret;
        $admin->two_factor_recovery_codes = $this->twoFactorService->hashRecoveryCodes($recoveryCodes);
        $admin->two_factor_confirmed_at = now();
        $admin->save();
        $request->session()->forget(self::SETUP_SECRET_KEY);
        $this->auditLogService->record('admin.two_factor.enabled', $admin, actor: $admin, request: $request);

        return redirect()->route('admin.two-factor.show')
            ->with('success', 'Two-factor authentication enabled.')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password:admin']]);
        $admin = $request->user('admin');

        if (! $admin->hasTwoFactorAuthentication()) {
            return back()->with('error', 'Two-factor authentication is not enabled.');
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
        $admin->two_factor_recovery_codes = $this->twoFactorService->hashRecoveryCodes($recoveryCodes);
        $admin->save();
        $this->auditLogService->record('admin.two_factor.recovery_regenerated', $admin, actor: $admin, request: $request);

        return back()->with('success', 'Recovery codes regenerated.')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password:admin']]);
        $admin = $request->user('admin');
        $admin->two_factor_secret = null;
        $admin->two_factor_recovery_codes = null;
        $admin->two_factor_confirmed_at = null;
        $admin->two_factor_last_used_step = null;
        $admin->save();
        $this->auditLogService->record('admin.two_factor.disabled', $admin, actor: $admin, request: $request);

        return back()->with('success', 'Two-factor authentication disabled.');
    }
}
