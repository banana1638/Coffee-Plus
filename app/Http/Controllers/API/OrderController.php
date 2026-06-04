<?php

namespace App\Http\Controllers\API;

use App\Contracts\CheckoutServiceInterface;
use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Http\Requests\API\CheckoutRequest;
use App\Traits\ApiResponse;

class OrderController extends Controller
{
    use ApiResponse;

    protected CheckoutServiceInterface $checkoutService;

    public function __construct(CheckoutServiceInterface $checkoutService)
    {
        $this->checkoutService = $checkoutService;
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
}
