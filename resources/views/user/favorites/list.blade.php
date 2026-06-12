<div class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-semibold text-slate-950">My Collections</h3>
            <p class="text-sm text-slate-500">Saved combinations for quick reorder.</p>
        </div>
        <div class="h-px flex-1 bg-slate-200"></div>
    </div>

    @if($favorites->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-sm font-semibold text-slate-500">Your collection is empty.</p>
            <p class="mt-1 text-sm text-slate-500">Save your favorite coffee combinations here.</p>
            <x-ui.button :href="route('dashboard')" class="mt-4">
                Browse Menu
            </x-ui.button>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
            @foreach($favorites as $favorite)
                @php
                    $product = $favorite->product;
                @endphp
                <div class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <a href="{{ route('product.detail', $product->id) }}?favorite_id={{ $favorite->id }}" class="relative block aspect-square overflow-hidden bg-slate-100">
                        @if($product->image)
                            <img src="{{ $product->thumbnail_image_url }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                alt="{{ $product->name }}" onerror="this.src='https://placehold.co/400x400?text=Image+Missing'">
                        @else
                            <img src="https://placehold.co/400x400?text={{ urlencode($product->name) }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105" alt="{{ $product->name }}">
                        @endif

                        <div class="absolute right-3 top-3 rounded-lg border border-white/70 bg-white/95 px-2.5 py-1 text-xs font-semibold text-slate-950 shadow-sm">
                            {{ $favorite->temp }}
                        </div>

                        <div class="absolute bottom-3 left-3 right-3 translate-y-2 opacity-0 transition duration-200 group-hover:translate-y-0 group-hover:opacity-100">
                            <button onclick="removeFromFavorites(event, {{ $favorite->id }})" class="w-full rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-700">
                                Remove
                            </button>
                        </div>
                    </a>

                    <div class="flex flex-1 flex-col p-4">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <h4 class="min-w-0 flex-1 truncate font-semibold text-slate-950">{{ $product->name }}</h4>
                            <x-ui.badge variant="info">{{ $favorite->size }}</x-ui.badge>
                        </div>

                        @if(!empty($favorite->addons))
                            <div class="mb-3 flex flex-wrap gap-1">
                                @foreach($favorite->addons as $addon)
                                    <x-ui.badge>{{ $addon }}</x-ui.badge>
                                @endforeach
                            </div>
                        @endif

                        @if($favorite->remark)
                            <p class="mb-3 line-clamp-2 text-xs text-slate-500">"{{ $favorite->remark }}"</p>
                        @endif

                        <div class="mt-auto flex items-center justify-between border-t border-slate-200 pt-4">
                            <p class="text-sm font-semibold text-slate-950">RM {{ number_format($product->price, 2) }}</p>
                            <form action="{{ route('cart.add') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="size" value="{{ $favorite->size }}">
                                <input type="hidden" name="temp" value="{{ $favorite->temp }}">
                                @foreach($favorite->addons as $addon)
                                    <input type="hidden" name="addons[]" value="{{ $addon }}">
                                @endforeach
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-white transition hover:bg-indigo-700" aria-label="Add favorite to cart">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
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
