<?php

namespace App\Http\Controllers;

use App\Models\{CartItem, Order, OrderItem, Transaction};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

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

            return redirect()->route('tangki.transactions')
                ->with('success', 'Enjoy your coffee! Order #' . $order->bill_id . ' placed.');
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}