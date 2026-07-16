<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\RealtimeNotificationService;
use Illuminate\Support\Facades\Auth;

class OrderObserver
{
    public function __construct(private readonly RealtimeNotificationService $notificationService)
    {
    }

    public function created(Order $order): void
    {
        $this->record($order, null, $order->status);
    }

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $this->record($order, $order->getOriginal('status'), $order->status);
        $this->notificationService->orderStatusChanged($order);
    }

    private function record(Order $order, ?string $fromStatus, string $toStatus): void
    {
        [$actorType, $actorId] = $this->actor();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'source' => request()->route()?->getName()
                ?? (app()->runningInConsole() ? 'console' : request()->path()),
        ]);
    }

    private function actor(): array
    {
        if ($admin = Auth::guard('admin')->user()) {
            return ['admin', $admin->getAuthIdentifier()];
        }

        $user = request()->user() ?? Auth::guard('web')->user();

        if ($user instanceof User) {
            return ['user', $user->getAuthIdentifier()];
        }

        return ['system', null];
    }
}
