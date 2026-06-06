<?php

namespace App\Http\Controllers\API;

use App\Contracts\CheckoutServiceInterface;
use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Http\Requests\API\CheckoutRequest;
use App\Traits\ApiResponse;
use App\Models\Order;
use App\Services\IdempotencyService;
use App\Services\OrderService;

class OrderController extends Controller
{
    use ApiResponse;

    protected CheckoutServiceInterface $checkoutService;
    protected OrderService $orderService;
    protected IdempotencyService $idempotencyService;

    public function __construct(
        CheckoutServiceInterface $checkoutService,
        OrderService $orderService,
        IdempotencyService $idempotencyService
    )
    {
        $this->checkoutService = $checkoutService;
        $this->orderService = $orderService;
        $this->idempotencyService = $idempotencyService;
    }

    public function checkout(CheckoutRequest $request)
    {
        $useOzIds = $request->input('use_oz', []);
        $couponCode = $request->input('coupon_code');
        $pickupTime = $request->input('pickup_time');
        $idempotencyKey = $request->input('idempotency_key');
        $requestHash = IdempotencyService::hashPayload([
            'use_oz' => $useOzIds,
            'coupon_code' => $couponCode,
            'pickup_time' => $pickupTime,
        ]);

        try {
            $result = $this->idempotencyService->run(
                $request->user(),
                $idempotencyKey,
                'api.checkout',
                $requestHash,
                function () use ($request, $useOzIds, $couponCode, $pickupTime) {
                    $order = $this->checkoutService->processCheckout(
                        $request->user(),
                        $useOzIds,
                        $couponCode,
                        $pickupTime
                    );

                    return [
                        'order_id' => $order->id,
                        'message' => 'Enjoy your coffee! Order #' . $order->bill_id . ' placed.',
                    ];
                }
            );

            $order = Order::with(['items.product'])->findOrFail($result['order_id']);
            $order->load(['items.product']);

            return $this->success(
                new OrderResource($order),
                $result['message']
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

    public function show($order)
    {
        $order = Order::where('user_id', request()->user()->id)
            ->where('id', $order)
            ->first();

        if (!$order) {
            return $this->error('Order not found.', 404);
        }

        $order->load(['items.product']);

        return $this->success(new OrderResource($order));
    }

    public function cancel($order)
    {
        try {
            $order = Order::where('user_id', request()->user()->id)
                ->where('id', $order)
                ->first();

            if (!$order) {
                return $this->error('Order not found.', 404);
            }

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
