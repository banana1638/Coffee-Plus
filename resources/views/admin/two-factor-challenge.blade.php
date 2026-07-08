<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-[rgb(var(--cp-canvas))] px-4 py-10">
        <div class="w-full max-w-md">
            <div class="rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-brand))]">Second security check</p>
                <h1 class="mt-1 text-2xl font-bold text-[rgb(var(--cp-ink))]">Verify your identity</h1>
                <p class="mt-2 text-sm text-[rgb(var(--cp-muted))]">Enter a six-digit authenticator code or one unused recovery code.</p>

                <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="mt-6 space-y-5">
                    @csrf
                    <div class="space-y-2">
                        <label for="code" class="text-sm font-medium text-slate-700">Authentication code</label>
                        <input id="code" name="code" type="text" required autofocus autocomplete="one-time-code"
                            class="cp-tabular w-full rounded-lg border-[rgb(var(--cp-line))] bg-white font-mono text-lg uppercase shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('code')
                            <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.button type="submit" size="lg" class="w-full">Verify and continue</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
