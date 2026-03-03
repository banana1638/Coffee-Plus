<x-admin-layout>
    <div class="py-6 bg-gray-50/30 min-h-screen">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">

            <!-- Welcome & Primary Stats Header -->
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-6 mb-8">
                <div
                    class="xl:col-span-1 bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 flex items-center gap-6">
                    <div
                        class="shrink-0 w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-[0.2em] mb-1">Owner Analytics
                        </p>
                        <h2 class="text-2xl font-black text-gray-900 leading-tight">
                            Sales Overview
                            <span class="block text-blue-600 text-lg">{{ Auth::guard('admin')->user()->name }}</span>
                        </h2>
                    </div>
                </div>

                <div class="xl:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-sm flex flex-col justify-center transform hover:scale-[1.02] transition-transform">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-2">Today's Revenue
                        </p>
                        <p class="text-4xl font-black text-gray-900 leading-none">
                            <span class="text-sm font-bold text-emerald-600 uppercase mr-1">RM</span>{{ number_format($revenueToday, 2) }}
                        </p>
                    </div>
                    <div
                        class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-sm flex flex-col justify-center transform hover:scale-[1.02] transition-transform">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-2">Monthly Sales</p>
                        <p class="text-4xl font-black text-gray-900 leading-none">
                            <span class="text-sm font-bold text-blue-600 uppercase mr-1">RM</span>{{ number_format($revenueThisMonth, 2) }}
                        </p>
                    </div>
                    <div
                        class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-sm flex flex-col justify-center transform hover:scale-[1.02] transition-transform">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-2">Total Users</p>
                        <p class="text-4xl font-black text-gray-900 leading-none">{{ number_format($totalUsers) }}</p>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">

                <!-- Left Column: Chart & Products -->
                <div class="xl:col-span-9 space-y-8">
                    <!-- Sales Chart -->
                    <div
                        class="bg-white rounded-[2.5rem] p-10 shadow-sm border border-gray-100 relative overflow-hidden">
                        <div class="flex items-center justify-between mb-10">
                            <div>
                                <h3 class="text-2xl font-black text-gray-900 italic uppercase">Revenue Trend</h3>
                                <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-widest">Last 7 Days Performance</p>
                            </div>
                            <span
                                class="px-4 py-2 bg-blue-50 text-blue-600 text-xs font-black rounded-xl uppercase tracking-widest">Live
                                Analytics</span>
                        </div>
                        <div class="h-[400px] w-full">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>

                    <!-- Top Selling Products -->
                    <div class="bg-white rounded-[2.5rem] p-10 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-2xl font-black text-gray-900 italic uppercase">Top Performers</h3>
                            <a href="{{ route('admin.products.index') }}" class="text-[10px] font-black text-blue-600 uppercase tracking-widest hover:underline">View All Products</a>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($topProducts as $product)
                                <div
                                    class="flex items-center justify-between p-6 bg-gray-50/50 rounded-3xl hover:bg-white hover:shadow-md border border-transparent hover:border-gray-100 transition-all group">
                                    <div class="flex items-center gap-6">
                                        <div
                                            class="w-12 h-12 bg-gray-900 rounded-2xl flex items-center justify-center text-white text-lg font-black group-hover:bg-blue-600 transition-colors">
                                            {{ $loop->iteration }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-black text-gray-900 uppercase italic text-lg">{{ $product->name }}</span>
                                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Beverage</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black text-blue-600 leading-none">{{ $product->total_sold }}</p>
                                        <p class="text-[10px] uppercase font-black text-gray-400 tracking-tighter mt-1">Units Sold</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right Column: Secondary Stats & Actions -->
                <div class="xl:col-span-3 space-y-8">
                    <div class="bg-gray-900 rounded-[2.5rem] p-10 text-white shadow-2xl shadow-blue-900/10 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-32 h-32 bg-blue-600/20 rounded-full blur-3xl group-hover:bg-blue-600/30 transition-all"></div>
                        <div class="relative z-10">
                            <p class="text-[10px] font-black uppercase tracking-[0.3em] opacity-50 mb-4">Lifetime Revenue
                            </p>
                            <h4 class="text-5xl font-black italic leading-none mb-8 tracking-tighter">
                                <span class="text-xl align-top mr-1">RM</span>{{ number_format($totalRevenue, 2) }}
                            </h4>
                            <div class="pt-8 border-t border-white/10 space-y-6">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.3em] opacity-50 mb-2">Total Orders</p>
                                    <p class="text-2xl font-black italic">{{ number_format($totalOrders) }}</p>
                                </div>
                                <div class="bg-white/5 rounded-2xl p-4 border border-white/10">
                                    <p class="text-[10px] font-black uppercase tracking-[0.3em] opacity-50 mb-2">Platform Status</p>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="text-xs font-black uppercase tracking-widest">Active & Secure</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2.5rem] p-10 shadow-sm border border-gray-100">
                        <h3 class="text-xl font-black text-gray-900 uppercase italic mb-6">Quick Actions</h3>
                        <div class="space-y-4">
                            <a href="{{ route('admin.products.index') }}"
                                class="flex items-center justify-between p-5 bg-blue-50 text-blue-600 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-blue-600 hover:text-white transition-all group overflow-hidden relative">
                                <span class="relative z-10">Manage Products</span>
                                <svg class="w-5 h-5 relative z-10 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.orders.index') }}"
                                class="flex items-center justify-between p-5 bg-gray-50 text-gray-900 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-gray-900 hover:text-white transition-all group">
                                <span>Order Logs</span>
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('salesChart').getContext('2d');
                const salesData = @json($salesData);

                const labels = salesData.map(item => item.date);
                const totals = salesData.map(item => item.total);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Revenue (RM)',
                            data: totals,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            borderWidth: 4,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { display: false },
                                ticks: {
                                    font: { weight: 'bold' },
                                    callback: function (value) { return 'RM' + value; }
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { weight: 'bold' } }
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>