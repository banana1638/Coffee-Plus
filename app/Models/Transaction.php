<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{

    protected $casts = [
        'oz_delta' => 'integer',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'bill_id', 'bill_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function boot()
    {
        parent::boot();
        static::updating(function ($model) {
            return false;
        });
    }
}