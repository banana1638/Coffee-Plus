<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;

class OrderController extends Controller
{
    protected $checkoutService;

    public function __construct(\App\Services\CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function checkout(Request $request)
    {
        $useOzIds = $request->input('use_oz', []);

        try {
            $order = $this->checkoutService->processCheckout($useOzIds);

            // 加载关联以确保 OrderResource 能够正确解析 items 数据
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