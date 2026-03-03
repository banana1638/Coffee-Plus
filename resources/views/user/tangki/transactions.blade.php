<x-app-layout>
    <div class="py-8 md:py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex items-center gap-4 mb-8">
                <a href="{{ url()->previous() }}"
                    class="w-10 h-10 flex items-center justify-center bg-white rounded-xl shadow-sm border border-gray-100 text-gray-400 hover:text-blue-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M15 19l-7-7 7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">Transaction History</h2>
            </div>

            <div class="flex p-1 bg-gray-200/50 rounded-2xl mb-6">
                @php $type = request('type', 'all'); @endphp
                @foreach(['all' => 'All', 'in' => 'Refills', 'out' => 'Usage'] as $key => $label)
                    <a href="{{ route('tangki.transactions', ['type' => $key]) }}"
                        class="flex-1 py-3 text-center text-xs font-black uppercase tracking-widest rounded-xl transition {{ $type == $key ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="space-y-4">
                @forelse($transactions as $trx)
                    <div
                        class="group bg-white rounded-[2rem] p-6 border border-gray-100 shadow-sm hover:shadow-md transition-all">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 bg-gray-100 rounded-full text-[10px] font-black text-gray-500 uppercase">
                                        {{ $trx->type }}
                                    </span>
                                    <span
                                        class="text-[10px] font-bold text-gray-400">{{ $trx->created_at->format('M d, H:i') }}</span>
                                </div>

                                <h3 class="font-black text-gray-900 leading-tight">{{ $trx->description }}</h3>

                                @if($trx->bill && $trx->bill->items)
                                    <div class="mt-4 space-y-2 border-l-2 border-gray-50 pl-4">
                                        @foreach($trx->bill->items->take(2) as $item)
                                            <div class="flex justify-between text-xs">
                                                <span class="text-gray-500 font-bold">{{ $item->quantity }}x
                                                    {{ $item->product->name }}</span>
                                            </div>
                                        @endforeach
                                        @if($trx->bill->items->count() > 2)
                                            <p class="text-[9px] text-gray-400">...and {{ $trx->bill->items->count() - 2 }} more
                                                items</p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="text-right ml-4 flex flex-col items-end">
                                <span
                                    class="text-lg font-black {{ $trx->oz_delta > 0 ? 'text-green-500' : 'text-blue-600' }}">
                                    {{ $trx->oz_delta > 0 ? '+' : '' }}{{ $trx->oz_delta }}
                                    <span class="text-[10px] uppercase">oz</span>
                                </span>
                                <p class="text-[10px] text-gray-400 font-bold mt-1">#{{ $trx->bill_id }}</p>

                                @if($trx->bill_id)
                                    <a href="{{ route('tangki.order-detail', $trx->bill_id) }}"
                                        class="mt-4 px-4 py-2 bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                        View Detail
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 bg-white rounded-[2.5rem] border border-dashed border-gray-200">
                        <p class="text-gray-400 font-bold">No transactions found.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8 px-2">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>