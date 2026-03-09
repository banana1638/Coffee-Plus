<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TransactionResource;
use App\Http\Resources\Api\UserResource;

class TangkiController extends Controller
{
    public function index()
    {
        $transactions = Auth::user()->transactions()->latest()->take(5)->get();
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

        $ozToInject = (int) ($amount * 10);

        $billId = 'TOPUP-' . strtoupper(uniqid());

        try {
            DB::transaction(function () use ($user, $amount, $ozToInject, $billId) {
                $user->increment('tangki_balance', $amount);
                $user->increment('tangki_oz', $ozToInject);

                $order = new Order();
                $order->user_id = $user->id;
                $order->bill_id = $billId;
                $order->subtotal = $amount;
                $order->oz_used = 0;
                $order->final_amount = $amount;
                $order->status = 'completed';
                $order->save();

                $transaction = new Transaction();
                $transaction->user_id = $user->id;
                $transaction->bill_id = $billId;
                $transaction->oz_delta = $ozToInject;
                $transaction->type = 'refill';
                $transaction->description = "Refilled RM" . number_format($amount, 2) . " (Earned {$ozToInject} OZ)";
                $transaction->save();
            });

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