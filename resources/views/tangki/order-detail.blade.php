<x-app-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-md mx-auto px-4">
            <a href="{{ url()->previous() }}" class="inline-flex items-center text-sm text-gray-500 mb-6 hover:text-blue-600 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Back
            </a>

            <div class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-100 receipt-paper">
                <div class="bg-gray-900 p-8 text-center relative">
                    <div class="absolute top-0 left-0 w-full h-2 bg-blue-500"></div>
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-4 shadow-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-white text-xl font-black italic tracking-wider">COFFEE PLUS+</h2>
                    <p class="text-blue-300 text-[10px] tracking-[0.3em] uppercase mt-1">Order Detail Verified</p>
                </div>

                <div class="p-8">
                    <div class="flex justify-between text-[10px] text-gray-400 font-black uppercase mb-6 tracking-widest">
                        <span>Bill ID</span>
                        <span class="text-gray-800">{{ $order->bill_id }}</span>
                    </div>

                    <div class="space-y-3 mb-8">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-medium">Date</span>
                            <span class="font-bold text-gray-800">{{ $order->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-medium">Status</span>
                            <span class="bg-green-100 text-green-600 text-[10px] font-black px-2 py-1 rounded-md uppercase">{{ $order->status }}</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 pb-2">Items Purchased</p>
                        
                        @foreach($order->items as $item)
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col flex-1 pr-4">
                                    <span class="text-sm font-black text-gray-800 leading-tight">
                                        {{ $item->product->name ?? 'Product' }}
                                    </span>
                                    
                                    @if($item->options)
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($item->options as $key => $val)
                                                @if($key === 'addons' && is_array($val))
                                                    @foreach($val as $addonName)
                                                        <span class="text-[9px] bg-blue-50 px-1.5 py-0.5 rounded text-blue-600 uppercase font-bold">
                                                            + {{ $addonName }}
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span class="text-[9px] bg-gray-100 px-1.5 py-0.5 rounded text-gray-500 uppercase font-bold">
                                                        {{ $key }}: {{ $val }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($item->oz_at_time > 0)
                                        <div class="mt-1 flex items-center">
                                            <span class="text-[9px] text-blue-600 font-black uppercase tracking-tighter">
                                                Paid with Tank Balance
                                            </span>
                                        </div>
                                    @endif

                                    <span class="text-[10px] text-gray-400 font-bold mt-1">Quantity: {{ $item->quantity }}</span>
                                </div>

                                <div class="flex flex-col items-end">
                                    <span class="text-sm font-black text-gray-800">
                                        @if($item->oz_at_time > 0)
                                            {{ number_format($item->oz_at_time * $item->quantity, 1) }} OZ
                                        @else
                                            RM {{ number_format($item->price_at_time * $item->quantity, 2) }}
                                        @endif
                                    </span>
                                    
                                    @if($item->oz_at_time > 0)
                                        <span class="text-[9px] text-gray-400 font-bold uppercase italic">
                                            {{ $item->oz_at_time }} OZ / unit
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-dashed border-gray-200 my-8"></div>

                    <div class="space-y-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 font-medium">Subtotal</span>
                            <span class="font-bold text-gray-800">RM {{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        
                        @if($order->oz_used > 0)
                            <div class="flex justify-between items-center p-3 bg-blue-50 rounded-2xl border border-blue-100">
                                <div class="flex flex-col">
                                    <span class="text-blue-600 font-black text-[10px] uppercase">Tank Deduction</span>
                                    <span class="text-[9px] text-blue-400 leading-none">Balance Payment Applied</span>
                                </div>
                                <span class="font-black text-blue-600 text-lg">-{{ number_format($order->oz_used, 1) }} OZ</span>
                            </div>
                        @endif

                        <div class="flex justify-between pt-4 border-t border-gray-100 items-baseline">
                            <span class="text-lg font-black text-gray-900">Total Cash</span>
                            <span class="text-3xl font-black text-gray-900 tracking-tighter">
                                <span class="text-sm font-bold mr-1">RM</span>{{ number_format($order->final_amount, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-gray-50/50 border-t border-gray-100 flex flex-col items-center">
                    <p class="text-[10px] text-gray-400 font-black italic uppercase tracking-[0.2em]">Thank you for your order.</p>
                </div>
            </div>
            
            <div class="mt-8 text-center no-print">
                <button onclick="window.print()" class="text-xs font-black text-gray-400 hover:text-blue-600 uppercase tracking-widest transition">
                    Print Order Detail
                </button>
            </div>
        </div>
    </div>

    <style>
        @media print { .no-print { display: none; } nav { display: none; } }
        .receipt-paper { background-image: radial-gradient(#fafafa 1px, transparent 0); background-size: 20px 20px; }
    </style>
</x-app-layout>