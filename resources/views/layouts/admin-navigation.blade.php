@php
    $admin = Auth::guard('admin')->user();
@endphp

<div x-data="{ open: false }" @keydown.escape.window="open = false">
    <header class="sticky top-0 z-40 border-b border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))]/95 backdrop-blur lg:hidden">
        <div class="flex h-16 items-center justify-between px-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <x-application-logo class="block h-8 w-auto" />
                <div>
                    <p class="text-sm font-bold text-[rgb(var(--cp-ink))]">Coffee-Plus</p>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-[rgb(var(--cp-muted))]">Operations</p>
                </div>
            </a>

            <button type="button" x-on:click="open = ! open"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-[rgb(var(--cp-line))] text-slate-600 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--cp-brand))]"
                aria-label="Toggle admin navigation" x-bind:aria-expanded="open.toString()">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>
        </div>
    </header>

    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
        x-on:click="open = false"></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-white/10 bg-[rgb(var(--cp-ink))] text-white shadow-xl transition-transform duration-200 lg:translate-x-0 lg:shadow-none"
        x-bind:class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="flex h-20 items-center border-b border-white/10 px-5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white">
                    <x-application-logo class="block h-8 w-auto" />
                </span>
                <div>
                    <p class="text-sm font-bold text-white">Coffee-Plus</p>
                    <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-300">Prep station</p>
                </div>
            </a>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5" aria-label="Admin navigation">
            <section aria-labelledby="admin-operations-label">
                <p id="admin-operations-label" class="px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-500">Operations</p>
                <div class="mt-2 space-y-1">
                    @adminCan('order.view')
                        <x-nav.admin-sidebar-item :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16M4 12h16M4 19h10" />
                                </svg>
                            </x-slot:icon>
                            Dashboard
                        </x-nav.admin-sidebar-item>

                        <x-nav.admin-sidebar-item :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h10a2 2 0 012 2v16l-3-2-4 2-4-2-3 2V5a2 2 0 012-2zM8 8h8M8 12h6" />
                                </svg>
                            </x-slot:icon>
                            Orders
                        </x-nav.admin-sidebar-item>
                    @endadminCan
                </div>
            </section>

            @if($admin->canPerform('product.view') || $admin->canPerform('coupon.view') || $admin->canPerform('report.view') || $admin->canPerform('payment.view'))
                <section aria-labelledby="admin-management-label">
                    <p id="admin-management-label" class="px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-500">Management</p>
                    <div class="mt-2 space-y-1">
                        @adminCan('report.view')
                            <x-nav.admin-sidebar-item :href="route('admin.owner.dashboard')" :active="request()->routeIs('admin.owner.dashboard')">
                                <x-slot:icon>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m6 10V5m6 14v-7m4 7H2" />
                                    </svg>
                                </x-slot:icon>
                                Analytics
                            </x-nav.admin-sidebar-item>
                        @endadminCan

                        @adminCan('product.view')
                            <x-nav.admin-sidebar-item :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                                <x-slot:icon>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14l-1 13H6L5 7zM9 7V5a3 3 0 016 0v2" />
                                    </svg>
                                </x-slot:icon>
                                Products
                            </x-nav.admin-sidebar-item>
                        @endadminCan

                        @adminCan('coupon.view')
                            <x-nav.admin-sidebar-item :href="route('admin.coupons.index')" :active="request()->routeIs('admin.coupons.*')">
                                <x-slot:icon>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7a2 2 0 012-2h12a2 2 0 012 2v2a3 3 0 000 6v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2a3 3 0 000-6V7zM12 8v8" />
                                    </svg>
                                </x-slot:icon>
                                Coupons
                            </x-nav.admin-sidebar-item>
                        @endadminCan

                        @adminCan('payment.view')
                            <x-nav.admin-sidebar-item :href="route('admin.payment-events.index')" :active="request()->routeIs('admin.payment-events.*')">
                                <x-slot:icon>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18v10H3V7zm0 3h18M7 14h3" />
                                    </svg>
                                </x-slot:icon>
                                Payment events
                            </x-nav.admin-sidebar-item>
                        @endadminCan
                    </div>
                </section>
            @endif
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3 px-2">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-sm font-bold text-white">
                    {{ substr($admin->name, 0, 1) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ $admin->name }}</p>
                    <p class="mt-0.5 truncate text-[10px] font-semibold uppercase tracking-[0.12em] text-stone-400">{{ str_replace('_', ' ', $admin->role) }}</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2">
                <a href="{{ route('admin.two-factor.show') }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg border border-white/10 px-3 py-2 text-xs font-semibold text-stone-300 transition hover:border-white/20 hover:bg-white/5 hover:text-white">
                    Security
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center rounded-lg border border-white/10 px-3 py-2 text-xs font-semibold text-stone-300 transition hover:border-rose-400/30 hover:bg-rose-400/10 hover:text-rose-200">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </aside>
</div>
