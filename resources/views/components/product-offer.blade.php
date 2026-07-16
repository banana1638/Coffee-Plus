@props(['product'])

<article {{ $attributes->class(['group min-w-0']) }}>
    <a href="{{ route('product.detail', $product->id) }}" @guest @click.prevent="authModal = 'login'" @endguest
        class="block rounded-lg focus-visible:outline-none">
        <div class="relative aspect-[5/4] overflow-hidden rounded-lg bg-stone-200">
            <img src="{{ $product->image ? $product->thumbnail_image_url : 'https://placehold.co/800x640?text=' . urlencode($product->name) }}"
                class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.035]"
                alt="{{ $product->name }}" loading="lazy"
                onerror="this.src='https://placehold.co/800x640?text=Image+Missing'">

            <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-md border border-white/70 bg-white/95 px-2.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.1em] text-slate-800 shadow-sm">
                <span class="h-1.5 w-1.5 rounded-full {{ $product->is_active ? 'bg-emerald-600' : 'bg-rose-500' }}" aria-hidden="true"></span>
                {{ $product->is_active ? 'Available' : 'Unavailable' }}
            </span>
        </div>

        <div class="border-b border-[rgb(var(--cp-line))] px-1 pb-5 pt-4 transition group-hover:border-emerald-300">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h4 class="font-display text-2xl font-medium leading-tight tracking-[-0.02em] text-[rgb(var(--cp-ink))] transition group-hover:text-[rgb(var(--cp-brand-strong))]">{{ $product->name }}</h4>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-medium text-[rgb(var(--cp-muted))]">
                        @if($product->reviews_count > 0)
                            <span aria-label="Rated {{ number_format($product->average_rating, 1) }} out of 5 from {{ $product->reviews_count }} reviews">
                                <span class="text-[rgb(var(--cp-caramel))]" aria-hidden="true">&#9733;</span>
                                {{ number_format($product->average_rating, 1) }}/5
                                <span class="text-slate-400">({{ $product->reviews_count }})</span>
                            </span>
                        @else
                            <span>New to the menu</span>
                        @endif
                        <span class="cp-tabular">{{ number_format($product->oz_redeem_value ?? 0) }} OZ</span>
                    </div>
                </div>
                <x-ui.price :amount="$product->price" size="lg" accent class="shrink-0" />
            </div>

            <div class="mt-5 flex items-center justify-between text-sm font-semibold text-[rgb(var(--cp-brand))]">
                <span>{{ $product->is_active ? 'Customise this item' : 'View item details' }}</span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-emerald-200 transition duration-200 group-hover:border-[rgb(var(--cp-brand))] group-hover:bg-[rgb(var(--cp-brand))] group-hover:text-white" aria-hidden="true">&#8594;</span>
            </div>
        </div>
    </a>
</article>
