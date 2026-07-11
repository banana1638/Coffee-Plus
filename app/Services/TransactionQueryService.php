<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TransactionQueryService
{
    public function forUser(User $user, ?string $searchId, ?string $direction): Builder
    {
        return $this->applyFilters(
            Transaction::where('user_id', $user->id),
            $searchId,
            $direction,
        );
    }

    public function refundsForUser(User $user, ?string $searchId): Builder
    {
        return $this->applySearch(
            Transaction::where('user_id', $user->id)->where('type', 'refund'),
            $searchId,
        );
    }

    public function orderForUser(User $user, string $billId, array $relations = []): Order
    {
        return Order::where('user_id', $user->id)
            ->where('bill_id', $billId)
            ->with($relations)
            ->firstOrFail();
    }

    private function applyFilters(Builder $query, ?string $searchId, ?string $direction): Builder
    {
        $this->applySearch($query, $searchId);

        return $query
            ->when($direction === 'in', fn (Builder $query) => $query->where('oz_delta', '>', 0))
            ->when($direction === 'out', fn (Builder $query) => $query->where('oz_delta', '<', 0));
    }

    private function applySearch(Builder $query, ?string $searchId): Builder
    {
        $searchId = trim((string) $searchId);

        return $query->when(
            $searchId !== '',
            fn (Builder $query) => $query->where('bill_id', 'like', '%'.$searchId.'%'),
        );
    }
}
