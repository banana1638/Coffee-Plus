@props(['product'])

<a href="{{ route('product.detail', $product->id) }}" @guest @click.prevent="authModal = 'login'" @endguest
    {{ $attributes->merge(['class' => 'group flex min-w-0 flex-col overflow-hidden rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] shadow-sm transition duration-200 hover:border-emerald-300 focus-visible:border-emerald-500']) }}>
    <div class="aspect-[4/3] overflow-hidden bg-stone-100">
        <img src="{{ $product->image ? $product->thumbnail_image_url : 'https://placehold.co/640x480?text=' . urlencode($product->name) }}"
            class="h-full w-full object-cover" alt="{{ $product->name }}"
            onerror="this.src='https://placehold.co/640x480?text=Image+Missing'">
    </div>

    <div class="flex flex-1 flex-col p-4">
        <div class="flex items-start justify-between gap-3">
            <h4 class="min-w-0 font-semibold leading-5 text-[rgb(var(--cp-ink))]">{{ $product->name }}</h4>
            <x-ui.price :amount="$product->price" size="sm" accent class="shrink-0" />
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs text-[rgb(var(--cp-muted))]">
            <span aria-label="Rated {{ number_format($product->average_rating, 1) }} out of 5">
                <span class="text-amber-600" aria-hidden="true">&#9733;</span>
                {{ number_format($product->average_rating, 1) }}/5
            </span>
            <span>{{ number_format($product->oz_redeem_value ?? 0) }} OZ</span>
        </div>

        <span class="mt-4 inline-flex min-h-10 items-center justify-between border-t border-[rgb(var(--cp-line))] pt-3 text-sm font-semibold text-[rgb(var(--cp-brand))]">
            Configure drink
            <span aria-hidden="true">&rarr;</span>
        </span>
    </div>
</a>
