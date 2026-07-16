<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Models\Order;
use App\Models\User;
use App\Notifications\RealtimeBusinessNotification;
use App\Services\OrderStateMachine;
use App\Services\RealtimeNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RealtimeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_broadcast_notification_channel_uses_public_uuid(): void
    {
        $user = User::factory()->create();

        $this->assertSame(
            'App.Models.User.'.$user->uuid,
            $user->receivesBroadcastNotificationsOn(),
        );
        $this->assertNotSame(
            'App.Models.User.'.$user->id,
            $user->receivesBroadcastNotificationsOn(),
        );
    }

    public function test_order_placed_dispatches_accepted_notification_with_stable_contract(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = $this->createOrder($user, 'CP-NOTIFY-ACCEPTED');

        event(new OrderPlaced($order, $user, [], 0.00, 0, 0));

        Notification::assertSentTo(
            $user,
            RealtimeBusinessNotification::class,
            function (RealtimeBusinessNotification $notification) use ($order, $user): bool {
                $payload = $notification->toArray($user);

                return $notification->eventName() === 'order.accepted'
                    && $notification->broadcastType() === 'order.accepted'
                    && $notification->via($user) === ['database', 'broadcast']
                    && $payload['event'] === 'order.accepted'
                    && $payload['action'] === [
                        'type' => 'order_detail',
                        'order_id' => $order->id,
                        'bill_id' => $order->bill_id,
                    ]
                    && $payload['data']['order_status'] === Order::STATUS_PENDING;
            },
        );
    }

    public function test_order_status_transitions_dispatch_each_notification_once(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = $this->createOrder($user, 'CP-NOTIFY-FLOW');
        $stateMachine = app(OrderStateMachine::class);

        $stateMachine->transition($order, Order::STATUS_PREPARING);
        $stateMachine->transition($order, Order::STATUS_READY);
        $stateMachine->transition($order, Order::STATUS_COMPLETED);

        $this->assertSame(
            [
                'order.preparing',
                'order.ready_for_pickup',
                'order.completed',
            ],
            Notification::sent($user, RealtimeBusinessNotification::class)
                ->map(fn (RealtimeBusinessNotification $notification) => $notification->eventName())
                ->values()
                ->all(),
        );
    }

    public function test_notification_is_persisted_with_the_same_business_envelope(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'CP-NOTIFY-DATABASE');

        app(RealtimeNotificationService::class)->orderAccepted($order, $user);

        $storedNotification = $user->notifications()->sole();

        $this->assertSame('order.accepted', $storedNotification->data['event']);
        $this->assertSame($storedNotification->id, $storedNotification->data['notification_id']);
        $this->assertSame($order->id, $storedNotification->data['data']['order_id']);
        $this->assertSame('order_detail', $storedNotification->data['action']['type']);
    }

    public function test_order_cancellation_dispatches_cancelled_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = $this->createOrder($user, 'CP-NOTIFY-CANCEL');

        app(OrderStateMachine::class)->transition($order, Order::STATUS_CANCELLED);

        $this->assertSame(
            ['order.cancelled'],
            Notification::sent($user, RealtimeBusinessNotification::class)
                ->map(fn (RealtimeBusinessNotification $notification) => $notification->eventName())
                ->all(),
        );
    }

    public function test_pickup_reminder_command_is_idempotent(): void
    {
        Notification::fake();
        $this->travelTo(now()->startOfMinute());

        $user = User::factory()->create();
        $order = $this->createOrder(
            $user,
            'CP-NOTIFY-REMINDER',
            Order::STATUS_READY,
            now()->addMinutes(5),
        );

        $this->artisan('orders:send-pickup-reminders')
            ->expectsOutput('Pickup reminders queued: 1')
            ->assertSuccessful();

        $this->artisan('orders:send-pickup-reminders')
            ->expectsOutput('Pickup reminders queued: 0')
            ->assertSuccessful();

        $this->assertNotNull($order->fresh()->pickup_reminder_sent_at);
        $this->assertSame(
            ['order.pickup_reminder'],
            Notification::sent($user, RealtimeBusinessNotification::class)
                ->map(fn (RealtimeBusinessNotification $notification) => $notification->eventName())
                ->all(),
        );
    }

    private function createOrder(
        User $user,
        string $billId,
        string $status = Order::STATUS_PENDING,
        mixed $pickupTime = null,
    ): Order {
        return Order::create([
            'user_id' => $user->id,
            'bill_id' => $billId,
            'subtotal' => 10.00,
            'final_amount' => 10.00,
            'status' => $status,
            'pickup_time' => $pickupTime,
        ]);
    }
}
