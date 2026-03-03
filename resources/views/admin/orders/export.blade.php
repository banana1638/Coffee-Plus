<x-admin-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen">
        <div class="max-w-2xl mx-auto px-4">
            
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center text-[10px] font-black uppercase tracking-widest text-gray-400 mb-8 hover:text-blue-600 transition group">
                <svg class="w-4 h-4 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 19l-7-7 7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Return to Orders
            </a>

            <div class="bg-white rounded-[3rem] shadow-xl border border-gray-100 overflow-hidden">
                <div class="bg-gray-900 p-10 text-center relative">
                    <div class="absolute top-0 left-0 w-full h-1 bg-blue-600"></div>
                    <div class="w-16 h-16 bg-blue-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white shadow-2xl shadow-blue-900/50 rotate-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h2 class="text-white text-2xl font-black italic tracking-wider uppercase">Report Center</h2>
                    <p class="text-blue-300 text-[10px] tracking-[0.4em] uppercase mt-2 font-bold opacity-80">Financial Data Extraction</p>
                </div>

                <div class="p-10">
                    <form action="{{ route('admin.orders.export.download') }}" method="GET" class="space-y-8">
                        
                        <div>
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-4 mb-3 block italic">
                                01. Select Export Logic
                            </label>
                            <div class="grid grid-cols-1 gap-3">
                                <select name="type" id="export_type" onchange="toggleExportFields()" 
                                    class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl py-4 px-6 text-xs font-black uppercase italic focus:ring-0 focus:border-blue-600 transition-all appearance-none cursor-pointer">
                                    <option value="date">Specific Daily Log</option>
                                    <option value="month">Monthly Statement</option>
                                    <option value="year">Annual Master File (Multi-Sheet)</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-gray-50/50 rounded-[2rem] p-8 border border-gray-50">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4 block italic">
                                02. Define Parameters
                            </label>
                            
                            <div id="field_date" class="export-field">
                                <input type="date" name="date" value="{{ date('Y-m-d') }}"
                                    class="w-full bg-white border-none rounded-xl py-3 px-4 text-sm font-bold focus:ring-2 focus:ring-blue-600">
                                <p class="mt-2 text-[9px] text-gray-400 font-bold italic">Extract all transactions for a single 24h period.</p>
                            </div>

                            <div id="field_month" class="export-field hidden">
                                <input type="month" name="month" value="{{ date('Y-m') }}"
                                    class="w-full bg-white border-none rounded-xl py-3 px-4 text-sm font-bold focus:ring-2 focus:ring-blue-600">
                                <p class="mt-2 text-[9px] text-gray-400 font-bold italic">Extract full month data into a single sheet.</p>
                            </div>

                            <div id="field_year" class="export-field hidden">
                                <select name="year" class="w-full bg-white border-none rounded-xl py-3 px-4 text-sm font-bold focus:ring-2 focus:ring-blue-600">
                                    @for($y = date('Y'); $y >= 2024; $y--)
                                        <option value="{{ $y }}">{{ $y }} Fiscal Year</option>
                                    @endfor
                                </select>
                                <p class="mt-2 text-[9px] text-gray-400 font-bold italic">Generates 12 monthly sheets in one Excel file.</p>
                            </div>
                        </div>

                        <button type="submit" class="w-full group relative overflow-hidden bg-gray-900 py-5 rounded-2xl transition-all hover:bg-blue-600 active:scale-[0.98] shadow-xl shadow-gray-200">
                            <div class="relative z-10 flex items-center justify-center gap-3">
                                <span class="text-white text-xs font-black uppercase tracking-[0.3em] italic">Generate .XLSX Report</span>
                                <svg class="w-4 h-4 text-white animate-bounce-x" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 8l4 4m0 0l-4 4m4-4H3" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </button>

                    </form>
                </div>

                <div class="px-10 py-6 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-[9px] font-black text-gray-300 uppercase tracking-widest italic">System Ready</span>
                    <span class="text-[9px] font-black text-gray-300 uppercase tracking-widest italic">v1.2.0-SECURE</span>
                </div>
            </div>
        </div>
    </div>

    <style>
        .animate-bounce-x {
            animation: bounce-x 1s infinite;
        }
        @keyframes bounce-x {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(4px); }
        }
    </style>

    <script>
    function toggleExportFields() {
        const type = document.getElementById('export_type').value;
        document.querySelectorAll('.export-field').forEach(el => el.classList.add('hidden'));
        document.getElementById('field_' + type).classList.remove('hidden');
    }
    </script>
</x-admin-layout>