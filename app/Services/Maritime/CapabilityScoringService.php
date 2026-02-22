<?php

namespace App\Services\Maritime;

use App\Models\CapabilityScore;
use App\Models\CommandClass;
use App\Models\FormInterview;
use Illuminate\Support\Facades\Log;

/**
 * Scores Phase-2 scenario responses into 7 independent capability dimensions.
 *
 * NO composite score.
 * Each dimension scored independently.
 * Class modifiers applied from weight_vector.
 * CRL computed from multi-axis thresholds.
 */
class CapabilityScoringService
{
    /**
     * Capability dimension → Phase-2 scenario slot mapping.
     */
    private const SLOT_MAP = [
        'NAV_COMPLEX' => 1,
        'CMD_SCALE' => 2,
        'TECH_DEPTH' => 3,
        'RISK_MGMT' => 4,
        'CREW_LEAD' => 5,
        'AUTO_DEP' => 6,
        'CRISIS_RSP' => 7,
    ];

    /**
     * Score a Phase-2 interview and generate capability profile.
     */
    public function score(FormInterview $interview): CapabilityScore
    {
        $commandClass = $interview->command_class_detected;
        $classModel = CommandClass::where('code', $commandClass)->first();

        if (!$classModel) {
            throw new \InvalidArgumentException("Unknown command class: {$commandClass}");
        }

        $candidateId = $interview->pool_candidate_id
            ?? $interview->meta['candidate_id']
            ?? null;

        $interview->load('answers');

        // Score each capability from its corresponding scenario answer
        $rawScores = [];
        $adjustedScores = [];
        $axisBreakdown = [];

        foreach (self::SLOT_MAP as $capability => $slot) {
            $answer = $interview->answers->firstWhere('slot', $slot);
            $answerText = $answer?->answer_text ?? '';

            // Per-axis scoring (heuristic from text quality)
            $axes = $this->scoreAxes($capability, $answerText);
            $axisBreakdown[$capability] = $axes;

            // Raw score: weighted axis average → 0-100 scale
            $rawScore = $this->computeRawScore($axes);
            $rawScores[$capability] = $rawScore;

            // Apply class modifier
            $modifier = $classModel->getWeightFor($capability);
            $adjusted = min(100, round($rawScore * $modifier, 1));
            $adjustedScores[$capability] = $adjusted;
        }

        // Compute CRL
        $crl = $this->computeCRL($adjustedScores);

        // Compute deployment flags
        $deploymentFlags = $this->computeDeploymentFlags($adjustedScores, $crl);

        // Persist
        $capScore = CapabilityScore::updateOrCreate(
            ['form_interview_id' => $interview->id],
            [
                'candidate_id' => $candidateId,
                'command_class' => $commandClass,
                'nav_complex_raw' => $rawScores['NAV_COMPLEX'],
                'cmd_scale_raw' => $rawScores['CMD_SCALE'],
                'tech_depth_raw' => $rawScores['TECH_DEPTH'],
                'risk_mgmt_raw' => $rawScores['RISK_MGMT'],
                'crew_lead_raw' => $rawScores['CREW_LEAD'],
                'auto_dep_raw' => $rawScores['AUTO_DEP'],
                'crisis_rsp_raw' => $rawScores['CRISIS_RSP'],
                'nav_complex_adj' => $adjustedScores['NAV_COMPLEX'],
                'cmd_scale_adj' => $adjustedScores['CMD_SCALE'],
                'tech_depth_adj' => $adjustedScores['TECH_DEPTH'],
                'risk_mgmt_adj' => $adjustedScores['RISK_MGMT'],
                'crew_lead_adj' => $adjustedScores['CREW_LEAD'],
                'auto_dep_adj' => $adjustedScores['AUTO_DEP'],
                'crisis_rsp_adj' => $adjustedScores['CRISIS_RSP'],
                'axis_scores' => $axisBreakdown,
                'crl' => $crl,
                'deployment_flags' => $deploymentFlags,
                'scoring_version' => 'v2',
                'scored_at' => now(),
            ]
        );

        // Store capability profile on interview
        $capabilityProfile = [];
        foreach (CapabilityScore::CAPABILITIES as $cap) {
            $col = CapabilityScore::COLUMN_MAP[$cap];
            $capabilityProfile[$cap] = [
                'raw' => $rawScores[$cap],
                'adjusted' => $adjustedScores[$cap],
                'modifier' => $classModel->getWeightFor($cap),
                'axes' => $axisBreakdown[$cap],
            ];
        }

        $interview->update([
            'capability_profile_json' => $capabilityProfile,
        ]);

        Log::info('CapabilityScoringService: scored', [
            'interview_id' => $interview->id,
            'candidate_id' => $candidateId,
            'command_class' => $commandClass,
            'crl' => $crl,
        ]);

        return $capScore;
    }

