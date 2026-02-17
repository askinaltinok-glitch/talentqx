<?php

namespace App\Services\DecisionEngine;

use App\Models\CoreCompetency;
use App\Models\ScoringRule;
use App\Models\DecisionRule;
use App\Models\RedFlag;

/**
 * TalentQX Decision Engine Simulator
 * Tests and validates the scoring logic with sample candidates
 */
class DecisionEngineSimulator
{
    private array $competencies;
    private array $scoringRules;
    private array $decisionRules;
    private array $redFlags;

    public function __construct()
    {
        $this->loadEngineData();
    }

    /**
     * Load all engine data from database
     */
    private function loadEngineData(): void
    {
        $this->competencies = CoreCompetency::active()
            ->orderBy('sort_order')
            ->get()
            ->keyBy('code')
            ->toArray();

        $this->scoringRules = ScoringRule::active()
            ->orderBy('sort_order')
            ->get()
            ->keyBy('code')
            ->toArray();

        $this->decisionRules = DecisionRule::active()
            ->orderBy('priority')
            ->get()
            ->toArray();

        $this->redFlags = RedFlag::active()
            ->orderBy('sort_order')
            ->get()
            ->keyBy('code')
            ->toArray();
    }

    /**
     * Evaluate a candidate profile
     */
    public function evaluate(array $candidateProfile): array
    {
        $result = [
            'candidate_name' => $candidateProfile['name'],
            'candidate_type' => $candidateProfile['type'],
            'competency_scores' => [],
            'primary_scores' => [],
            'risk_scores' => [],
            'overall_score' => 0,
            'red_flags_triggered' => [],
            'decision' => null,
            'decision_label' => null,
            'decision_reason' => null,
        ];

        // Step 1: Map raw competency scores (1-5 scale to 0-100)
        foreach ($candidateProfile['competencies'] as $code => $rawScore) {
            $percentage = $this->rawToPercentage($rawScore);
            $result['competency_scores'][$code] = [
                'raw' => $rawScore,
                'percentage' => $percentage,
                'name' => $this->competencies[$code]['name_tr'] ?? $code,
            ];
        }

        // Step 2: Calculate primary scores
        $result['primary_scores'] = $this->calculatePrimaryScores($candidateProfile['competencies']);

        // Step 3: Calculate risk scores
        $result['risk_scores'] = $this->calculateRiskScores($candidateProfile['competencies'], $candidateProfile['behaviors'] ?? []);

        // Step 4: Detect red flags
        $result['red_flags_triggered'] = $this->detectRedFlags($candidateProfile['behaviors'] ?? [], $candidateProfile['responses'] ?? []);

        // Step 5: Calculate overall score
        $result['overall_score'] = $this->calculateOverallScore(
            $result['primary_scores'],
            $result['risk_scores'],
            $result['red_flags_triggered']
        );

        // Step 6: Make final decision
        $decision = $this->makeDecision($result['overall_score'], $result['red_flags_triggered']);
        $result['decision'] = $decision['decision'];
        $result['decision_label'] = $decision['label'];
        $result['decision_reason'] = $decision['reason'];

        return $result;
    }

    /**
     * Convert raw score (1-5) to percentage (0-100)
     */
    private function rawToPercentage(float $raw): int
    {
        return min(100, max(0, (int) round($raw * 20)));
    }

    /**
     * Calculate primary scores based on competency mappings
     */
    private function calculatePrimaryScores(array $competencies): array
    {
        $scores = [];

        // Communication Score: communication (100%)
        $scores['communication_score'] = [
            'value' => $this->rawToPercentage($competencies['communication'] ?? 3),
            'label' => $this->getScoreLabel('communication_score', $this->rawToPercentage($competencies['communication'] ?? 3)),
        ];

        // Reliability Score: accountability (60%) + integrity (40%)
        $reliability = ($competencies['accountability'] ?? 3) * 0.6 + ($competencies['integrity'] ?? 3) * 0.4;
        $scores['reliability_score'] = [
            'value' => $this->rawToPercentage($reliability),
            'label' => $this->getScoreLabel('reliability_score', $this->rawToPercentage($reliability)),
        ];

        // Team Fit Score: teamwork (70%) + adaptability (30%)
        $teamFit = ($competencies['teamwork'] ?? 3) * 0.7 + ($competencies['adaptability'] ?? 3) * 0.3;
        $scores['team_fit_score'] = [
            'value' => $this->rawToPercentage($teamFit),
            'label' => $this->getScoreLabel('team_fit_score', $this->rawToPercentage($teamFit)),
        ];

        // Stress Score: stress_resilience (100%)
        $scores['stress_score'] = [
            'value' => $this->rawToPercentage($competencies['stress_resilience'] ?? 3),
            'label' => $this->getScoreLabel('stress_score', $this->rawToPercentage($competencies['stress_resilience'] ?? 3)),
        ];

        // Growth Potential: learning_agility (60%) + adaptability (40%)
        $growth = ($competencies['learning_agility'] ?? 3) * 0.6 + ($competencies['adaptability'] ?? 3) * 0.4;
        $scores['growth_potential'] = [
            'value' => $this->rawToPercentage($growth),
            'label' => $this->getScoreLabel('growth_potential', $this->rawToPercentage($growth)),
        ];

        // Job Fit Score: role_competence (100%)
        $scores['job_fit_score'] = [
            'value' => $this->rawToPercentage($competencies['role_competence'] ?? 3),
            'label' => $this->getScoreLabel('job_fit_score', $this->rawToPercentage($competencies['role_competence'] ?? 3)),
        ];

        return $scores;
    }

