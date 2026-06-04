<?php

namespace App\Http\Controllers\API;

use App\Contracts\CheckoutServiceInterface;
use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Http\Requests\API\CheckoutRequest;
use App\Traits\ApiResponse;
use App\Models\Order;
use App\Services\OrderService;

class OrderController extends Controller
{
    use ApiResponse;

    protected CheckoutServiceInterface $checkoutService;
    protected OrderService $orderService;

    public function __construct(CheckoutServiceInterface $checkoutService, OrderService $orderService)
    {
        $this->checkoutService = $checkoutService;
        $this->orderService = $orderService;
    }

    public function checkout(CheckoutRequest $request)
    {
        $useOzIds = $request->input('use_oz', []);
        $couponCode = $request->input('coupon_code');
        $pickupTime = $request->input('pickup_time');

        try {
            $order = $this->checkoutService->processCheckout(
                $request->user(),
                $useOzIds,
                $couponCode,
                $pickupTime
            );

            $order->load(['items.product']);

            return $this->success(
                new OrderResource($order),
                'Enjoy your coffee! Order #' . $order->bill_id . ' placed.'
            );
        } catch (CheckoutException $e) {
            return $this->error($e->getMessage(), $e->statusCode());
        } catch (\Throwable $e) {
            report($e);

            return $this->error('Checkout failed. Please try again.', 500);
        }
    }

    public function index()
    {
        $orders = Order::where('user_id', request()->user()->id)
            ->with(['items.product'])
            ->latest()
            ->paginate(15);

        return $this->success([
            'orders' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Order $order)
    {
        if ($order->user_id !== request()->user()->id) {
            return $this->error('Order not found.', 404);
        }

        $order->load(['items.product']);

        return $this->success(new OrderResource($order));
    }

    public function cancel(Order $order)
    {
        try {
            $order = $this->orderService->cancel($order, request()->user());

            return $this->success(
                new OrderResource($order),
                'Order cancelled and refunded to Tangki.'
            );
        } catch (\App\Exceptions\OrderException $e) {
            return $this->error($e->getMessage(), $e->statusCode());
        } catch (\Throwable $e) {
            report($e);

            return $this->error('Order cancellation failed. Please try again.', 500);
        }
    }
}
