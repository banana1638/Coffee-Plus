<x-admin-layout>
    <div class="py-8 md:py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 flex items-center justify-center bg-gray-900 rounded-2xl shadow-lg text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-black text-gray-800 tracking-tight italic uppercase">Order Management</h2>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-[0.3em] italic">Internal Logistics
                        Control</p>
                </div>
                <a href="{{ route('admin.orders.export.page') }}"
                    class="px-5 py-2.5 bg-white border border-gray-200 text-gray-800 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-gray-900 hover:text-white hover:border-gray-900 transition-all shadow-sm active:scale-95 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Export Center
                </a>
            </div>

            <div class="flex p-1 bg-gray-200/50 rounded-2xl mb-6">
                <div
                    class="flex-1 py-3 text-center text-xs font-black uppercase tracking-widest rounded-xl bg-white text-blue-600 shadow-sm">
                    All Active Orders ({{ $orders->total() }})
                </div>
            </div>

            <div class="space-y-4">
                @forelse($orders as $order)
                    <div
                        class="group bg-white rounded-[2rem] p-6 border border-gray-100 shadow-sm hover:shadow-md transition-all">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="px-3 py-1 {{ $order->status === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600' }} rounded-full text-[10px] font-black uppercase italic border border-current opacity-80">
                                        {{ $order->status }}
                                    </span>
                                    <span
                                        class="text-[10px] font-bold text-gray-400">{{ $order->created_at->format('M d, H:i A') }}</span>
                                </div>

                                <h3 class="font-black text-gray-900 leading-tight text-lg italic uppercase">
                                    #{{ $order->bill_id }}</h3>
                                <p class="text-[10px] text-gray-400 font-bold mt-1 uppercase tracking-widest">Customer:
                                    {{ $order->user->name }}</p>

                                <div class="mt-4 space-y-1 border-l-2 border-gray-50 pl-4">
                                    @foreach($order->items->take(2) as $item)
                                        <p class="text-[11px] text-gray-500 font-bold uppercase italic">
                                            {{ $item->quantity }}x {{ $item->product->name }} ({{ $item->size }})
                                        </p>
                                    @endforeach
                                    @if($order->items->count() > 2)
                                        <p class="text-[9px] text-gray-300 italic">+ {{ $order->items->count() - 2 }} more items
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="text-right ml-4 flex flex-col items-end justify-between min-h-[120px]">
                                <div>
                                    <span class="text-xl font-black text-gray-900 italic">
                                        <span
                                            class="text-xs not-italic mr-0.5">RM</span>{{ number_format($order->final_amount, 2) }}
                                    </span>
                                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-tighter mt-1">Final
                                        Amount</p>
                                </div>

                                <div class="flex gap-2">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                        class="px-4 py-2 bg-gray-50 text-gray-600 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                        View Detail
                                    </a>

                                    @if($order->status !== 'completed')
                                        <form action="{{ route('admin.orders.complete', $order) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit" onclick="return confirm('Confirm complete?')"
                                                class="px-4 py-2 bg-gray-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-600 transition-all active:scale-95 shadow-md">
                                                Complete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 bg-white rounded-[2.5rem] border border-dashed border-gray-200">
                        <p class="text-gray-400 font-bold italic uppercase tracking-widest">No orders found.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8 px-2">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>