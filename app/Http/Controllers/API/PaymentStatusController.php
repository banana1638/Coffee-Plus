<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentEvent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PaymentStatusController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request, string $sessionId)
    {
        $paymentEvent = PaymentEvent::where('session_id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$paymentEvent) {
            return $this->success([
                'session_id' => $sessionId,
                'status' => 'pending',
            ]);
        }

        return $this->success([
            'session_id' => $paymentEvent->session_id,
            'provider' => $paymentEvent->provider,
            'type' => $paymentEvent->type,
            'status' => $paymentEvent->status,
            'amount_cents' => $paymentEvent->amount_cents,
            'currency' => $paymentEvent->currency,
            'processed_at' => $paymentEvent->processed_at?->toISOString(),
            'updated_at' => $paymentEvent->updated_at?->toISOString(),
        ]);
    }
}
