<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CrmAuditLog extends Model
{
    use HasUuids;

    protected $table = 'crm_audit_logs';

    protected $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public static function log(
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $userId = null,
        ?string $ip = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ip,
        ]);
    }
}
