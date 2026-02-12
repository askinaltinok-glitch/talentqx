<?php

namespace App\Services\DecisionEngine;

use App\Models\FormInterview;

/**
 * FormInterview → DecisionEngine Adapter
 *
 * Converts form interview answers into DecisionEngine input,
 * calculates real scores, detects red flags, applies skill gates,
 * and produces final decision.
 *
 * MVP SCORING APPROACH:
 * - Heuristic competency scores based on answer length
 * - Red flag detection via keyword matching
 * - Weighted base score calculation
 * - Risk and red flag penalties
 * - Skill gate validation
 */
class FormInterviewDecisionEngineAdapter
{
    // FROZEN WEIGHTS - Normalized to sum to 100.00%
    private const WEIGHTS = [
        'communication'      => 11.54,
        'accountability'     => 15.38,
        'teamwork'           => 11.54,
        'stress_resilience'  => 11.54,
        'adaptability'       => 7.69,
        'learning_agility'   => 7.69,
        'integrity'          => 15.38,
        'role_competence'    => 19.23,
    ];

    // RED FLAG DEFINITIONS with strict trigger patterns
    // NOTE: Keywords must be specific phrases to avoid false positives
    private const RED_FLAGS = [
        'RF_BLAME' => [
            'severity' => 'high',
            'name' => 'Sorumluluk Atma',
            'penalty' => 8,
            'trigger_keywords' => [
                'onlarin hatasi', 'benim hatam degil', 'onun sucu', 'onlarin sucu',
                'ekip beni dinlemedi', 'yoneticiler yuzunden', 'sistem yuzunden',
                'baska birinin hatasi', 'ekip desteklemedi', 'beni desteklemediler'
            ],
        ],
        'RF_INCONSIST' => [
            'severity' => 'high',
            'name' => 'Tutarsizlik',
            'penalty' => 8,
            'trigger_keywords' => [
                'demek istedim', 'yanlis anladin', 'tam olarak degil', 'oyle demedim'
            ],
        ],
        'RF_EGO' => [
            'severity' => 'medium',
            'name' => 'Ego Baskinligi',
            'penalty' => 4,
            'trigger_keywords' => [
                'en iyi ben', 'benden iyi yok', 'tek basima hallederim', 'herkesten iyiyim',
                'digerleri yetersiz', 'ben olmasam olmaz', 'bana ihtiyaclari var',
                'kimse benim kadar'
            ],
        ],
        'RF_AVOID' => [
            'severity' => 'medium',
            'name' => 'Kacinma / Sorumluluk Reddi',
            'penalty' => 4,
            'trigger_keywords' => [
                'benim isim degil', 'sorumluluk almam', 'ben karismam',
                'beni ilgilendirmez', 'gorevim degil', 'bana ne',
                'not my job', 'not my problem', 'i refuse to'
            ],
        ],
        'RF_AGGRESSION' => [
            'severity' => 'critical',
            'name' => 'Agresif Dil',
            'penalty' => 15,
            'auto_reject' => true,
            'trigger_keywords' => [
                'aptal', 'salak', 'gerizekali', 'ahmak', 'dangalak',
                'beyinsiz', 'budala', 'embesil', 'moron', 'sikeyim', 'lanet olsun',
                'sert cikarim', 'bagiririm'
            ],
        ],
        'RF_UNSTABLE' => [
            'severity' => 'medium',
            'name' => 'Istikrarsizlik',
            'penalty' => 4,
            'trigger_keywords' => [
                'cok is degistirdim', 'surekli degisim', 'kisa sureli calistim',
                '3 ayda ayrildim', 'hep problem yasadim', 'hicbir yerde tutunamadim'
            ],
        ],
    ];

    // RISK THRESHOLDS
    private const RISK_WARNING_THRESHOLD = 35;
    private const RISK_CRITICAL_THRESHOLD = 55;
    private const RISK_WARNING_PENALTY = 1;
    private const RISK_CRITICAL_PENALTY = 3;

