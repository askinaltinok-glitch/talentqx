<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TalentQX Karar Motoru - Karar Matrisi
 */
class DecisionRule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'decision',
        'label_tr',
        'label_en',
        'conditions',
        'color',
        'icon',
        'description_tr',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    // Decision types
    public const DECISION_HIRE = 'HIRE';
    public const DECISION_HOLD = 'HOLD';
    public const DECISION_REJECT = 'REJECT';

    /**
     * Scope: Only active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all active rules ordered by priority
     */
    public static function getAllActive()
    {
        return static::active()
            ->orderBy('priority')
            ->get();
    }

    /**
     * Get decision by type
     */
    public static function getByDecision(string $decision): ?self
    {
        return static::where('decision', $decision)->first();
    }

    /**
     * Get label by locale
     */
    public function getLabel(string $locale = 'tr'): string
    {
        return $locale === 'en' ? ($this->label_en ?? $this->label_tr) : $this->label_tr;
    }

    /**
     * Evaluate if this decision applies to given scores
     */
    public function evaluate(array $scores, array $redFlags): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $scores, $redFlags)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition(string $condition, array $scores, array $redFlags): bool
    {
        // Parse condition like "overall_score >= 75"
        if (preg_match('/(\w+)\s*(>=|<=|>|<|==)\s*(\d+)/', $condition, $matches)) {
            $field = $matches[1];
            $operator = $matches[2];
            $value = (int) $matches[3];

            $actualValue = $scores[$field] ?? 0;

            return match ($operator) {
                '>=' => $actualValue >= $value,
                '<=' => $actualValue <= $value,
                '>' => $actualValue > $value,
                '<' => $actualValue < $value,
                '==' => $actualValue == $value,
                default => false,
            };
        }

        // Check for red flag conditions
        if ($condition === 'no critical_red_flags') {
            return !in_array('critical', array_column($redFlags, 'severity'));
        }

        if ($condition === 'critical_red_flag_detected') {
            return in_array('critical', array_column($redFlags, 'severity'));
        }

        return true;
    }
}
