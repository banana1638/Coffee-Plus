<x-app-layout>
    <div class="py-8 bg-gray-50 min-h-screen pb-32">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between mb-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-black transition group">
                    <div class="p-2 bg-white rounded-xl shadow-sm mr-3 group-hover:bg-gray-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    Back to Menu
                </a>
                <h2 class="hidden md:block font-black text-xl text-gray-900">Customize Order</h2>
                <div class="w-24"></div> 
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                <div class="lg:sticky lg:top-8">
                    <div class="rounded-[3rem] overflow-hidden aspect-square bg-white shadow-xl shadow-gray-200/50 border border-white">
                        <img src="{{ $product->image_url }}" class="w-full h-full object-cover" alt="{{ $product->name }}">
                    </div>
                </div>

                <form action="{{ route('cart.add') }}" method="POST" id="orderForm">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <div class="mb-10 flex items-start justify-between">
                        <div>
                            <span class="text-blue-600 font-bold text-sm uppercase tracking-widest">Premium Selection</span>
                            <h1 class="text-4xl font-black text-gray-900 mt-1">{{ $product->name }}</h1>
                        </div>
                        <button type="button" onclick="toggleFavorite()" id="favoriteBtn" class="p-4 bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 text-gray-300 hover:text-red-500 transition-all active:scale-95 group">
                            <svg id="favoriteIcon" class="w-8 h-8 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </button>
                    </div>

                    <div id="favoriteRemarkContainer" class="mb-8 hidden">
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-gray-400 mb-3">Add a Personal Note</h3>
                        <textarea id="favoriteRemark" placeholder="e.g. My Monday Morning Coffee" class="w-full bg-white border-2 border-gray-100 rounded-2xl p-4 text-sm font-bold focus:border-blue-600 focus:ring-0 transition-all placeholder:text-gray-300 h-20 resize-none"></textarea>
                    </div>

                    <div class="mb-10">
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-gray-400 mb-5">Select Temperature</h3>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach($options['temps'] as $temp)
                                <label class="cursor-pointer">
                                    <input type="radio" name="temp" value="{{ $temp }}" class="hidden peer" {{ $loop->first ? 'checked' : '' }}>
                                    <div class="py-5 text-center rounded-2xl border-2 border-transparent bg-white shadow-sm font-bold text-gray-500 peer-checked:border-blue-600 peer-checked:text-blue-600 peer-checked:shadow-md transition-all">
                                        {{ $temp }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-10">
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-gray-400 mb-5">Cup Size</h3>
                        <div class="space-y-3">
                            @foreach($options['sizes'] as $size)
                                <label class="flex items-center p-5 rounded-2xl border-2 border-transparent bg-white shadow-sm cursor-pointer has-[:checked]:border-blue-600 transition-all hover:bg-gray-50">
                                    <input type="radio" name="size" value="{{ $size['name'] }}" data-extra="{{ $size['extra'] }}" class="w-5 h-5 text-blue-600 border-gray-300" {{ $loop->first ? 'checked' : '' }}>
                                    <div class="ml-4 flex justify-between w-full items-center">
                                        <span class="font-bold text-gray-700">{{ $size['name'] }}</span>
                                        @if($size['extra'] > 0)
                                            <span class="text-xs font-bold text-gray-400">+ RM {{ number_format($size['extra'], 2) }}</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if($product->addons->isNotEmpty())
                    <div class="mb-10">
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-gray-400 mb-5">Extra Add-ons</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($product->addons as $addon)
                                <label class="flex items-center justify-between p-5 rounded-2xl border-2 border-transparent bg-white shadow-sm cursor-pointer has-[:checked]:border-blue-600 transition-all">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="addons[]" value="{{ $addon->name }}" data-price="{{ $addon->price }}" class="w-5 h-5 rounded text-blue-600 border-gray-300">
                                        <span class="ml-4 font-bold text-gray-700">{{ $addon->name }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-blue-500">+ RM {{ number_format($addon->price, 2) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-xl border-t border-gray-100 px-6 py-6 z-50">
                        <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex items-center bg-gray-100 p-1.5 rounded-2xl shadow-inner">
                                <button type="button" onclick="changeQty(-1)" class="w-12 h-12 flex items-center justify-center font-black text-xl hover:text-blue-600 transition">－</button>
                                <input type="number" name="quantity" id="qtyInput" value="1" readonly class="w-14 bg-transparent border-none text-center font-black text-xl focus:ring-0">
                                <button type="button" onclick="changeQty(1)" class="w-12 h-12 flex items-center justify-center font-black text-xl hover:text-blue-600 transition">＋</button>
                            </div>
                            
                            <button type="submit" class="w-full md:w-auto md:min-w-[400px] bg-gray-900 text-white py-5 rounded-3xl font-black text-xl shadow-2xl hover:bg-blue-600 transition-all flex items-center justify-between px-10 group">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span>Add to Cart</span>
                                </div>
                                <div class="flex flex-col items-end border-l border-white/20 pl-6">
                                    <span class="text-[10px] uppercase tracking-widest text-white/50 leading-none mb-1">Estimated Total</span>
                                    <span id="realTimePrice" class="leading-none text-2xl">RM {{ number_format($product->price, 2) }}</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
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
            btn.innerHTML = `<span class="flex items-center"><svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" ...>...</svg> Processing...</span>`;

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
            }
        }

        const toggleModal = (id, show) => {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden', !show);
            modal.classList.toggle('flex', show);
        };

        // Favorite Functionality
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
                btn.classList.add('text-red-500');
                btn.classList.remove('text-gray-300');
                remarkContainer.classList.remove('hidden');
            } else {
                icon.setAttribute('fill', 'none');
                btn.classList.add('text-gray-300');
                btn.classList.remove('text-red-500');
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
            icon.classList.add('scale-150', 'animate-pulse');

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
                icon.classList.remove('scale-150', 'animate-pulse');
            }
        }

        // Initial check and listen for changes
        checkFavoriteStatus();
        orderForm.addEventListener('change', checkFavoriteStatus);
    </script>
</x-app-layout>