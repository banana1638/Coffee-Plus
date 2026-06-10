<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    protected $fillable = [
        'session_id',
        'event_type',
        'user_id',
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
