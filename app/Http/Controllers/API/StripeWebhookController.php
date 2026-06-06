<?php

namespace App\Http\Controllers\API;

use App\DataTransferObjects\PaymentResult;
use App\Http\Controllers\Controller;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Services\Payment\PaymentHandlerFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly PaymentHandlerFactory $handlerFactory)
    {
    }

    public function __invoke(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\Throwable) {
            return response()->json(['message' => 'Invalid signature.'], Response::HTTP_BAD_REQUEST);
        }

        if ($event->type !== 'checkout.session.completed') {
            return response()->json(['received' => true]);
        }

        $session = $event->data->object;
        $metadata = $session->metadata?->toArray() ?? [];
        $sessionId = $session->id ?? null;

        if (!$sessionId) {
            return response()->json(['message' => 'Missing session id.'], Response::HTTP_BAD_REQUEST);
        }

        $alreadyProcessed = PaymentEvent::where(function ($query) use ($event, $sessionId) {
                $query->where('event_id', $event->id)
                    ->orWhere('session_id', $sessionId);
            })
            ->where('status', 'processed')
            ->exists();

        if ($alreadyProcessed) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        try {
            DB::transaction(function () use ($event, $session, $metadata, $sessionId) {
                $paymentEvent = PaymentEvent::firstOrCreate(
                    ['event_id' => $event->id],
                    [
                        'provider' => 'stripe',
                        'session_id' => $sessionId,
                        'user_id' => isset($metadata['user_id']) ? (int) $metadata['user_id'] : null,
                        'type' => $event->type,
                        'amount_cents' => (int) ($session->amount_total ?? 0),
                        'currency' => $session->currency ?? null,
                        'status' => 'processing',
                        'payload_json' => $event->toArray(),
                    ]
                );

                if ($paymentEvent->status === 'processed') {
                    return;
                }

                $user = User::find($paymentEvent->user_id);
                if (!$user) {
                    throw new \RuntimeException('Payment event user not found.');
                }

                $result = new PaymentResult(
                    status: 'success',
                    amount: ((int) ($session->amount_total ?? 0)) / 100,
                    metadata: $metadata,
                    platformRef: $sessionId
                );

                $this->handlerFactory->make($result->getType())->handle($result, $user);

                $paymentEvent->status = 'processed';
                $paymentEvent->processed_at = now();
                $paymentEvent->save();
            });
        } catch (\Throwable $e) {
            report($e);

            PaymentEvent::where('event_id', $event->id)->update(['status' => 'failed']);

            return response()->json(['message' => 'Webhook processing failed.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['received' => true]);
    }
}
