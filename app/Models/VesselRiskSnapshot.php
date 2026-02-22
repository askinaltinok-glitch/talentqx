<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselRiskSnapshot extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'vessel_id',
        'fleet_type',
        'vessel_tier',
        'crew_count',
        'avg_predictive_risk',
        'avg_stability_index',
        'avg_compliance_score',
        'avg_competency_score',
        'high_risk_count',
        'critical_risk_count',
        'detail_json',
        'computed_at',
    ];

    protected $casts = [
        'avg_predictive_risk' => 'float',
        'avg_stability_index' => 'float',
        'avg_compliance_score' => 'float',
        'avg_competency_score' => 'float',
        'high_risk_count' => 'integer',
        'critical_risk_count' => 'integer',
        'detail_json' => 'array',
        'computed_at' => 'datetime',
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }
}