    /**
     * Score individual axes for a capability from answer text.
     *
     * Returns array of [axis => score (0-5)].
     */
    private function scoreAxes(string $capability, string $text): array
    {
        $textLen = mb_strlen(trim($text));

        // Base text quality score (0-5)
        $baseScore = match (true) {
            $textLen === 0 => 0,
            $textLen < 30 => 1,
            $textLen < 80 => 2,
            $textLen < 180 => 3,
            $textLen < 400 => 4,
            default => 5,
        };

        // Axis definitions per capability
        $axesDef = $this->getAxesDefinition($capability);
        $scores = [];

        foreach ($axesDef as $axis => $weight) {
            // Start from base, with small variance based on axis
            $axisScore = $baseScore;

            // Keyword-based adjustments
            $axisScore = $this->adjustForKeywords($axis, $text, $axisScore);

            $scores[$axis] = [
                'score' => max(0, min(5, $axisScore)),
                'weight' => $weight,
            ];
        }

        return $scores;
    }

    /**
     * Get axes and their weights for each capability.
     */
    private function getAxesDefinition(string $capability): array
    {
        return match ($capability) {
            'NAV_COMPLEX' => [
                'situational_awareness' => 0.30,
                'passage_planning' => 0.25,
                'traffic_management' => 0.25,
                'environmental_adaptation' => 0.20,
            ],
            'CMD_SCALE' => [
                'organizational_structure' => 0.25,
                'resource_allocation' => 0.25,
                'decision_authority' => 0.25,
                'operational_tempo' => 0.25,
            ],
            'TECH_DEPTH' => [
                'systems_knowledge' => 0.30,
                'cargo_competence' => 0.30,
                'stability_awareness' => 0.20,
                'equipment_operation' => 0.20,
            ],
            'RISK_MGMT' => [
                'risk_identification' => 0.30,
                'risk_assessment' => 0.25,
                'mitigation_quality' => 0.25,
                'safety_culture' => 0.20,
            ],
            'CREW_LEAD' => [
                'bridge_resource_management' => 0.30,
                'crew_development' => 0.20,
                'multicultural_management' => 0.20,
                'fatigue_management' => 0.15,
                'conflict_resolution' => 0.15,
            ],
            'AUTO_DEP' => [
                'automation_proficiency' => 0.30,
                'manual_capability' => 0.30,
                'degraded_mode_operations' => 0.25,
                'complacency_awareness' => 0.15,
            ],
            'CRISIS_RSP' => [
                'initial_response' => 0.30,
                'decision_under_pressure' => 0.30,
                'communication_in_crisis' => 0.20,
                'post_incident' => 0.20,
            ],
            default => ['general' => 1.0],
        };
    }

    /**
     * Adjust axis score based on keyword presence.
     */
    private function adjustForKeywords(string $axis, string $text, int $baseScore): int
    {
        if (empty($text)) return $baseScore;

        $textLower = mb_strtolower($text);
        $boost = 0;

        // Positive indicators per axis
        $positiveKeywords = match ($axis) {
            'situational_awareness' => ['radar', 'ais', 'lookout', 'traffic', 'bearing', 'range', 'cpa', 'tcpa'],
            'passage_planning' => ['waypoint', 'route', 'contingency', 'abort', 'alternative', 'tide', 'current'],
            'traffic_management' => ['colreg', 'rule', 'stand-on', 'give-way', 'crossing', 'overtaking'],
            'risk_identification' => ['risk', 'hazard', 'danger', 'assess', 'identify', 'cascade'],
            'risk_assessment' => ['probability', 'consequence', 'severity', 'likelihood', 'matrix'],
            'mitigation_quality' => ['mitigate', 'barrier', 'control', 'prevent', 'reduce', 'monitor'],
            'safety_culture' => ['safety', 'stop work', 'near miss', 'report', 'toolbox'],
            'bridge_resource_management' => ['brm', 'team', 'challenge', 'response', 'closed loop'],
            'initial_response' => ['immediately', 'alarm', 'muster', 'emergency', 'first action'],
            'decision_under_pressure' => ['decide', 'priority', 'critical', 'time-critical', 'act now'],
            'communication_in_crisis' => ['mayday', 'pan-pan', 'notify', 'inform', 'vhf', 'sitrep'],
            'cargo_competence' => ['stowage', 'loading', 'discharge', 'tank', 'hold', 'ullage', 'sounding'],
            'stability_awareness' => ['stability', 'gm', 'free surface', 'trim', 'list', 'metacentric'],
            'manual_capability' => ['manual', 'celestial', 'radar plotting', 'dead reckoning', 'compass'],
            'degraded_mode_operations' => ['backup', 'degraded', 'failure', 'fallback', 'redundancy'],
            default => [],
        };

        foreach ($positiveKeywords as $kw) {
            if (str_contains($textLower, $kw)) {
                $boost++;
            }
        }

        // Cap boost at +2 from keywords
        return min(5, $baseScore + min(2, (int) ($boost / 2)));
    }

