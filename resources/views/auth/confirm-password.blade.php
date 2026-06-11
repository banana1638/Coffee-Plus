<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950">{{ __('Confirm Password') }}</h1>
                <p class="mt-2 text-sm text-slate-600">
                    {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                @csrf

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-slate-700">{{ __('Password') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit">{{ __('Confirm') }}</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
