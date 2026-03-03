<div class="mt-8 space-y-12">
    @forelse($menus as $menu)
        <section>
            <div class="flex items-center gap-4 mb-6">
                <h3 class="text-lg font-black text-gray-800 uppercase tracking-wider">{{ $menu->name }}</h3>
                <div class="h-px flex-1 bg-gray-200"></div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($menu->products as $product)
                    <a href="{{ route('product.detail', $product->id) }}" @guest @click.prevent="authModal = 'login'" @endguest
                        class="group bg-white rounded-[2rem] border border-gray-100 overflow-hidden hover:shadow-xl transition-all">

                        <div class="aspect-square bg-gray-100 relative">
                            @if($product->image)
                                <img src="{{ asset('images/products/' . $product->image) }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    alt="{{ $product->name }}" onerror="this.src='https://placehold.co/400x400?text=Image+Missing'">
                            @else
                                <img src="https://placehold.co/400x400?text={{ urlencode($product->name) }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @endif

                            <div
                                class="absolute top-4 right-4 bg-white/90 backdrop-blur px-3 py-1 rounded-lg text-xs font-bold shadow-sm">
                                RM {{ number_format($product->price, 2) }}
                            </div>
                        </div>

                        <div class="p-4">
                            <h4 class="font-bold text-gray-900 truncate">{{ $product->name }}</h4>
                            <p class="text-[10px] text-blue-500 font-bold mt-1 uppercase tracking-tighter">
                                {{ $product->oz_redeem_value ?? 0 }} oz required
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="py-20 text-center">
            <div class="inline-block p-6 bg-gray-100 rounded-full mb-4">
                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <p class="text-gray-400 font-medium text-lg">No coffees found.</p>
            <a href="{{ route('dashboard') }}" class="text-blue-600 font-bold mt-2 inline-block hover:underline">Clear
                search and filters</a>
        </div>
    @endforelse
</div>