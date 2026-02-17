<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ApplyFormEvent extends Model
{
    use HasUuids;

    protected $table = 'apply_form_events';

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'event_type',
        'step_number',
        'time_spent_seconds',
        'country_code',
        'source_channel',
        'user_agent',
        'ip_hash',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    // Event types
    public const EVENT_STEP_VIEW = 'step_view';
    public const EVENT_STEP_COMPLETE = 'step_complete';
    public const EVENT_ABANDON = 'abandon';
    public const EVENT_SUBMIT = 'submit';

    // Steps
    public const STEP_PERSONAL = 1;
    public const STEP_MARITIME = 2;
    public const STEP_CONFIRM = 3;

    public function scopeInDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to . ' 23:59:59']);
    }
}
