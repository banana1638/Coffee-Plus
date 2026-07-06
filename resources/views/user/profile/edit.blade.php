<x-app-layout>
    @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition
            class="fixed left-1/2 top-6 z-[100] w-full max-w-sm -translate-x-1/2 px-4">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-sm" role="status">
                {{ session('status') === 'profile-updated' ? 'Profile updated.' : 'Action successful.' }}
            </div>
        </div>
    @endif

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <x-layout.page-header title="Account and security" description="Manage identity details, backend-confirmed Tangki balances, password, and account access." />

            <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
                <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                    <x-ui.card>
                        <div class="text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-lg bg-[rgb(var(--cp-brand-strong))] text-2xl font-bold text-white">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <h2 class="mt-4 text-xl font-semibold text-slate-950">{{ Auth::user()->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ Auth::user()->email }}</p>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-4 border-t border-[rgb(var(--cp-line))] pt-6">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Balance</p>
                                <p class="cp-tabular mt-1 text-lg font-bold text-[rgb(var(--cp-ink))]">RM {{ number_format(Auth::user()->tangki_balance, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Storage</p>
                                <p class="cp-tabular mt-1 text-lg font-bold text-[rgb(var(--cp-brand-strong))]">{{ Auth::user()->tangki_oz }} OZ</p>
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card padding="compact">
                        <nav class="space-y-1">
                            <a href="#profile-info" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Profile Information</a>
                            <a href="#password-info" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Update Password</a>
                            <a href="#delete-account" class="block rounded-lg px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Delete Account</a>
                        </nav>
                    </x-ui.card>
                </aside>

                <div class="space-y-6">
                    <section id="profile-info" class="scroll-mt-24">
                        <x-ui.card>
                            <div class="max-w-xl">
                                @include('user.profile.partials.update-profile-information-form')
                            </div>
                        </x-ui.card>
                    </section>

                    <section id="password-info" class="scroll-mt-24">
                        <x-ui.card>
                            <div class="max-w-xl">
                                @include('user.profile.partials.update-password-form')
                            </div>
                        </x-ui.card>
                    </section>

                    <section id="delete-account" class="scroll-mt-24">
                        <x-ui.card class="border-rose-200">
                            <div class="max-w-xl">
                                @include('user.profile.partials.delete-user-form')
                            </div>
                        </x-ui.card>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
