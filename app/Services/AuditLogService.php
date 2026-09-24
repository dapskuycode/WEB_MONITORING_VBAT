<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function log(
        string $actorType,
        ?int $actorId,
        string $action,
        Model|string $auditable,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        $auditableType = is_string($auditable) ? $auditable : get_class($auditable);
        $auditableIdValue = $auditableId ?? (is_object($auditable) ? $auditable->getKey() : null);

        return AuditLog::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableIdValue,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function logModelChange(
        string $actorType,
        ?int $actorId,
        string $action,
        Model $model,
        ?array $oldValues = null,
    ): AuditLog {
        return $this->log(
            $actorType,
            $actorId,
            $action,
            $model,
            null,
            $oldValues,
            $model->toArray(),
        );
    }
}
