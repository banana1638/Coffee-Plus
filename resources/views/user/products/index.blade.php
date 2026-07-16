<div class="space-y-16">
    @forelse($menus as $menu)
        <section aria-labelledby="menu-{{ $menu->id }}" class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgb(var(--cp-brand))]">Made to order</p>
                    <h3 id="menu-{{ $menu->id }}" class="mt-1 font-display text-3xl font-medium tracking-[-0.025em] text-[rgb(var(--cp-ink))] sm:text-4xl">{{ $menu->name }}</h3>
                </div>
                <p class="text-sm text-[rgb(var(--cp-muted))]">
                    <span class="cp-tabular font-bold text-[rgb(var(--cp-ink))]">{{ $menu->products->count() }}</span>
                    {{ Str::plural('item', $menu->products->count()) }} available
                </p>
            </div>

            <div class="cp-menu-grid grid gap-x-5 gap-y-8 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($menu->products as $product)
                    <x-product-offer :product="$product" />
                @endforeach
            </div>
        </section>
    @empty
        <div class="border-y border-dashed border-[rgb(var(--cp-line))] py-16 text-center">
            <p class="font-display text-3xl font-medium text-[rgb(var(--cp-ink))]">Nothing matched that order.</p>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-[rgb(var(--cp-muted))]">Try another category, use a shorter search, or return to the full menu.</p>
            <a href="{{ route('dashboard') }}#menu" class="mt-5 inline-flex text-sm font-semibold text-[rgb(var(--cp-brand))] hover:text-[rgb(var(--cp-brand-strong))]">
                Clear search and filters <span class="ml-2" aria-hidden="true">&#8594;</span>
            </a>
        </div>
    @endforelse
</div>
