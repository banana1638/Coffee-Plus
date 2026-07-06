<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-6">
            <x-layout.page-header title="Operations analytics" description="Backend-recorded revenue, order volume, customer totals, and product performance.">
                <x-slot:actions>
                    <x-ui.badge variant="success">
                        <span class="mr-1 h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Active & Secure
                    </x-ui.badge>
                </x-slot:actions>
            </x-layout.page-header>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.card>
                    <p class="text-sm font-medium text-slate-500">Today's Revenue</p>
                    <p class="cp-tabular mt-3 text-3xl font-bold text-[rgb(var(--cp-ink))]">
                        <span class="text-sm text-emerald-700">RM</span>{{ number_format($revenueToday, 2) }}
                    </p>
                </x-ui.card>

                <x-ui.card>
                    <p class="text-sm font-medium text-slate-500">Monthly Sales</p>
                    <p class="cp-tabular mt-3 text-3xl font-bold text-[rgb(var(--cp-ink))]">
                        <span class="text-sm text-emerald-700">RM</span>{{ number_format($revenueThisMonth, 2) }}
                    </p>
                </x-ui.card>

                <x-ui.card>
                    <p class="text-sm font-medium text-slate-500">Total Users</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-950">{{ number_format($totalUsers) }}</p>
                </x-ui.card>

                <x-ui.card>
                    <p class="text-sm font-medium text-slate-500">Total Orders</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-950">{{ number_format($totalOrders) }}</p>
                </x-ui.card>
            </div>

            <div class="grid gap-6 xl:grid-cols-12">
                <x-ui.card class="xl:col-span-8">
                    <div class="mb-6 flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950">Revenue Trend</h2>
                            <p class="text-sm text-slate-600">Last 7 days performance.</p>
                        </div>
                        <x-ui.badge variant="info">Live Analytics</x-ui.badge>
                    </div>
                    <div class="h-[380px] w-full">
                        <canvas id="salesChart"></canvas>
                    </div>
                </x-ui.card>

                <aside class="space-y-6 xl:col-span-4">
                    <x-ui.card class="bg-[#18201d] text-white">
                        <p class="text-sm font-medium text-slate-400">Lifetime Revenue</p>
                        <p class="mt-4 text-4xl font-semibold tracking-tight">
                            <span class="text-base text-slate-400">RM</span>{{ number_format($totalRevenue, 2) }}
                        </p>
                        <div class="mt-6 border-t border-white/10 pt-6">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Platform Status</p>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                <span class="text-sm font-semibold">Active & Secure</span>
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <h2 class="text-base font-semibold text-slate-950">Quick Actions</h2>
                        <div class="mt-4 grid gap-2">
                            <x-ui.button :href="route('admin.products.index')" variant="secondary" class="justify-between">
                                Manage Products
                                <span aria-hidden="true">-></span>
                            </x-ui.button>
                            <x-ui.button :href="route('admin.orders.index')" variant="secondary" class="justify-between">
                                Order Logs
                                <span aria-hidden="true">-></span>
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                </aside>
            </div>

            <x-ui.card>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Top Performers</h2>
                        <p class="text-sm text-slate-600">Best selling products by units sold.</p>
                    </div>
                    <x-ui.button :href="route('admin.products.index')" variant="subtle" size="sm">
                        View Products
                    </x-ui.button>
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($topProducts as $product)
                        <div class="flex items-center justify-between border-b border-[rgb(var(--cp-line))] p-4 last:border-b-0">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#18201d] text-sm font-semibold text-white">
                                    {{ $loop->iteration }}
                                </span>
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $product->name }}</p>
                                    <p class="text-xs text-slate-500">Beverage</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="cp-tabular text-lg font-bold text-emerald-800">{{ $product->total_sold }}</p>
                                <p class="text-xs text-slate-500">sold</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('salesChart').getContext('2d');
                const salesData = @json($salesData);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: salesData.map(item => item.date),
                        datasets: [{
                            label: 'Revenue (RM)',
                            data: salesData.map(item => item.total),
                            borderColor: '#136f54',
                            backgroundColor: 'rgba(19, 111, 84, 0.08)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#136f54',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#e2e8f0' },
                                ticks: {
                                    color: '#64748b',
                                    callback: function (value) { return 'RM' + value; }
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#64748b' }
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
