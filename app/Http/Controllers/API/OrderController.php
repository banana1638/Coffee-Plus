<?php

namespace App\Http\Controllers\API;

use App\Contracts\CheckoutServiceInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;

class OrderController extends Controller
{
    protected CheckoutServiceInterface $checkoutService;

    public function __construct(CheckoutServiceInterface $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function checkout(Request $request)
    {
        $useOzIds = $request->input('use_oz', []);

        try {
            $order = $this->checkoutService->processCheckout($request->user(), $useOzIds);

            $order->load(['items.product']);

            return response()->json([
                'status' => 'success',
                'message' => 'Enjoy your coffee! Order #' . $order->bill_id . ' placed.',
                'order' => new OrderResource($order)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}