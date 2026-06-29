<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->remember)) {
            $admin = Auth::guard('admin')->user();

            if ($admin->hasTwoFactorAuthentication()) {
                Auth::guard('admin')->logout();
                $request->session()->regenerate();
                $request->session()->put([
                    'admin.two_factor_id' => $admin->id,
                    'admin.two_factor_remember' => $request->boolean('remember'),
                    'admin.two_factor_started_at' => now()->timestamp,
                ]);

                return redirect()->route('admin.two-factor.challenge');
            }

            $request->session()->regenerate();
            $this->auditLogService->record('admin.login', null, null, [
                'email' => $credentials['email'],
            ], $admin, $request);

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
