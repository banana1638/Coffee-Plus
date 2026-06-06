<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(
        string $action,
        ?Model $target = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Admin $actor = null,
        ?Request $request = null
    ): AuditLog {
        $request ??= request();
        $actor ??= $request->user('admin');

        return AuditLog::create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
