<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OTPHP\TOTP;

class AdminTwoFactorService
{
    public function createTotp(Admin $admin, ?string $secret = null): TOTP
    {
        $totp = TOTP::create($secret);
        $totp->setLabel($admin->email);
        $totp->setIssuer(config('app.name', 'Coffee-Plus').' Admin');

        return $totp;
    }

    public function verifySecret(string $secret, string $code): bool
    {
        return preg_match('/^\d{6}$/', $code) === 1
            && TOTP::create($secret)->verify($code, null, 29);
    }

    public function verify(Admin $admin, string $code): bool
    {
        if (! $admin->hasTwoFactorAuthentication()) {
            return false;
        }

        $code = trim($code);

        if ($this->verifyTotp($admin, $code)) {
            return true;
        }

        foreach ($admin->two_factor_recovery_codes ?? [] as $index => $hashedCode) {
            if (! Hash::check(Str::upper($code), $hashedCode)) {
                continue;
            }

            $codes = $admin->two_factor_recovery_codes;
            unset($codes[$index]);
            $admin->two_factor_recovery_codes = array_values($codes);
            $admin->save();

            return true;
        }

        return false;
    }

    private function verifyTotp(Admin $admin, string $code): bool
    {
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            return false;
        }

        $totp = TOTP::create($admin->two_factor_secret);
        $timestamp = now()->timestamp;
        $checkedSteps = [];

        foreach ([-29, 0, 29] as $offset) {
            $candidateTimestamp = $timestamp + $offset;
            $step = intdiv($candidateTimestamp, 30);

            if (isset($checkedSteps[$step])) {
                continue;
            }
            $checkedSteps[$step] = true;

            if (! hash_equals($totp->at($candidateTimestamp), $code)) {
                continue;
            }

            if ($admin->two_factor_last_used_step !== null && $step <= $admin->two_factor_last_used_step) {
                return false;
            }

            $admin->two_factor_last_used_step = $step;
            $admin->save();

            return true;
        }

        return false;
    }

    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(fn (string $code) => Hash::make($code), $codes);
    }
}