    /**
     * Calculate risk scores based ONLY on competencies
     * Behaviors are handled separately via red flags to avoid double-counting
     */
    private function calculateRiskScores(array $competencies, array $behaviors): array
    {
        $scores = [];

        // Integrity Risk: inverse of integrity + accountability scores
        $integrityBase = 100 - (
            ($this->rawToPercentage($competencies['integrity'] ?? 3) * 0.7) +
            ($this->rawToPercentage($competencies['accountability'] ?? 3) * 0.3)
        );
        $scores['integrity_risk'] = [
            'value' => max(0, min(100, (int) round($integrityBase))),
            'status' => $this->getRiskStatus((int) round($integrityBase), 35, 55),
        ];

        // Team Risk: inverse of teamwork + communication
        $teamBase = 100 - (
            ($this->rawToPercentage($competencies['teamwork'] ?? 3) * 0.6) +
            ($this->rawToPercentage($competencies['communication'] ?? 3) * 0.4)
        );
        $scores['team_risk'] = [
            'value' => max(0, min(100, (int) round($teamBase))),
            'status' => $this->getRiskStatus((int) round($teamBase), 35, 55),
        ];

        // Stability Risk: inverse of stress_resilience + adaptability
        $stabilityBase = 100 - (
            ($this->rawToPercentage($competencies['stress_resilience'] ?? 3) * 0.6) +
            ($this->rawToPercentage($competencies['adaptability'] ?? 3) * 0.4)
        );
        $scores['stability_risk'] = [
            'value' => max(0, min(100, (int) round($stabilityBase))),
            'status' => $this->getRiskStatus((int) round($stabilityBase), 35, 55),
        ];

        return $scores;
    }

    /**
     * Get risk status label
     */
    private function getRiskStatus(int $value, int $warning, int $critical): string
    {
        if ($value >= $critical) return 'critical';
        if ($value >= $warning) return 'warning';
        return 'normal';
    }

    /**
     * Detect red flags from behaviors and responses
     */
    private function detectRedFlags(array $behaviors, array $responses): array
    {
        $triggered = [];

        // Check behavior-based red flags
        $behaviorMap = [
            'blame_shifting' => 'RF_BLAME',
            'inconsistent_answers' => 'RF_INCONSIST',
            'ego_dominant' => 'RF_EGO',
            'avoidance' => 'RF_AVOID',
            'aggressive_language' => 'RF_AGGRESSION',
            'unstable_history' => 'RF_UNSTABLE',
        ];

        foreach ($behaviors as $behavior) {
            if (isset($behaviorMap[$behavior]) && isset($this->redFlags[$behaviorMap[$behavior]])) {
                $flag = $this->redFlags[$behaviorMap[$behavior]];
                $triggered[] = [
                    'code' => $flag['code'],
                    'name' => $flag['name_tr'],
                    'severity' => $flag['severity'],
                    'causes_auto_reject' => $flag['causes_auto_reject'],
                    'triggered_by' => $behavior,
                ];
            }
        }

        // Check response text for trigger phrases
        foreach ($responses as $response) {
            foreach ($this->redFlags as $flag) {
                $phrases = $flag['trigger_phrases'] ?? [];
                foreach ($phrases as $phrase) {
                    if (stripos($response, $phrase) !== false) {
                        // Avoid duplicates
                        $exists = array_filter($triggered, fn($t) => $t['code'] === $flag['code']);
                        if (empty($exists)) {
                            $triggered[] = [
                                'code' => $flag['code'],
                                'name' => $flag['name_tr'],
                                'severity' => $flag['severity'],
                                'causes_auto_reject' => $flag['causes_auto_reject'],
                                'triggered_by' => "phrase: \"{$phrase}\"",
                            ];
                        }
                    }
                }
            }
        }

        return $triggered;
    }