    // DECISION THRESHOLDS
    private const HIRE_THRESHOLD = 75;
    private const HOLD_THRESHOLD = 60;

    // SKILL GATES per position category
    private const SKILL_GATES = [
        // Default/Generic
        '__generic__' => ['gate' => 45, 'action' => 'HOLD', 'safety_critical' => false],

        // Retail positions
        'retail_cashier' => ['gate' => 45, 'action' => 'HOLD', 'safety_critical' => false],
        'retail_sales' => ['gate' => 50, 'action' => 'HOLD', 'safety_critical' => false],
        'sales_associate' => ['gate' => 50, 'action' => 'HOLD', 'safety_critical' => false],

        // Customer service
        'customer_service' => ['gate' => 45, 'action' => 'HOLD', 'safety_critical' => false],
        'customer_support' => ['gate' => 55, 'action' => 'HOLD', 'safety_critical' => false],

        // Logistics/Warehouse
        'warehouse_picker' => ['gate' => 45, 'action' => 'HOLD', 'safety_critical' => true],
        'forklift_operator' => ['gate' => 55, 'action' => 'REJECT', 'safety_critical' => true],

        // Technical roles
        'software_developer' => ['gate' => 65, 'action' => 'HOLD', 'safety_critical' => false],
        'senior_developer' => ['gate' => 70, 'action' => 'HOLD', 'safety_critical' => false],

        // Safety-critical roles
        'driver' => ['gate' => 60, 'action' => 'REJECT', 'safety_critical' => true],
        'security' => ['gate' => 60, 'action' => 'REJECT', 'safety_critical' => true],
        'healthcare' => ['gate' => 60, 'action' => 'REJECT', 'safety_critical' => true],

        // Entry-level
        'intern' => ['gate' => 35, 'action' => 'HOLD', 'safety_critical' => false],
        'trainee' => ['gate' => 35, 'action' => 'HOLD', 'safety_critical' => false],
    ];

    /**
     * Evaluate a form interview and produce decision
     */
    public function evaluate(FormInterview $interview): array
    {
        // Use loaded relationship if available, otherwise query
        $answers = $interview->relationLoaded('answers')
            ? $interview->answers->keyBy('competency')
            : $interview->answers()->get()->keyBy('competency');

        // Step 1: Calculate competency scores (heuristic based on answer length)
        $competencyScores = $this->calculateCompetencyScores($answers);

        // Step 2: Calculate weighted base score
        $baseScore = $this->calculateBaseScore($competencyScores);

        // Step 3: Calculate risk scores and penalties
        $riskResult = $this->calculateRiskScores($competencyScores);

        // Step 4: Detect red flags from answer text
        $allText = $this->combineAnswerTexts($answers);
        $redFlagResult = $this->detectRedFlags($allText);

        // Step 5: Calculate final score
        $finalScore = max(0, min(100, (int) round(
            $baseScore - $riskResult['penalty'] - $redFlagResult['penalty']
        )));

        // Step 6: Check skill gate
        $positionCode = $interview->position_code ?: '__generic__';
        $gateConfig = $this->getSkillGate($positionCode);
        $skillGate = $this->checkSkillGate($competencyScores['role_competence'] ?? 0, $gateConfig);

        // Step 7: Make final decision
        $decision = $this->makeDecision(
            $finalScore,
            $skillGate,
            $riskResult['scores'],
            $redFlagResult['flags'],
            $redFlagResult['auto_reject']
        );

        return [
            'competency_scores' => $competencyScores,
            'base_score' => $baseScore,
            'risk_penalty' => $riskResult['penalty'],
            'risk_scores' => $riskResult['scores'],
            'red_flag_penalty' => $redFlagResult['penalty'],
            'risk_flags' => $redFlagResult['flags'],
            'auto_reject' => $redFlagResult['auto_reject'],
            'final_score' => $finalScore,
            'skill_gate' => $skillGate,
            'decision' => $decision['decision'],
            'decision_reason' => $decision['reason'],
        ];
    }

