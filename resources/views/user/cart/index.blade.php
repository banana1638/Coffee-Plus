<x-app-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <a href="{{ route('dashboard') }}" class="w-10 h-10 flex items-center justify-center bg-white rounded-xl shadow-sm border border-gray-100 text-gray-400 hover:text-blue-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <h1 class="text-3xl font-black text-gray-900">My Cart</h1>
                </div>
                <span class="bg-blue-600 text-white px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest shadow-lg shadow-blue-200">
                    {{ $cartItems->sum('quantity') }} Items
                </span>
            </div>

            {{-- 错误信息反馈区 --}}
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-600 rounded-2xl font-bold text-sm flex items-center gap-3">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-10 bg-white rounded-[2.5rem] p-6 md:p-8 shadow-sm border border-gray-100 flex flex-col md:flex-row items-center gap-8">
                <div class="w-32 md:w-40 shrink-0">
                    @include('components.tank-visualization')
                </div>

                <div class="flex-1 grid grid-cols-2 gap-4 w-full md:border-l md:px-8 border-gray-100">
                    <div class="bg-blue-50/50 p-4 rounded-3xl border border-blue-100/50">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Current Storage</p>
                        <p class="text-2xl font-black text-blue-600">
                            <span id="user-balance" data-balance="{{ Auth::user()->tangki_oz }}">{{ number_format(Auth::user()->tangki_oz) }}</span> 
                            <span class="text-xs font-bold opacity-60">OZ</span>
                        </p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-3xl border border-gray-100">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Account Balance</p>
                        <p class="text-2xl font-black text-gray-800">
                            <span class="text-sm font-bold mr-0.5">RM</span>{{ number_format(Auth::user()->tangki_balance, 2) }}
                        </p>
                    </div>
                </div>
            </div>

            @if($cartItems->isEmpty())
                <div class="bg-white/40 border-2 border-dashed border-gray-200 rounded-[3rem] p-16 text-center">
                    <p class="text-gray-400 font-black italic uppercase tracking-[0.3em]">Your cart is empty. ☕️</p>
                    <a href="{{ route('dashboard') }}" class="inline-block mt-6 px-8 py-3 bg-blue-600 text-white rounded-xl font-black text-sm uppercase transition-all hover:scale-105">Browse Products</a>
                </div>
            @else
                {{-- 主表单：用于余额/OZ 支付 --}}
                <form action="{{ route('order.checkout') }}" method="POST" id="checkout-form">
                    @csrf
                    <div class="space-y-4">
                        @foreach($cartItems as $item)
                            @php
                                $itemTotalCash = $item->unit_price * $item->quantity;
                                $itemTotalOz = (int)($itemTotalCash * 100);
                            @endphp

                            <div class="bg-white rounded-[2rem] p-5 md:p-6 shadow-sm border border-gray-100 flex items-center gap-6 group hover:border-blue-100 transition-all">
                                <div class="w-20 h-20 rounded-2xl overflow-hidden bg-gray-50 shrink-0">
                                    <img src="{{ $item->product->image_url }}" class="w-full h-full object-cover">
                                </div>

                                <div class="flex-1">
                                    <h3 class="font-black text-gray-900">{{ $item->product->name }}</h3>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-md font-bold uppercase">{{ $item->size }}</span>
                                        <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-md font-bold uppercase">{{ $item->temp }}</span>
                                        @foreach($item->addons as $addon)
                                            <span class="text-[10px] bg-blue-50 text-blue-500 px-2 py-0.5 rounded-md font-bold uppercase">+ {{ $addon }}</span>
                                        @endforeach
                                    </div>
                                    <p class="text-blue-600 font-black mt-2 item-price-label" 
                                       data-cash="RM {{ number_format($itemTotalCash, 2) }}">
                                        RM {{ number_format($itemTotalCash, 2) }}
                                    </p>
                                </div>

                                <div class="flex flex-col items-end gap-2">
                                    <label class="relative inline-flex items-center cursor-pointer oz-label transition-opacity">
                                        <input type="checkbox" name="use_oz[]" value="{{ $item->id }}" 
                                               class="sr-only peer oz-checkbox" 
                                               data-price="{{ $itemTotalCash }}" 
                                               data-oz-needed="{{ $itemTotalOz }}">
                                        
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        <span class="ml-3 text-[10px] font-black text-gray-400 uppercase tracking-widest peer-checked:text-blue-600">Redeem</span>
                                    </label>
                                    <span class="text-[9px] font-bold text-gray-300 uppercase tracking-tighter">
                                        {{ number_format($itemTotalOz) }} OZ
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-10 bg-gray-900 rounded-[3rem] p-8 text-white shadow-2xl relative overflow-hidden">
                        <div class="flex flex-col md:flex-row justify-between items-center gap-6 relative z-10">
                            <div>
                                <p class="text-gray-400 text-sm font-bold uppercase tracking-widest">Grand Total</p>
                                <p class="text-4xl font-black mt-1">
                                    <span class="text-xl font-bold text-blue-500 mr-1">RM</span><span id="display-total">0.00</span>
                                </p>
                                <p id="oz-summary" class="text-blue-400 text-[10px] font-bold uppercase tracking-widest mt-2 h-4"></p>
                                <div class="mt-2 h-4">
                                    <p id="oz-summary" class="text-blue-400 text-[10px] font-bold uppercase tracking-widest"></p>
                                    <p id="balance-error" class="hidden text-red-500 text-[10px] font-black uppercase tracking-widest italic animate-pulse">
                                        Insufficient Cash Balance
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                <button type="submit" id="btn-use-balance" form="checkout-form" class="bg-gray-800 hover:bg-black text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-lg active:scale-95">
                                    <span id="btn-text">Use Balance</span>
                                </button>
                                
                                <button type="submit" form="checkout-form" formaction="{{ route('stripe.checkout') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-10 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-lg">
                                    Pay with Stripe
                                </button>
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
        
        const userOzBalance = parseInt(document.getElementById('user-balance').dataset.balance);
        const userCashBalance = parseFloat("{{ Auth::user()->tangki_balance }}");

        function updateCalculations() {
            let currentTotalCash = 0;
            let totalOzUsed = 0;

            checkboxes.forEach(cb => {
                const price = parseFloat(cb.dataset.price);
                const ozNeeded = parseInt(cb.dataset.ozNeeded);
                const card = cb.closest('.group');
                const priceLabel = card.querySelector('.item-price-label');

                if (cb.checked) {
                    totalOzUsed += ozNeeded;
                    priceLabel.innerHTML = `<span class="text-gray-300 line-through">${priceLabel.dataset.cash}</span> <span class="text-[10px] ml-1 text-blue-400 font-bold">REDEEMED</span>`;
                } else {
                    currentTotalCash += price;
                    priceLabel.innerHTML = priceLabel.dataset.cash;
                }
            });

            totalDisplay.innerText = currentTotalCash.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            ozSummary.innerText = totalOzUsed > 0 ? `${totalOzUsed.toLocaleString()} OZ WILL BE DEDUCTED` : '';

            if (currentTotalCash > userCashBalance) {
                btnUseBalance.disabled = true;
                btnText.innerText = 'Balance Insufficient';
                balanceError.classList.remove('hidden');
                btnUseBalance.classList.replace('bg-gray-800', 'bg-red-900/20');
                btnUseBalance.classList.add('text-red-400', 'border', 'border-red-500/30');
            } else {
                btnUseBalance.disabled = false;
                btnText.innerText = 'Use Balance';
                balanceError.classList.add('hidden');
                btnUseBalance.classList.replace('bg-red-900/20', 'bg-gray-800');
                btnUseBalance.classList.remove('text-red-400', 'border', 'border-red-500/30');
            }

            checkboxes.forEach(cb => {
                if (!cb.checked) {
                    const neededForThis = parseInt(cb.dataset.ozNeeded);
                    const label = cb.closest('.oz-label');
                    if (totalOzUsed + neededForThis > userOzBalance) {
                        cb.disabled = true;
                        label.classList.add('opacity-20', 'cursor-not-allowed');
                    } else {
                        cb.disabled = false;
                        label.classList.remove('opacity-20', 'cursor-not-allowed');
                    }
                }
            });
        }

        checkboxes.forEach(cb => cb.addEventListener('change', updateCalculations));
        updateCalculations();
    });
    </script>
</x-app-layout>