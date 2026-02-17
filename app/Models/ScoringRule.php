<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TalentQX Karar Motoru - Puanlama Kurallari
 */
class ScoringRule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name_tr',
        'name_en',
        'score_type',
        'weight_percent',
        'source_competencies',
        'formula',
        'min_value',
        'max_value',
        'warning_threshold',
        'critical_threshold',
        'display_labels',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'source_competencies' => 'array',
        'display_labels' => 'array',
        'is_active' => 'boolean',
    ];

    // Score types
    public const TYPE_PRIMARY = 'primary';
    public const TYPE_RISK = 'risk';
    public const TYPE_FINAL = 'final';

    // Score codes
    public const CODE_COMMUNICATION = 'communication_score';
    public const CODE_RELIABILITY = 'reliability_score';
    public const CODE_TEAM_FIT = 'team_fit_score';
    public const CODE_STRESS = 'stress_score';
    public const CODE_GROWTH = 'growth_potential';
    public const CODE_JOB_FIT = 'job_fit_score';
    public const CODE_INTEGRITY_RISK = 'integrity_risk';
    public const CODE_TEAM_RISK = 'team_risk';
    public const CODE_STABILITY_RISK = 'stability_risk';
    public const CODE_OVERALL = 'overall_score';
    public const CODE_JOB_FIT_PROBABILITY = 'job_fit_probability';

    /**
     * Scope: Only active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('score_type', $type);
    }

    /**
     * Get primary scores
     */
    public static function getPrimaryScores()
    {
        return static::active()
            ->byType(self::TYPE_PRIMARY)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get risk scores
     */
    public static function getRiskScores()
    {
        return static::active()
            ->byType(self::TYPE_RISK)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Find by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get display label for a score value
     */
    public function getLabelForScore(int $score): ?array
    {
        foreach ($this->display_labels as $range => $label) {
            $parts = explode('-', $range);
            if (count($parts) === 2) {
                $min = (int) $parts[0];
                $max = (int) $parts[1];
                if ($score >= $min && $score <= $max) {
                    return $label;
                }
            }
        }
        return null;
    }

    /**
     * Check if score is in warning zone
     */
    public function isWarning(int $score): bool
    {
        if ($this->warning_threshold === null) {
            return false;
        }

        // For risk scores, higher is worse
        if ($this->score_type === self::TYPE_RISK) {
            return $score >= $this->warning_threshold;
        }

        // For primary scores, lower is worse
        return $score <= $this->warning_threshold;
    }

    /**
     * Check if score is critical
     */
    public function isCritical(int $score): bool
    {
        if ($this->critical_threshold === null) {
            return false;
        }

        if ($this->score_type === self::TYPE_RISK) {
            return $score >= $this->critical_threshold;
        }

        return $score <= $this->critical_threshold;
    }
}
