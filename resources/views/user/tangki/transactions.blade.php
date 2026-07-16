<x-app-layout>
    <div class="px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-8">
            <x-layout.customer-page-header eyebrow="Account ledger" title="Tangki activity" description="Review backend-confirmed refills, redemptions, and order-linked movements.">
                <x-slot:actions>
                    <x-ui.button :href="url()->previous()" variant="secondary">
                        Back
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.customer-page-header>

            @php $type = request('type', 'all'); @endphp
            <nav class="inline-flex border-b border-[rgb(var(--cp-line))]" aria-label="Transaction type">
                @foreach(['all' => 'All', 'in' => 'Refills', 'out' => 'Usage'] as $key => $label)
                    <a href="{{ route('tangki.transactions', ['type' => $key]) }}"
                        class="border-b-2 px-4 py-3 text-sm font-semibold transition {{ $type == $key ? 'border-[rgb(var(--cp-brand))] text-[rgb(var(--cp-brand-strong))]' : 'border-transparent text-[rgb(var(--cp-muted))] hover:border-emerald-200 hover:text-emerald-800' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="grid gap-3">
                @forelse($transactions as $trx)
                    <x-ui.card padding="compact" class="shadow-none">
                        <div class="grid gap-5 md:grid-cols-[1fr_150px_110px] md:items-center">
                            <div>
                                <div class="mb-2 flex items-center gap-2">
                                    <x-ui.badge>{{ $trx->type }}</x-ui.badge>
                                    <span class="text-xs text-slate-500">{{ $trx->created_at->format('M d, H:i') }}</span>
                                </div>
                                <h3 class="font-display text-xl font-medium text-[rgb(var(--cp-ink))]">{{ $trx->description }}</h3>

                                @if($trx->bill && $trx->bill->items)
                                    <div class="mt-3 border-l border-slate-200 pl-4">
                                        @foreach($trx->bill->items->take(2) as $item)
                                            <p class="text-sm text-slate-600">{{ $item->quantity }}x {{ $item->product->name }}</p>
                                        @endforeach
                                        @if($trx->bill->items->count() > 2)
                                            <p class="text-xs text-slate-500">+ {{ $trx->bill->items->count() - 2 }} more items</p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <p class="cp-tabular text-lg font-bold {{ $trx->oz_delta > 0 ? 'text-emerald-700' : 'text-slate-800' }}">
                                {{ $trx->oz_delta > 0 ? '+' : '' }}{{ $trx->oz_delta }} <span class="text-xs">oz</span>
                            </p>

                            @if($trx->bill_id)
                                <x-ui.button :href="route('tangki.order-detail', $trx->bill_id)" variant="secondary" size="sm">
                                    View Detail
                                </x-ui.button>
                            @endif
                        </div>
                    </x-ui.card>
                @empty
                    <div class="border-y border-dashed border-[rgb(var(--cp-line))] py-14 text-center">
                        <p class="font-display text-2xl font-medium text-[rgb(var(--cp-ink))]">No matching ledger entries</p>
                    </div>
                @endforelse
            </div>

            <div>
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
