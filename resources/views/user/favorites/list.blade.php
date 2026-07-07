<div class="space-y-4">
    <div class="flex items-end justify-between gap-4 border-b border-[rgb(var(--cp-line))] pb-3">
        <div>
            <h3 class="text-lg font-bold text-[rgb(var(--cp-ink))]">Saved recipes</h3>
            <p class="mt-1 text-sm text-[rgb(var(--cp-muted))]">Your preferred size, temperature, and add-ons.</p>
        </div>
        <span class="hidden text-xs font-semibold uppercase text-[rgb(var(--cp-muted))] sm:inline">Quick reorder</span>
    </div>

    @if($favorites->isEmpty())
        <div class="rounded-lg border border-dashed border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-10 text-center">
            <p class="font-semibold text-[rgb(var(--cp-ink))]">No saved recipes yet</p>
            <p class="mt-1 text-sm text-[rgb(var(--cp-muted))]">Configure a drink and save it for faster ordering.</p>
            <x-ui.button :href="route('dashboard')" class="mt-4">
                Browse Menu
            </x-ui.button>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($favorites as $favorite)
                @php
                    $product = $favorite->product;
                @endphp
                <article class="flex flex-col overflow-hidden rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] shadow-sm">
                    <a href="{{ route('product.detail', $product->id) }}?favorite_id={{ $favorite->id }}" class="relative block aspect-[4/3] overflow-hidden bg-stone-100">
                        @if($product->image)
                            <img src="{{ $product->thumbnail_image_url }}"
                                class="h-full w-full object-cover"
                                alt="{{ $product->name }}" onerror="this.src='https://placehold.co/400x400?text=Image+Missing'">
                        @else
                            <img src="https://placehold.co/400x400?text={{ urlencode($product->name) }}"
                                class="h-full w-full object-cover" alt="{{ $product->name }}">
                        @endif

                        <div class="absolute right-3 top-3 rounded-md border border-white/70 bg-white/95 px-2 py-1 text-xs font-semibold text-slate-950 shadow-sm">
                            {{ $favorite->temp }}
                        </div>

                    </a>

                    <div class="flex flex-1 flex-col p-4">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <h4 class="min-w-0 flex-1 font-semibold text-[rgb(var(--cp-ink))]">{{ $product->name }}</h4>
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
                                <p class="cp-tabular text-sm font-bold text-[rgb(var(--cp-ink))]">RM {{ number_format($product->price, 2) }}</p>
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
