<?php

namespace Tests\Feature;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_state_machine_rejects_invalid_transition(): void
    {
        $order = new Order();
        $order->user_id = User::factory()->create()->id;
        $order->bill_id = 'CP-STATE';
        $order->subtotal = 10.00;
        $order->final_amount = 10.00;
        $order->status = Order::STATUS_PENDING;
        $order->save();

        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('Order cannot transition from pending to completed.');

        app(OrderStateMachine::class)->transition($order, Order::STATUS_COMPLETED);
    }
}
