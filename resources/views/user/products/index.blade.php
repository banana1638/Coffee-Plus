<div class="space-y-12">
    @forelse($menus as $menu)
        <section class="space-y-4">
            <div class="flex items-end justify-between gap-4 border-b border-[rgb(var(--cp-line))] pb-3">
                <div>
                    <h3 class="text-lg font-bold text-[rgb(var(--cp-ink))]">{{ $menu->name }}</h3>
                    <p class="mt-1 text-sm text-[rgb(var(--cp-muted))]">{{ $menu->products->count() }} available items</p>
                </div>
                <span class="hidden text-xs font-semibold uppercase text-[rgb(var(--cp-muted))] sm:inline">Made to order</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($menu->products as $product)
                    <x-product-offer :product="$product" />
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-lg border border-dashed border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-10 text-center">
            <p class="font-semibold text-[rgb(var(--cp-ink))]">No matching drinks</p>
            <p class="mt-1 text-sm text-[rgb(var(--cp-muted))]">Try another category or clear the current search.</p>
            <a href="{{ route('dashboard') }}" class="mt-4 inline-flex text-sm font-semibold text-[rgb(var(--cp-brand))] hover:text-[rgb(var(--cp-brand-strong))]">
                Clear search and filters
            </a>
        </div>
    @endforelse
</div>
