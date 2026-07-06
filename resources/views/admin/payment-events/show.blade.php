<x-admin-layout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1100px] space-y-6">
            <x-layout.page-header title="Payment verification event" description="Read-only server event detail. Retry re-checks Stripe before invoking idempotent handlers.">
                <x-slot:actions>
                    <x-ui.button :href="route('admin.payment-events.index')" variant="secondary">
                        Back to Payment Events
                    </x-ui.button>
                    @if($paymentEvent->canRetry() && auth('admin')->user()?->canPerform('payment.retry'))
                        <form method="POST" action="{{ route('admin.payment-events.retry', $paymentEvent) }}">
                            @csrf
                            <x-ui.button type="submit">
                                Verify provider and retry
                            </x-ui.button>
                        </form>
                    @endif
                </x-slot:actions>
            </x-layout.page-header>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.card>
                    <x-slot:header>
                        <h2 class="text-sm font-semibold text-slate-950">Event</h2>
                    </x-slot:header>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-slate-500">Event ID</dt>
                            <dd class="mt-1 font-mono text-xs text-slate-950">{{ $paymentEvent->event_id }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Session ID</dt>
                            <dd class="mt-1 font-mono text-xs text-slate-950">{{ $paymentEvent->session_id ?? 'None' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Status</dt>
                            <dd class="mt-1">{{ $paymentEvent->status }}</dd>
                        </div>
                    </dl>
                </x-ui.card>

                <x-ui.card>
                    <x-slot:header>
                        <h2 class="text-sm font-semibold text-slate-950">Payment</h2>
                    </x-slot:header>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-slate-500">User</dt>
                            <dd class="mt-1 text-slate-950">{{ $paymentEvent->user->email ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Amount</dt>
                            <dd class="cp-tabular mt-1 font-bold text-[rgb(var(--cp-ink))]">{{ strtoupper((string) $paymentEvent->currency) }} {{ number_format($paymentEvent->amount_cents / 100, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Processed At</dt>
                            <dd class="mt-1 text-slate-950">{{ $paymentEvent->processed_at?->format('Y-m-d H:i') ?? 'Not processed' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Retry Attempts</dt>
                            <dd class="mt-1 text-slate-950">{{ $paymentEvent->retry_attempts }}</dd>
                        </div>
                        @if($paymentEvent->last_error)
                            <div>
                                <dt class="font-medium text-slate-500">Last Error</dt>
                                <dd class="mt-1 text-rose-700">{{ $paymentEvent->last_error }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-ui.card>
            </div>

            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-sm font-semibold text-slate-950">Payload</h2>
                </x-slot:header>
                <pre class="max-h-[520px] overflow-auto rounded-lg bg-slate-950 p-4 text-xs leading-6 text-slate-100">{{ json_encode($paymentEvent->payload_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </x-ui.card>
        </div>
    </div>
</x-admin-layout>
