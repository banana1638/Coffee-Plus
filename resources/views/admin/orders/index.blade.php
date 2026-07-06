<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1400px] space-y-6">
            <x-layout.page-header title="Order operations" description="Scan active orders, verify pickup codes, advance preparation status, and inspect refunds.">
                <x-slot:actions>
                    @adminCan('report.export')
                        <x-ui.button :href="route('admin.orders.export.page')" variant="secondary">
                            Export Center
                        </x-ui.button>
                    @endadminCan
                    <x-ui.button :href="route('admin.orders.refunds')" variant="secondary">
                        Refunds
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            <div class="grid gap-4 xl:grid-cols-[1fr_360px]">
                <x-ui.card padding="compact">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-950">All Active Orders</p>
                            <p class="text-xs text-slate-500">Total: {{ $orders->total() }}</p>
                        </div>
                        <x-ui.badge variant="info">{{ $orders->count() }} visible</x-ui.badge>
                    </div>
                </x-ui.card>

                @adminCan('order.status.update')
                    <form action="{{ route('admin.orders.complete-by-code') }}" method="POST"
                        class="flex gap-2 rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-2 shadow-sm">
                        @csrf
                        <label class="sr-only" for="pickup_code">Pickup code</label>
                        <input id="pickup_code" name="pickup_code" type="text" maxlength="12" placeholder="Pickup code"
                            class="min-w-0 flex-1 rounded-lg border-[rgb(var(--cp-line))] bg-stone-100 text-sm font-semibold uppercase text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <x-ui.button type="submit" size="sm">
                            Verify
                        </x-ui.button>
                    </form>
                @endadminCan
            </div>

            <div class="grid gap-3">
                @forelse($orders as $order)
                    @php
                        $statusVariant = match($order->status) {
                            'completed' => 'success',
                            'cancelled', 'refunded' => 'danger',
                            'ready', 'preparing' => 'warning',
                            default => 'info',
                        };
                    @endphp

                    <x-ui.card padding="compact" class="group">
                        <div class="grid gap-4 xl:grid-cols-[1.1fr_1fr_180px_260px] xl:items-center">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge :variant="$statusVariant">{{ str_replace('_', ' ', $order->status) }}</x-ui.badge>
                                    <span class="text-xs text-slate-500">{{ $order->created_at->format('M d, H:i A') }}</span>
                                </div>
                                <h3 class="mt-2 text-base font-semibold text-slate-950">#{{ $order->bill_id }}</h3>
                                <p class="mt-1 text-xs text-slate-500">Customer: {{ $order->user->name }}</p>
                                @if($order->pickup_code)
                                    <p class="mt-1 text-xs font-semibold uppercase text-emerald-800">Pickup: {{ $order->pickup_code }}</p>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Items</p>
                                <div class="mt-2 space-y-1">
                                    @foreach($order->items->take(2) as $item)
                                        <p class="truncate text-sm text-slate-700">{{ $item->quantity }}x {{ $item->product->name }} ({{ $item->size }})</p>
                                    @endforeach
                                    @if($order->items->count() > 2)
                                        <p class="text-xs text-slate-500">+ {{ $order->items->count() - 2 }} more items</p>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Final Amount</p>
                                <p class="cp-tabular mt-2 text-xl font-bold text-[rgb(var(--cp-ink))]">
                                    <span class="text-xs text-slate-500">RM</span>{{ number_format($order->final_amount, 2) }}
                                </p>
                            </div>

                            <div class="flex flex-wrap justify-start gap-2 xl:justify-end">
                                <x-ui.button :href="route('admin.orders.show', $order)" variant="secondary" size="sm">
                                    View Detail
                                </x-ui.button>

                                @if($order->canAdvanceStatus() && Auth::guard('admin')->user()->canPerform('order.status.update'))
                                    <form action="{{ route('admin.orders.advance-status', $order) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <x-ui.button type="submit" size="sm" onclick="return confirm('Move order to {{ str_replace('_', ' ', $order->nextStatus()) }}?')">
                                            {{ str_replace('_', ' ', $order->nextStatus()) }}
                                        </x-ui.button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </x-ui.card>
                @empty
                    <div class="rounded-lg border border-dashed border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-10 text-center">
                        <p class="font-semibold text-[rgb(var(--cp-ink))]">No orders in this view</p>
                    </div>
                @endforelse
            </div>

            <div>
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
