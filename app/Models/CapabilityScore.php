<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapabilityScore extends Model
{
    use HasUuids;

    protected $fillable = [
        'form_interview_id',
        'candidate_id',
        'command_class',
        'nav_complex_raw', 'cmd_scale_raw', 'tech_depth_raw',
        'risk_mgmt_raw', 'crew_lead_raw', 'auto_dep_raw', 'crisis_rsp_raw',
        'nav_complex_adj', 'cmd_scale_adj', 'tech_depth_adj',
        'risk_mgmt_adj', 'crew_lead_adj', 'auto_dep_adj', 'crisis_rsp_adj',
        'axis_scores',
        'crl',
        'deployment_flags',
        'scoring_version',
        'scored_at',
    ];

    protected $casts = [
        'axis_scores' => 'array',
        'deployment_flags' => 'array',
        'scored_at' => 'datetime',
        'nav_complex_raw' => 'float', 'cmd_scale_raw' => 'float',
        'tech_depth_raw' => 'float', 'risk_mgmt_raw' => 'float',
        'crew_lead_raw' => 'float', 'auto_dep_raw' => 'float',
        'crisis_rsp_raw' => 'float',
        'nav_complex_adj' => 'float', 'cmd_scale_adj' => 'float',
        'tech_depth_adj' => 'float', 'risk_mgmt_adj' => 'float',
        'crew_lead_adj' => 'float', 'auto_dep_adj' => 'float',
        'crisis_rsp_adj' => 'float',
    ];

    /**
     * The 7 capability dimension codes.
     */
    public const CAPABILITIES = [
        'NAV_COMPLEX', 'CMD_SCALE', 'TECH_DEPTH', 'RISK_MGMT',
        'CREW_LEAD', 'AUTO_DEP', 'CRISIS_RSP',
    ];

    /**
     * Column prefix map for capabilities.
     */
    public const COLUMN_MAP = [
        'NAV_COMPLEX' => 'nav_complex',
        'CMD_SCALE' => 'cmd_scale',
        'TECH_DEPTH' => 'tech_depth',
        'RISK_MGMT' => 'risk_mgmt',
        'CREW_LEAD' => 'crew_lead',
        'AUTO_DEP' => 'auto_dep',
        'CRISIS_RSP' => 'crisis_rsp',
    ];

    /**
     * CRL levels.
     */
    public const CRL_1 = 'CRL_1';
    public const CRL_2 = 'CRL_2';
    public const CRL_3 = 'CRL_3';
    public const CRL_4 = 'CRL_4';
    public const CRL_5 = 'CRL_5';

    public const CRL_LABELS = [
        'CRL_1' => 'Not ready — significant capability gaps',
        'CRL_2' => 'Development needed — targeted training required',
        'CRL_3' => 'Ready for supervised command',
        'CRL_4' => 'Ready for independent command',
        'CRL_5' => 'Ready for complex/high-value command',
    ];

    // ── Relationships ──

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    // ── Accessors ──

    public function getRawScore(string $capabilityCode): float
    {
        $col = self::COLUMN_MAP[$capabilityCode] ?? null;
        return $col ? (float) $this->{$col . '_raw'} : 0;
    }

    public function getAdjustedScore(string $capabilityCode): float
    {
        $col = self::COLUMN_MAP[$capabilityCode] ?? null;
        return $col ? (float) $this->{$col . '_adj'} : 0;
    }

    /**
     * Get all raw scores as associative array.
     */
    public function getRawScores(): array
    {
        $scores = [];
        foreach (self::CAPABILITIES as $cap) {
            $scores[$cap] = $this->getRawScore($cap);
        }
        return $scores;
    }

    /**
     * Get all adjusted scores as associative array.
     */
    public function getAdjustedScores(): array
    {
        $scores = [];
        foreach (self::CAPABILITIES as $cap) {
            $scores[$cap] = $this->getAdjustedScore($cap);
        }
        return $scores;
    }

    public function getCrlLabel(): string
    {
        return self::CRL_LABELS[$this->crl] ?? 'Unknown';
    }
}
