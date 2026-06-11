<x-app-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <x-layout.page-header title="My Cart" description="Review items, choose Tangki redemption, and complete checkout.">
                <x-slot:actions>
                    <x-ui.badge variant="info">{{ $cartItems->sum('quantity') }} items</x-ui.badge>
                    <x-ui.button :href="route('dashboard')" variant="secondary">
                        Back to Menu
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            @if(session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            <x-ui.card>
                <div class="grid gap-6 md:grid-cols-[180px_1fr] md:items-center">
                    <div class="mx-auto w-36">
                        @include('components.tank-visualization')
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-medium text-slate-500">Current Storage</p>
                            <p class="mt-2 text-3xl font-semibold text-indigo-700">
                                <span id="user-balance" data-balance="{{ Auth::user()->tangki_oz }}">{{ number_format(Auth::user()->tangki_oz) }}</span>
                                <span class="text-sm text-slate-500">OZ</span>
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-medium text-slate-500">Account Balance</p>
                            <p class="mt-2 text-3xl font-semibold text-slate-950">
                                <span class="text-sm text-slate-500">RM</span>{{ number_format(Auth::user()->tangki_balance, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            @if($cartItems->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <p class="text-sm font-semibold text-slate-500">Your cart is empty.</p>
                    <x-ui.button :href="route('dashboard')" class="mt-4">
                        Browse Products
                    </x-ui.button>
                </div>
            @else
                <form action="{{ route('order.checkout') }}" method="POST" id="checkout-form" class="space-y-4">
                    @csrf
                    @foreach($cartItems as $item)
                        @php
                            $itemTotalCash = $item->unit_price * $item->quantity;
                            $itemTotalCashCents = (int) round($itemTotalCash * 100);
                            $itemTotalOz = $itemTotalCashCents;
                        @endphp

                        <x-ui.card padding="compact" class="group">
                            <div class="grid gap-4 md:grid-cols-[88px_1fr_170px] md:items-center">
                                <div class="h-20 w-20 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                    <img src="{{ $item->product->image_url }}" class="h-20 w-20 object-cover" alt="{{ $item->product->name }}">
                                </div>

                                <div>
                                    <h3 class="font-semibold text-slate-950">{{ $item->product->name }}</h3>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <x-ui.badge>{{ $item->size }}</x-ui.badge>
                                        <x-ui.badge>{{ $item->temp }}</x-ui.badge>
                                        @foreach($item->addons as $addon)
                                            <x-ui.badge variant="info">+ {{ $addon }}</x-ui.badge>
                                        @endforeach
                                    </div>
                                    <p class="mt-3 font-semibold text-indigo-700 item-price-label"
                                        data-cash="RM {{ number_format($itemTotalCash, 2) }}">
                                        RM {{ number_format($itemTotalCash, 2) }}
                                    </p>
                                </div>

                                <div class="flex flex-col items-start gap-2 md:items-end">
                                    <label class="relative inline-flex cursor-pointer items-center gap-3 transition-opacity oz-label">
                                        <input type="checkbox" name="use_oz[]" value="{{ $item->id }}"
                                            class="peer sr-only oz-checkbox"
                                            data-price="{{ $itemTotalCash }}"
                                            data-price-cents="{{ $itemTotalCashCents }}"
                                            data-oz-needed="{{ $itemTotalOz }}">
                                        <span class="h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white"></span>
                                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 peer-checked:text-indigo-700">Redeem</span>
                                    </label>
                                    <span class="text-xs font-medium text-slate-400">{{ number_format($itemTotalOz) }} OZ</span>
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach

                    <div class="rounded-xl bg-slate-950 p-6 text-white shadow-sm">
                        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-400">Grand Total</p>
                                <p class="mt-2 text-4xl font-semibold">
                                    <span class="text-lg text-indigo-300">RM</span><span id="display-total">0.00</span>
                                </p>
                                <p id="oz-summary" class="mt-2 h-4 text-xs font-semibold uppercase tracking-wide text-indigo-300"></p>
                                <p id="balance-error" class="hidden text-xs font-semibold uppercase tracking-wide text-rose-300">
                                    Insufficient cash balance
                                </p>
                            </div>

                            <div class="flex flex-col gap-2 sm:flex-row">
                                <x-ui.button type="submit" id="btn-use-balance" form="checkout-form" variant="secondary">
                                    <span id="btn-text">Use Balance</span>
                                </x-ui.button>
                                <x-ui.button type="submit" form="checkout-form" formaction="{{ route('stripe.checkout') }}">
                                    Pay with Stripe
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.oz-checkbox');
            const totalDisplay = document.getElementById('display-total');
            const ozSummary = document.getElementById('oz-summary');
            const balanceError = document.getElementById('balance-error');
            const btnUseBalance = document.getElementById('btn-use-balance');
            const btnText = document.getElementById('btn-text');
            const userBalance = document.getElementById('user-balance');

            if (!totalDisplay || !btnUseBalance || !userBalance) return;

            const userOzBalance = parseInt(userBalance.dataset.balance);
            const userCashBalanceCents = {{ (int) round(Auth::user()->tangki_balance * 100) }};

            function updateCalculations() {
                let currentTotalCashCents = 0;
                let totalOzUsed = 0;

                checkboxes.forEach(cb => {
                    const priceCents = parseInt(cb.dataset.priceCents);
                    const ozNeeded = parseInt(cb.dataset.ozNeeded);
                    const card = cb.closest('.group');
                    const priceLabel = card.querySelector('.item-price-label');

                    if (cb.checked) {
                        totalOzUsed += ozNeeded;
                        priceLabel.innerHTML = `<span class="text-slate-400 line-through">${priceLabel.dataset.cash}</span> <span class="ml-1 text-xs font-semibold text-indigo-700">REDEEMED</span>`;
                    } else {
                        currentTotalCashCents += priceCents;
                        priceLabel.innerHTML = priceLabel.dataset.cash;
                    }
                });

                const currentTotalCash = currentTotalCashCents / 100;
                totalDisplay.innerText = currentTotalCash.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                ozSummary.innerText = totalOzUsed > 0 ? `${totalOzUsed.toLocaleString()} OZ WILL BE DEDUCTED` : '';

                if (currentTotalCashCents > userCashBalanceCents) {
                    btnUseBalance.disabled = true;
                    btnText.innerText = 'Balance Insufficient';
                    balanceError.classList.remove('hidden');
                    btnUseBalance.classList.add('opacity-60', 'cursor-not-allowed');
                } else {
                    btnUseBalance.disabled = false;
                    btnText.innerText = 'Use Balance';
                    balanceError.classList.add('hidden');
                    btnUseBalance.classList.remove('opacity-60', 'cursor-not-allowed');
                }

                checkboxes.forEach(cb => {
                    if (!cb.checked) {
                        const neededForThis = parseInt(cb.dataset.ozNeeded);
                        const label = cb.closest('.oz-label');
                        if (totalOzUsed + neededForThis > userOzBalance) {
                            cb.disabled = true;
                            label.classList.add('opacity-40', 'cursor-not-allowed');
                        } else {
                            cb.disabled = false;
                            label.classList.remove('opacity-40', 'cursor-not-allowed');
                        }
                    }
                });
            }

            checkboxes.forEach(cb => cb.addEventListener('change', updateCalculations));
            updateCalculations();
        });
    </script>
</x-app-layout>
