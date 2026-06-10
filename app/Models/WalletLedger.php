<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletLedger extends Model
{
    protected $table = 'wallet_ledger';

    protected $fillable = [
        'user_id',
        'type',
        'amount_cents',
        'balance_after_cents',
        'description',
        'idempotency_key',
        'reference_type',
        'reference_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
