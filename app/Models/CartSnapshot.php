<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartSnapshot extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_EXPIRED = 'expired';

    protected $casts = [
        'items_json' => 'array',
        'pickup_time' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
