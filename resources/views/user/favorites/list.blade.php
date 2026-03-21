<div class="mt-8">
    <div class="flex items-center gap-4 mb-6">
        <h3 class="text-lg font-black text-gray-800 uppercase tracking-wider">My Collections</h3>
        <div class="h-px flex-1 bg-gray-200"></div>
    </div>

    @if($favorites->isEmpty())
        <div class="py-20 text-center bg-white rounded-[3rem] border border-dashed border-gray-200">
            <div class="inline-block p-6 bg-gray-50 rounded-full mb-4">
                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
            <p class="text-gray-400 font-medium text-lg">Your collection is empty.</p>
            <p class="text-gray-400 text-sm mt-1">Save your favorite coffee combinations here!</p>
            <a href="{{ route('dashboard') }}" class="text-blue-600 font-bold mt-4 inline-block hover:underline">Browse Menu</a>
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($favorites as $favorite)
                @php
                    $product = $favorite->product;
                @endphp
                <div class="group bg-white rounded-[2.5rem] border border-gray-100 overflow-hidden hover:shadow-2xl transition-all duration-500 flex flex-col">
                    <a href="{{ route('product.detail', $product->id) }}?favorite_id={{ $favorite->id }}" class="block relative aspect-square bg-gray-50 overflow-hidden">
                        @if($product->image)
                            <img src="{{ asset('images/products/' . $product->image) }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                alt="{{ $product->name }}" onerror="this.src='https://placehold.co/400x400?text=Image+Missing'">
                        @else
                            <img src="https://placehold.co/400x400?text={{ urlencode($product->name) }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        @endif

                        <div class="absolute top-4 right-4 bg-white/95 backdrop-blur-md px-3 py-1.5 rounded-2xl text-[10px] font-black shadow-xl border border-white/50">
                            {{ $favorite->temp }}
                        </div>

                        <div class="absolute bottom-4 left-4 right-4 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                            <button onclick="removeFromFavorites(event, {{ $favorite->id }})" class="w-full bg-red-500 text-white py-2 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:bg-red-600 transition-colors">
                                Remove
                            </button>
                        </div>
                    </a>

                    <div class="p-5 flex-1 flex flex-col">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-extrabold text-gray-900 truncate flex-1 pr-2">{{ $product->name }}</h4>
                            <span class="text-xs font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded-lg">
                                {{ $favorite->size }}
                            </span>
                        </div>
                        
                        @if(!empty($favorite->addons))
                            <div class="flex flex-wrap gap-1 mb-3">
                                @foreach($favorite->addons as $addon)
                                    <span class="text-[9px] font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100">{{ $addon }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if($favorite->remark)
                            <p class="text-[10px] italic text-gray-400 line-clamp-2 mb-3">"{{ $favorite->remark }}"</p>
                        @endif

                        <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
                             <p class="text-xs font-black text-gray-800">RM {{ number_format($product->price, 2) }}</p>
                             <form action="{{ route('cart.add') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="size" value="{{ $favorite->size }}">
                                <input type="hidden" name="temp" value="{{ $favorite->temp }}">
                                @foreach($favorite->addons as $addon)
                                    <input type="hidden" name="addons[]" value="{{ $addon }}">
                                @endforeach
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="p-2 bg-gray-900 text-white rounded-xl hover:bg-blue-600 transition-all shadow-md group/btn">
                                    <svg class="w-4 h-4 group-hover/btn:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                             </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    function removeFromFavorites(event, id) {
        event.preventDefault();
        event.stopPropagation();
        
        if (!confirm('Remove from collections?')) return;

        // Note: Using the web endpoint
        fetch(`/favorites/${id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(response => {
            if (response.ok) {
                window.location.reload();
            }
        });
    }
</script>
