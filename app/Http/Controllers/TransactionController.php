<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransactionQueryService;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionQueryService $transactionQueryService)
    {
    }

    public function index(Request $request)
    {
        $transactions = $this->transactionQueryService
            ->forUser($request->user(), $request->input('search_id'), $request->input('type'))
            ->with(['bill.items.product'])
            ->latest()
            ->paginate(15);

        return view('user.tangki.transactions', compact('transactions'));
    }

    public function showOrderDetail(Request $request, string $bill_id)
    {
        $order = $this->transactionQueryService->orderForUser(
            $request->user(),
            $bill_id,
            ['items.product', 'reviews'],
        );

        return view('user.tangki.order-detail', compact('order'));
    }
}
