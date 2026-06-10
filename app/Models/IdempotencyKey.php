<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'key',
        'route',
        'status',
        'response_json',
        'locked_until',
    ];

    protected $casts = [
        'response_json' => 'array',
        'locked_until' => 'datetime',
    ];
}
