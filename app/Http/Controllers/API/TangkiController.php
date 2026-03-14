<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TransactionResource;
use App\Http\Resources\Api\UserResource;
use App\Services\TangkiService;

class TangkiController extends Controller
{
    public function index()
    {
        $transactions = Auth::user()->transactions()->with(['bill.items.product'])->latest()->take(5)->get();
        return response()->json([
            'transactions' => TransactionResource::collection($transactions),
            'user' => new UserResource(Auth::user()),
        ]);
    }

    public function refill(Request $request)
    {
        $user = Auth::user();
        $amount = floatval($request->input('amount'));

        if ($amount <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid amount.',
            ], 400);
        }

        $billId = 'TOPUP-' . strtoupper(uniqid());

        try {
            TangkiService::refill($user, $amount, $billId);

            return response()->json([
                'status' => 'success',
                'message' => 'Refill successful!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}