<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletLedger extends Model
{
    protected $table = 'wallet_ledger';

    protected $fillable = [
        'user_id',
        'direction',
        'amount_cents',
        'balance_before_cents',
        'balance_after_cents',
        'source_type',
        'source_id',
        'idempotency_key',
        'description',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
