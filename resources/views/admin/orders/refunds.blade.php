<x-admin-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Refund Records</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-[0.2em] mt-1">Tangki refund audit log</p>
                </div>
                <a href="{{ route('admin.orders.index') }}"
                    class="px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-900 hover:text-white transition">
                    Back to Orders
                </a>
            </div>

            <form method="GET" action="{{ route('admin.orders.refunds') }}"
                class="mb-6 flex gap-2 bg-white border border-gray-100 rounded-2xl p-2 shadow-sm">
                <input name="search_id" type="text" value="{{ request('search_id') }}" placeholder="Search bill id"
                    class="flex-1 border-0 bg-gray-50 rounded-xl text-xs font-black uppercase tracking-widest focus:ring-blue-500">
                <button type="submit"
                    class="px-5 bg-gray-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-600 transition">
                    Search
                </button>
            </form>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/50 border-b border-gray-50">
                        <tr>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Bill</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Customer</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">OZ Delta</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Description</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($refunds as $refund)
                            <tr class="hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-5">
                                    @if($refund->bill)
                                        <a href="{{ route('admin.orders.show', $refund->bill) }}"
                                            class="font-black text-blue-600 hover:text-blue-800">
                                            {{ $refund->bill_id }}
                                        </a>
                                    @else
                                        <span class="font-black text-gray-900">{{ $refund->bill_id }}</span>
                                    @endif
                                </td>
                                <td class="px-8 py-5 text-sm font-bold text-gray-700">
                                    {{ $refund->user->name ?? 'Unknown' }}
                                </td>
                                <td class="px-8 py-5">
                                    <span class="{{ $refund->oz_delta >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50' }} px-3 py-1 rounded-full text-xs font-black">
                                        {{ $refund->oz_delta >= 0 ? '+' : '' }}{{ $refund->oz_delta }} OZ
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-sm font-bold text-gray-500">
                                    {{ $refund->description }}
                                </td>
                                <td class="px-8 py-5 text-sm font-bold text-gray-500">
                                    {{ $refund->created_at->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-12 text-center text-gray-400 font-bold uppercase tracking-widest">
                                    No refund records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-8">
                {{ $refunds->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
