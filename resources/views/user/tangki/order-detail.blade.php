<x-app-layout>
    @php
        $statusVariant = $order->status === 'cancelled' ? 'danger' : ($order->status === 'completed' ? 'success' : 'info');
        $steps = [
            'pending' => 'Pending',
            'preparing' => 'Preparing',
            'ready_for_pickup' => 'Ready',
            'completed' => 'Completed',
        ];
        $activeStep = $order->statusStep();
    @endphp

    <div class="px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-8">
            <x-layout.customer-page-header eyebrow="Order status" title="Pickup ticket {{ $order->bill_id }}" description="Backend-confirmed order status, collection code, item recipe, and payment summary.">
                <x-slot:actions>
                    <x-ui.button :href="url()->previous()" variant="secondary">
                        Back
                    </x-ui.button>
                    <x-ui.button type="button" variant="secondary" onclick="window.print()">
                        Print
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.customer-page-header>

            <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                <div class="min-w-0 space-y-6">
                    <x-ui.card class="shadow-none">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Date</p>
                                <p class="mt-2 font-semibold text-slate-950">{{ $order->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                                <div class="mt-2">
                                    <x-ui.badge :variant="$statusVariant">{{ str_replace('_', ' ', $order->status) }}</x-ui.badge>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bill ID</p>
                                <p class="mt-2 break-all font-semibold text-slate-950">{{ $order->bill_id }}</p>
                            </div>
                        </div>

                        @if($order->status !== 'cancelled')
                            <div class="mt-8 grid grid-cols-4 gap-2">
                                @foreach($steps as $key => $label)
                                    @php $stepIndex = $loop->iteration; @endphp
                                    <div>
                                        <div class="h-2 rounded-full {{ $stepIndex <= $activeStep ? 'bg-emerald-700' : 'bg-slate-200' }}"></div>
                                        <p class="mt-2 text-xs font-semibold {{ $stepIndex <= $activeStep ? 'text-emerald-800' : 'text-slate-400' }}">
                                            {{ $label }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-ui.card>

                    <x-ui.card class="shadow-none">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgb(var(--cp-brand))]">Order contents</p>
                        <h2 class="mt-2 font-display text-3xl font-medium text-[rgb(var(--cp-ink))]">Drink manifest</h2>
                        <div class="mt-4 divide-y divide-slate-200">
                            @foreach($order->items as $item)
                                @php
                                    $existingReview = $order->reviews
                                        ->where('product_id', $item->product_id)
                                        ->first();
                                @endphp
                                <div class="grid gap-4 py-5 md:grid-cols-[1fr_150px]">
                                    <div>
                                        <p class="font-display text-xl font-medium text-[rgb(var(--cp-ink))]">{{ $item->product->name ?? 'Product' }}</p>

                                        @if($item->options)
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach($item->options as $key => $val)
                                                    @if($key === 'addons' && is_array($val))
                                                        @foreach($val as $addonName)
                                                            <x-ui.badge variant="info">+ {{ $addonName }}</x-ui.badge>
                                                        @endforeach
                                                    @else
                                                        <x-ui.badge>{{ $key }}: {{ $val }}</x-ui.badge>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        @if($item->oz_at_time > 0)
                                            <p class="mt-2 text-xs font-semibold uppercase text-emerald-800">Paid with Tangki balance</p>
                                        @endif

                                        <p class="mt-2 text-sm text-slate-500">Quantity: {{ $item->quantity }}</p>

                                        @if($order->status === 'completed')
                                            @if($existingReview)
                                                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                                                        Your rating: {{ $existingReview->rating }} / 5
                                                    </p>
                                                    @if($existingReview->comment)
                                                        <p class="mt-1 text-sm text-amber-800">{{ $existingReview->comment }}</p>
                                                    @endif
                                                </div>
                                            @else
                                                <form action="{{ route('orders.reviews.store', $order) }}" method="POST"
                                                    class="mt-4 space-y-3 rounded-lg border border-[rgb(var(--cp-line))] bg-stone-100 p-3">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                                                    <div class="flex gap-2">
                                                        <select name="rating" required
                                                            class="rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                                            <option value="">Rating</option>
                                                            @for($rating = 5; $rating >= 1; $rating--)
                                                                <option value="{{ $rating }}">{{ $rating }} / 5</option>
                                                            @endfor
                                                        </select>
                                                        <x-ui.button type="submit" size="sm">Review</x-ui.button>
                                                    </div>
                                                    <textarea name="comment" rows="2" maxlength="1000" placeholder="Comment"
                                                        class="w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-medium text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                                                </form>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="text-left md:text-right">
                                        <p class="font-semibold text-slate-950">
                                            @if($item->oz_at_time > 0)
                                                {{ number_format($item->oz_at_time * $item->quantity, 1) }} OZ
                                            @else
                                                <x-ui.price :amount="$item->price_at_time * $item->quantity" accent />
                                            @endif
                                        </p>
                                        @if($item->oz_at_time > 0)
                                            <p class="mt-1 text-xs text-slate-500">{{ $item->oz_at_time }} OZ / unit</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                </div>

                <aside class="min-w-0 space-y-6">
                    @if($order->pickup_code)
                        <section class="overflow-hidden rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] shadow-sm">
                            <x-ticket-perforation />
                            <div class="p-4 sm:p-6">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgb(var(--cp-brand))]">Show at the counter</p>
                                <h2 class="mt-2 font-display text-3xl font-medium text-[rgb(var(--cp-ink))]">Collection code</h2>
                                <div class="mt-5 rounded-lg border border-dashed border-[rgb(var(--cp-line))] bg-stone-100 p-4 text-center">
                                    <img class="mx-auto h-40 w-40 rounded-lg border border-slate-200 bg-white p-2"
                                        src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($order->pickup_qr_payload) }}"
                                        alt="Pickup QR code">
                                    <p class="mt-4 text-2xl font-semibold tracking-[0.2em] text-slate-950">{{ $order->pickup_code }}</p>
                                </div>
                            </div>
                        </section>
                    @endif

                    <x-ui.card class="shadow-none">
                        <div class="space-y-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Subtotal</span>
                                <x-ui.price :amount="$order->subtotal" size="sm" accent />
                            </div>

                            @if($order->oz_used > 0)
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                    <div class="flex justify-between gap-3">
                                        <span class="text-sm font-semibold text-emerald-800">Tangki deduction</span>
                                        <span class="cp-tabular font-bold text-emerald-800">-{{ number_format($order->oz_used, 1) }} OZ</span>
                                    </div>
                                </div>
                            @endif

                            <div class="border-t border-slate-200 pt-4">
                                <p class="text-sm font-medium text-slate-500">Total Cash</p>
                                <x-ui.price :amount="$order->final_amount" size="xl" accent class="mt-2" />
                            </div>

                            @if($order->canBeCancelled())
                                <form action="{{ route('order.cancel', $order) }}" method="POST">
                                    @csrf
                                    <x-ui.button type="submit" variant="danger" class="w-full" onclick="return confirm('Cancel this order and refund to Tangki?')">
                                        Cancel Order
                                    </x-ui.button>
                                </form>
                            @endif
                        </div>
                    </x-ui.card>
                </aside>
            </div>
        </div>
    </div>

    <style>
        @media print {
            nav, aside { display: none !important; }
        }
    </style>
</x-app-layout>
