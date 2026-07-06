<x-admin-layout>
    @php
        $admin = Auth::guard('admin')->user();
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <x-layout.page-header title="Preparation board" description="Oldest active orders first, with role-aware controls for {{ $admin->name }}.">
                <x-slot:actions>
                    <x-ui.badge variant="success">
                        <span class="mr-1 h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Production online
                    </x-ui.badge>
                </x-slot:actions>
            </x-layout.page-header>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.card>
                    <p class="text-sm font-medium text-slate-500">Live Orders</p>
                    <p class="cp-tabular mt-3 text-3xl font-bold text-[rgb(var(--cp-ink))]">{{ $pendingOrders->total() }}</p>
                    <p class="mt-1 text-xs text-slate-500">Orders awaiting preparation</p>
                </x-ui.card>

                <x-ui.card>
                    <p class="text-sm font-medium text-slate-500">Admin Role</p>
                    <p class="mt-3 text-3xl font-semibold capitalize text-slate-950">{{ str_replace('_', ' ', $admin->role) }}</p>
                    <p class="mt-1 text-xs text-slate-500">RBAC controlled view</p>
                </x-ui.card>

                @adminCan('report.view')
                    <x-ui.card>
                        <p class="text-sm font-medium text-slate-500">Total Balance</p>
                        <p class="cp-tabular mt-3 text-3xl font-bold text-[rgb(var(--cp-ink))]">
                            <span class="text-sm text-emerald-700">RM</span> {{ number_format(\App\Models\User::sum('tangki_balance'), 2) }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Tangki balance snapshot</p>
                    </x-ui.card>

                    <x-ui.card>
                        <p class="text-sm font-medium text-slate-500">Active Members</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-950">{{ number_format(\App\Models\User::count()) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Registered customers</p>
                    </x-ui.card>
                @endadminCan
            </div>

            <div class="grid gap-6 lg:grid-cols-12">
                <section class="space-y-4 lg:col-span-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-[rgb(var(--cp-ink))]">Active preparation queue</h2>
                            <p class="text-sm text-slate-600">Oldest orders stay at the top for faster service flow.</p>
                        </div>
                        <x-ui.badge variant="info">Queue: {{ $pendingOrders->total() }}</x-ui.badge>
                    </div>

                    <div class="space-y-3">
                        @forelse($pendingOrders as $order)
                            <x-ui.card padding="compact" class="group">
                                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                    <div class="flex min-w-0 items-start gap-4">
                                        <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-[#18201d] text-white shadow-sm">
                                            <span class="text-[10px] font-semibold uppercase text-slate-300">Order</span>
                                            <span class="text-sm font-semibold">#{{ substr($order->bill_id, -3) }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="font-semibold text-slate-950">{{ $order->user->name }}</h3>
                                                <span class="text-xs text-slate-500">{{ $order->created_at->diffForHumans() }}</span>
                                            </div>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach($order->items as $item)
                                                    <x-ui.badge variant="neutral">
                                                        {{ $item->product->name }} x{{ $item->quantity }} {{ strtoupper($item->size) }}
                                                    </x-ui.badge>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    @adminCan('order.status.update')
                                        <form action="{{ route('admin.orders.complete', $order) }}" method="POST" class="shrink-0">
                                            @csrf @method('PATCH')
                                            <x-ui.button type="submit" size="sm">
                                                Mark completed
                                            </x-ui.button>
                                        </form>
                                    @endadminCan
                                </div>
                            </x-ui.card>
                        @empty
                            <div class="rounded-lg border border-dashed border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-10 text-center">
                                <p class="font-semibold text-[rgb(var(--cp-ink))]">Preparation queue clear</p>
                                <p class="mt-1 text-xs text-slate-400">The kitchen queue is clear.</p>
                            </div>
                        @endforelse
                    </div>

                    @if($pendingOrders->hasPages())
                        <div>
                            {{ $pendingOrders->links() }}
                        </div>
                    @endif
                </section>

                <aside class="space-y-4 lg:col-span-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">System Control</h2>
                        <p class="text-sm text-slate-600">Role-aware shortcuts for this admin.</p>
                    </div>

                    <div class="grid gap-3">
                        <x-ui.button :href="route('admin.orders.index')" variant="secondary" class="justify-between">
                            Orders
                            <span aria-hidden="true">-></span>
                        </x-ui.button>

                        @adminCan('product.view')
                            <x-ui.button :href="route('admin.products.index')" variant="secondary" class="justify-between">
                                Manage Products
                                <span aria-hidden="true">-></span>
                            </x-ui.button>
                        @endadminCan

                        @adminCan('coupon.view')
                            <x-ui.button :href="route('admin.coupons.index')" variant="secondary" class="justify-between">
                                Coupons
                                <span aria-hidden="true">-></span>
                            </x-ui.button>
                        @endadminCan

                        @adminCan('report.view')
                            <x-ui.button :href="route('admin.owner.dashboard')" variant="secondary" class="justify-between">
                                Analytics
                                <span aria-hidden="true">-></span>
                            </x-ui.button>
                        @endadminCan
                    </div>

                    <x-ui.card>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                            <div>
                                <p class="text-sm font-semibold text-slate-950">Server active</p>
                                <p class="text-xs text-slate-500">Checkout, order queue, and admin tools are reachable.</p>
                            </div>
                        </div>
                    </x-ui.card>
                </aside>
            </div>
        </div>
    </div>
</x-admin-layout>
