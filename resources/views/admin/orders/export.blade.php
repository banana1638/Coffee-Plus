<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl space-y-6">
            <x-layout.page-header title="Export Center" description="Generate operational order reports for finance review.">
                <x-slot:actions>
                    <x-ui.button :href="route('admin.orders.index')" variant="secondary">
                        Return to Orders
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            <x-ui.card>
                <form action="{{ route('admin.orders.export.download') }}" method="GET" class="space-y-6">
                    <div>
                        <label for="export_type" class="text-sm font-medium text-slate-700">Export type</label>
                        <select name="type" id="export_type" onchange="toggleExportFields()"
                            class="mt-2 w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-[rgb(var(--cp-ink))] shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="date">Specific daily log</option>
                            <option value="month">Monthly statement</option>
                            <option value="year">Annual master file</option>
                        </select>
                    </div>

                    <div class="rounded-lg border border-[rgb(var(--cp-line))] bg-stone-100 p-4">
                        <p class="text-sm font-semibold text-slate-950">Parameters</p>

                        <div id="field_date" class="export-field mt-4">
                            <label for="date" class="text-sm font-medium text-slate-700">Date</label>
                            <input id="date" type="date" name="date" value="{{ date('Y-m-d') }}"
                                class="mt-2 w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <p class="mt-2 text-xs text-slate-500">Extract all transactions for a single 24-hour period.</p>
                        </div>

                        <div id="field_month" class="export-field mt-4 hidden">
                            <label for="month" class="text-sm font-medium text-slate-700">Month</label>
                            <input id="month" type="month" name="month" value="{{ date('Y-m') }}"
                                class="mt-2 w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <p class="mt-2 text-xs text-slate-500">Extract full month data into a single sheet.</p>
                        </div>

                        <div id="field_year" class="export-field mt-4 hidden">
                            <label for="year" class="text-sm font-medium text-slate-700">Year</label>
                            <select id="year" name="year" class="mt-2 w-full rounded-lg border-[rgb(var(--cp-line))] bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                @for($y = date('Y'); $y >= 2024; $y--)
                                    <option value="{{ $y }}">{{ $y }} fiscal year</option>
                                @endfor
                            </select>
                            <p class="mt-2 text-xs text-slate-500">Generates 12 monthly sheets in one Excel file.</p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" size="lg">
                            Generate XLSX Report
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    <script>
        function toggleExportFields() {
            const type = document.getElementById('export_type').value;
            document.querySelectorAll('.export-field').forEach(el => el.classList.add('hidden'));
            document.getElementById('field_' + type).classList.remove('hidden');
        }
    </script>
</x-admin-layout>
