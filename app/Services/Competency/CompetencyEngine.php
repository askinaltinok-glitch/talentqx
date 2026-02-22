<?php

namespace App\Services\Competency;

use App\Models\CandidateTrustProfile;
use App\Models\CompetencyAssessment;
use App\Models\FormInterview;
use App\Models\PoolCandidate;
use App\Models\TrustEvent;
use App\Services\Maritime\CalibrationConfig;
use Illuminate\Support\Facades\Log;

/**
 * Competency Engine v1 — Orchestrator
 *
 * Reads interview answers, runs CompetencyScorer, detects flags,
 * produces evidence summary, stores result as append-only assessment.
 *
 * Fail-open: catches exceptions, returns null.
 */
class CompetencyEngine
{
    public function __construct(
        private CompetencyScorer $scorer,
    ) {}

    public function compute(string $poolCandidateId, ?CalibrationConfig $calibration = null): ?array
    {
        if (!config('maritime.competency_v1')) {
            return null;
        }

        try {
            return $this->doCompute($poolCandidateId, $calibration);
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('[CompetencyEngine] compute failed', [
                'candidate' => $poolCandidateId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function doCompute(string $poolCandidateId, ?CalibrationConfig $calibration = null): ?array
    {
        $candidate = PoolCandidate::find($poolCandidateId);
        if (!$candidate) {
            return null;
        }

        // Find the latest completed interview with answers
        $interview = FormInterview::where('pool_candidate_id', $poolCandidateId)
            ->where('status', FormInterview::STATUS_COMPLETED)
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->first();

        if (!$interview) {
            return null;
        }

        $answers = $interview->answers()->get();
        if ($answers->isEmpty()) {
            return null;
        }

        // Determine role/vessel/operation scope from interview or candidate
        $roleScope = $this->resolveRoleScope($interview, $candidate);
        $vesselScope = $this->resolveVesselScope($interview, $candidate);
        $operationScope = $this->resolveOperationScope($interview, $candidate);

        // Score (with optional fleet-calibrated dimension weights)
        $dimensionWeights = $calibration?->competencyDimensionWeights();
        $scoreResult = $this->scorer->score($answers, $roleScope, $vesselScope, $operationScope, $dimensionWeights);

        if ($scoreResult['questions_evaluated'] === 0) {
            return null;
        }

        // Detect flags
        $flags = $this->detectFlags($scoreResult['score_by_dimension']);

        // Evidence by dimension (keyword matches)
        $evidenceByDimension = $scoreResult['evidence_by_dimension'] ?? [];

        // Build evidence summary (including evidence bullets)
        $evidence = $this->buildEvidence($scoreResult['score_by_dimension'], $flags, $evidenceByDimension);

        // Resolve status
        $status = $this->resolveStatus($scoreResult['score_total']);

        $result = [
            'score_total' => $scoreResult['score_total'],
            'score_by_dimension' => $scoreResult['score_by_dimension'],
            'flags' => $flags,
            'evidence_summary' => $evidence,
            'evidence_by_dimension' => $evidenceByDimension,
            'status' => $status,
            'questions_evaluated' => $scoreResult['questions_evaluated'],
            'interview_id' => $interview->id,
            'computed_at' => now()->toIso8601String(),
            // Language metadata for fairness guardrail
            'language' => $scoreResult['language'] ?? 'unknown',
            'language_confidence' => $scoreResult['language_confidence'] ?? 0.0,
            'coverage' => $scoreResult['coverage'] ?? 0.0,
            // Technical depth layer v1
            'technical_depth_index' => $scoreResult['technical_depth_index'] ?? null,
            'technical_depth_detail' => $scoreResult['technical_depth_detail'] ?? null,
        ];

        // Append-only: always create new assessment row
        $assessment = CompetencyAssessment::create([
            'pool_candidate_id' => $poolCandidateId,
            'form_interview_id' => $interview->id,
            'computed_at' => now(),
            'score_total' => $scoreResult['score_total'],
            'score_by_dimension' => $scoreResult['score_by_dimension'],
            'flags' => $flags,
            'evidence_summary' => $evidence,
            'answer_scores' => $scoreResult['answer_scores'],
        ]);

        // Update trust profile
        $this->storeTrustProfile($poolCandidateId, $result);

        // Audit event
        TrustEvent::create([
            'pool_candidate_id' => $poolCandidateId,
            'event_type' => 'competency_assessed',
            'payload_json' => [
                'assessment_id' => $assessment->id,
                'score_total' => $result['score_total'],
                'status' => $status,
                'flag_count' => count($flags),
                'questions_evaluated' => $scoreResult['questions_evaluated'],
                'technical_depth_index' => $result['technical_depth_index'],
            ],
        ]);

        return $result;
    }

    private function detectFlags(array $scoreByDimension): array
    {
        $cfg = config('maritime.competency', []);
        $thresholds = $cfg['flag_thresholds'] ?? [];
        $flagDimMap = $cfg['flag_dimension_map'] ?? [];
        $flags = [];

        foreach ($thresholds as $flagName => $threshold) {
            $dimCode = $flagDimMap[$flagName] ?? null;
            if (!$dimCode) continue;

            $dimScore = $scoreByDimension[$dimCode] ?? null;
            if ($dimScore !== null && $dimScore < $threshold) {
                $flags[] = $flagName;
            }
        }

        // Special: safety_mindset_missing if DISCIPLINE is critically low
        // AND no professional safety keywords detected (already captured by low score)
        // This is already handled by the threshold above

        return $flags;
    }

    private function buildEvidence(array $scoreByDimension, array $flags, array $evidenceByDimension = []): array
    {
        $cfg = config('maritime.competency', []);
        $criticalFlags = $cfg['critical_flags'] ?? [];

        // Sort dimensions by score
        arsort($scoreByDimension);
        $sorted = array_keys($scoreByDimension);

        // Top strengths (max 3 — dimensions scoring >= 60)
        $strengths = [];
        foreach ($sorted as $dimCode) {
            if ($scoreByDimension[$dimCode] >= 60 && count($strengths) < 3) {
                $strengths[] = $this->dimensionStrengthLine($dimCode, $scoreByDimension[$dimCode]);
            }
        }

        // Top concerns (max 3 — dimensions scoring < 50)
        $reversed = array_reverse($sorted);
        $concerns = [];
        foreach ($reversed as $dimCode) {
            if ($scoreByDimension[$dimCode] < 50 && count($concerns) < 3) {
                $concerns[] = $this->dimensionConcernLine($dimCode, $scoreByDimension[$dimCode]);
            }
        }

        // Why lines for flags
        $whyLines = [];
        foreach ($flags as $flag) {
            $isCritical = in_array($flag, $criticalFlags);
            $whyLines[] = [
                'flag' => $flag,
                'severity' => $isCritical ? 'critical' : 'warning',
                'reason' => $this->flagReasonLine($flag),
            ];
        }

        // Evidence bullets: top 3 strongest domain signals across all dimensions
        $evidenceBullets = $this->buildEvidenceBullets($scoreByDimension, $evidenceByDimension);

        return [
            'strengths' => $strengths,
            'concerns' => $concerns,
            'why_lines' => $whyLines,
            'evidence_bullets' => $evidenceBullets,
        ];
    }

    /**
     * Build top evidence bullets from matched keywords, prioritizing
     * highest-scoring dimensions.
     */
    private function buildEvidenceBullets(array $scoreByDimension, array $evidenceByDimension): array
    {
        arsort($scoreByDimension);
        $bullets = [];
        $dimLabels = [
            'DISCIPLINE' => 'Safety/Discipline',
            'LEADERSHIP' => 'Leadership',
            'STRESS'     => 'Stress Management',
            'TEAMWORK'   => 'Teamwork',
            'COMMS'      => 'Communication',
            'TECH_PRACTICAL' => 'Technical',
        ];

        foreach ($scoreByDimension as $dimCode => $score) {
            $kws = $evidenceByDimension[$dimCode] ?? [];
            if (empty($kws)) continue;
            $label = $dimLabels[$dimCode] ?? $dimCode;
            $kwList = implode(', ', array_slice($kws, 0, 3));
            $bullets[] = "{$label}: {$kwList}";
            if (count($bullets) >= 3) break;
        }

        return $bullets;
    }

    private function resolveStatus(float $scoreTotal): string
    {
        $cfg = config('maritime.competency.status_thresholds', []);
        $strongThreshold = $cfg['strong'] ?? 70;
        $moderateThreshold = $cfg['moderate'] ?? 45;

        if ($scoreTotal >= $strongThreshold) return 'strong';
        if ($scoreTotal >= $moderateThreshold) return 'moderate';
        return 'weak';
    }

    private function storeTrustProfile(string $poolCandidateId, array $result): void
    {
        $tp = CandidateTrustProfile::firstOrNew(
            ['pool_candidate_id' => $poolCandidateId]
        );

        $detailJson = $tp->detail_json ?? [];
        $detailJson['competency_engine'] = $result;

        $tp->detail_json = $detailJson;
        $tp->competency_score = (int) round($result['score_total']);
        $tp->competency_status = $result['status'];
        $tp->competency_computed_at = now();

        if (!$tp->exists) {
            $tp->pool_candidate_id = $poolCandidateId;
            $tp->cri_score = 0;
            $tp->confidence_level = 'low';
            $tp->computed_at = now();
        }

        $tp->save();
    }

    private function resolveRoleScope(FormInterview $interview, PoolCandidate $candidate): string
    {
        // Try to get from interview position_code, then map via RankToRoleScopeMapper
        $posCode = trim($interview->position_code ?? $interview->template_position_code ?? '');
        if ($posCode) {
            return RankToRoleScopeMapper::map($posCode);
        }
        return 'ALL';
    }

    private function resolveVesselScope(FormInterview $interview, PoolCandidate $candidate): string
    {
        return 'all';
    }

    private function resolveOperationScope(FormInterview $interview, PoolCandidate $candidate): string
    {
        return 'both';
    }

    private function dimensionStrengthLine(string $dimCode, float $score): string
    {
        $labels = [
            'DISCIPLINE' => 'Strong procedural discipline and safety awareness',
            'LEADERSHIP' => 'Effective leadership and decision-making ability',
            'STRESS' => 'Good stress management and composure under pressure',
            'TEAMWORK' => 'Strong team collaboration and multicultural awareness',
            'COMMS' => 'Clear communication and reporting skills',
            'TECH_PRACTICAL' => 'Solid technical knowledge and practical problem-solving',
        ];
        $label = $labels[$dimCode] ?? "Strong in $dimCode";
        return "$label (" . round($score) . "%)";
    }

    private function dimensionConcernLine(string $dimCode, float $score): string
    {
        $labels = [
            'DISCIPLINE' => 'Procedural discipline needs improvement',
            'LEADERSHIP' => 'Leadership capabilities require development',
            'STRESS' => 'Stress management approach needs attention',
            'TEAMWORK' => 'Team collaboration skills below expectations',
            'COMMS' => 'Communication and reporting accuracy needs improvement',
            'TECH_PRACTICAL' => 'Technical knowledge gaps identified',
        ];
        $label = $labels[$dimCode] ?? "Concern in $dimCode";
        return "$label (" . round($score) . "%)";
    }

    private function flagReasonLine(string $flag): string
    {
        $reasons = [
            'low_discipline' => 'Candidate showed insufficient awareness of safety procedures and ISM compliance',
            'poor_teamwork' => 'Team collaboration and conflict resolution skills below threshold',
            'high_stress_risk' => 'Stress management responses indicate potential risk under pressure',
            'communication_gap' => 'Communication skills and reporting accuracy need significant improvement',
            'safety_mindset_missing' => 'Critical: Safety awareness below minimum acceptable standard',
            'leadership_risk' => 'Leadership and decision-making capability needs development',
        ];
        return $reasons[$flag] ?? "Flag triggered: $flag";
    }
}
