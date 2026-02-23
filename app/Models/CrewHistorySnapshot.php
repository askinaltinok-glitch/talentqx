<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewHistorySnapshot extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_id',
        'snapshot_date',
        'crew_roster',
        'dimension_averages',
        'avg_synergy_score',
        'crew_count',
        'trigger',
    ];

    protected $casts = [
        'snapshot_date'      => 'date',
        'crew_roster'        => 'array',
        'dimension_averages' => 'array',
        'avg_synergy_score'  => 'float',
        'crew_count'         => 'integer',
    ];

    const TRIGGER_SCHEDULED = 'scheduled';
    const TRIGGER_ASSIGNMENT_CHANGE = 'assignment_change';
    const TRIGGER_MANUAL = 'manual';

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }
}