    /**
     * Calculate overall score with weighted average and penalties
     *
     * Calibration notes:
     * - Base score is weighted average of primary scores
     * - Risk penalties are moderate (don't want to over-penalize)
     * - Red flags have tiered penalties based on severity
     * - Critical red flags cause auto-reject regardless of score
     */
    private function calculateOverallScore(array $primaryScores, array $riskScores, array $redFlags): int
    {
        // Weighted primary score calculation
        $weights = [
            'communication_score' => 15,
            'reliability_score' => 20,
            'team_fit_score' => 15,
            'stress_score' => 10,
            'growth_potential' => 15,
            'job_fit_score' => 25,
        ];

        $totalWeight = array_sum($weights);
        $weightedSum = 0;

        foreach ($primaryScores as $code => $data) {
            $weight = $weights[$code] ?? 0;
            $weightedSum += $data['value'] * $weight;
        }

        $baseScore = $weightedSum / $totalWeight;

        // Apply risk penalties (calibrated to be moderate)
        // Only penalize when risks are significantly elevated
        $riskPenalty = 0;
        foreach ($riskScores as $code => $data) {
            if ($data['status'] === 'critical') {
                $riskPenalty += 4;  // Reduced from 10
            } elseif ($data['status'] === 'warning') {
                $riskPenalty += 2;  // Reduced from 5
            }
        }

        // Apply red flag penalties (calibrated)
        // Note: Critical flags trigger auto-reject in makeDecision()
        // so the penalty here is for score display purposes
        foreach ($redFlags as $flag) {
            switch ($flag['severity']) {
                case 'critical':
                    $riskPenalty += 15;  // Reduced from 25
                    break;
                case 'high':
                    $riskPenalty += 8;   // Reduced from 15
                    break;
                case 'medium':
                    $riskPenalty += 4;   // Reduced from 8
                    break;
                case 'low':
                    $riskPenalty += 2;   // Reduced from 3
                    break;
            }
        }

        return max(0, min(100, (int) round($baseScore - $riskPenalty)));
    }

    /**
     * Make final hiring decision
     */
    private function makeDecision(int $overallScore, array $redFlags): array
    {
        // Check for auto-reject (critical red flags)
        foreach ($redFlags as $flag) {
            if ($flag['causes_auto_reject']) {
                return [
                    'decision' => 'REJECT',
                    'label' => 'Reddet',
                    'reason' => "Kritik red flag tespit edildi: {$flag['name']}",
                ];
            }
        }

        // Check for critical severity flags
        $hasCriticalFlag = !empty(array_filter($redFlags, fn($f) => $f['severity'] === 'critical'));
        if ($hasCriticalFlag) {
            return [
                'decision' => 'REJECT',
                'label' => 'Reddet',
                'reason' => 'Kritik seviye red flag mevcut',
            ];
        }

        // Score-based decision
        if ($overallScore >= 75) {
            $hasHighFlags = !empty(array_filter($redFlags, fn($f) => $f['severity'] === 'high'));
            if ($hasHighFlags) {
                return [
                    'decision' => 'HOLD',
                    'label' => 'Beklet',
                    'reason' => "Skor yuksek ({$overallScore}) ancak yuksek seviye red flag mevcut",
                ];
            }
            return [
                'decision' => 'HIRE',
                'label' => 'Ise Al',
                'reason' => "Genel skor {$overallScore} >= 75, red flag yok",
            ];
        }

        if ($overallScore >= 60) {
            return [
                'decision' => 'HOLD',
                'label' => 'Beklet',
                'reason' => "Genel skor {$overallScore} (60-74 arasi), ek degerlendirme onerilir",
            ];
        }

        return [
            'decision' => 'REJECT',
            'label' => 'Reddet',
            'reason' => "Genel skor {$overallScore} < 60",
        ];
    }

    /**
     * Get score label based on value
     */
    private function getScoreLabel(string $scoreCode, int $value): string
    {
        if ($value >= 80) return 'Cok Iyi';
        if ($value >= 60) return 'Iyi';
        if ($value >= 40) return 'Orta';
        if ($value >= 20) return 'Dusuk';
        return 'Cok Dusuk';
    }

    /**
     * Run simulation with predefined test candidates
     */
    public function runTestSimulation(): array
    {
        $testCandidates = $this->getTestCandidates();
        $results = [];

        foreach ($testCandidates as $candidate) {
            $results[] = $this->evaluate($candidate);
        }

        return $results;
    }

