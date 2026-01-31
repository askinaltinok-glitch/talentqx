<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_id',
        'action',
        'actor_type',
        'actor_id',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Action constants
    public const ACTION_GENERATED = 'generated';
    public const ACTION_VIEWED = 'viewed';
    public const ACTION_DOWNLOADED = 'downloaded';
    public const ACTION_SHARED = 'shared';
    public const ACTION_DELETED = 'deleted';

    public function report(): BelongsTo
    {
        return $this->belongsTo(InterviewReport::class, 'report_id');
    }

    /**
     * Log an action
     */
    public static function log(
        string $reportId,
        string $action,
        ?string $actorType = null,
        ?string $actorId = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'report_id' => $reportId,
            'action' => $action,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
