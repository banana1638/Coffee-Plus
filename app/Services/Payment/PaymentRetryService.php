<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentEvent;
use App\Services\RealtimeNotificationService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PaymentRetryService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly PaymentHandlerFactory $handlerFactory,
        private readonly RealtimeNotificationService $notificationService,
    ) {}

    public function retry(PaymentEvent $paymentEvent): PaymentEvent
    {
        $paymentEvent = DB::transaction(function () use ($paymentEvent) {
            $lockedEvent = PaymentEvent::whereKey($paymentEvent->id)->lockForUpdate()->firstOrFail();

            if (! $lockedEvent->canRetry()) {
                throw new RuntimeException('This payment event is not eligible for retry.');
            }

            $lockedEvent->status = PaymentEvent::STATUS_PROCESSING;
            $lockedEvent->retry_attempts++;
            $lockedEvent->last_retried_at = now();
            $lockedEvent->last_error = null;
            $lockedEvent->save();

            return $lockedEvent;
        });

        try {
            $result = $this->gateway->getSessionData($paymentEvent->session_id);

            if (! $result->isSuccess()) {
                throw new RuntimeException('Stripe does not report this payment as paid.');
            }

            [$processedEvent, $handlerResult] = DB::transaction(function () use ($paymentEvent, $result) {
                $lockedEvent = PaymentEvent::whereKey($paymentEvent->id)->lockForUpdate()->firstOrFail();

                if ($lockedEvent->status !== PaymentEvent::STATUS_PROCESSING) {
                    throw new RuntimeException('Payment retry state changed before processing.');
                }

                $user = $lockedEvent->user()->firstOrFail();
                $type = $result->getType() === 'tangki_refill' ? 'refill' : $result->getType();
                $matchesExpectedPayment = (int) ($result->metadata['user_id'] ?? 0) === (int) $lockedEvent->user_id
                    && $type === $lockedEvent->type
                    && Money::toCents($result->amount) === $lockedEvent->amount_cents
                    && strtolower((string) $result->currency) === strtolower((string) $lockedEvent->currency)
                    && hash_equals($lockedEvent->session_id, (string) $result->platformRef);

                if (! $matchesExpectedPayment) {
                    throw new RuntimeException('Stripe session does not match the pending payment record.');
                }

                $handlerResult = $this->handlerFactory->make($type)->handle($result, $user);

                $lockedEvent->status = PaymentEvent::STATUS_PROCESSED;
                $lockedEvent->processed_at = now();
                $lockedEvent->last_error = null;
                $lockedEvent->save();

                return [$lockedEvent, $handlerResult];
            });

            $this->notificationService->paymentProcessed(
                $processedEvent->loadMissing('user'),
                $handlerResult,
            );

            return $processedEvent;
        } catch (Throwable $exception) {
            PaymentEvent::whereKey($paymentEvent->id)->update([
                'status' => PaymentEvent::STATUS_FAILED,
                'last_error' => Str::limit($exception->getMessage(), 500, ''),
            ]);

            $failedEvent = PaymentEvent::with('user')->find($paymentEvent->id);
            if ($failedEvent) {
                $this->notificationService->paymentFailed($failedEvent);
            }

            throw $exception;
        }
    }
}
