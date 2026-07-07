<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TransactionResource;
use App\Http\Resources\Api\OrderResource;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id())
            ->select(['id', 'user_id', 'bill_id', 'type', 'description', 'oz_delta', 'created_at']);

        if ($request->filled('search_id')) {
            $query->where('bill_id', 'LIKE', "%{$request->search_id}%");
        }

        if ($request->type === 'in') {
            $query->where('oz_delta', '>', 0);
        } elseif ($request->type === 'out') {
            $query->where('oz_delta', '<', 0);
        }

        $transactions = $query->latest()->paginate(15);

        return response()->json([
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    public function showOrderDetail($bill_id)
    {
        $order = Order::where('bill_id', $bill_id)
            ->where('user_id', Auth::id())
            ->with(['items.product'])
            ->firstOrFail();

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }

    public function refunds(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id())
            ->where('type', 'refund')
            ->select(['id', 'user_id', 'bill_id', 'type', 'description', 'oz_delta', 'created_at']);

        if ($request->filled('search_id')) {
            $query->where('bill_id', 'LIKE', "%{$request->search_id}%");
        }

        $refunds = $query->latest()->paginate(15);

        return response()->json([
            'refunds' => TransactionResource::collection($refunds),
            'meta' => [
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
                'per_page' => $refunds->perPage(),
                'total' => $refunds->total(),
            ],
        ]);
    }
}
