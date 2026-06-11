<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'session_id',
        'user_id',
        'type',
        'amount_cents',
        'currency',
        'status',
        'payload_json',
        'processed_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
