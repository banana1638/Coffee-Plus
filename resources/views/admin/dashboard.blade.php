<x-admin-layout>
    @php
        $admin = Auth::guard('admin')->user();
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <x-layout.page-header eyebrow="Live operations" title="Preparation board" description="Oldest active orders first, with role-aware controls for {{ $admin->name }}.">
                <x-slot:actions>
                    <x-ui.badge variant="success">
                        <span class="mr-1 h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                        Queue connected
                    </x-ui.badge>
                </x-slot:actions>
            </x-layout.page-header>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Preparation summary">
                <x-ui.card padding="compact" class="border-l-4 border-l-[rgb(var(--cp-brand))] shadow-none">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[rgb(var(--cp-muted))]">Live Orders</p>
                    <div class="mt-3 flex items-end justify-between gap-4">
                        <p class="cp-tabular text-3xl font-bold text-[rgb(var(--cp-ink))]">{{ $pendingOrders->total() }}</p>
                        <span class="text-xs font-semibold text-[rgb(var(--cp-brand))]">Live orders</span>
                    </div>
                </x-ui.card>

                <x-ui.card padding="compact" class="shadow-none">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[rgb(var(--cp-muted))]">Access scope</p>
                    <p class="mt-3 truncate text-lg font-bold capitalize text-[rgb(var(--cp-ink))]">{{ str_replace('_', ' ', $admin->role) }}</p>
                    <p class="mt-1 text-xs text-[rgb(var(--cp-muted))]">Role-based controls</p>
                </x-ui.card>

                @adminCan('report.view')
                    <x-ui.card padding="compact" class="shadow-none">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[rgb(var(--cp-muted))]">Tangki snapshot</p>
                        <x-ui.price :amount="\App\Models\User::sum('tangki_balance')" size="lg" accent class="mt-3" />
                        <p class="mt-1 text-xs text-[rgb(var(--cp-muted))]">Backend-recorded balances</p>
                    </x-ui.card>

                    <x-ui.card padding="compact" class="shadow-none">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[rgb(var(--cp-muted))]">Customers</p>
                        <p class="cp-tabular mt-3 text-2xl font-bold text-[rgb(var(--cp-ink))]">{{ number_format(\App\Models\User::count()) }}</p>
                        <p class="mt-1 text-xs text-[rgb(var(--cp-muted))]">Registered accounts</p>
                    </x-ui.card>
                @endadminCan
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
                <section class="min-w-0 space-y-4" aria-labelledby="preparation-queue-title">
                    <div class="flex flex-col gap-3 border-b border-[rgb(var(--cp-line))] pb-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[rgb(var(--cp-brand))]">Barista queue</p>
                            <h2 id="preparation-queue-title" class="mt-1 text-xl font-bold text-[rgb(var(--cp-ink))]">Active preparation queue</h2>
                            <p class="mt-1 text-sm text-[rgb(var(--cp-muted))]">Oldest orders remain first. Open the ticket when recipe or payment context is needed.</p>
                        </div>
                        <x-ui.badge variant="info">Queue: {{ $pendingOrders->total() }}</x-ui.badge>
                    </div>

                    <div class="space-y-3">
                        @forelse($pendingOrders as $order)
                            <article class="rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-4 shadow-sm transition hover:border-emerald-200 sm:p-5">
                                <div class="grid gap-5 md:grid-cols-[72px_minmax(0,1fr)_auto] md:items-center">
                                    <div class="flex h-16 w-16 flex-col items-center justify-center rounded-lg bg-[rgb(var(--cp-ink))] text-white">
                                        <span class="text-[9px] font-semibold uppercase tracking-[0.14em] text-stone-400">Ticket</span>
                                        <span class="cp-tabular mt-1 text-sm font-bold">#{{ substr($order->bill_id, -4) }}</span>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <h3 class="font-bold text-[rgb(var(--cp-ink))]">{{ $order->user->name }}</h3>
                                            <x-ui.badge variant="warning">{{ str_replace('_', ' ', $order->status) }}</x-ui.badge>
                                            <span class="text-xs font-medium text-[rgb(var(--cp-muted))]">{{ $order->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="cp-tabular mt-1 break-all text-xs text-slate-400">{{ $order->bill_id }}</p>

                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach($order->items as $item)
                                                <span class="inline-flex items-center rounded-md border border-[rgb(var(--cp-line))] bg-stone-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                    {{ $item->quantity }}x {{ $item->product->name }} &middot; {{ strtoupper($item->size) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2 md:flex-col md:items-stretch">
                                        <x-ui.button :href="route('admin.orders.show', $order)" variant="secondary" size="sm">
                                            Open ticket
                                        </x-ui.button>

                                        @adminCan('order.status.update')
                                            <form action="{{ route('admin.orders.complete', $order) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <x-ui.button type="submit" size="sm" class="w-full">
                                                    Mark completed
                                                </x-ui.button>
                                            </form>
                                        @endadminCan
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="border-y border-dashed border-[rgb(var(--cp-line))] py-14 text-center">
                                <p class="text-lg font-bold text-[rgb(var(--cp-ink))]">Preparation queue clear</p>
                                <p class="mt-1 text-sm text-[rgb(var(--cp-muted))]">New backend-confirmed orders will appear here.</p>
                            </div>
                        @endforelse
                    </div>

                    @if($pendingOrders->hasPages())
                        <div>{{ $pendingOrders->links() }}</div>
                    @endif
                </section>

                <aside class="space-y-4 xl:sticky xl:top-6 xl:self-start" aria-labelledby="admin-shortcuts-title">
                    <x-ui.card class="shadow-none">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[rgb(var(--cp-brand))]">Authorized tools</p>
                        <h2 id="admin-shortcuts-title" class="mt-2 text-lg font-bold text-[rgb(var(--cp-ink))]">Station controls</h2>
                        <p class="mt-1 text-sm leading-6 text-[rgb(var(--cp-muted))]">Only modules allowed by this administrator role are shown.</p>

                        <nav class="mt-5 divide-y divide-[rgb(var(--cp-line))] border-y border-[rgb(var(--cp-line))]" aria-label="Admin shortcuts">
                            <a href="{{ route('admin.orders.index') }}" class="flex items-center justify-between py-3 text-sm font-semibold text-[rgb(var(--cp-ink))] hover:text-[rgb(var(--cp-brand))]">
                                Order operations <span aria-hidden="true">&rarr;</span>
                            </a>

                            @adminCan('product.view')
                                <a href="{{ route('admin.products.index') }}" class="flex items-center justify-between py-3 text-sm font-semibold text-[rgb(var(--cp-ink))] hover:text-[rgb(var(--cp-brand))]">
                                    Product workspace <span aria-hidden="true">&rarr;</span>
                                </a>
                            @endadminCan

                            @adminCan('coupon.view')
                                <a href="{{ route('admin.coupons.index') }}" class="flex items-center justify-between py-3 text-sm font-semibold text-[rgb(var(--cp-ink))] hover:text-[rgb(var(--cp-brand))]">
                                    Coupon controls <span aria-hidden="true">&rarr;</span>
                                </a>
                            @endadminCan

                            @adminCan('report.view')
                                <a href="{{ route('admin.owner.dashboard') }}" class="flex items-center justify-between py-3 text-sm font-semibold text-[rgb(var(--cp-ink))] hover:text-[rgb(var(--cp-brand))]">
                                    Analytics <span aria-hidden="true">&rarr;</span>
                                </a>
                            @endadminCan
                        </nav>
                    </x-ui.card>

                    <div class="rounded-lg border border-[rgb(var(--cp-line))] bg-stone-100 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[rgb(var(--cp-muted))]">Access note</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Visible controls reflect role permissions. Server authorization remains required for every action.</p>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-admin-layout>
