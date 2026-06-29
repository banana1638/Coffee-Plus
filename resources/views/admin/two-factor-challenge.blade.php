<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-semibold text-slate-950">Two-factor verification</h1>
                <p class="mt-2 text-sm text-slate-600">Enter the six-digit authenticator code or one unused recovery code.</p>

                <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="mt-6 space-y-5">
                    @csrf
                    <div class="space-y-2">
                        <label for="code" class="text-sm font-medium text-slate-700">Authentication code</label>
                        <input id="code" name="code" type="text" required autofocus autocomplete="one-time-code"
                            class="w-full rounded-lg border-slate-300 font-mono text-sm uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
