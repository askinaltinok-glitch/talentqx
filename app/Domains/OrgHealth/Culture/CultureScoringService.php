<?php

namespace App\Domains\OrgHealth\Culture;

use App\Models\OrgAssessment;
use App\Models\OrgCultureProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CultureScoringService
{
    private const CULTURE_TYPES = ['clan', 'adhocracy', 'market', 'hierarchy'];
    private const ITEMS_PER_TYPE = 6;
    private const TOTAL_QUESTIONS = 24;

    public function completeAndScore(OrgAssessment $assessment): OrgCultureProfile
    {
        $questionnaire = $assessment->questionnaire()->with('questions')->firstOrFail();

        if ($assessment->status !== 'started') {
            throw ValidationException::withMessages(['assessment' => 'Assessment is not in started state.']);
        }

        $questions = $questionnaire->questions;
        if ($questions->count() !== self::TOTAL_QUESTIONS) {
            throw ValidationException::withMessages(['questionnaire' => 'Culture v1 requires exactly 24 questions.']);
        }

        $answers = $assessment->answers()->get()->keyBy('question_id');

        // Validate all 24 answered with both current and preferred
        foreach ($questions as $q) {
            if (!$answers->has($q->id)) {
                throw ValidationException::withMessages(['answers' => 'All 24 questions must be answered before completion.']);
            }
            $a = $answers[$q->id];
            $cv = (int) $a->value;
            $pv = (int) $a->preferred_value;
            if ($cv < 1 || $cv > 5 || $pv < 1 || $pv > 5) {
                throw ValidationException::withMessages(['answers' => 'Both current and preferred values must be between 1 and 5.']);
            }
        }

        // Compute per-type averages
        $currentSums = array_fill_keys(self::CULTURE_TYPES, 0);
        $preferredSums = array_fill_keys(self::CULTURE_TYPES, 0);
        $counts = array_fill_keys(self::CULTURE_TYPES, 0);

        foreach ($questions as $q) {
            $a = $answers[$q->id];
            $type = $q->dimension;
            $currentSums[$type] += (int) $a->value;
            $preferredSums[$type] += (int) $a->preferred_value;
            $counts[$type]++;
        }

        $scores = [];
        foreach (self::CULTURE_TYPES as $type) {
            $n = $counts[$type];
            if ($n !== self::ITEMS_PER_TYPE) {
                throw ValidationException::withMessages(['questionnaire' => "Culture type $type must have exactly 6 items."]);
            }
            $scores["{$type}_current"] = round($currentSums[$type] / $n, 2);
            $scores["{$type}_preferred"] = round($preferredSums[$type] / $n, 2);
        }

        return DB::transaction(function () use ($assessment, $scores) {
            $assessment->status = 'completed';
            $assessment->completed_at = Carbon::now();
            $assessment->next_due_at = Carbon::now()->addDays(365); // yearly
            $assessment->save();

            return OrgCultureProfile::create([
                'tenant_id' => $assessment->tenant_id,
                'employee_id' => $assessment->employee_id,
                'assessment_id' => $assessment->id,
                'clan_current' => $scores['clan_current'],
                'clan_preferred' => $scores['clan_preferred'],
                'adhocracy_current' => $scores['adhocracy_current'],
                'adhocracy_preferred' => $scores['adhocracy_preferred'],
                'market_current' => $scores['market_current'],
                'market_preferred' => $scores['market_preferred'],
                'hierarchy_current' => $scores['hierarchy_current'],
                'hierarchy_preferred' => $scores['hierarchy_preferred'],
                'computed_at' => Carbon::now(),
            ]);
        });
    }
}
