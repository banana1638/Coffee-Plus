<x-admin-layout>
    @php
        $statusVariant = match($order->status) {
            'completed' => 'success',
            'cancelled', 'refunded' => 'danger',
            'ready', 'preparing' => 'warning',
            default => 'info',
        };
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1200px] space-y-6">
            <x-layout.page-header title="Order #{{ $order->bill_id }}" description="Checkout snapshot, immutable status history, pickup verification, and authorized controls.">
                <x-slot:actions>
                    <x-ui.button :href="route('admin.orders.index')" variant="secondary">
                        Back to Orders
                    </x-ui.button>
                    <x-ui.button type="button" variant="secondary" onclick="window.print()">
                        Print Log
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                <div class="space-y-6">
                    <x-ui.card>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Customer</p>
                                <p class="mt-2 font-semibold text-slate-950">{{ $order->user->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Date & Time</p>
                                <p class="mt-2 font-semibold text-slate-950">{{ $order->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                                <div class="mt-2">
                                    <x-ui.badge :variant="$statusVariant">{{ str_replace('_', ' ', $order->status) }}</x-ui.badge>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-bold text-[rgb(var(--cp-ink))]">Drink manifest</h2>
                                <p class="text-sm text-slate-600">Items captured at checkout time.</p>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-200">
                            @foreach($order->items as $item)
                                <div class="grid gap-3 py-4 md:grid-cols-[1fr_120px_140px] md:items-center">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ $item->product->name }}</p>
                                        <div class="mt-2 flex gap-2">
                                            <x-ui.badge>Size: {{ $item->size }}</x-ui.badge>
                                            <x-ui.badge>Qty: {{ $item->quantity }}</x-ui.badge>
                                        </div>
                                    </div>
                                    <p class="text-sm text-slate-600">RM {{ number_format($item->price_at_time, 2) }} each</p>
                                    <p class="text-right font-semibold text-slate-950">RM {{ number_format($item->price_at_time * $item->quantity, 2) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <x-slot:header>
                            <div>
                                <h2 class="text-base font-semibold text-slate-950">Status History</h2>
                                <p class="text-sm text-slate-600">Immutable record of order state changes.</p>
                            </div>
                        </x-slot:header>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3">Time</th>
                                        <th class="px-4 py-3">Transition</th>
                                        <th class="px-4 py-3">Actor</th>
                                        <th class="px-4 py-3">Source</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($order->statusHistories as $history)
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $history->created_at->format('Y-m-d H:i:s') }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-950">
                                                {{ $history->from_status ?? 'created' }} &rarr; {{ $history->to_status }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                                {{ $history->actor_type }}{{ $history->actor_id ? ' #' . $history->actor_id : '' }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">{{ $history->source ?? 'unknown' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>
                </div>

                <aside class="space-y-6">
                    @if($order->pickup_code)
                        <x-ui.card>
                            <h2 class="text-base font-bold text-[rgb(var(--cp-ink))]">Pickup verification</h2>
                            <div class="mt-5 rounded-lg border border-dashed border-[rgb(var(--cp-line))] bg-stone-100 p-4 text-center">
                                <img class="mx-auto h-40 w-40 rounded-lg border border-slate-200 bg-white p-2"
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($order->pickup_qr_payload) }}"
                                    alt="Pickup QR code">
                                <p class="mt-4 text-2xl font-semibold tracking-[0.2em] text-slate-950">{{ $order->pickup_code }}</p>
                            </div>
                        </x-ui.card>
                    @endif

                    <x-ui.card>
                        <p class="text-sm font-medium text-slate-500">Total Cash</p>
                        <p class="cp-tabular mt-3 text-3xl font-bold text-[rgb(var(--cp-ink))]">
                            <span class="text-sm text-slate-500">RM</span>{{ number_format($order->final_amount, 2) }}
                        </p>

                        @if($order->canAdvanceStatus() && Auth::guard('admin')->user()->canPerform('order.status.update'))
                            <form action="{{ route('admin.orders.advance-status', $order) }}" method="POST" class="mt-6">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" class="w-full">
                                    Move to {{ str_replace('_', ' ', $order->nextStatus()) }}
                                </x-ui.button>
                            </form>
                        @endif
                    </x-ui.card>
                </aside>
            </div>
        </div>
    </div>

    <style>
        @media print {
            aside, nav, .no-print { display: none !important; }
            main { padding-left: 0 !important; }
        }
    </style>
</x-admin-layout>
