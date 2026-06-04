<?php

namespace App\Http\Controllers;

use App\Contracts\CheckoutServiceInterface;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected CheckoutServiceInterface $checkoutService;
    protected OrderService $orderService;

    public function __construct(CheckoutServiceInterface $checkoutService, OrderService $orderService)
    {
        $this->checkoutService = $checkoutService;
        $this->orderService = $orderService;
    }

    public function checkout(Request $request)
    {
        $useOzIds = $request->input('use_oz', []);

        try {
            $order = $this->checkoutService->processCheckout(Auth::user(), $useOzIds);

            return redirect()->route('tangki.transactions')
                ->with('success', 'Enjoy your coffee! Order #' . $order->bill_id . ' placed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Order $order)
    {
        try {
            $this->orderService->cancel($order, Auth::user());

            return redirect()->route('tangki.transactions')
                ->with('success', 'Order cancelled and refunded to your Tangki.');
        } catch (OrderException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
