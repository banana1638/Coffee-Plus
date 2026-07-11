<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\TransactionQueryService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TransactionResource;
use App\Http\Resources\Api\OrderResource;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionQueryService $transactionQueryService)
    {
    }

    public function index(Request $request)
    {
        $query = $this->transactionQueryService
            ->forUser($request->user(), $request->input('search_id'), $request->input('type'))
            ->select(['id', 'user_id', 'bill_id', 'type', 'description', 'oz_delta', 'created_at']);
        $transactions = $query->latest()->paginate(15);

        return response()->json([
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    public function showOrderDetail(Request $request, string $bill_id)
    {
        $order = $this->transactionQueryService->orderForUser(
            $request->user(),
            $bill_id,
            ['items.product'],
        );

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }

    public function refunds(Request $request)
    {
        $query = $this->transactionQueryService
            ->refundsForUser($request->user(), $request->input('search_id'))
            ->select(['id', 'user_id', 'bill_id', 'type', 'description', 'oz_delta', 'created_at']);
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
