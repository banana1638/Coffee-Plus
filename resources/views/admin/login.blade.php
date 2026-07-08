<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-[rgb(var(--cp-canvas))] px-4 py-10">
        <div class="w-[calc(100vw-2rem)] max-w-md sm:w-full">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-3 shadow-sm">
                    <x-application-logo class="h-full w-full" />
                </div>
                <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-brand))]">Protected staff access</p>
                <h1 class="mt-1 text-2xl font-bold text-[rgb(var(--cp-ink))]">Coffee Plus Operations</h1>
                <p class="mt-2 text-sm text-[rgb(var(--cp-muted))]">Sign in with an authorized administrator account.</p>
            </div>

            <div class="rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-6 shadow-sm">
                <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
                    @csrf

                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium text-slate-700">Admin Email</label>
                        <input id="email" type="email" name="email" required autofocus
                            class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('email')
                            <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                        <input id="password" type="password" name="password" required
                            class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="remember" id="remember"
                            class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                        <span class="text-sm font-medium text-slate-600">Keep this staff session signed in</span>
                    </label>

                    <x-ui.button type="submit" size="lg" class="w-full">
                        Sign in securely
                    </x-ui.button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs font-medium text-slate-500">
                Authorized staff only &middot; {{ date('Y') }}
            </p>
        </div>
    </div>
</x-guest-layout>
