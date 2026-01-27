<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'company_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'erased_by_request',
        'erasure_reason',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'erased_by_request' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            $log->created_at = $log->created_at ?? now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function log(
        string $action,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null
    ): self {
        return static::create([
            'user_id' => $user?->id ?? auth()->id(),
            'company_id' => $user?->company_id ?? auth()->user()?->company_id,
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function logErasure(
        string $entityType,
        string $entityId,
        string $reason,
        array $erasedData
    ): self {
        return static::create([
            'action' => 'erase',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => auth()->id(),
            'company_id' => auth()->user()?->company_id,
            'metadata' => ['erased_fields' => array_keys($erasedData)],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'erased_by_request' => true,
            'erasure_reason' => $reason,
        ]);
    }

    public function scopeForEntity($query, string $type, string $id)
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }

    public function scopeErasures($query)
    {
        return $query->where('erased_by_request', true);
    }
}
