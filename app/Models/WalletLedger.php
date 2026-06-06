<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletLedger extends Model
{
    protected $table = 'wallet_ledger';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