    /**
     * Get 5 predefined test candidate profiles
     */
    public function getTestCandidates(): array
    {
        return [
            // 1. Strong Hire - Excellent across all competencies
            [
                'name' => 'Aday A - Guclu Ise Alim',
                'type' => 'strong_hire',
                'competencies' => [
                    'communication' => 5,      // Excellent
                    'accountability' => 5,     // Excellent
                    'teamwork' => 4.5,         // Very Good
                    'stress_resilience' => 4,  // Good
                    'adaptability' => 4.5,     // Very Good
                    'learning_agility' => 5,   // Excellent
                    'integrity' => 5,          // Excellent
                    'role_competence' => 4.5,  // Very Good
                ],
                'behaviors' => [],
                'responses' => [
                    'Projede bir hata yaptigimda hemen ekiple paylastim ve birlikte cozum urettik.',
                    'Zor musterilerle calisirken sabir ve empati gostermeye odaklaniyorum.',
                ],
            ],

            // 2. Average Hire - Decent but not exceptional
            [
                'name' => 'Aday B - Ortalama Ise Alim',
                'type' => 'average_hire',
                'competencies' => [
                    'communication' => 3.5,    // Above Average
                    'accountability' => 3.5,   // Above Average
                    'teamwork' => 3,           // Average
                    'stress_resilience' => 3,  // Average
                    'adaptability' => 3.5,     // Above Average
                    'learning_agility' => 3,   // Average
                    'integrity' => 4,          // Good
                    'role_competence' => 3.5,  // Above Average
                ],
                'behaviors' => [],
                'responses' => [
                    'Genellikle islerimi zamaninda tamamlarim.',
                    'Ekip calismasi bazen zor oluyor ama uyum saglamaya calisiyorum.',
                ],
            ],

            // 3. Risky but Skilled - High technical, average soft skills + red flags
            // This person CAN do the job but shows some concerning behaviors
            [
                'name' => 'Aday C - Riskli ama Yetenekli',
                'type' => 'risky_skilled',
                'competencies' => [
                    'communication' => 3,      // Average (can communicate)
                    'accountability' => 3.5,   // Above Average
                    'teamwork' => 3,           // Average (works with team)
                    'stress_resilience' => 3,  // Average
                    'adaptability' => 3,       // Average
                    'learning_agility' => 4,   // Good (learns fast)
                    'integrity' => 3.5,        // Above Average
                    'role_competence' => 5,    // Excellent (very skilled)
                ],
                'behaviors' => ['avoidance', 'ego_dominant'],  // Behavioral concerns
                'responses' => [
                    'Teknik konularda cok iyiyim, zor problemleri ben cozerim.',
                    'Toplantilarda cok vakit kaybediyoruz bence.',
                ],
            ],

            // 4. High Integrity, Low Skill - Good person, needs training
            [
                'name' => 'Aday D - Yuksek Durustluk, Dusuk Beceri',
                'type' => 'high_integrity_low_skill',
                'competencies' => [
                    'communication' => 4,      // Good
                    'accountability' => 5,     // Excellent
                    'teamwork' => 4.5,         // Very Good
                    'stress_resilience' => 3.5,// Above Average
                    'adaptability' => 4,       // Good
                    'learning_agility' => 4,   // Good (can learn)
                    'integrity' => 5,          // Excellent
                    'role_competence' => 2,    // Poor (lacks experience)
                ],
                'behaviors' => [],
                'responses' => [
                    'Bu pozisyonda deneyimim az ama hizla ogrenirim.',
                    'Hatalarimdan ders cikarmaya ve gelismeye acigim.',
                ],
            ],

            // 5. High Skill, Toxic Personality - Red flags galore
            [
                'name' => 'Aday E - Yuksek Beceri, Toksik Kisilik',
                'type' => 'toxic_skilled',
                'competencies' => [
                    'communication' => 3,      // Average
                    'accountability' => 1.5,   // Very Poor
                    'teamwork' => 1,           // Very Poor
                    'stress_resilience' => 2,  // Poor
                    'adaptability' => 2,       // Poor
                    'learning_agility' => 3,   // Average
                    'integrity' => 1.5,        // Very Poor
                    'role_competence' => 5,    // Excellent
                ],
                'behaviors' => [
                    'blame_shifting',
                    'ego_dominant',
                    'aggressive_language',
                ],
                'responses' => [
                    'O projede basarisiz olduk cunku ekip beni dinlemedi.',
                    'Ben bu isi en iyi yapan kisiyim, digerleri yetersiz.',
                    'Aptal yoneticiler yuzunden isten ayrildim.',
                ],
            ],
        ];
    }
}
