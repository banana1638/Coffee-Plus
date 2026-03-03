<x-admin-layout>
    <div class="py-10 bg-gray-50/50 min-h-screen">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">

            <div class="flex flex-col lg:flex-row items-stretch gap-6 mb-8">
                <div class="flex-1 bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 flex items-center gap-6">
                    <div class="shrink-0 w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-100 transition-transform hover:scale-105">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-[0.2em] mb-1">Admin Portal</p>
                        <h2 class="text-2xl font-black text-gray-900 leading-tight">
                            Welcome, <span class="text-blue-600">{{ Auth::guard('admin')->user()->name }}</span>!
                        </h2>
                    </div>
                </div>

                <div class="flex-[1.5] grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-[2.5rem] p-6 border border-gray-100 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Total Revenue</p>
                            <p class="text-2xl font-black text-gray-900 leading-none">
                                <span class="text-xs font-bold text-emerald-600 uppercase">RM</span> {{ number_format(\App\Models\User::sum('tangki_balance'), 2) }}
                            </p>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2.5rem] p-6 border border-gray-100 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Active Members</p>
                            <p class="text-2xl font-black text-gray-900 leading-none">{{ \App\Models\User::count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-8 items-start">
                
                <div class="w-full lg:w-[70%] space-y-6">
                    <div class="flex items-center justify-between px-2">
                        <h2 class="text-3xl font-black text-gray-900 tracking-tight italic uppercase">Live Orders</h2>
                        <span class="px-4 py-1.5 bg-gray-900 text-white text-[10px] font-black rounded-full uppercase tracking-[0.2em] shadow-lg shadow-gray-200">
                            Queue: {{ count($pendingOrders = \App\Models\Order::where('status', 'pending')->with(['user', 'items.product'])->oldest()->get()) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        @forelse($pendingOrders as $order)
                            <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 flex justify-between items-center transition-all hover:shadow-xl hover:shadow-blue-500/5 group">
                                <div class="flex items-center gap-6">
                                    <div class="w-16 h-16 bg-gray-900 rounded-2xl flex flex-col items-center justify-center text-white shrink-0 shadow-lg shadow-gray-200 transition-transform group-hover:scale-105">
                                        <span class="text-[10px] font-black uppercase opacity-60 tracking-tighter">Order</span>
                                        <span class="text-lg font-black italic">#{{ substr($order->bill_id, -3) }}</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3">
                                            <h4 class="text-lg font-black text-gray-900">{{ $order->user->name }}</h4>
                                            <span class="text-[10px] text-gray-300 font-bold uppercase tracking-widest italic">{{ $order->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            @foreach($order->items as $item)
                                                <span class="text-[10px] font-black bg-blue-50 text-blue-600 px-3 py-1 rounded-xl uppercase italic border border-blue-100/50">
                                                    {{ $item->product->name }} <span class="opacity-50 text-[8px]">x</span>{{ $item->quantity }} ({{ $item->size }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="shrink-0 ml-4">
                                    <form action="{{ route('admin.orders.complete', $order) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-8 py-4 bg-gray-900 hover:bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-[0.3em] shadow-lg shadow-gray-100 transition-all hover:-translate-y-1 active:scale-95">
                                            Done
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="bg-white/40 border-2 border-dashed border-gray-200 rounded-[3rem] p-16 text-center shadow-inner">
                                <p class="text-gray-400 font-black italic uppercase tracking-[0.3em]">No pending orders. Enjoy the silence. ☕️</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="w-full lg:w-[30%] space-y-6 lg:sticky lg:top-8">
                    <h2 class="text-xl font-black text-gray-900 tracking-tight italic uppercase px-2">System Control</h2>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <a href="{{ route('admin.products.index') }}" class="group bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 transition-all hover:shadow-2xl hover:shadow-blue-500/10">
                            <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-blue-200 group-hover:rotate-6 transition-transform text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 10-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            </div>
                            <h3 class="text-xl font-black text-gray-900 mb-1 italic uppercase leading-none">Manage Products</h3>
                            <p class="text-blue-600 text-[10px] font-black uppercase tracking-[0.3em] mt-2 italic">Open Inventory ➜</p>
                        </a>

                        <div class="bg-white/60 rounded-[2.5rem] p-6 border border-gray-100 flex items-center justify-between opacity-60">
                            <span class="text-sm font-black text-gray-900 uppercase italic tracking-tight">Analytics</span>
                            <span class="text-[8px] font-black bg-gray-200 px-2 py-0.5 rounded text-gray-500 uppercase">Coming Soon</span>
                        </div>
                    </div>

                    <div class="p-6 bg-emerald-50 rounded-[2rem] border border-emerald-100/50 flex items-center gap-3">
                        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-ping"></div>
                        <span class="text-[10px] font-black text-emerald-700 uppercase tracking-[0.2em] italic">Server: Production Online</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-admin-layout>