<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PaymentService;

class TangkiController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $transactions = Auth::user()->transactions()->latest()->take(5)->get();
        return view('user.tangki.index', compact('transactions'));
    }

    public function refill(Request $request)
    {
        $user = Auth::user();
        $amount = floatval($request->input('amount'));

        if ($amount <= 0) {
            return back()->with('error', 'Invalid amount.');
        }

        $session = $this->paymentService->createRefillSession($user->id, $amount);

        return redirect($session->url);
    }
}