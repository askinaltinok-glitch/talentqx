<?php

namespace App\Domains\OrgHealth\WorkStyle;

use App\Models\OrgAssessment;
use App\Models\OrgQuestion;
use App\Models\OrgWorkstyleProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkstyleScoringService
{
    public function completeAndScore(OrgAssessment $assessment): OrgWorkstyleProfile
    {
        $questionnaire = $assessment->questionnaire()->with('questions')->firstOrFail();

        if ($assessment->status !== 'started') {
            throw ValidationException::withMessages(['assessment' => 'Assessment is not in started state.']);
        }

        $questions = $questionnaire->questions;
        if ($questions->count() !== 40) {
            throw ValidationException::withMessages(['questionnaire' => 'WorkStyle v1 requires exactly 40 questions.']);
        }

        $answers = $assessment->answers()->get()->keyBy('question_id');

        // Validate all answered
        foreach ($questions as $q) {
            if (!$answers->has($q->id)) {
                throw ValidationException::withMessages(['answers' => 'All 40 questions must be answered before completion.']);
            }
            $v = (int)$answers[$q->id]->value;
            if ($v < 1 || $v > 5) {
                throw ValidationException::withMessages(['answers' => 'Answer values must be between 1 and 5.']);
            }
        }

        $schema = $questionnaire->scoring_schema;
        $rawMin = (int)($schema['normalization']['raw_min'] ?? 8);
        $rawMax = (int)($schema['normalization']['raw_max'] ?? 40);
        $range = $rawMax - $rawMin; // 32
        if ($range <= 0) {
            throw ValidationException::withMessages(['schema' => 'Invalid normalization schema.']);
        }

        $dimensionSums = [
            'planning' => 0,
            'social' => 0,
            'cooperation' => 0,
            'stability' => 0,
            'adaptability' => 0,
        ];

        /** @var OrgQuestion $q */
        foreach ($questions as $q) {
            $value = (int)$answers[$q->id]->value;
            $adjusted = $q->is_reverse ? (6 - $value) : $value;
            $dimensionSums[$q->dimension] += $adjusted;
        }

        $scores = [];
        foreach ($dimensionSums as $dim => $raw) {
            $score = (($raw - $rawMin) / $range) * 100;
            $scores[$dim] = round($score, 2);
        }

        return DB::transaction(function () use ($assessment, $scores) {
            $assessment->status = 'completed';
            $assessment->completed_at = Carbon::now();
            $assessment->next_due_at = Carbon::now()->addDays(365);
            $assessment->save();

            return OrgWorkstyleProfile::create([
                'tenant_id' => $assessment->tenant_id,
                'employee_id' => $assessment->employee_id,
                'assessment_id' => $assessment->id,
                'planning_score' => $scores['planning'],
                'social_score' => $scores['social'],
                'cooperation_score' => $scores['cooperation'],
                'stability_score' => $scores['stability'],
                'adaptability_score' => $scores['adaptability'],
                'computed_at' => Carbon::now(),
            ]);
        });
    }
}
