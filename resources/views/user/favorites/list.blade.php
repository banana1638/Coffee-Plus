<div class="space-y-7">
    <div class="flex items-end justify-between gap-4 border-b border-[rgb(var(--cp-line))] pb-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgb(var(--cp-brand))]">Ready to reorder</p>
            <h3 class="mt-2 font-display text-3xl font-medium text-[rgb(var(--cp-ink))] sm:text-4xl">Saved recipes</h3>
            <p class="mt-2 text-sm text-[rgb(var(--cp-muted))]">Your preferred size, temperature, and add-ons.</p>
        </div>
        <span class="hidden text-xs font-semibold uppercase text-[rgb(var(--cp-muted))] sm:inline">Quick reorder</span>
    </div>

    @if($favorites->isEmpty())
        <div class="border-y border-dashed border-[rgb(var(--cp-line))] py-14 text-center">
            <p class="font-display text-2xl font-medium text-[rgb(var(--cp-ink))]">No saved recipes yet</p>
            <p class="mt-1 text-sm text-[rgb(var(--cp-muted))]">Configure a drink and save it for faster ordering.</p>
            <x-ui.button :href="route('dashboard')" class="mt-4">
                Browse Menu
            </x-ui.button>
        </div>
    @else
        <div class="cp-menu-grid grid gap-x-5 gap-y-8 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($favorites as $favorite)
                @php
                    $product = $favorite->product;
                @endphp
                <article class="group flex flex-col">
                    <a href="{{ route('product.detail', $product->id) }}?favorite_id={{ $favorite->id }}" class="relative block aspect-[5/4] overflow-hidden rounded-lg bg-stone-100">
                        @if($product->image)
                            <img src="{{ $product->thumbnail_image_url }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]"
                                alt="{{ $product->name }}" onerror="this.src='https://placehold.co/400x400?text=Image+Missing'">
                        @else
                            <img src="https://placehold.co/400x400?text={{ urlencode($product->name) }}"
                                class="h-full w-full object-cover" alt="{{ $product->name }}">
                        @endif

                        <div class="absolute right-3 top-3 rounded-md border border-white/70 bg-white/95 px-2 py-1 text-xs font-semibold text-slate-950 shadow-sm">
                            {{ $favorite->temp }}
                        </div>

                    </a>

                    <div class="flex flex-1 flex-col border-b border-[rgb(var(--cp-line))] px-1 pb-5 pt-4 transition group-hover:border-emerald-300">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <h4 class="min-w-0 flex-1 font-display text-2xl font-medium text-[rgb(var(--cp-ink))]">{{ $product->name }}</h4>
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

                        <div class="mt-auto flex items-center justify-between border-t border-[rgb(var(--cp-line))] pt-4">
                            <div>
                                <x-ui.price :amount="$product->price" size="sm" accent />
                                <button type="button" onclick="removeFromFavorites(event, {{ $favorite->id }})" class="mt-1 text-xs font-semibold text-rose-700 hover:text-rose-900">
                                    Remove recipe
                                </button>
                            </div>
                            <form action="{{ route('cart.add') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="size" value="{{ $favorite->size }}">
                                <input type="hidden" name="temp" value="{{ $favorite->temp }}">
                                @foreach($favorite->addons as $addon)
                                    <input type="hidden" name="addons[]" value="{{ $addon }}">
                                @endforeach
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-[rgb(var(--cp-brand))] text-white transition hover:bg-[rgb(var(--cp-brand-strong))]" aria-label="Add {{ $product->name }} recipe to cart">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if($favorites->hasPages())
            <div>
                {{ $favorites->links() }}
            </div>
        @endif
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