    /**
     * Calculate competency scores based on answer quality (heuristic)
     *
     * MVP: Score based on answer length + presence
     * - No answer: 0%
     * - Very short answer (<30 chars): 35%
     * - Short answer (30-80 chars): 50%
     * - Medium answer (80-180 chars): 70%
     * - Good answer (180-350 chars): 85%
     * - Detailed answer (>350 chars): 95%
     */
    private function calculateCompetencyScores($answers): array
    {
        $competencies = array_keys(self::WEIGHTS);
        $scores = [];

        foreach ($competencies as $competency) {
            $answer = $answers->get($competency);
            $answerText = $answer?->answer_text ?? '';
            $length = mb_strlen(trim($answerText), 'UTF-8');

            $scores[$competency] = match (true) {
                $length === 0 => 0,
                $length < 30 => 35,
                $length < 80 => 50,
                $length < 180 => 70,
                $length < 350 => 85,
                default => 95,
            };
        }

        return $scores;
    }

    /**
     * Calculate weighted base score
     */
    private function calculateBaseScore(array $competencyScores): float
    {
        $weightedSum = 0;

        foreach ($competencyScores as $code => $score) {
            $weight = self::WEIGHTS[$code] ?? 0;
            $weightedSum += ($score * $weight / 100);
        }

        return $weightedSum;
    }

    /**
     * Calculate risk scores based on competency values
     */
    private function calculateRiskScores(array $competencyScores): array
    {
        $penalty = 0;
        $scores = [];

        // Integrity Risk
        $integrityRisk = 100 - (
            (($competencyScores['integrity'] ?? 0) * 0.7) +
            (($competencyScores['accountability'] ?? 0) * 0.3)
        );
        $integrityRisk = max(0, min(100, (int) round($integrityRisk)));
        $integrityStatus = $this->getRiskStatus($integrityRisk);
        $integrityPen = $this->getRiskPenalty($integrityStatus);
        $penalty += $integrityPen;
        $scores['integrity_risk'] = [
            'value' => $integrityRisk,
            'status' => $integrityStatus,
            'penalty' => $integrityPen,
        ];

        // Team Risk
        $teamRisk = 100 - (
            (($competencyScores['teamwork'] ?? 0) * 0.6) +
            (($competencyScores['communication'] ?? 0) * 0.4)
        );
        $teamRisk = max(0, min(100, (int) round($teamRisk)));
        $teamStatus = $this->getRiskStatus($teamRisk);
        $teamPen = $this->getRiskPenalty($teamStatus);
        $penalty += $teamPen;
        $scores['team_risk'] = [
            'value' => $teamRisk,
            'status' => $teamStatus,
            'penalty' => $teamPen,
        ];

        // Stability Risk
        $stabilityRisk = 100 - (
            (($competencyScores['stress_resilience'] ?? 0) * 0.6) +
            (($competencyScores['adaptability'] ?? 0) * 0.4)
        );
        $stabilityRisk = max(0, min(100, (int) round($stabilityRisk)));
        $stabilityStatus = $this->getRiskStatus($stabilityRisk);
        $stabilityPen = $this->getRiskPenalty($stabilityStatus);
        $penalty += $stabilityPen;
        $scores['stability_risk'] = [
            'value' => $stabilityRisk,
            'status' => $stabilityStatus,
            'penalty' => $stabilityPen,
        ];

        return [
            'penalty' => $penalty,
            'scores' => $scores,
        ];
    }

    /**
     * Combine all answer texts for red flag detection
     */
    private function combineAnswerTexts($answers): string
    {
        return mb_strtolower(
            $answers->pluck('answer_text')->filter()->implode(' '),
            'UTF-8'
        );
    }

