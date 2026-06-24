<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1300px] space-y-6">
            <x-layout.page-header title="Payment Events" description="Stripe webhook processing audit trail.">
                <x-slot:actions>
                    <x-ui.button :href="route('admin.dashboard')" variant="secondary">
                        Back to Dashboard
                    </x-ui.button>
                </x-slot:actions>
            </x-layout.page-header>

            <form method="GET" action="{{ route('admin.payment-events.index') }}"
                class="grid gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:grid-cols-[180px_1fr_auto]">
                <label class="sr-only" for="status">Status</label>
                <select id="status" name="status"
                    class="rounded-lg border-slate-200 bg-slate-50 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    @foreach(['processing', 'processed', 'failed', 'ignored'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>

                <label class="sr-only" for="session_id">Session ID</label>
                <input id="session_id" name="session_id" type="text" value="{{ request('session_id') }}" placeholder="Search session id"
                    class="min-w-0 rounded-lg border-slate-200 bg-slate-50 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                <x-ui.button type="submit" size="sm">Filter</x-ui.button>
            </form>

            <x-ui.table-shell>
                <table class="w-full min-w-[960px] text-left">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Session</th>
                            <th class="px-4 py-3">Event</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Processed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white text-sm">
                        @forelse($paymentEvents as $event)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.payment-events.show', $event) }}" class="font-semibold text-indigo-700 hover:text-indigo-900">
                                        {{ $event->session_id ?? 'No session' }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $event->event_id }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $event->user->email ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-950">
                                    {{ strtoupper((string) $event->currency) }} {{ number_format($event->amount_cents / 100, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :variant="$event->status === 'processed' ? 'success' : ($event->status === 'failed' ? 'danger' : 'warning')">
                                        {{ $event->status }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $event->processed_at?->format('Y-m-d H:i') ?? 'Not processed' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-sm font-semibold text-slate-500">
                                    No payment events found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-shell>

            <div>
                {{ $paymentEvents->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
