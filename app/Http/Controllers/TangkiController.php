<?php

namespace App\Http\Controllers;

use App\Http\Requests\InitiateRefillRequest;
use App\Services\RefillInitiationService;
use Illuminate\Support\Facades\Auth;

class TangkiController extends Controller
{
    public function __construct(private readonly RefillInitiationService $refillInitiationService)
    {
    }

    public function index()
    {
        $transactions = Auth::user()->transactions()->latest()->take(5)->get();
        return view('user.tangki.index', compact('transactions'));
    }

    public function refill(InitiateRefillRequest $request)
    {
        $payment = $this->refillInitiationService->initiate(
            $request->user(),
            $request->amountCents(),
        );

        return redirect($payment->redirectUrl);
    }
}
