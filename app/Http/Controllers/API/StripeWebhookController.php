<?php

namespace App\Http\Controllers\API;

use App\DataTransferObjects\PaymentResult;
use App\Http\Controllers\Controller;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Services\Payment\PaymentHandlerFactory;
use App\Services\RealtimeNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    private const PROVIDER = 'stripe';

    private const COMPLETED_CHECKOUT_EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
    ];

    private const FAILED_CHECKOUT_EVENTS = [
        'checkout.session.async_payment_failed',
        'checkout.session.expired',
    ];

    private const SUPPORTED_METADATA_TYPES = ['refill', 'tangki_refill', 'checkout'];

    public function __construct(
        private readonly PaymentHandlerFactory $handlerFactory,
        private readonly RealtimeNotificationService $notificationService,
    ) {}

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

        if (in_array($event->type, self::FAILED_CHECKOUT_EVENTS, true)) {
            return $this->handleFailedCheckout($event);
        }

        if (! in_array($event->type, self::COMPLETED_CHECKOUT_EVENTS, true)) {
            return response()->json(['received' => true]);
        }

        $session = $event->data->object;
        $metadata = $session->metadata?->toArray() ?? [];
        $sessionId = $session->id ?? null;

        if (! $sessionId) {
            return response()->json(['message' => 'Missing session id.'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->shouldIgnoreSession($session, $metadata)) {
            return response()->json(['status' => 'ignored']);
        }

        $alreadyProcessed = $this->paymentEventQuery($event->id, $sessionId)
            ->where('status', PaymentEvent::STATUS_PROCESSED)
            ->exists();

        if ($alreadyProcessed) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        try {
            DB::transaction(function () use ($event, $session, $metadata, $sessionId) {
                $metadataUserId = isset($metadata['user_id']) ? (int) $metadata['user_id'] : null;
                $user = $metadataUserId ? User::find($metadataUserId) : null;

                $paymentEvent = $this->paymentEventQuery($event->id, $sessionId)
                    ->lockForUpdate()
                    ->first();

                if (! $paymentEvent) {
                    $paymentEvent = PaymentEvent::create([
                        'provider' => self::PROVIDER,
                        'event_id' => $event->id,
                        'session_id' => $sessionId,
                        'user_id' => $user?->id,
                        'type' => $this->normalizePaymentType($metadata['type'] ?? null),
                        'amount_cents' => (int) ($session->amount_total ?? 0),
                        'currency' => $session->currency ?? null,
                        'status' => PaymentEvent::STATUS_PROCESSING,
                        'payload_json' => $event->toArray(),
                    ]);
                } else {
                    if ($paymentEvent->status === PaymentEvent::STATUS_PROCESSED) {
                        return;
                    }

                    $this->assertPendingPaymentMatches($paymentEvent, $metadataUserId, $session, $metadata);

                    $paymentEvent->fill([
                        'event_id' => $event->id,
                        'user_id' => $user?->id ?? $paymentEvent->user_id,
                        'type' => $this->normalizePaymentType($metadata['type'] ?? null),
                        'amount_cents' => (int) ($session->amount_total ?? 0),
                        'currency' => $session->currency ?? null,
                        'status' => PaymentEvent::STATUS_PROCESSING,
                        'payload_json' => $event->toArray(),
                    ])->save();
                }

                if (! $user) {
                    $paymentEvent->status = PaymentEvent::STATUS_IGNORED;
                    $paymentEvent->save();

                    return;
                }

                $metadata['type'] = $this->normalizePaymentType($metadata['type'] ?? null);

                $result = new PaymentResult(
                    status: 'success',
                    amount: $this->sessionAmount($session),
                    metadata: $metadata,
                    platformRef: $sessionId,
                    currency: $session->currency ?? null,
                );

                $handlerResult = $this->handlerFactory->make($result->getType())->handle($result, $user);

                $paymentEvent->status = PaymentEvent::STATUS_PROCESSED;
                $paymentEvent->processed_at = now();
                $paymentEvent->save();

                $this->notificationService->paymentProcessed(
                    $paymentEvent->loadMissing('user'),
                    $handlerResult,
                );
            });
        } catch (\Throwable $e) {
            report($e);

            $paymentEvent = $this->paymentEventQuery($event->id, $sessionId)
                ->with('user')
                ->first();

            if ($paymentEvent && $paymentEvent->status !== PaymentEvent::STATUS_PROCESSED) {
                $shouldNotify = $paymentEvent->status !== PaymentEvent::STATUS_FAILED;

                $paymentEvent->status = PaymentEvent::STATUS_FAILED;
                $paymentEvent->last_error = 'Webhook processing failed.';
                $paymentEvent->save();

                if ($shouldNotify) {
                    $this->notificationService->paymentFailed($paymentEvent);
                }
            }

            return response()->json(['message' => 'Webhook processing failed.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['received' => true]);
    }

    private function handleFailedCheckout(object $event)
    {
        $session = $event->data->object;
        $sessionId = $session->id ?? null;

        if (! $sessionId) {
            return response()->json(['message' => 'Missing session id.'], Response::HTTP_BAD_REQUEST);
        }

        DB::transaction(function () use ($event, $sessionId) {
            $paymentEvent = PaymentEvent::where('session_id', $sessionId)
                ->with('user')
                ->lockForUpdate()
                ->first();

            if (! $paymentEvent
                || $paymentEvent->status === PaymentEvent::STATUS_PROCESSED
                || $paymentEvent->status === PaymentEvent::STATUS_FAILED) {
                return;
            }

            $paymentEvent->fill([
                'event_id' => $event->id,
                'status' => PaymentEvent::STATUS_FAILED,
                'last_error' => 'Stripe checkout was not completed.',
                'payload_json' => $event->toArray(),
            ])->save();

            $this->notificationService->paymentFailed($paymentEvent);
        });

        return response()->json(['received' => true, 'status' => PaymentEvent::STATUS_FAILED]);
    }

    private function shouldIgnoreSession(object $session, array $metadata): bool
    {
        $type = $metadata['type'] ?? null;

        return ($session->payment_status ?? null) !== 'paid'
            || ($session->mode ?? null) !== 'payment'
            || strtolower((string) ($session->currency ?? '')) !== 'myr'
            || (int) ($session->amount_total ?? 0) <= 0
            || ! in_array($type, self::SUPPORTED_METADATA_TYPES, true)
            || empty($metadata['user_id']);
    }

    private function paymentEventQuery(string $eventId, string $sessionId)
    {
        return PaymentEvent::where(function ($query) use ($eventId, $sessionId) {
            $query->where('event_id', $eventId)
                ->orWhere('session_id', $sessionId);
        });
    }

    private function normalizePaymentType(?string $type): string
    {
        return $type === 'tangki_refill' ? 'refill' : ($type ?: 'checkout');
    }

    private function assertPendingPaymentMatches(
        PaymentEvent $paymentEvent,
        ?int $metadataUserId,
        object $session,
        array $metadata,
    ): void {
        if (! str_starts_with($paymentEvent->event_id, 'pending:')) {
            return;
        }

        $matches = (int) $paymentEvent->user_id === $metadataUserId
            && $paymentEvent->amount_cents === (int) ($session->amount_total ?? 0)
            && strtolower((string) $paymentEvent->currency) === strtolower((string) ($session->currency ?? ''))
            && $paymentEvent->type === $this->normalizePaymentType($metadata['type'] ?? null);

        if (! $matches) {
            throw new \RuntimeException('Stripe session does not match the pending payment record.');
        }
    }

    private function sessionAmount(object $session): float
    {
        return ((int) ($session->amount_total ?? 0)) / 100;
    }
}
