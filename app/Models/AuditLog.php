<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'action',
        'target_type',
        'target_id',
        'old_values_json',
        'new_values_json',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values_json' => 'array',
        'new_values_json' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(Admin::class, 'actor_user_id');
    }
}
