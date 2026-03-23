<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Contracts\PaymentGatewayInterface;

class TangkiController extends Controller
{
    protected PaymentGatewayInterface $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function index()
    {
        $transactions = Auth::user()->transactions()->latest()->take(5)->get();
        return view('user.tangki.index', compact('transactions'));
    }

    public function refill(Request $request)
    {
        $user = Auth::user();
        $amount = (float) $request->input('amount');

        if ($amount <= 0) {
            return back()->with('error', 'Invalid amount.');
        }

        $items = [
            [
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => 'Tangki Refill',
                        'description' => "Refill RM" . number_format($amount, 2),
                    ],
                    'unit_amount' => (int) ($amount * 100),
                ],
                'quantity' => 1,
            ]
        ];

        $url = $this->paymentGateway->createCheckoutUrl($user, $items, [
            'type' => 'refill',
            'user_id' => $user->id,
            'amount' => $amount,
        ]);

        return redirect($url);
    }
}