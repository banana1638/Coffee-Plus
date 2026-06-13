<?php

namespace App\Http\Controllers\API;

use App\Contracts\TangkiServiceInterface;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TransactionResource;
use App\Http\Resources\Api\UserResource;

class TangkiController extends Controller
{
    protected TangkiServiceInterface $tangkiService;
    protected PaymentGatewayInterface $gateway;

    public function __construct(TangkiServiceInterface $tangkiService, PaymentGatewayInterface $gateway)
    {
        $this->tangkiService = $tangkiService;
        $this->gateway = $gateway;
    }

    public function index()
    {
        $transactions = Auth::user()->transactions()->with(['bill.items.product'])->latest()->take(5)->get();
        return response()->json([
            'transactions' => TransactionResource::collection($transactions),
            'user' => new UserResource(Auth::user()),
        ]);
    }

    /**
     * Initiate a balance refill via Stripe Checkout.
     * Actual balance crediting is handled by the Webhook → RefillHandler.
     */
    public function initiateRefill(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5|max:500',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $amount = (float) $request->input('amount');
        $amountCents = (int) round($amount * 100);

        try {
            $url = $this->gateway->createCheckoutUrl($user, [
                [
                    'price_data' => [
                        'currency' => 'myr',
                        'product_data' => ['name' => 'Tangki Balance Refill'],
                        'unit_amount' => $amountCents,
                    ],
                    'quantity' => 1,
                ],
            ], [
                'type' => 'refill',
                'user_id' => $user->id,
                'amount' => (string) $amount,
            ]);

            return response()->json([
                'status' => 'success',
                'redirect_url' => $url,
                'message' => 'Redirect to Stripe checkout.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate refill. Please try again.',
            ], 500);
        }
    }
}
