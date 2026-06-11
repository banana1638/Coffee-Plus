<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <x-application-logo class="h-full w-full" />
                </div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Admin Portal</h1>
                <p class="mt-2 text-sm text-slate-600">Coffee-Plus management console</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
                    @csrf

                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium text-slate-700">Admin Email</label>
                        <input id="email" type="email" name="email" required autofocus
                            class="w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('email')
                            <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-sm font-medium text-slate-700">Security Token</label>
                        <input id="password" type="password" name="password" required
                            class="w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="remember" id="remember"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-medium text-slate-600">Stay authenticated</span>
                    </label>

                    <x-ui.button type="submit" size="lg" class="w-full">
                        Access Console
                    </x-ui.button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs font-medium text-slate-500">
                Protected environment {{ date('Y') }}
            </p>
        </div>
    </div>
</x-guest-layout>
