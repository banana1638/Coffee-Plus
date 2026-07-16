<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\RealtimeNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendPickupReminders extends Command
{
    protected $signature = 'orders:send-pickup-reminders {--minutes= : Override the reminder window}';

    protected $description = 'Send one reminder for ready orders approaching their pickup time';

    public function handle(RealtimeNotificationService $notificationService): int
    {
        $minutes = $this->boundedMinutes(
            $this->option('minutes') ?? config('realtime_notifications.pickup_reminder_minutes', 10),
        );
        $graceMinutes = $this->boundedMinutes(
            config('realtime_notifications.pickup_reminder_grace_minutes', 15),
        );
        $windowStartsAt = now()->subMinutes($graceMinutes);
        $windowEndsAt = now()->addMinutes($minutes);
        $sent = 0;

        Order::query()
            ->where('status', Order::STATUS_READY)
            ->whereNull('pickup_reminder_sent_at')
            ->whereBetween('pickup_time', [$windowStartsAt, $windowEndsAt])
            ->select('id')
            ->chunkById(100, function ($orders) use (
                $notificationService,
                $windowStartsAt,
                $windowEndsAt,
                &$sent,
            ) {
                foreach ($orders as $order) {
                    DB::transaction(function () use (
                        $order,
                        $notificationService,
                        $windowStartsAt,
                        $windowEndsAt,
                        &$sent,
                    ) {
                        $lockedOrder = Order::whereKey($order->id)
                            ->with('user')
                            ->lockForUpdate()
                            ->first();

                        if (! $lockedOrder
                            || $lockedOrder->status !== Order::STATUS_READY
                            || $lockedOrder->pickup_reminder_sent_at !== null
                            || $lockedOrder->pickup_time === null
                            || $lockedOrder->pickup_time->lt($windowStartsAt)
                            || $lockedOrder->pickup_time->gt($windowEndsAt)) {
                            return;
                        }

                        $lockedOrder->pickup_reminder_sent_at = now();
                        $lockedOrder->save();

                        $notificationService->pickupReminder($lockedOrder);
                        $sent++;
                    });
                }
            });

        $this->info("Pickup reminders queued: {$sent}");

        return self::SUCCESS;
    }

    private function boundedMinutes(mixed $value): int
    {
        return min(max((int) $value, 1), 120);
    }
}
