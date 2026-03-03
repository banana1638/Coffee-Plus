<x-admin-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-md mx-auto px-4">
            
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center text-[10px] font-black uppercase tracking-widest text-gray-400 mb-6 hover:text-blue-600 transition group">
                <svg class="w-4 h-4 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 19l-7-7 7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Back to Logistics
            </a>

            <div class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-100 receipt-paper">
                <div class="bg-gray-900 p-8 text-center relative">
                    <div class="absolute top-0 left-0 w-full h-2 {{ $order->status === 'completed' ? 'bg-emerald-500' : 'bg-blue-600' }}"></div>
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-4 shadow-lg rotate-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-white text-xl font-black italic tracking-wider uppercase">COFFEE PLUS+</h2>
                    <p class="text-blue-300 text-[10px] tracking-[0.3em] uppercase mt-1 font-bold">Admin Verification</p>
                </div>

                <div class="p-8">
                    <div class="flex justify-between text-[10px] text-gray-400 font-black uppercase mb-6 tracking-widest border-b border-gray-50 pb-2">
                        <span>Ref ID</span>
                        <span class="text-gray-800 italic">#{{ $order->bill_id }}</span>
                    </div>

                    <div class="space-y-3 mb-8">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-500 font-bold uppercase italic">Customer</span>
                            <span class="font-black text-gray-800 italic">{{ $order->user->name }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-500 font-bold uppercase italic">Date & Time</span>
                            <span class="font-black text-gray-800">{{ $order->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-500 font-bold uppercase italic">Status</span>
                            <span class="{{ $order->status === 'completed' ? 'text-emerald-600 bg-emerald-50' : 'text-blue-600 bg-blue-50' }} text-[9px] font-black px-2 py-0.5 rounded uppercase italic border border-current">
                                {{ $order->status }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 pb-2 italic">Product Manifest</p>
                        
                        @foreach($order->items as $item)
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col flex-1 pr-4">
                                    <span class="text-sm font-black text-gray-800 leading-tight italic uppercase">
                                        {{ $item->product->name }}
                                    </span>
                                    <div class="flex gap-2 mt-1">
                                        <span class="text-[9px] bg-gray-100 px-1.5 py-0.5 rounded text-gray-500 uppercase font-bold italic">
                                            Size: {{ $item->size }}
                                        </span>
                                        <span class="text-[9px] bg-blue-50 px-1.5 py-0.5 rounded text-blue-600 uppercase font-bold italic">
                                            Qty: {{ $item->quantity }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-black text-gray-800 italic">
                                        RM {{ number_format($item->price_at_time * $item->quantity, 2) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-dashed border-gray-200 my-8"></div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-baseline">
                            <span class="text-lg font-black text-gray-900 italic uppercase">Total Cash</span>
                            <span class="text-3xl font-black text-gray-900 tracking-tighter italic">
                                <span class="text-sm font-bold mr-1 not-italic">RM</span>{{ number_format($order->final_amount, 2) }}
                            </span>
                        </div>
                    </div>

                    @if($order->status !== 'completed')
                        <div class="mt-10">
                            <form action="{{ route('admin.orders.complete', $order) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="w-full py-5 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-black text-xs uppercase tracking-[0.3em] transition-all shadow-xl shadow-blue-900/20 active:scale-95">
                                    Mark as Completed
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="p-8 bg-gray-50/50 border-t border-gray-100 flex flex-col items-center">
                    <p class="text-[9px] text-gray-300 font-black italic uppercase tracking-[0.4em]">Official Log Content</p>
                </div>
            </div>
            
            <div class="mt-8 text-center no-print">
                <button onclick="window.print()" class="text-[10px] font-black text-gray-400 hover:text-blue-600 uppercase tracking-[0.3em] transition italic">
                    Print Log Reference
                </button>
            </div>
        </div>
    </div>

    <style>
        @media print { .no-print { display: none; } nav { display: none; } }
        .receipt-paper { background-image: radial-gradient(#fafafa 2px, transparent 0); background-size: 20px 20px; }
    </style>
</x-admin-layout>