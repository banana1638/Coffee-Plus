<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\InitiateRefillRequest;
use App\Services\RefillInitiationService;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TransactionResource;
use App\Http\Resources\Api\UserResource;

class TangkiController extends Controller
{
    public function __construct(private readonly RefillInitiationService $refillInitiationService)
    {
    }

    public function index()
    {
        $transactions = Auth::user()->transactions()
            ->select(['id', 'user_id', 'bill_id', 'type', 'description', 'oz_delta', 'created_at'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'transactions' => TransactionResource::collection($transactions),
            'user' => new UserResource(Auth::user()),
        ]);
    }

    /**
     * Initiate a balance refill via Stripe Checkout.
     * Actual balance crediting is handled by the Webhook → RefillHandler.
     */
    public function initiateRefill(InitiateRefillRequest $request)
    {
        try {
            $payment = $this->refillInitiationService->initiate(
                $request->user(),
                $request->amountCents(),
            );

            return response()->json([
                'status' => 'success',
                'session_id' => $payment->sessionId,
                'redirect_url' => $payment->redirectUrl,
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
