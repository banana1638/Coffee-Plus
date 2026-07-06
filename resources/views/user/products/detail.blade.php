<x-app-layout>
    <div class="px-4 py-6 pb-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex items-center justify-between">
                <x-ui.button :href="route('dashboard')" variant="secondary">
                    Back to Menu
                </x-ui.button>
                <x-ui.badge variant="info">Drink recipe</x-ui.badge>
            </div>

            <div class="grid gap-8 lg:grid-cols-2 lg:items-start">
                <div class="lg:sticky lg:top-24 lg:self-start">
                    <div class="overflow-hidden rounded-lg border border-[rgb(var(--cp-line))] bg-stone-100 shadow-sm">
                        <img src="{{ $product->detail_image_url }}" class="aspect-[4/3] w-full object-cover lg:aspect-square" alt="{{ $product->name }}">
                    </div>
                </div>

                <form action="{{ route('cart.add') }}" method="POST" id="orderForm" class="space-y-8">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-brand))]">Made to order</p>
                            <h1 class="mt-1 text-3xl font-bold text-[rgb(var(--cp-ink))]">{{ $product->name }}</h1>
                            <p class="mt-2 text-sm text-[rgb(var(--cp-muted))]">
                                {{ number_format($product->average_rating, 1) }} / 5 from {{ $product->reviews_count }} reviews
                            </p>
                        </div>
                        <button type="button" onclick="toggleFavorite()" id="favoriteBtn"
                            class="inline-flex h-12 w-12 items-center justify-center rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] text-slate-300 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-500 active:translate-y-px"
                            aria-label="Toggle favorite">
                            <svg id="favoriteIcon" class="h-6 w-6 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                    </div>

                    <div id="favoriteRemarkContainer" class="hidden space-y-2">
                        <label for="favoriteRemark" class="text-sm font-medium text-slate-700">Personal Note</label>
                        <textarea id="favoriteRemark" placeholder="e.g. My Monday Morning Coffee"
                            class="h-24 w-full resize-none rounded-lg border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] text-sm font-medium text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                    </div>

                    <x-ui.card>
                        <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-muted))]">Step 1</p>
                        <h2 class="mt-1 text-base font-bold text-[rgb(var(--cp-ink))]">Temperature</h2>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            @foreach($options['temps'] as $temp)
                                <label class="cursor-pointer">
                                    <input type="radio" name="temp" value="{{ $temp }}" class="peer sr-only" {{ $loop->first ? 'checked' : '' }}>
                                    <span class="block rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] px-4 py-3 text-center text-sm font-semibold text-[rgb(var(--cp-muted))] transition peer-checked:border-emerald-700 peer-checked:bg-emerald-50 peer-checked:text-emerald-800 hover:border-emerald-300">
                                        {{ $temp }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-muted))]">Step 2</p>
                        <h2 class="mt-1 text-base font-bold text-[rgb(var(--cp-ink))]">Cup size</h2>
                        <div class="mt-4 space-y-3">
                            @foreach($options['sizes'] as $size)
                                <label class="flex cursor-pointer items-center rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-4 transition has-[:checked]:border-emerald-700 has-[:checked]:bg-emerald-50 hover:border-emerald-300">
                                    <input type="radio" name="size" value="{{ $size['name'] }}" data-extra="{{ $size['extra'] }}"
                                        class="h-4 w-4 border-slate-300 text-emerald-700 focus:ring-emerald-600" {{ $loop->first ? 'checked' : '' }}>
                                    <span class="ml-3 flex w-full items-center justify-between gap-3">
                                        <span class="font-semibold text-slate-700">{{ $size['name'] }}</span>
                                        @if($size['extra'] > 0)
                                            <span class="text-sm font-medium text-slate-500">+ RM {{ number_format($size['extra'], 2) }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </x-ui.card>

                    @if($product->addons->isNotEmpty())
                        <x-ui.card>
                            <p class="text-xs font-semibold uppercase text-[rgb(var(--cp-muted))]">Step 3</p>
                            <h2 class="mt-1 text-base font-bold text-[rgb(var(--cp-ink))]">Add-ons</h2>
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                @foreach($product->addons as $addon)
                                    <label class="flex cursor-pointer items-center justify-between rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] p-4 transition has-[:checked]:border-emerald-700 has-[:checked]:bg-emerald-50 hover:border-emerald-300">
                                        <span class="flex items-center">
                                            <input type="checkbox" name="addons[]" value="{{ $addon->name }}" data-price="{{ $addon->price }}"
                                                class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                                            <span class="ml-3 font-semibold text-slate-700">{{ $addon->name }}</span>
                                        </span>
                                        <span class="cp-tabular text-sm font-semibold text-[rgb(var(--cp-brand-strong))]">+ RM {{ number_format($addon->price, 2) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </x-ui.card>
                    @endif
                </form>
            </div>

            <x-ui.card>
                <h2 class="text-base font-bold text-[rgb(var(--cp-ink))]">Recent customer reviews</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @forelse($product->reviews as $review)
                        <article class="border-l-2 border-amber-400 bg-amber-50/40 p-4">
                            <div class="flex justify-between gap-3">
                                <p class="font-semibold text-slate-950">{{ $review->user->name ?? 'Customer' }}</p>
                                <p class="text-sm font-semibold text-amber-700">{{ $review->rating }} / 5</p>
                            </div>
                            @if($review->comment)
                                <p class="mt-2 text-sm text-slate-600">{{ $review->comment }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm font-semibold text-slate-500">No reviews yet.</p>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 z-50 border-t border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))]/95 px-4 py-4 shadow-lg backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center rounded-lg border border-[rgb(var(--cp-line))] bg-stone-100 p-1" aria-label="Quantity selector">
                <button type="button" onclick="changeQty(-1)" class="flex h-10 w-10 items-center justify-center rounded-lg text-lg font-semibold text-slate-700 hover:bg-white" aria-label="Decrease quantity">-</button>
                <input type="number" name="quantity" id="qtyInput" value="1" readonly form="orderForm"
                    class="w-14 border-0 bg-transparent text-center text-lg font-semibold text-slate-950 focus:ring-0">
                <button type="button" onclick="changeQty(1)" class="flex h-10 w-10 items-center justify-center rounded-lg text-lg font-semibold text-slate-700 hover:bg-white" aria-label="Increase quantity">+</button>
            </div>

            <button type="submit" form="orderForm"
                class="inline-flex w-full items-center justify-between gap-6 rounded-lg bg-[rgb(var(--cp-brand))] px-6 py-4 text-white shadow-sm transition hover:bg-[rgb(var(--cp-brand-strong))] active:translate-y-px md:w-auto md:min-w-[360px]">
                <span class="font-semibold">Add to Cart</span>
                <span class="border-l border-white/20 pl-6 text-right">
                    <span class="block text-xs text-white/70">Estimated, confirmed in cart</span>
                    <span id="realTimePrice" class="cp-tabular text-lg font-bold">RM {{ number_format($product->price, 2) }}</span>
                </span>
            </button>
        </div>
    </div>

    @include('components.order-modals')

    <script>
        const BASE_PRICE = {{ $product->price }};
        const qtyInput = document.getElementById('qtyInput');
        const priceDisplay = document.getElementById('realTimePrice');
        const orderForm = document.getElementById('orderForm');

        const updatePreviewPrice = () => {
            let extra = 0;
            const qty = parseInt(qtyInput.value) || 1;
            const size = document.querySelector('input[name="size"]:checked');
            if (size) extra += parseFloat(size.dataset.extra || 0);
            document.querySelectorAll('input[name="addons[]"]:checked').forEach(el => {
                extra += parseFloat(el.dataset.price || 0);
            });
            const total = (BASE_PRICE + extra) * qty;
            priceDisplay.innerText = `RM ${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        };

        const changeQty = (val) => {
            qtyInput.value = Math.max(1, parseInt(qtyInput.value) + val);
            updatePreviewPrice();
        };

        orderForm.addEventListener('change', updatePreviewPrice);
        orderForm.onsubmit = (e) => {
            e.preventDefault();
            toggleModal('confirmModal', true);
        };

        async function executeSubmit() {
            const btn = document.getElementById('btnConfirm');
            if (btn.disabled) return;

            btn.disabled = true;
            btn.innerHTML = 'Processing...';

            try {
                const response = await fetch("{{ route('cart.add') }}", {
                    method: 'POST',
                    body: new FormData(orderForm),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (response.ok) {
                    toggleModal('confirmModal', false);
                    const badge = document.getElementById('cart-badge');
                    if (badge) {
                        badge.innerText = result.cartCount;
                        badge.classList.remove('hidden');
                    }
                    toggleModal('successModal', true);
                } else {
                    alert(result.message || 'Validation error');
                    btn.disabled = false;
                    btn.innerText = 'Confirm Order';
                }
            } catch (e) {
                console.error(e);
                btn.disabled = false;
                btn.innerText = 'Confirm Order';
            }
        }

        const toggleModal = (id, show) => {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden', !show);
            modal.classList.toggle('flex', show);
        };

        async function checkFavoriteStatus() {
            const size = document.querySelector('input[name="size"]:checked').value;
            const temp = document.querySelector('input[name="temp"]:checked').value;
            const addons = Array.from(document.querySelectorAll('input[name="addons[]"]:checked')).map(el => el.value);

            const params = new URLSearchParams({
                product_id: "{{ $product->id }}",
                size: size,
                temp: temp,
            });
            addons.forEach(a => params.append('addons[]', a));

            const response = await fetch("{{ route('favorites.check') }}?" + params.toString());
            const data = await response.json();
            const icon = document.getElementById('favoriteIcon');
            const btn = document.getElementById('favoriteBtn');
            const remarkContainer = document.getElementById('favoriteRemarkContainer');

            if (data.is_favorite) {
                icon.setAttribute('fill', 'currentColor');
                btn.classList.add('text-rose-500');
                btn.classList.remove('text-slate-300');
                remarkContainer.classList.remove('hidden');
            } else {
                icon.setAttribute('fill', 'none');
                btn.classList.add('text-slate-300');
                btn.classList.remove('text-rose-500');
                remarkContainer.classList.add('hidden');
            }
        }

        async function toggleFavorite() {
            @guest
                alert('Please login first');
                return;
            @endguest

            const size = document.querySelector('input[name="size"]:checked').value;
            const temp = document.querySelector('input[name="temp"]:checked').value;
            const addons = Array.from(document.querySelectorAll('input[name="addons[]"]:checked')).map(el => el.value);
            const remark = document.getElementById('favoriteRemark').value;
            const icon = document.getElementById('favoriteIcon');
            icon.classList.add('scale-125');

            try {
                const response = await fetch("{{ route('favorites.toggle') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        product_id: "{{ $product->id }}",
                        size: size,
                        temp: temp,
                        addons: addons,
                        remark: remark
                    })
                });

                if (response.ok) {
                    await checkFavoriteStatus();
                }
            } finally {
                icon.classList.remove('scale-125');
            }
        }

        checkFavoriteStatus();
        orderForm.addEventListener('change', checkFavoriteStatus);
    </script>
</x-app-layout>
