<?php

namespace App\Domains\OrgHealth\Pulse;

use App\Models\Company;
use App\Models\OrgAssessment;
use App\Models\OrgPulseProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PulseScoringService
{
    private const TOTAL_QUESTIONS = 5;

    private const DIMENSIONS = ['engagement', 'wellbeing', 'alignment', 'growth', 'retention_intent'];

    private const FREQUENCY_DAYS = [
        'weekly' => 7,
        'biweekly' => 14,
        'monthly' => 30,
    ];

    public function completeAndScore(OrgAssessment $assessment): OrgPulseProfile
    {
        $questionnaire = $assessment->questionnaire()->with('questions')->firstOrFail();

        if ($assessment->status !== 'started') {
            throw ValidationException::withMessages(['assessment' => 'Assessment is not in started state.']);
        }

        $questions = $questionnaire->questions;
        if ($questions->count() !== self::TOTAL_QUESTIONS) {
            throw ValidationException::withMessages(['questionnaire' => 'Pulse v1 requires exactly 5 questions.']);
        }

        $answers = $assessment->answers()->get()->keyBy('question_id');

        // Build dimension => raw value map
        $dimensionScores = [];
        foreach ($questions as $q) {
            if (!$answers->has($q->id)) {
                throw ValidationException::withMessages(['answers' => 'All 5 questions must be answered before completion.']);
            }
            $raw = (int) $answers[$q->id]->value;
            if ($raw < 1 || $raw > 5) {
                throw ValidationException::withMessages(['answers' => 'Values must be between 1 and 5.']);
            }
            $dimensionScores[$q->dimension] = $raw;
        }

        // Validate all 5 dimensions present
        foreach (self::DIMENSIONS as $dim) {
            if (!isset($dimensionScores[$dim])) {
                throw ValidationException::withMessages(['answers' => "Missing answer for dimension: $dim"]);
            }
        }

        // Per-dimension score: (raw / 5) * 100
        $scores = [];
        foreach (self::DIMENSIONS as $dim) {
            $scores["{$dim}_score"] = round(($dimensionScores[$dim] / 5) * 100, 2);
        }

        // Overall: average of 5 dimension scores
        $overall = round(array_sum($scores) / count($scores), 2);

        // Burnout proxy: ((6 - retention_intent_raw) / 5) * 100
        $burnoutProxy = round(((6 - $dimensionScores['retention_intent']) / 5) * 100, 2);

        // Cooldown days from company settings
        $cooldownDays = $this->getCooldownDays($assessment->tenant_id);

        return DB::transaction(function () use ($assessment, $scores, $overall, $burnoutProxy, $cooldownDays) {
            $assessment->status = 'completed';
            $assessment->completed_at = Carbon::now();
            $assessment->next_due_at = Carbon::now()->addDays($cooldownDays);
            $assessment->save();

            return OrgPulseProfile::create([
                'tenant_id' => $assessment->tenant_id,
                'employee_id' => $assessment->employee_id,
                'assessment_id' => $assessment->id,
                'engagement_score' => $scores['engagement_score'],
                'wellbeing_score' => $scores['wellbeing_score'],
                'alignment_score' => $scores['alignment_score'],
                'growth_score' => $scores['growth_score'],
                'retention_intent_score' => $scores['retention_intent_score'],
                'overall_score' => $overall,
                'burnout_proxy' => $burnoutProxy,
                'computed_at' => Carbon::now(),
            ]);
        });
    }

    public function getCooldownDays(string $tenantId): int
    {
        $company = Company::find($tenantId);
        $frequency = ($company->settings ?? [])['orghealth']['pulse_frequency'] ?? 'monthly';

        return self::FREQUENCY_DAYS[$frequency] ?? 30;
    }
}
