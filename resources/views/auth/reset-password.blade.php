<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-[rgb(var(--cp-canvas))] px-4 py-10">
        <div class="cp-menu-paper w-full max-w-md rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-6 shadow-[0_24px_65px_-38px_rgba(24,32,29,0.6)] sm:p-8">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-lg bg-emerald-50 text-emerald-800">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.105.895-2 2-2h1V7a3 3 0 10-6 0v2h1c1.105 0 2 .895 2 2zm-7 0h14v10H5V11z" />
                    </svg>
                </div>
                <h1 class="font-display text-4xl font-medium tracking-[-0.035em] text-[rgb(var(--cp-ink))]">Create a new password</h1>
                <p class="mt-2 text-sm text-slate-600">Create a strong password for your account.</p>
            </div>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium text-slate-700">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus readonly
                        class="w-full rounded-lg border-slate-300 bg-slate-100 text-sm font-semibold text-slate-500 shadow-sm">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-slate-700">New Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="space-y-2">
                    <label for="password_confirmation" class="text-sm font-medium text-slate-700">Confirm New Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <x-ui.button type="submit" class="w-full">{{ __('Reset Password') }}</x-ui.button>
                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">Return to login</a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
