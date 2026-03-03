<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Order;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id())
            ->with(['bill.items.product']);

        if ($request->filled('search_id')) {
            $query->where('bill_id', 'LIKE', "%{$request->search_id}%");
        }

        if ($request->type === 'in') {
            $query->where('oz_delta', '>', 0);
        } elseif ($request->type === 'out') {
            $query->where('oz_delta', '<', 0);
        }

        $transactions = $query->latest()->paginate(15);

        return view('tangki.transactions', compact('transactions'));
    }

    public function showOrderDetail($bill_id)
    {
        $order = Order::where('bill_id', $bill_id)
            ->with(['items.product'])
            ->firstOrFail();
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('tangki.order-detail', compact('order'));
    }
}