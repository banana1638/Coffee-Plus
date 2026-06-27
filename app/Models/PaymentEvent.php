<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_IGNORED = 'ignored';

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

    public static function recordPending(
        User $user,
        string $sessionId,
        string $type,
        int $amountCents,
        array $metadata = [],
    ): self {
        return self::create([
            'provider' => 'stripe',
            'event_id' => 'pending:' . $sessionId,
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'type' => $type,
            'amount_cents' => $amountCents,
            'currency' => 'myr',
            'status' => self::STATUS_PENDING,
            'payload_json' => ['metadata' => $metadata],
        ]);
    }
}