    /**
     * Detect red flags in answer text
     */
    private function detectRedFlags(string $text): array
    {
        $flags = [];
        $penalty = 0;
        $autoReject = false;

        foreach (self::RED_FLAGS as $code => $flagDef) {
            $triggered = false;
            $evidence = [];

            foreach ($flagDef['trigger_keywords'] as $keyword) {
                $keywordLower = mb_strtolower($keyword, 'UTF-8');
                $pattern = '/\b' . preg_quote($keywordLower, '/') . '\b/u';

                if (preg_match($pattern, $text)) {
                    $triggered = true;
                    $evidence[] = $keyword;
                }
            }

            if ($triggered) {
                $flags[] = [
                    'code' => $code,
                    'name' => $flagDef['name'],
                    'severity' => $flagDef['severity'],
                    'penalty' => $flagDef['penalty'],
                    'evidence' => $evidence,
                ];
                $penalty += $flagDef['penalty'];

                if (!empty($flagDef['auto_reject'])) {
                    $autoReject = true;
                }
            }
        }

        return [
            'flags' => $flags,
            'penalty' => $penalty,
            'auto_reject' => $autoReject,
        ];
    }

    /**
     * Get skill gate configuration for position
     */
    private function getSkillGate(string $positionCode): array
    {
        return self::SKILL_GATES[$positionCode]
            ?? self::SKILL_GATES['__generic__'];
    }

    /**
     * Check skill gate status
     */
    private function checkSkillGate(int $roleCompetence, array $gateConfig): array
    {
        $passed = $roleCompetence >= $gateConfig['gate'];

        return [
            'passed' => $passed,
            'role_competence' => $roleCompetence,
            'gate' => $gateConfig['gate'],
            'action' => $gateConfig['action'],
            'safety_critical' => $gateConfig['safety_critical'],
        ];
    }

    /**
     * Make final decision based on all factors
     */
    private function makeDecision(
        int $finalScore,
        array $skillGate,
        array $riskScores,
        array $redFlags,
        bool $autoReject
    ): array {
        // Priority 1: Auto-reject from critical red flag
        if ($autoReject) {
            $aggressiveFlag = collect($redFlags)->firstWhere('code', 'RF_AGGRESSION');
            return [
                'decision' => 'REJECT',
                'reason' => 'Kritik red flag tespit edildi: ' . ($aggressiveFlag['name'] ?? 'Agresif Dil'),
            ];
        }

        // Priority 2: Skill gate failure
        if (!$skillGate['passed']) {
            return [
                'decision' => $skillGate['action'],
                'reason' => sprintf(
                    'Skill gate failed: role_competence %d%% < gate %d%%',
                    $skillGate['role_competence'],
                    $skillGate['gate']
                ),
            ];
        }

        // Priority 3: Score-based decision
        if ($finalScore >= self::HIRE_THRESHOLD) {
            // Check for critical risks or high severity red flags
            $hasCriticalRisk = collect($riskScores)->contains(fn($r) => $r['status'] === 'critical');
            $hasHighRedFlag = collect($redFlags)->contains(fn($f) => $f['severity'] === 'high');

            if ($hasCriticalRisk || $hasHighRedFlag) {
                return [
                    'decision' => 'HOLD',
                    'reason' => "Skor yuksek ({$finalScore}%) ama risk/red flag mevcut",
                ];
            }

            return [
                'decision' => 'HIRE',
                'reason' => "Genel skor {$finalScore}% >= 75%, kritik risk yok, skill gate passed",
            ];
        }

        if ($finalScore >= self::HOLD_THRESHOLD) {
            return [
                'decision' => 'HOLD',
                'reason' => "Genel skor {$finalScore}% (60-74 arasi)",
            ];
        }

        return [
            'decision' => 'REJECT',
            'reason' => "Genel skor {$finalScore}% < 60%",
        ];
    }

    private function getRiskStatus(int $value): string
    {
        if ($value >= self::RISK_CRITICAL_THRESHOLD) return 'critical';
        if ($value >= self::RISK_WARNING_THRESHOLD) return 'warning';
        return 'normal';
    }

    private function getRiskPenalty(string $status): int
    {
        return match ($status) {
            'critical' => self::RISK_CRITICAL_PENALTY,
            'warning' => self::RISK_WARNING_PENALTY,
            default => 0,
        };
    }
}
