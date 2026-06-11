<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Reset Password</h1>
                <p class="mt-2 text-sm text-slate-600">{{ __('Forgot your password? No problem. Just let us know your email address.') }}</p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium text-slate-700">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="name@example.com">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <x-ui.button type="submit" class="w-full">{{ __('Email Reset Link') }}</x-ui.button>
                <x-ui.button :href="route('login')" variant="secondary" class="w-full">Back to Login</x-ui.button>
            </form>
        </div>
    </div>
</x-guest-layout>
