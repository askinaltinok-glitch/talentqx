<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'action',
        'user_id',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
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
        ?string $userId = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'report_id' => $reportId,
            'action' => $action,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
