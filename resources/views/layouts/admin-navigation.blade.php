@php
    $admin = Auth::guard('admin')->user();
@endphp

<div x-data="{ open: false }">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur lg:hidden">
        <div class="flex h-16 items-center justify-between px-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <x-application-logo class="block h-9 w-auto" />
                <span class="text-sm font-semibold text-slate-950">Coffee-Plus Admin</span>
            </a>

            <button type="button" x-on:click="open = ! open"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100"
                aria-label="Toggle admin navigation" x-bind:aria-expanded="open.toString()">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>
        </div>
    </header>

    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden"
        x-on:click="open = false"></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white shadow-xl transition-transform duration-200 lg:translate-x-0 lg:shadow-none"
        x-bind:class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="flex h-20 items-center gap-3 border-b border-slate-200 px-6">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <x-application-logo class="block h-10 w-auto" />
                <div>
                    <p class="text-sm font-semibold text-slate-950">Coffee-Plus</p>
                    <p class="text-xs text-slate-500">Admin console</p>
                </div>
            </a>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6">
            <div>
                <p class="px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Operations</p>
                <div class="mt-2 space-y-1">
                    @adminCan('order.view')
                        <a href="{{ route('admin.dashboard') }}"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                            <span class="h-2 w-2 rounded-full {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-400' : 'bg-slate-300 group-hover:bg-indigo-500' }}"></span>
                            Dashboard
                        </a>
                        <a href="{{ route('admin.orders.index') }}"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.orders.*') ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                            <span class="h-2 w-2 rounded-full {{ request()->routeIs('admin.orders.*') ? 'bg-emerald-400' : 'bg-slate-300 group-hover:bg-indigo-500' }}"></span>
                            Orders
                        </a>
                    @endadminCan
                </div>
            </div>

            @if($admin->canPerform('product.view') || $admin->canPerform('coupon.view') || $admin->canPerform('report.view'))
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Management</p>
                    <div class="mt-2 space-y-1">
                        @adminCan('report.view')
                            <a href="{{ route('admin.owner.dashboard') }}"
                                class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.owner.dashboard') ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                                <span class="h-2 w-2 rounded-full {{ request()->routeIs('admin.owner.dashboard') ? 'bg-emerald-400' : 'bg-slate-300 group-hover:bg-indigo-500' }}"></span>
                                Analytics
                            </a>
                        @endadminCan

                        @adminCan('product.view')
                            <a href="{{ route('admin.products.index') }}"
                                class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.products.*') ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                                <span class="h-2 w-2 rounded-full {{ request()->routeIs('admin.products.*') ? 'bg-emerald-400' : 'bg-slate-300 group-hover:bg-indigo-500' }}"></span>
                                Products
                            </a>
                        @endadminCan

                        @adminCan('coupon.view')
                            <a href="{{ route('admin.coupons.index') }}"
                                class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.coupons.*') ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                                <span class="h-2 w-2 rounded-full {{ request()->routeIs('admin.coupons.*') ? 'bg-emerald-400' : 'bg-slate-300 group-hover:bg-indigo-500' }}"></span>
                                Coupons
                            </a>
                        @endadminCan
                    </div>
                </div>
            @endif
        </nav>

        <div class="border-t border-slate-200 p-4">
            <div class="rounded-xl bg-slate-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-950">{{ $admin->name }}</p>
                        <p class="mt-0.5 text-xs capitalize text-slate-500">{{ str_replace('_', ' ', $admin->role) }}</p>
                    </div>
                    <x-ui.badge variant="info">Role</x-ui.badge>
                </div>

                <form method="POST" action="{{ route('admin.logout') }}" class="mt-4">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" size="sm" class="w-full">
                        Log Out
                    </x-ui.button>
                </form>
            </div>
        </div>
    </aside>
</div>
