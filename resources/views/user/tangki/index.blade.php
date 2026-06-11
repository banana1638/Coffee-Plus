<x-app-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <x-layout.page-header title="Tangki Management" description="Manage storage, balance, refills, and recent activity.">
                <x-slot:actions>
                    <x-ui.button :href="url()->previous()" variant="secondary">
                        Back
                    </x-ui.button>
                    <x-ui.button :href="route('tangki.transactions')" variant="secondary">
                        View Activity
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
                <div class="space-y-6">
                    <x-ui.card>
                        <div class="mx-auto mb-6 max-w-[240px]">
                            @include('components.tank-visualization')
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-slate-200 pt-6">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Current Storage</p>
                                <p class="mt-2 text-3xl font-semibold text-indigo-700">
                                    {{ Auth::user()->tangki_oz }} <span class="text-sm text-slate-500">oz</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Account Balance</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-950">
                                    <span class="text-sm text-slate-500">RM</span>{{ number_format(Auth::user()->tangki_balance, 2) }}
                                </p>
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <h2 class="text-base font-semibold text-slate-950">Refill Tangki</h2>
                        <p class="mt-1 text-sm text-slate-600">1 RM = 10 oz.</p>

                        <form action="{{ route('tangki.refill') }}" method="POST" id="refillForm" class="mt-5 space-y-4">
                            @csrf
                            <div class="grid grid-cols-3 gap-2">
                                @foreach([10, 20, 50, 100, 200, 500] as $v)
                                    <x-ui.button type="button" variant="secondary" size="sm" onclick="quickSubmit({{ $v }})">
                                        RM{{ number_format($v, 2) }}
                                    </x-ui.button>
                                @endforeach
                            </div>

                            <div class="space-y-2">
                                <label for="amountInput" class="text-sm font-medium text-slate-700">Custom amount</label>
                                <input type="number" id="amountInput" name="amount" step="0.01" inputmode="decimal"
                                    placeholder="e.g. 10.00" onwheel="this.blur()" onblur="formatDecimal(this)"
                                    class="w-full rounded-lg border-slate-300 text-center text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                            </div>

                            <x-ui.button type="submit" class="w-full">
                                Confirm Refill
                            </x-ui.button>
                        </form>
                    </x-ui.card>
                </div>

                <x-ui.card>
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950">Recent Transactions</h2>
                            <p class="text-sm text-slate-600">Latest Tangki activity.</p>
                        </div>
                        <x-ui.badge>Activity Log</x-ui.badge>
                    </div>

                    <div class="divide-y divide-slate-200">
                        @forelse($transactions as $trx)
                            <div class="grid gap-4 py-4 md:grid-cols-[1fr_150px_80px] md:items-center">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $trx->description }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $trx->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <p class="text-lg font-semibold {{ $trx->oz_delta > 0 ? 'text-emerald-700' : 'text-indigo-700' }}">
                                    {{ $trx->oz_delta > 0 ? '+' : '' }}{{ $trx->oz_delta }} <span class="text-xs">oz</span>
                                </p>
                                <x-ui.button :href="route('tangki.order-detail', $trx->bill_id)" variant="secondary" size="sm">
                                    View
                                </x-ui.button>
                            </div>
                        @empty
                            <div class="py-12 text-center">
                                <p class="text-sm font-semibold text-slate-500">No transaction data available.</p>
                            </div>
                        @endforelse
                    </div>
                </x-ui.card>
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
            btn.innerHTML = 'Processing...';
        }
    </script>
</x-app-layout>
