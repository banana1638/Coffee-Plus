<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentEvent;
use App\Services\AuditLogService;
use App\Services\Payment\PaymentRetryService;
use Illuminate\Http\Request;
use Throwable;

class PaymentEventAdminController extends Controller
{
    public function __construct(
        private readonly PaymentRetryService $paymentRetryService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(Request $request)
    {
        $query = PaymentEvent::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', 'like', '%'.$request->input('session_id').'%');
        }

        $paymentEvents = $query->paginate(20)->withQueryString();

        return view('admin.payment-events.index', compact('paymentEvents'));
    }

    public function show(PaymentEvent $paymentEvent)
    {
        $paymentEvent->load('user');

        return view('admin.payment-events.show', compact('paymentEvent'));
    }

    public function retry(Request $request, PaymentEvent $paymentEvent)
    {
        $oldValues = $paymentEvent->only(['status', 'retry_attempts', 'last_error']);

        try {
            $paymentEvent = $this->paymentRetryService->retry($paymentEvent);
            $this->auditLogService->record(
                'payment.retry.succeeded',
                $paymentEvent,
                $oldValues,
                $paymentEvent->only(['status', 'retry_attempts', 'last_error']),
                request: $request,
            );

            return redirect()->route('admin.payment-events.show', $paymentEvent)
                ->with('success', 'Payment event was verified with Stripe and processed.');
        } catch (Throwable $exception) {
            report($exception);
            $paymentEvent->refresh();
            $this->auditLogService->record(
                'payment.retry.failed',
                $paymentEvent,
                $oldValues,
                $paymentEvent->only(['status', 'retry_attempts', 'last_error']),
                request: $request,
            );

            return redirect()->route('admin.payment-events.show', $paymentEvent)
                ->with('error', 'Payment retry failed. Review the recorded error before trying again.');
        }
    }
}
