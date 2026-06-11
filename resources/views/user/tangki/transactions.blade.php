<x-app-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <x-layout.page-header title="Transaction History" description="Review refills, redemptions, and order-linked Tangki activity.">
                <x-slot:actions>
                    <x-ui.button :href="url()->previous()" variant="secondary">
                        Back
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            @php $type = request('type', 'all'); @endphp
            <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                @foreach(['all' => 'All', 'in' => 'Refills', 'out' => 'Usage'] as $key => $label)
                    <a href="{{ route('tangki.transactions', ['type' => $key]) }}"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $type == $key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="grid gap-3">
                @forelse($transactions as $trx)
                    <x-ui.card padding="compact">
                        <div class="grid gap-4 md:grid-cols-[1fr_150px_110px] md:items-center">
                            <div>
                                <div class="mb-2 flex items-center gap-2">
                                    <x-ui.badge>{{ $trx->type }}</x-ui.badge>
                                    <span class="text-xs text-slate-500">{{ $trx->created_at->format('M d, H:i') }}</span>
                                </div>
                                <h3 class="font-semibold text-slate-950">{{ $trx->description }}</h3>

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

                            <p class="text-lg font-semibold {{ $trx->oz_delta > 0 ? 'text-emerald-700' : 'text-indigo-700' }}">
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
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
                        <p class="text-sm font-semibold text-slate-500">No transactions found.</p>
                    </div>
                @endforelse
            </div>

            <div>
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