    /**
     * Compute raw score from axis scores: weighted average → 0-100.
     */
    private function computeRawScore(array $axes): float
    {
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($axes as $axis) {
            $weightedSum += $axis['score'] * $axis['weight'];
            $totalWeight += $axis['weight'];
        }

        if ($totalWeight <= 0) return 0;

        $avgScore = $weightedSum / $totalWeight;

        // Convert 0-5 scale to 0-100
        return round($avgScore * 20, 1);
    }

    /**
     * Compute Command Readiness Level from adjusted scores.
     *
     * CRL_1: Any capability < 30 OR crisis_response < 40
     * CRL_2: All >= 30 AND avg >= 50 AND crisis >= 40
     * CRL_3: All >= 50 AND avg >= 65 AND crisis >= 60
     * CRL_4: All >= 60 AND avg >= 75 AND crisis >= 70
     * CRL_5: All >= 70 AND avg >= 85 AND crisis >= 80
     */
    private function computeCRL(array $adjustedScores): string
    {
        $min = min($adjustedScores);
        $avg = array_sum($adjustedScores) / count($adjustedScores);
        $crisis = $adjustedScores['CRISIS_RSP'];

        if ($min < 30 || $crisis < 40) return CapabilityScore::CRL_1;
        if ($min >= 70 && $avg >= 85 && $crisis >= 80) return CapabilityScore::CRL_5;
        if ($min >= 60 && $avg >= 75 && $crisis >= 70) return CapabilityScore::CRL_4;
        if ($min >= 50 && $avg >= 65 && $crisis >= 60) return CapabilityScore::CRL_3;

        return CapabilityScore::CRL_2;
    }

    /**
     * Compute deployment flags based on scores and CRL.
     */
    private function computeDeploymentFlags(array $adjustedScores, string $crl): array
    {
        $flags = [];

        // Low capability flags
        foreach ($adjustedScores as $cap => $score) {
            if ($score < 40) {
                $flags[] = [
                    'type' => 'LOW_CAPABILITY',
                    'capability' => $cap,
                    'score' => $score,
                    'severity' => $score < 20 ? 'critical' : 'major',
                ];
            } elseif ($score < 60) {
                $flags[] = [
                    'type' => 'BORDERLINE_CAPABILITY',
                    'capability' => $cap,
                    'score' => $score,
                    'severity' => 'minor',
                ];
            }
        }

        // Video trigger determination
        $videoRequired = false;
        $videoReasons = [];

        if (in_array($crl, [CapabilityScore::CRL_3]) && $adjustedScores['CRISIS_RSP'] < 65) {
            $videoRequired = true;
            $videoReasons[] = 'CRL-3 with borderline crisis response';
        }

        if ($crl === CapabilityScore::CRL_2) {
            $videoRequired = true;
            $videoReasons[] = 'CRL-2 requires command validation';
        }

        $borderlineCount = count(array_filter($adjustedScores, fn($s) => $s >= 50 && $s < 60));
        if ($borderlineCount >= 3) {
            $videoRequired = true;
            $videoReasons[] = "Multiple borderline capabilities ({$borderlineCount})";
        }

        if ($videoRequired) {
            $flags[] = [
                'type' => 'VIDEO_REQUIRED',
                'reasons' => $videoReasons,
            ];
        }

        return $flags;
    }
}
