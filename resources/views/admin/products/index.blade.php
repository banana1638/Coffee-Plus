<x-admin-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Product Manager</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-[0.2em] mt-1">Inventory Control</p>
                </div>
                <a href="{{ route('admin.products.create') }}" class="px-8 py-4 bg-gray-900 text-white rounded-[1.5rem] font-black text-sm shadow-xl shadow-gray-200 hover:bg-blue-600 transition-all hover:-translate-y-1">
                    + ADD NEW COFFEE
                </a>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-500 text-white rounded-2xl font-bold shadow-lg shadow-green-100 flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/50 border-b border-gray-50">
                        <tr>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Product info</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Category</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pricing</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">Management</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($products as $product)
                        <tr class="group hover:bg-gray-50/50 transition-all">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-5">
                                    <div class="w-16 h-16 rounded-[1.2rem] bg-gray-100 overflow-hidden border border-gray-100 shrink-0">
                                        @if($product->image)
                                            <img src="{{ asset('images/products/'.$product->image) }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-[10px] text-gray-300 font-bold uppercase">No Img</div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900 text-lg leading-tight">{{ $product->name }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold mt-1">ID: #{{ str_pad($product->id, 4, '0', STR_PAD_LEFT) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="px-4 py-1.5 bg-gray-100 text-gray-500 rounded-full text-[10px] font-black uppercase tracking-widest">
                                    {{ $product->menu->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <p class="text-blue-600 font-black text-lg">RM {{ number_format($product->price, 2) }}</p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $product->oz_redeem_value }} oz redeem</p>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-3 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-all">
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="px-5 py-2.5 bg-gray-900 text-white rounded-xl text-xs font-black hover:bg-blue-600 transition-all shadow-lg shadow-gray-200">
                                        EDIT
                                    </a>
                                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('⚠️ Are you sure you want to delete this product?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-5 py-2.5 bg-red-50 text-red-600 rounded-xl text-xs font-black hover:bg-red-600 hover:text-white transition-all">
                                            DELETE
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>