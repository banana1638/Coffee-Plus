<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-[rgb(var(--cp-canvas))] px-4 py-10">
        <div class="cp-menu-paper w-full max-w-lg rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-6 shadow-[0_24px_65px_-38px_rgba(24,32,29,0.6)] sm:p-8">
            <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-brand))]">Account verification</p>
            <h1 class="mt-2 font-display text-4xl font-medium tracking-[-0.035em] text-[rgb(var(--cp-ink))]">{{ __('Verify Email') }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ __('Thanks for signing up! Please verify your email address by clicking on the link we just emailed to you.') }}
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700" role="status">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </div>
            @endif

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <x-ui.button type="submit">{{ __('Resend Verification Email') }}</x-ui.button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">{{ __('Log Out') }}</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
