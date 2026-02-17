<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RetentionAuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'run_at',
        'dry_run',
        'batch_size',
        'deleted_incomplete_count',
        'anonymized_completed_count',
        'deleted_orphan_consents_count',
        'errors_count',
        'duration_ms',
        'notes',
    ];

    protected $casts = [
        'run_at' => 'datetime',
        'dry_run' => 'boolean',
        'batch_size' => 'integer',
        'deleted_incomplete_count' => 'integer',
        'anonymized_completed_count' => 'integer',
        'deleted_orphan_consents_count' => 'integer',
        'errors_count' => 'integer',
        'duration_ms' => 'integer',
    ];
}
