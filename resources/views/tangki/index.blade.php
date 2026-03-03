<x-app-layout>
    <div class="py-8 md:py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex flex-col lg:flex-row gap-8">

                <div class="lg:w-5/12 space-y-6">
                    <div class="flex items-center gap-4 mb-4">
                        <a href="{{ url()->previous() }}"
                            class="w-10 h-10 flex items-center justify-center bg-white rounded-xl shadow-sm border border-gray-100 text-gray-400 hover:text-blue-600 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M15 19l-7-7 7-7" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>
                        <h2 class="text-2xl font-black text-gray-800 tracking-tight">Tangki Management</h2>
                    </div>

                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 text-center">
                        <div class="mb-8 flex justify-center">
                            <div class="w-full max-w-[240px]">
                                @include('components.tank-visualization')
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-gray-50 pt-6">
                            <div class="border-r border-gray-100">
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Current
                                    Storage</p>
                                <p class="text-2xl md:text-3xl font-black text-blue-600">{{ Auth::user()->tangki_oz }}
                                    <span class="text-xs font-normal text-gray-400">oz</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Account
                                    Balance</p>
                                <p class="text-2xl md:text-3xl font-black text-gray-800">RM {{
    number_format(Auth::user()->tangki_balance, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 mb-6">
                        <h4 class="font-black text-gray-800 mb-4 text-center text-sm uppercase tracking-widest">Select
                            the irrigation amount (1 RM = 10 oz)</h4>

                        <form action="{{ route('tangki.refill') }}" method="POST" id="refillForm">
                            @csrf
                            <div class="grid grid-cols-3 gap-2 mb-4">
                                @foreach([10, 20, 50, 100, 200, 500] as $v)
                                    <button type="button" onclick="quickSubmit({{ $v }})"
                                        class="border border-blue-100 text-blue-600 py-3 rounded-xl font-bold hover:bg-blue-600 hover:text-white transition text-sm active:scale-95">
                                        RM{{ number_format($v, 2) }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="relative mb-4">
                                <input type="number" id="amountInput" name="amount" step="0.01" inputmode="decimal"
                                    placeholder="Custom amount (e.g. 10.00)" onwheel="this.blur()"
                                    onblur="formatDecimal(this)"
                                    class="w-full bg-gray-50 border-none rounded-xl py-3 text-center font-bold focus:ring-2 focus:ring-blue-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                            </div>

                            <button type="submit"
                                class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 active:scale-95 transition">
                                Confirm watering
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:w-7/12">
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 h-full">
                        <div class="flex justify-between items-center mb-8">
                            <h4 class="font-black text-gray-800 text-xl tracking-tight">Recent Transactions</h4>
                            <span
                                class="text-xs bg-gray-100 text-gray-400 px-4 py-1.5 rounded-full font-bold uppercase tracking-tighter">Activity
                                Log</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($transactions as $trx)
                                <div
                                    class="flex justify-between items-center p-5 hover:bg-gray-50 rounded-[1.5rem] transition-all group">
                                    <div class="flex items-center gap-5">
                                        <div
                                            class="w-12 h-12 rounded-2xl flex items-center justify-center transition-colors {{ $trx->oz_delta > 0 ? 'bg-green-50 text-green-500 group-hover:bg-green-500 group-hover:text-white' : 'bg-blue-50 text-blue-500 group-hover:bg-blue-500 group-hover:text-white' }}">
                                            @if($trx->oz_delta > 0)
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round" />
                                                </svg>
                                            @else
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 14l-7 7m0 0l-7-7m7 7V3" stroke-width="3"
                                                        stroke-linecap="round" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-800">{{ $trx->description }}</p>
                                            <p class="text-xs text-gray-400 font-medium">{{ $trx->created_at->format('M d, Y
                                                • h:i A') }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-6">
                                        <span
                                            class="text-lg font-black {{ $trx->oz_delta > 0 ? 'text-green-500' : 'text-blue-600' }}">
                                            {{ $trx->oz_delta > 0 ? '+' : '' }}{{ $trx->oz_delta }} <span
                                                class="text-[10px] uppercase opacity-60">oz</span>
                                        </span>
                                        <a href="{{ route('tangki.order-detail', $trx->bill_id) }}"
                                            class="opacity-0 group-hover:opacity-100 bg-gray-900 px-4 py-2 rounded-xl text-[10px] font-black text-white transition-all shadow-lg hover:scale-105 active:scale-95">
                                            VIEW
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-20">
                                    <p class="text-gray-400 font-bold">No transaction data available.</p>
                                </div>
                            @endforelse
                        </div>
                        <a href="{{ route('tangki.transactions') }}"
                            class="inline-flex items-center gap-2 text-sm bg-white border border-gray-100 text-gray-500 px-6 py-2.5 rounded-2xl font-black uppercase tracking-widest shadow-sm hover:bg-blue-600 hover:text-white hover:border-blue-600 hover:shadow-lg hover:shadow-blue-200 transition-all active:scale-95 cursor-pointer">
                            <span>View More Activity</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function quickSubmit(value) {
            const form = document.getElementById('refillForm');
            const submitBtn = form.querySelector('button[type="submit"]');

            if (submitBtn.disabled) return;

            handleLoading(submitBtn);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'amount';
            hiddenInput.value = value;
            form.appendChild(hiddenInput);

            form.submit();
        }

        function formatDecimal(el) {
            if (el.value !== '') {
                el.value = parseFloat(el.value).toFixed(2);
            }
        }

        document.getElementById('refillForm').addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const amountInput = document.getElementById('amountInput');

            if (!amountInput.value || amountInput.value <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount.');
                return false;
            }

            if (submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
            handleLoading(submitBtn);
        });

        function handleLoading(btn) {
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            `;
        }
    </script>
</x-app-layout>