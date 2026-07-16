<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-[rgb(var(--cp-canvas))] px-4 py-10">
        <div class="cp-menu-paper w-full max-w-md rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-6 shadow-[0_24px_65px_-38px_rgba(24,32,29,0.6)] sm:p-8">
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-brand))]">Secure account area</p>
                <h1 class="mt-2 font-display text-4xl font-medium tracking-[-0.035em] text-[rgb(var(--cp-ink))]">{{ __('Confirm Password') }}</h1>
                <p class="mt-2 text-sm text-slate-600">
                    {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                @csrf

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-slate-700">{{ __('Password') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit">{{ __('Confirm') }}</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
