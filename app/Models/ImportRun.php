<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportRun extends Model
{
    use HasUuids;

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'user_id',
        'company_id',
        'type',
        'filename',
        'status',
        'total_rows',
        'created_count',
        'updated_count',
        'skipped_count',
        'error_count',
        'row_issues',
        'summary',
    ];

    protected $casts = [
        'total_rows'    => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'skipped_count' => 'integer',
        'error_count'   => 'integer',
        'row_issues'    => 'array',
        'summary'       => 'array',
    ];
}
