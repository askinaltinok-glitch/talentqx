<?php

namespace App\Services\Fleet;

use App\Models\BehavioralProfile;
use App\Models\CandidateContract;
use App\Models\CaptainProfile;
use App\Models\CrewFeedback;
use App\Models\CrewOutcome;
use App\Models\PoolCandidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaptainProfilingService
{
    /**
     * Compute or recompute the captain profile for a candidate.
     * Uses multi-source evidence: contract outcomes, crew feedback, behavioral interview.
     */
    public function compute(string $candidateId): ?CaptainProfile
    {
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return null;
        }

        try {
            // Source 1: Contract history outcomes (highest priority)
            $outcomeVector = $this->extractFromOutcomes($candidateId);

            // Source 2: Crew feedback patterns (filtered for suspicious)
            $feedbackVector = $this->extractFromFeedback($candidateId);

            // Source 3: Interview behavioral dimensions
            $interviewVector = $this->extractFromInterview($candidateId);

            // Merge vectors with priority weighting
            $mergedVector = $this->mergeVectors($outcomeVector, $feedbackVector, $interviewVector);

            // Generate command labels
            $labels = $this->generateLabels($mergedVector['style']);

            // Calculate confidence from evidence breadth + depth
            $confidence = $this->calculateConfidence($mergedVector['evidence']);

            $profile = CaptainProfile::updateOrCreate(
                ['candidate_id' => $candidateId],
                [
                    'style_vector_json' => $mergedVector['style'],
                    'command_profile_json' => [
                        'labels' => $labels,
                        'primary_style' => $labels[0] ?? 'Unknown',
                        'trait_summary' => $this->traitSummary($mergedVector['style']),
                    ],
                    'evidence_counts_json' => $mergedVector['evidence'],
                    'confidence' => $confidence,
                    'last_computed_at' => now(),
                ]
            );

            return $profile;
        } catch (\Throwable $e) {
            Log::channel('single')->warning('CaptainProfilingService::compute failed', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get profile (cached/computed) for a candidate.
     */
    public function getProfile(string $candidateId): ?CaptainProfile
    {
        $profile = CaptainProfile::where('candidate_id', $candidateId)->first();

        // Recompute if stale (>7 days)
        if ($profile && $profile->last_computed_at && $profile->last_computed_at->diffInDays(now()) > 7) {
            $profile = $this->compute($candidateId);
        }

        // Compute if never done
        if (!$profile) {
            $profile = $this->compute($candidateId);
        }

        return $profile;
    }

    // ─── Source Extraction ─────────────────────────────────────────────

    /**
     * Extract style signals from crew outcomes where this candidate was captain.
     */
    private function extractFromOutcomes(string $candidateId): array
    {
        $outcomes = CrewOutcome::withoutTenantScope()
            ->where('captain_candidate_id', $candidateId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        if ($outcomes->isEmpty()) {
            return ['style' => [], 'count' => 0];
        }

        $authority = 0.5;
        $discipline = 0.5;
        $coaching = 0.5;
        $teamOrientation = 0.5;
        $procedural = 0.5;
        $empathy = 0.5;
        $count = $outcomes->count();

        foreach ($outcomes as $outcome) {
            $weight = $outcome->severity / 100; // 0-1

            switch ($outcome->outcome_type) {
                case CrewOutcome::TYPE_EARLY_TERMINATION:
                    // Frequent early terminations under this captain → lower team/empathy
                    $teamOrientation -= 0.08 * $weight;
                    $empathy -= 0.06 * $weight;
                    $authority += 0.04 * $weight; // may indicate overly authoritative
                    break;

                case CrewOutcome::TYPE_CONFLICT_REPORTED:
                    // Conflicts → lower coaching, lower empathy
                    $coaching -= 0.06 * $weight;
                    $empathy -= 0.08 * $weight;
                    $authority += 0.03 * $weight;
                    break;

                case CrewOutcome::TYPE_SAFETY_INCIDENT:
                    // Safety incidents → lower procedural
                    $procedural -= 0.10 * $weight;
                    $discipline -= 0.05 * $weight;
                    break;

                case CrewOutcome::TYPE_PERFORMANCE_HIGH:
                    // High performance crew → higher coaching, discipline
                    $coaching += 0.06 * $weight;
                    $discipline += 0.04 * $weight;
                    $teamOrientation += 0.03 * $weight;
                    break;

                case CrewOutcome::TYPE_RETENTION_SUCCESS:
                    // Crew stays → empathy, team orientation high
                    $empathy += 0.06 * $weight;
                    $teamOrientation += 0.06 * $weight;
                    $coaching += 0.03 * $weight;
                    break;
            }
        }

        return [
            'style' => [
                'authority' => $this->clamp($authority),
                'discipline' => $this->clamp($discipline),
                'coaching' => $this->clamp($coaching),
                'team_orientation' => $this->clamp($teamOrientation),
                'procedural' => $this->clamp($procedural),
                'empathy' => $this->clamp($empathy),
            ],
            'count' => $count,
        ];
    }

    /**
     * Extract style signals from crew feedback about this captain/officer.
     * Excludes suspicious feedback.
     */
    private function extractFromFeedback(string $candidateId): array
    {
        // Get contracts where this candidate served (to find feedback about their leadership)
        $contractIds = CandidateContract::where('pool_candidate_id', $candidateId)
            ->pluck('id');

        if ($contractIds->isEmpty()) {
            return ['style' => [], 'count' => 0];
        }

        // Get feedback FROM OTHER crew about this candidate (company_rates_seafarer about their vessel)
        $feedback = CrewFeedback::whereIn('candidate_contract_id', $contractIds)
            ->where('status', '!=', CrewFeedback::STATUS_REJECTED)
            ->get()
            ->filter(fn ($f) => !$f->isSuspicious()); // Exclude suspicious

        if ($feedback->isEmpty()) {
            return ['style' => [], 'count' => 0];
        }

        $count = $feedback->count();
        $avgCompetence = $feedback->avg('rating_competence') ?? 3;
        $avgTeamwork = $feedback->avg('rating_teamwork') ?? 3;
        $avgReliability = $feedback->avg('rating_reliability') ?? 3;
        $avgCommunication = $feedback->avg('rating_communication') ?? 3;
        $avgOverall = $feedback->avg('rating_overall') ?? 3;

        // Map 1-5 ratings to 0-1 traits
        return [
            'style' => [
                'authority' => $this->ratingToTrait($avgReliability, 0.6), // reliability → authority correlation
                'discipline' => $this->ratingToTrait($avgCompetence, 0.7),
                'coaching' => $this->ratingToTrait($avgOverall, 0.5),
                'team_orientation' => $this->ratingToTrait($avgTeamwork, 0.8),
                'procedural' => $this->ratingToTrait($avgReliability, 0.5),
                'empathy' => $this->ratingToTrait($avgCommunication, 0.7),
            ],
            'count' => $count,
        ];
    }

    /**
     * Extract style signals from behavioral interview dimensions.
     */
    private function extractFromInterview(string $candidateId): array
    {
        $profile = BehavioralProfile::where('candidate_id', $candidateId)
            ->where('status', BehavioralProfile::STATUS_FINAL)
            ->latest('computed_at')
            ->first();

        if (!$profile) {
            return ['style' => [], 'count' => 0];
        }

        $dims = $profile->dimensions_json ?? [];

        $dimScore = function (string $key) use ($dims): float {
            $val = $dims[$key] ?? null;
            if (is_array($val)) return (float) ($val['score'] ?? 50);
            return (float) ($val ?? 50);
        };

        return [
            'style' => [
                'authority' => $dimScore('DISCIPLINE_COMPLIANCE') / 100,
                'discipline' => $dimScore('DISCIPLINE_COMPLIANCE') / 100,
                'coaching' => $dimScore('LEARNING_GROWTH') / 100,
                'team_orientation' => $dimScore('TEAM_COOPERATION') / 100,
                'procedural' => ($dimScore('RELIABILITY_STABILITY') + $dimScore('DISCIPLINE_COMPLIANCE')) / 200,
                'empathy' => ($dimScore('COMM_CLARITY') + $dimScore('TEAM_COOPERATION')) / 200,
            ],
            'count' => 1,
        ];
    }

    // ─── Vector Processing ────────────────────────────────────────────

    /**
     * Merge vectors from 3 sources with priority weighting.
     * Outcomes: 45%, Feedback: 35%, Interview: 20%
     */
    private function mergeVectors(array $outcomes, array $feedback, array $interview): array
    {
        $traits = ['authority', 'discipline', 'coaching', 'team_orientation', 'procedural', 'empathy'];
        $weights = [
            'outcomes' => 0.45,
            'feedback' => 0.35,
            'interview' => 0.20,
        ];

        $merged = [];
        $totalWeight = 0;

        foreach ($traits as $trait) {
            $value = 0;
            $tw = 0;

            if (!empty($outcomes['style'][$trait])) {
                $value += $outcomes['style'][$trait] * $weights['outcomes'];
                $tw += $weights['outcomes'];
            }
            if (!empty($feedback['style'][$trait])) {
                $value += $feedback['style'][$trait] * $weights['feedback'];
                $tw += $weights['feedback'];
            }
            if (!empty($interview['style'][$trait])) {
                $value += $interview['style'][$trait] * $weights['interview'];
                $tw += $weights['interview'];
            }

            $merged[$trait] = $tw > 0 ? round($value / $tw, 2) : 0.50;
        }

        return [
            'style' => $merged,
            'evidence' => [
                ['source' => 'crew_outcomes', 'count' => $outcomes['count']],
                ['source' => 'crew_feedback', 'count' => $feedback['count']],
                ['source' => 'interview', 'count' => $interview['count']],
            ],
        ];
    }

    /**
     * Generate human-readable command profile labels from style vector.
     */
    private function generateLabels(array $style): array
    {
        $labels = [];

        if (($style['authority'] ?? 0) >= 0.65 && ($style['discipline'] ?? 0) >= 0.65) {
            $labels[] = 'Authoritative';
        }
        if (($style['team_orientation'] ?? 0) >= 0.65 && ($style['empathy'] ?? 0) >= 0.60) {
            $labels[] = 'Collaborative';
        }
        if (($style['procedural'] ?? 0) >= 0.70) {
            $labels[] = 'Procedural';
        }
        if (($style['coaching'] ?? 0) >= 0.65) {
            $labels[] = 'Coaching-Oriented';
        }
        if (($style['discipline'] ?? 0) >= 0.70 && !in_array('Authoritative', $labels)) {
            $labels[] = 'High Discipline';
        }

        if (empty($labels)) {
            $labels[] = 'Balanced';
        }

        return $labels;
    }

    /**
     * Calculate confidence from evidence breadth + depth.
     */
    private function calculateConfidence(array $evidence): float
    {
        $total = 0;
        $sources = 0;

        foreach ($evidence as $source) {
            if ($source['count'] > 0) {
                $sources++;
                $total += min($source['count'], 20); // cap at 20 per source
            }
        }

        // Confidence: sources breadth (0-3) × depth
        // 1 source with 1 data point = 0.15
        // 3 sources with 10+ each = 0.90+
        $breadthFactor = $sources / 3;
        $depthFactor = min(1.0, $total / 30);

        return round(min(0.99, $breadthFactor * 0.5 + $depthFactor * 0.5), 2);
    }

    /**
     * Generate trait summary text.
     */
    private function traitSummary(array $style): string
    {
        $top = collect($style)->sortDesc()->take(2)->keys()->all();
        $labels = [
            'authority' => 'authority-driven',
            'discipline' => 'disciplined',
            'coaching' => 'coaching-oriented',
            'team_orientation' => 'team-focused',
            'procedural' => 'procedure-adherent',
            'empathy' => 'empathetic',
        ];

        $parts = array_map(fn ($t) => $labels[$t] ?? $t, $top);
        return 'Primarily ' . implode(' and ', $parts);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    private function clamp(float $value, float $min = 0, float $max = 1): float
    {
        return max($min, min($max, round($value, 2)));
    }

    private function ratingToTrait(float $rating, float $weight): float
    {
        // Map 1-5 rating to 0-1, weighted
        $base = ($rating - 1) / 4; // 0-1
        return round(0.5 + ($base - 0.5) * $weight, 2);
    }
}
