<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[900px] space-y-6">
            <x-layout.page-header title="Two-factor authentication" description="Protect this administrator account with a time-based authenticator or one-time recovery code." />

            @if(session('recovery_codes'))
                <x-ui.card>
                    <x-slot:header>
                        <h2 class="text-base font-semibold text-slate-950">Save these recovery codes now</h2>
                    </x-slot:header>
                    <p class="text-sm text-slate-600">Each code works once. They will not be shown again.</p>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach(session('recovery_codes') as $code)
                            <code class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-950">{{ $code }}</code>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif

            @if($admin->hasTwoFactorAuthentication())
                <x-ui.card>
                    <x-slot:header>
                        <h2 class="text-base font-semibold text-slate-950">Two-factor authentication is enabled</h2>
                    </x-slot:header>
                    <p class="text-sm text-slate-600">Confirmed {{ $admin->two_factor_confirmed_at->format('Y-m-d H:i') }}.</p>

                    <div class="mt-6 grid gap-6 md:grid-cols-2">
                        <form method="POST" action="{{ route('admin.two-factor.recovery-codes') }}" class="space-y-4">
                            @csrf
                            <label for="recovery-password" class="text-sm font-medium text-slate-700">Current password</label>
                            <input id="recovery-password" name="password" type="password" required
                                class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <x-ui.button type="submit" variant="secondary">Regenerate recovery codes</x-ui.button>
                        </form>

                        <form method="POST" action="{{ route('admin.two-factor.disable') }}" class="space-y-4">
                            @csrf
                            @method('DELETE')
                            <label for="disable-password" class="text-sm font-medium text-slate-700">Current password</label>
                            <input id="disable-password" name="password" type="password" required
                                class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <x-ui.button type="submit" variant="danger">Disable two-factor authentication</x-ui.button>
                        </form>
                    </div>
                </x-ui.card>
            @else
                <x-ui.card>
                    <x-slot:header>
                        <h2 class="text-base font-semibold text-slate-950">Connect an authenticator app</h2>
                    </x-slot:header>
                    <ol class="space-y-3 text-sm text-slate-600">
                        <li>1. Add a new account in your authenticator app.</li>
                        <li>2. Enter the setup key below or use the provisioning URI.</li>
                        <li>3. Confirm setup with the generated six-digit code.</li>
                    </ol>

                    <div class="mt-5 space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Setup key</p>
                            <code class="mt-2 block break-all rounded-lg bg-slate-100 p-3 text-sm font-semibold text-slate-950">{{ $totp->getSecret() }}</code>
                        </div>
                        <details>
                            <summary class="cursor-pointer text-sm font-semibold text-slate-700">Show provisioning URI</summary>
                            <code class="mt-2 block break-all rounded-lg bg-slate-100 p-3 text-xs text-slate-700">{{ $totp->getProvisioningUri() }}</code>
                        </details>
                    </div>

                    <form method="POST" action="{{ route('admin.two-factor.confirm') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <div class="flex-1 space-y-2">
                            <label for="code" class="text-sm font-medium text-slate-700">Six-digit code</label>
                            <input id="code" name="code" type="text" required inputmode="numeric" autocomplete="one-time-code"
                                class="cp-tabular w-full rounded-lg border-[rgb(var(--cp-line))] bg-white font-mono text-lg shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            @error('code')
                                <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-ui.button type="submit">Enable two-factor authentication</x-ui.button>
                    </form>
                </x-ui.card>
            @endif
        </div>
    </div>
</x-admin-layout>
