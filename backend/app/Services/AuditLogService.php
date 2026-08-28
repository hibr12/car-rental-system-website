<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogService
{
    public function log(
        User $actor,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $notes = null,
        ?int $branchId = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actor->id,
            'role' => $actor->role,
            'branch_id' => $branchId ?? $actor->branch_id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'notes' => $notes,
        ]);
    }

    public function forEntity(string $entityType, int $entityId, int $limit = 50)
    {
        return AuditLog::with(['user:id,name,email,role'])
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
