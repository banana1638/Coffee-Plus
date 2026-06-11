<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1300px] space-y-6">
            <x-layout.page-header title="Refund Records" description="Tangki refund audit trail for order cancellations and customer support.">
                <x-slot:actions>
                    <x-ui.button :href="route('admin.orders.index')" variant="secondary">
                        Back to Orders
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            <form method="GET" action="{{ route('admin.orders.refunds') }}"
                class="flex max-w-xl gap-2 rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                <label class="sr-only" for="search_id">Search bill id</label>
                <input id="search_id" name="search_id" type="text" value="{{ request('search_id') }}" placeholder="Search bill id"
                    class="min-w-0 flex-1 rounded-lg border-slate-200 bg-slate-50 text-sm font-semibold uppercase tracking-wide text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-ui.button type="submit" size="sm">
                    Search
                </x-ui.button>
            </form>

            <x-ui.table-shell>
                <table class="w-full min-w-[860px] text-left">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Bill</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">OZ Delta</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white text-sm">
                        @forelse($refunds as $refund)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    @if($refund->bill)
                                        <a href="{{ route('admin.orders.show', $refund->bill) }}" class="font-semibold text-indigo-700 hover:text-indigo-900">
                                            {{ $refund->bill_id }}
                                        </a>
                                    @else
                                        <span class="font-semibold text-slate-950">{{ $refund->bill_id }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-700">
                                    {{ $refund->user->name ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :variant="$refund->oz_delta >= 0 ? 'success' : 'danger'">
                                        {{ $refund->oz_delta >= 0 ? '+' : '' }}{{ $refund->oz_delta }} OZ
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $refund->description }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $refund->created_at->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-sm font-semibold text-slate-500">
                                    No refund records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-shell>

            <div>
                {{ $refunds->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
