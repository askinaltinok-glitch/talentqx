<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SystemReportLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_type',
        'report_date',
        'to_email',
        'subject',
        'status',
        'metrics_snapshot',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'metrics_snapshot' => 'array',
        'sent_at' => 'datetime',
    ];
}
