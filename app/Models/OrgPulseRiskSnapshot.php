<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgPulseRiskSnapshot extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_pulse_risk_snapshots';
    protected $fillable = [
        'tenant_id', 'employee_id', 'pulse_profile_id',
        'risk_score', 'risk_level', 'drivers', 'suggestions',
        'computed_at',
    ];
    protected $casts = [
        'computed_at' => 'datetime',
        'drivers' => 'array',
        'suggestions' => 'array',
    ];

    public function employee() { return $this->belongsTo(OrgEmployee::class, 'employee_id'); }
    public function pulseProfile() { return $this->belongsTo(OrgPulseProfile::class, 'pulse_profile_id'); }
}
