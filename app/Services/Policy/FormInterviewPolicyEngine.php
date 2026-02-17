<?php

namespace App\Services\Policy;

use App\Models\FormInterview;

/**
 * Policy Engine for FormInterview Decisions
 *
 * Applies business rules and safety guardrails on top of
 * DecisionEngine scores and calibration.
 *
 * Priority Order:
 * 1. Critical red flags (RF_AGGRESSION) → auto REJECT
 * 2. Safety-critical position + gate fail → REJECT
 * 3. Skill gate fail → HOLD (or REJECT for safety positions)
 * 4. Calibrated score threshold (75/60)
 */
class FormInterviewPolicyEngine
{
    // Critical flags that trigger automatic rejection
    private const CRITICAL_FLAGS = ['RF_AGGRESSION'];

    // Positions that are safety-critical (extend as needed)
    private const SAFETY_CRITICAL_POSITIONS = [
        'driver',
        'forklift_operator',
        'maritime_deckhand',
        'maritime_officer',
        'maritime_captain',
        'security',
        'healthcare',
        'warehouse_picker', // heavy machinery context
    ];

    // High-severity flags that block HIRE even with good scores
    private const HIGH_SEVERITY_FLAGS = ['RF_BLAME', 'RF_INCONSIST'];

    // Structural flags that require human review
    private const REVIEW_REQUIRED_FLAGS = ['RF_INCOMPLETE'];

    /**
     * Apply policy rules to determine final decision.
     *
     * @param FormInterview $interview The interview being evaluated
     * @param array $engineResult Raw output from DecisionEngine
     * @param int|null $calibratedScore Z-score calibrated score (null if baseline insufficient)
     * @return array{final_score: int, decision: string, reason: string, policy_code: string}
     */
    public function decide(FormInterview $interview, array $engineResult, ?int $calibratedScore): array
    {
        $riskFlags = $engineResult['risk_flags'] ?? [];
        $riskCodes = $this->extractFlagCodes($riskFlags);

        $rawFinalScore = (int) ($engineResult['final_score'] ?? 0);
        $scoreForDecision = $calibratedScore ?? $rawFinalScore;

        // 1) Critical red flags → auto reject (no exceptions)
        foreach (self::CRITICAL_FLAGS as $critical) {
            if (in_array($critical, $riskCodes, true)) {
                return [
                    'final_score' => $scoreForDecision,
                    'decision' => 'REJECT',
                    'reason' => "Policy override: critical red flag {$critical} detected",
                    'policy_code' => 'POLICY_AUTO_REJECT_CRITICAL_RF',
                ];
            }
        }

        // 1b) Incomplete answers → HOLD for human review
        foreach (self::REVIEW_REQUIRED_FLAGS as $reviewFlag) {
            if (in_array($reviewFlag, $riskCodes, true)) {
                return [
                    'final_score' => $scoreForDecision,
                    'decision' => 'HOLD',
                    'reason' => "Policy: {$reviewFlag} detected - requires human review",
                    'policy_code' => 'POLICY_HOLD_INCOMPLETE_REVIEW',
                ];
            }
        }

        // 2) Safety-critical position checks
        $positionCode = $interview->template_position_code ?? $interview->position_code ?? '__generic__';
        $isSafetyCritical = in_array($positionCode, self::SAFETY_CRITICAL_POSITIONS, true);

        // Get skill gate info from engine result
        $skillGate = $engineResult['skill_gate'] ?? [];
        $skillGatePassed = $skillGate['passed'] ?? true;

        // Safety-critical + gate fail → always REJECT
        if ($isSafetyCritical && !$skillGatePassed) {
            return [
                'final_score' => $scoreForDecision,
                'decision' => 'REJECT',
                'reason' => sprintf(
                    'Policy override: safety-critical position [%s] skill gate failed (%d%% < %d%%)',
                    $positionCode,
                    $skillGate['role_competence'] ?? 0,
                    $skillGate['gate'] ?? 0
                ),
                'policy_code' => 'POLICY_REJECT_SAFETY_GATE_FAIL',
            ];
        }

        // 3) Non-safety-critical gate fail → HOLD
        if (!$skillGatePassed) {
            return [
                'final_score' => $scoreForDecision,
                'decision' => 'HOLD',
                'reason' => sprintf(
                    'Policy override: skill gate failed (%d%% < %d%%)',
                    $skillGate['role_competence'] ?? 0,
                    $skillGate['gate'] ?? 0
                ),
                'policy_code' => 'POLICY_HOLD_GATE_FAIL',
            ];
        }

        // 4) High-severity red flags block HIRE (downgrade to HOLD)
        $hasHighSeverityFlag = false;
        foreach (self::HIGH_SEVERITY_FLAGS as $highFlag) {
            if (in_array($highFlag, $riskCodes, true)) {
                $hasHighSeverityFlag = true;
                break;
            }
        }

        // 5) Apply score thresholds with flag modifiers
        if ($scoreForDecision >= 75) {
            // Would be HIRE, but check for blocking flags
            if ($hasHighSeverityFlag) {
                return [
                    'final_score' => $scoreForDecision,
                    'decision' => 'HOLD',
                    'reason' => sprintf(
                        'Policy: score %d%% qualifies for HIRE but blocked by high-severity flag',
                        $scoreForDecision
                    ),
                    'policy_code' => 'POLICY_HOLD_HIGH_FLAG_BLOCK',
                ];
            }

            return [
                'final_score' => $scoreForDecision,
                'decision' => 'HIRE',
                'reason' => sprintf('Policy: calibrated score %d%% >= 75%%', $scoreForDecision),
                'policy_code' => 'POLICY_HIRE_THRESHOLD',
            ];
        }

        if ($scoreForDecision >= 60) {
            return [
                'final_score' => $scoreForDecision,
                'decision' => 'HOLD',
                'reason' => sprintf('Policy: calibrated score %d%% (60-74 range)', $scoreForDecision),
                'policy_code' => 'POLICY_HOLD_THRESHOLD',
            ];
        }

        return [
            'final_score' => $scoreForDecision,
            'decision' => 'REJECT',
            'reason' => sprintf('Policy: calibrated score %d%% < 60%%', $scoreForDecision),
            'policy_code' => 'POLICY_REJECT_LOW_SCORE',
        ];
    }

    /**
     * Extract flag codes from risk_flags array (handles different formats)
     */
    private function extractFlagCodes(array $riskFlags): array
    {
        $codes = [];
        foreach ($riskFlags as $flag) {
            if (is_array($flag)) {
                $code = $flag['code'] ?? $flag['flag_code'] ?? null;
            } else {
                $code = $flag;
            }
            if ($code) {
                $codes[] = $code;
            }
        }
        return array_values(array_unique($codes));
    }
}
