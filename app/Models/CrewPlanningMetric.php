<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewPlanningMetric extends Model
{
    use HasUuids;

    protected $table = 'crew_planning_metrics';

    protected $fillable = [
        'company_id', 'vessel_id', 'metric_type',
        'rank_code', 'value', 'meta', 'period_date',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'meta' => 'array',
        'period_date' => 'date',
    ];

    public const TYPE_TIME_TO_FILL = 'time_to_fill_rank';
    public const TYPE_AVAIL_MATCH_RATE = 'availability_match_rate';
    public const TYPE_OVERLAP_REDUCTION = 'contract_overlap_reduction';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
