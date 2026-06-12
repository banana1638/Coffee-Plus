<div class="space-y-10">
    @forelse($menus as $menu)
        <section class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-950">{{ $menu->name }}</h3>
                    <p class="text-sm text-slate-500">{{ $menu->products->count() }} available items</p>
                </div>
                <div class="h-px flex-1 bg-slate-200"></div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                @foreach($menu->products as $product)
                    <a href="{{ route('product.detail', $product->id) }}" @guest @click.prevent="authModal = 'login'" @endguest
                        class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="relative aspect-square bg-slate-100">
                            @if($product->image)
                                <img src="{{ $product->thumbnail_image_url }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                    alt="{{ $product->name }}" onerror="this.src='https://placehold.co/400x400?text=Image+Missing'">
                            @else
                                <img src="https://placehold.co/400x400?text={{ urlencode($product->name) }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105" alt="{{ $product->name }}">
                            @endif

                            <div class="absolute right-3 top-3 rounded-lg border border-white/70 bg-white/95 px-2.5 py-1 text-xs font-semibold text-slate-950 shadow-sm">
                                RM {{ number_format($product->price, 2) }}
                            </div>
                        </div>

                        <div class="space-y-2 p-4">
                            <h4 class="truncate font-semibold text-slate-950">{{ $product->name }}</h4>
                            <div class="flex items-center justify-between gap-2">
                                <x-ui.badge variant="info">{{ $product->oz_redeem_value ?? 0 }} oz</x-ui.badge>
                                <span class="text-xs font-medium text-slate-500">{{ number_format($product->average_rating, 1) }}/5</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-sm font-semibold text-slate-500">No coffees found.</p>
            <a href="{{ route('dashboard') }}" class="mt-2 inline-flex text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                Clear search and filters
            </a>
        </div>
    @endforelse
</div>
