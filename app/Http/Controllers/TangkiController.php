<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentEvent;
use App\Support\Money;

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
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:5', 'max:500', 'decimal:0,2'],
        ]);

        $user = Auth::user();
        $amountCents = Money::toCents($validated['amount']);
        $amount = Money::fromCents($amountCents);

        $items = [
            [
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => 'Tangki Refill',
                        'description' => "Refill RM" . number_format($amount, 2),
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => 1,
            ]
        ];

        $metadata = [
            'type' => 'refill',
            'user_id' => $user->id,
            'amount' => number_format($amount, 2, '.', ''),
        ];
        $payment = $this->paymentGateway->createCheckout($user, $items, $metadata);

        PaymentEvent::recordPending($user, $payment->sessionId, 'refill', $amountCents, $metadata);

        return redirect($payment->redirectUrl);
    }
}
