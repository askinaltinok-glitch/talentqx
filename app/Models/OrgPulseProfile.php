<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgPulseProfile extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_pulse_profiles';
    protected $fillable = [
        'tenant_id', 'employee_id', 'assessment_id',
        'engagement_score', 'wellbeing_score', 'alignment_score',
        'growth_score', 'retention_intent_score',
        'overall_score', 'burnout_proxy',
        'computed_at',
    ];
    protected $casts = ['computed_at' => 'datetime'];

    public function employee() { return $this->belongsTo(OrgEmployee::class, 'employee_id'); }
    public function assessment() { return $this->belongsTo(OrgAssessment::class, 'assessment_id'); }
    public function riskSnapshot() { return $this->hasOne(OrgPulseRiskSnapshot::class, 'pulse_profile_id'); }
}
