<?php

namespace App\Services\DecisionEngine;

/**
 * TalentQX Decision Engine Audit
 *
 * ARCHITECTURE DECISION:
 * We use a SINGLE-TIER weighted competency model.
 * All 8 competencies are weighted and sum to exactly 100%.
 * No derived "primary scores" - direct competency-to-decision calculation.
 *
 * WEIGHT NORMALIZATION:
 * Original weights from seeder: 15+20+15+15+10+10+20+25 = 130
 * Normalized to 100%: each weight / 130 * 100
 */
class DecisionEngineAudit
{
    // FROZEN WEIGHTS - Normalized to sum to 100.00%
    private const WEIGHTS = [
        'communication'      => 11.54,  // 15/130*100
        'accountability'     => 15.38,  // 20/130*100
        'teamwork'           => 11.54,  // 15/130*100
        'stress_resilience'  => 11.54,  // 15/130*100
        'adaptability'       => 7.69,   // 10/130*100
        'learning_agility'   => 7.69,   // 10/130*100
        'integrity'          => 15.38,  // 20/130*100
        'role_competence'    => 19.23,  // 25/130*100
    ];
    // Total: 11.54+15.38+11.54+11.54+7.69+7.69+15.38+19.23 = 99.99 ≈ 100%

    // RED FLAG DEFINITIONS with strict trigger patterns
    // UPDATED: RF_AVOID now uses ONLY strong refusal/avoidance patterns
    private const RED_FLAGS = [
        'RF_BLAME' => [
            'severity' => 'high',
            'name' => 'Sorumluluk Atma',
            'penalty' => 8,
            'trigger_keywords' => [
                'onlarin hatasi', 'benim hatam degil', 'yuzunden', 'sucu',
                'dinlemedi', 'yetersiz', 'beceriksiz', 'baska birinin',
                'ekip beni dinlemedi', 'yoneticiler yuzunden'
            ],
        ],
        'RF_INCONSIST' => [
            'severity' => 'high',
            'name' => 'Tutarsizlik',
            'penalty' => 8,
            'trigger_keywords' => [
                'aslinda', 'demek istedim', 'yanlis anladin', 'tam olarak degil'
            ],
        ],
        'RF_EGO' => [
            'severity' => 'medium',
            'name' => 'Ego Baskinligi',
            'penalty' => 4,
            'trigger_keywords' => [
                'en iyi ben', 'benden iyi', 'tek basima', 'herkesten iyi',
                'digerleri yetersiz', 'ben cozerim', 'bana ihtiyaclari var'
            ],
        ],
        'RF_AVOID' => [
            'severity' => 'medium',
            'name' => 'Kacinma / Sorumluluk Reddi',
            'penalty' => 4,
            // STRICT patterns only - no "sevmiyorum" (preference is not avoidance)
            'trigger_keywords' => [
                // TR - Strong refusal/avoidance
                'yapmam', 'yapmayacagim', 'istemiyorum', 'asla',
                'benim isim degil', 'ugrasimam', 'sorumluluk almam',
                'kimseyi dinlemem', 'ben karismam', 'bana gore degil',
                'beni ilgilendirmez', 'ugrasmam',
                // EN - Strong refusal/avoidance
                'i won\'t', 'i don\'t want to', 'never', 'not my job',
                'i refuse', 'i won\'t take responsibility', 'not my problem'
            ],
        ],
        'RF_AGGRESSION' => [
            'severity' => 'critical',
            'name' => 'Agresif Dil',
            'penalty' => 15,
            'auto_reject' => true,
            'trigger_keywords' => [
                'aptal', 'salak', 'gerizekali', 'ahmak', 'mal', 'dangalak',
                'beyinsiz', 'budala', 'embesil', 'moron', 'sikeyim', 'lanet'
            ],
        ],
        'RF_UNSTABLE' => [
            'severity' => 'medium',
            'name' => 'Istikrarsizlik',
            'penalty' => 4,
            'trigger_keywords' => [
                'cok is degistirdim', 'surekli degisim', 'kisa sureli',
                '3 ayda ayrildim', 'hep problem yasadim'
            ],
        ],
    ];

    // RISK THRESHOLDS
    private const RISK_WARNING_THRESHOLD = 35;
    private const RISK_CRITICAL_THRESHOLD = 55;
    private const RISK_WARNING_PENALTY = 1;   // Reduced from 2
    private const RISK_CRITICAL_PENALTY = 3;  // Reduced from 4

    // DECISION THRESHOLDS
    private const HIRE_THRESHOLD = 75;
    private const HOLD_THRESHOLD = 60;

    /**
     * SKILL GATE CONFIGURATION
     * Per-position configurable minimum role_competence requirements
     *
     * Structure:
     * - gate: minimum role_competence % required
     * - action: HOLD or REJECT if gate fails
     * - safety_critical: if true, gate failure is more severe
     */
    private const SKILL_GATES = [
        // Entry-level roles (gate = 35, action = HOLD)
        'entry' => [
            'gate' => 35,
            'action' => 'HOLD',
            'safety_critical' => false,
            'roles' => ['stajyer', 'intern', 'trainee', 'entry_level', 'junior'],
        ],

        // Mid-level roles (gate = 50, action = HOLD)
        'mid' => [
            'gate' => 50,
            'action' => 'HOLD',
            'safety_critical' => false,
            'roles' => ['satis_temsilcisi', 'musteri_hizmetleri', 'ofis_yonetimi', 'pazarlama', 'muhasebe'],
        ],

        // Senior roles (gate = 65, action = HOLD)
        'senior' => [
            'gate' => 65,
            'action' => 'HOLD',
            'safety_critical' => false,
            'roles' => ['yazilim_gelistirici', 'proje_yoneticisi', 'takim_lideri', 'uzman', 'senior'],
        ],

        // Safety-critical roles (gate = 60, action = REJECT)
        'safety_critical' => [
            'gate' => 60,
            'action' => 'REJECT',
            'safety_critical' => true,
            'roles' => ['sofor', 'elektrikci', 'hemsire', 'guvenlik', 'eczaci', 'doktor', 'operasyon'],
        ],
    ];

    /**
     * RECOMMENDED SKILL GATES FOR TOP 15 CATEGORIES
     * This table shows recommended gates per position category
     */
    private const RECOMMENDED_GATES = [
        ['category' => 'Yazilim Gelistirme',      'gate' => 65, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Satis & Pazarlama',       'gate' => 50, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Musteri Hizmetleri',      'gate' => 45, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Finans & Muhasebe',       'gate' => 55, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Insan Kaynaklari',        'gate' => 50, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Uretim & Operasyon',      'gate' => 55, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Saglik Hizmetleri',       'gate' => 60, 'action' => 'REJECT', 'safety' => true],
        ['category' => 'Lojistik & Depolama',     'gate' => 50, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Tasarim & Kreatif',       'gate' => 55, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Egitim & Ogretim',        'gate' => 55, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Guvenlik Hizmetleri',     'gate' => 60, 'action' => 'REJECT', 'safety' => true],
        ['category' => 'Teknik Bakim & Onarim',   'gate' => 60, 'action' => 'REJECT', 'safety' => true],
        ['category' => 'Yonetim & Liderlik',      'gate' => 70, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Hukuk & Uyum',            'gate' => 65, 'action' => 'HOLD',   'safety' => false],
        ['category' => 'Staj & Giris Seviye',     'gate' => 35, 'action' => 'HOLD',   'safety' => false],
    ];

    /**
     * Get skill gate for a candidate based on their simulated position type
     * For simulation, we assign position types to test different gates
     */
    private function getSkillGateForCandidate(string $type): array
    {
        // Assign position types for simulation purposes
        $positionMap = [
            'strong_hire'              => ['gate' => 50, 'action' => 'HOLD', 'position' => 'Mid-level (satis_temsilcisi)'],
            'average_hire'             => ['gate' => 50, 'action' => 'HOLD', 'position' => 'Mid-level (musteri_hizmetleri)'],
            'risky_skilled'            => ['gate' => 65, 'action' => 'HOLD', 'position' => 'Senior (yazilim_gelistirici)'],
            'high_integrity_low_skill' => ['gate' => 50, 'action' => 'HOLD', 'position' => 'Mid-level (pazarlama)'],
            'toxic_skilled'            => ['gate' => 60, 'action' => 'REJECT', 'position' => 'Safety-critical (operasyon)'],
        ];

        return $positionMap[$type] ?? ['gate' => 50, 'action' => 'HOLD', 'position' => 'Default (mid-level)'];
    }

    /**
     * Check skill gate and return status
     */
    private function checkSkillGate(int $roleCompetence, array $gateConfig): array
    {
        $passed = $roleCompetence >= $gateConfig['gate'];
        return [
            'passed' => $passed,
            'role_competence' => $roleCompetence,
            'gate' => $gateConfig['gate'],
            'action' => $gateConfig['action'],
            'position' => $gateConfig['position'],
            'message' => $passed
                ? "PASS: role_competence {$roleCompetence}% >= gate {$gateConfig['gate']}%"
                : "FAIL: role_competence {$roleCompetence}% < gate {$gateConfig['gate']}% => {$gateConfig['action']}",
        ];
    }

    /**
     * Get frozen candidate profiles - DO NOT MODIFY
     */
    public function getFrozenCandidates(): array
    {
        return [
            // 1. STRONG HIRE
            [
                'name' => 'Aday A - Guclu Ise Alim',
                'type' => 'strong_hire',
                'competencies' => [
                    'communication'      => 5,    // 100%
                    'accountability'     => 5,    // 100%
                    'teamwork'           => 4.5,  // 90%
                    'stress_resilience'  => 4,    // 80%
                    'adaptability'       => 4.5,  // 90%
                    'learning_agility'   => 5,    // 100%
                    'integrity'          => 5,    // 100%
                    'role_competence'    => 4.5,  // 90%
                ],
                'responses' => [
                    'Projede bir hata yaptigimda hemen ekiple paylastim ve birlikte cozum urettik.',
                    'Zor musterilerle calisirken sabir ve empati gostermeye odaklaniyorum.',
                ],
            ],

            // 2. AVERAGE HIRE
            [
                'name' => 'Aday B - Ortalama Aday',
                'type' => 'average_hire',
                'competencies' => [
                    'communication'      => 3.5,  // 70%
                    'accountability'     => 3.5,  // 70%
                    'teamwork'           => 3,    // 60%
                    'stress_resilience'  => 3,    // 60%
                    'adaptability'       => 3.5,  // 70%
                    'learning_agility'   => 3,    // 60%
                    'integrity'          => 4,    // 80%
                    'role_competence'    => 3.5,  // 70%
                ],
                'responses' => [
                    'Genellikle islerimi zamaninda tamamlarim.',
                    'Ekip calismasi bazen zor oluyor ama uyum saglamaya calisiyorum.',
                ],
            ],

            // 3. RISKY BUT SKILLED
            [
                'name' => 'Aday C - Riskli ama Yetenekli',
                'type' => 'risky_skilled',
                'competencies' => [
                    'communication'      => 2.5,  // 50%
                    'accountability'     => 3,    // 60%
                    'teamwork'           => 2,    // 40%
                    'stress_resilience'  => 2.5,  // 50%
                    'adaptability'       => 2,    // 40%
                    'learning_agility'   => 4,    // 80%
                    'integrity'          => 3.5,  // 70%
                    'role_competence'    => 5,    // 100%
                ],
                'responses' => [
                    'Teknik konularda cok iyiyim ama toplantilari sevmiyorum.',
                    'Bazen ekip arkadaslarimla iletisim sorunlari yasiyorum.',
                ],
            ],

            // 4. HIGH INTEGRITY, LOW SKILL
            [
                'name' => 'Aday D - Yuksek Durustluk, Dusuk Beceri',
                'type' => 'high_integrity_low_skill',
                'competencies' => [
                    'communication'      => 4,    // 80%
                    'accountability'     => 5,    // 100%
                    'teamwork'           => 4.5,  // 90%
                    'stress_resilience'  => 3.5,  // 70%
                    'adaptability'       => 4,    // 80%
                    'learning_agility'   => 4,    // 80%
                    'integrity'          => 5,    // 100%
                    'role_competence'    => 2,    // 40%
                ],
                'responses' => [
                    'Bu pozisyonda deneyimim az ama hizla ogrenirim.',
                    'Hatalarimdan ders cikarmaya ve gelismeye acigim.',
                ],
            ],

            // 5. TOXIC BUT SKILLED
            [
                'name' => 'Aday E - Yuksek Beceri, Toksik Kisilik',
                'type' => 'toxic_skilled',
                'competencies' => [
                    'communication'      => 3,    // 60%
                    'accountability'     => 1.5,  // 30%
                    'teamwork'           => 1,    // 20%
                    'stress_resilience'  => 2,    // 40%
                    'adaptability'       => 2,    // 40%
                    'learning_agility'   => 3,    // 60%
                    'integrity'          => 1.5,  // 30%
                    'role_competence'    => 5,    // 100%
                ],
                'responses' => [
                    'O projede basarisiz olduk cunku ekip beni dinlemedi.',
                    'Ben bu isi en iyi yapan kisiyim, digerleri yetersiz.',
                    'Aptal yoneticiler yuzunden isten ayrildim.',
                ],
            ],
        ];
    }

    /**
     * Convert raw score (1-5) to percentage (0-100)
     */
    private function toPercentage(float $raw): int
    {
        return min(100, max(0, (int) round($raw * 20)));
    }

    /**
     * Run full audit for all candidates
     */
    public function runFullAudit(): void
    {
        $this->printHeader();
        $this->printWeightTable();
        $this->printSkillGatesTable();

        $candidates = $this->getFrozenCandidates();
        $results = [];

        foreach ($candidates as $index => $candidate) {
            $result = $this->auditCandidate($candidate, $index + 1);
            $results[] = $result;
        }

        $this->printSummary($results);
    }

    /**
     * Audit a single candidate with full transparency
     */
    private function auditCandidate(array $candidate, int $num): array
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║  ADAY #{$num}: {$candidate['name']}\n";
        echo "║  Type: {$candidate['type']}\n";
        echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";

        // STEP 1: Raw Competency Scores
        echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 1: RAW COMPETENCY SCORES                                              │\n";
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        $competencyScores = [];
        foreach ($candidate['competencies'] as $code => $raw) {
            $percentage = $this->toPercentage($raw);
            $competencyScores[$code] = $percentage;
            $weight = self::WEIGHTS[$code];
            printf("│  %-20s  Raw: %.1f  →  %3d%%  (weight: %5.2f%%)              │\n",
                $code, $raw, $percentage, $weight);
        }
        echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

        // STEP 2: Weighted Base Score Calculation
        echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 2: WEIGHTED BASE SCORE CALCULATION                                    │\n";
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        $weightedSum = 0;
        $totalWeight = 0;
        foreach ($competencyScores as $code => $score) {
            $weight = self::WEIGHTS[$code];
            $contribution = $score * $weight / 100;
            $weightedSum += $contribution;
            $totalWeight += $weight;
            printf("│  %-20s  %3d%% × %5.2f%% = %6.2f                            │\n",
                $code, $score, $weight, $contribution);
        }
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
        printf("│  TOTAL WEIGHT: %6.2f%%                                                    │\n", $totalWeight);
        printf("│  WEIGHTED SUM: %6.2f                                                      │\n", $weightedSum);
        printf("│  BASE SCORE:   %6.2f%% (weighted sum since weights sum to ~100%%)          │\n", $weightedSum);
        echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

        $baseScore = $weightedSum;

        // STEP 3: Risk Score Calculation
        echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 3: RISK SCORES (competency-based only, no behavior input)             │\n";
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        $riskScores = [];
        $riskPenalty = 0;

        // Integrity Risk
        $integrityRisk = 100 - (
            ($competencyScores['integrity'] * 0.7) +
            ($competencyScores['accountability'] * 0.3)
        );
        $integrityRisk = max(0, min(100, (int) round($integrityRisk)));
        $integrityStatus = $this->getRiskStatus($integrityRisk);
        $integrityPen = $this->getRiskPenalty($integrityStatus);
        $riskPenalty += $integrityPen;
        $riskScores['integrity_risk'] = ['value' => $integrityRisk, 'status' => $integrityStatus, 'penalty' => $integrityPen];

        printf("│  Integrity Risk:  100 - (integrity×0.7 + accountability×0.3)              │\n");
        printf("│                   100 - (%d×0.7 + %d×0.3) = %d%%                           │\n",
            $competencyScores['integrity'], $competencyScores['accountability'], $integrityRisk);
        printf("│                   Status: %-8s  Penalty: -%d points                     │\n",
            strtoupper($integrityStatus), $integrityPen);

        // Team Risk
        $teamRisk = 100 - (
            ($competencyScores['teamwork'] * 0.6) +
            ($competencyScores['communication'] * 0.4)
        );
        $teamRisk = max(0, min(100, (int) round($teamRisk)));
        $teamStatus = $this->getRiskStatus($teamRisk);
        $teamPen = $this->getRiskPenalty($teamStatus);
        $riskPenalty += $teamPen;
        $riskScores['team_risk'] = ['value' => $teamRisk, 'status' => $teamStatus, 'penalty' => $teamPen];

        printf("│  Team Risk:       100 - (teamwork×0.6 + communication×0.4)                │\n");
        printf("│                   100 - (%d×0.6 + %d×0.4) = %d%%                           │\n",
            $competencyScores['teamwork'], $competencyScores['communication'], $teamRisk);
        printf("│                   Status: %-8s  Penalty: -%d points                     │\n",
            strtoupper($teamStatus), $teamPen);

        // Stability Risk
        $stabilityRisk = 100 - (
            ($competencyScores['stress_resilience'] * 0.6) +
            ($competencyScores['adaptability'] * 0.4)
        );
        $stabilityRisk = max(0, min(100, (int) round($stabilityRisk)));
        $stabilityStatus = $this->getRiskStatus($stabilityRisk);
        $stabilityPen = $this->getRiskPenalty($stabilityStatus);
        $riskPenalty += $stabilityPen;
        $riskScores['stability_risk'] = ['value' => $stabilityRisk, 'status' => $stabilityStatus, 'penalty' => $stabilityPen];

        printf("│  Stability Risk:  100 - (stress_resilience×0.6 + adaptability×0.4)        │\n");
        printf("│                   100 - (%d×0.6 + %d×0.4) = %d%%                           │\n",
            $competencyScores['stress_resilience'], $competencyScores['adaptability'], $stabilityRisk);
        printf("│                   Status: %-8s  Penalty: -%d points                     │\n",
            strtoupper($stabilityStatus), $stabilityPen);

        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
        printf("│  TOTAL RISK PENALTY: -%d points                                            │\n", $riskPenalty);
        echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

        // STEP 4: Red Flag Detection (TEXT-BASED EVIDENCE ONLY)
        echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 4: RED FLAG DETECTION (evidence-based text analysis)                  │\n";
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        echo "│  Candidate responses analyzed:                                             │\n";
        foreach ($candidate['responses'] as $i => $response) {
            $truncated = mb_strlen($response) > 65 ? mb_substr($response, 0, 62) . '...' : $response;
            printf("│    [%d] \"%s\"\n", $i + 1, $truncated);
        }
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        $redFlags = [];
        $redFlagPenalty = 0;
        $autoReject = false;
        $autoRejectReason = null;

        $allText = mb_strtolower(implode(' ', $candidate['responses']), 'UTF-8');

        foreach (self::RED_FLAGS as $code => $flagDef) {
            $triggered = false;
            $evidence = [];

            foreach ($flagDef['trigger_keywords'] as $keyword) {
                $keywordLower = mb_strtolower($keyword, 'UTF-8');
                // Use word boundary matching to avoid false positives
                // e.g., "asla" should not match "arkadaslarimla"
                $pattern = '/\b' . preg_quote($keywordLower, '/') . '\b/u';
                if (preg_match($pattern, $allText)) {
                    $triggered = true;
                    $evidence[] = $keyword;
                }
            }

            if ($triggered) {
                $redFlags[] = [
                    'code' => $code,
                    'name' => $flagDef['name'],
                    'severity' => $flagDef['severity'],
                    'penalty' => $flagDef['penalty'],
                    'evidence' => $evidence,
                ];
                $redFlagPenalty += $flagDef['penalty'];

                if (isset($flagDef['auto_reject']) && $flagDef['auto_reject']) {
                    $autoReject = true;
                    $autoRejectReason = $flagDef['name'];
                }

                printf("│  [TRIGGERED] %s (%s)                                    \n", $code, strtoupper($flagDef['severity']));
                printf("│              Name: %s                                               \n", $flagDef['name']);
                printf("│              Penalty: -%d points                                            \n", $flagDef['penalty']);
                printf("│              Evidence: \"%s\"                                       \n", implode('", "', $evidence));
                if (isset($flagDef['auto_reject']) && $flagDef['auto_reject']) {
                    echo "│              *** AUTO-REJECT TRIGGERED ***                                 \n";
                }
                echo "│                                                                           │\n";
            }
        }

        if (empty($redFlags)) {
            echo "│  No red flags detected in candidate responses.                            │\n";
        }

        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
        printf("│  TOTAL RED FLAG PENALTY: -%d points                                        │\n", $redFlagPenalty);
        echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

        // STEP 5: Final Score Calculation
        echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 5: FINAL SCORE CALCULATION                                            │\n";
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        $finalScore = max(0, min(100, (int) round($baseScore - $riskPenalty - $redFlagPenalty)));

        printf("│  Base Score:           %6.2f%%                                            │\n", $baseScore);
        printf("│  Risk Penalty:         -%d points                                          │\n", $riskPenalty);
        printf("│  Red Flag Penalty:     -%d points                                         │\n", $redFlagPenalty);
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
        printf("│  FINAL SCORE:          %d%%                                               │\n", $finalScore);
        echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

        // STEP 6: Skill Gate Check
        echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 6: SKILL GATE CHECK (per-position role_competence requirement)        │\n";
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        $gateConfig = $this->getSkillGateForCandidate($candidate['type']);
        $skillGate = $this->checkSkillGate($competencyScores['role_competence'], $gateConfig);

        printf("│  Position Type:        %s\n", $gateConfig['position']);
        printf("│  Role Competence:      %d%%                                                │\n", $skillGate['role_competence']);
        printf("│  Required Gate:        %d%%                                                │\n", $skillGate['gate']);
        printf("│  Gate Action:          %s (if failed)                                     │\n", $skillGate['action']);
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        $gateStatus = $skillGate['passed'] ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m";
        printf("│  STATUS: %s - %s\n", $gateStatus, $skillGate['message']);
        echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

        // STEP 7: Decision
        echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ STEP 7: FINAL DECISION                                                     │\n";
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        $decision = null;
        $reason = null;
        $gateOverride = false;

        // Priority 1: Auto-reject from critical red flag
        if ($autoReject) {
            $decision = 'REJECT';
            $reason = "Kritik red flag tespit edildi: {$autoRejectReason}";
        }
        // Priority 2: Skill gate failure
        elseif (!$skillGate['passed']) {
            $gateOverride = true;
            $decision = $skillGate['action'];
            $reason = "Skill gate failed: role_competence {$skillGate['role_competence']}% < gate {$skillGate['gate']}%";
        }
        // Priority 3: Score-based decision
        elseif ($finalScore >= self::HIRE_THRESHOLD) {
            $hasCriticalRisk = false;
            foreach ($riskScores as $risk) {
                if ($risk['status'] === 'critical') $hasCriticalRisk = true;
            }
            $hasHighRedFlag = !empty(array_filter($redFlags, fn($f) => $f['severity'] === 'high'));

            if ($hasCriticalRisk || $hasHighRedFlag) {
                $decision = 'HOLD';
                $reason = "Skor yuksek ({$finalScore}%) ama risk/red flag mevcut";
            } else {
                $decision = 'HIRE';
                $reason = "Genel skor {$finalScore}% >= 75%, kritik risk yok, skill gate passed";
            }
        } elseif ($finalScore >= self::HOLD_THRESHOLD) {
            $decision = 'HOLD';
            $reason = "Genel skor {$finalScore}% (60-74 arasi)";
        } else {
            $decision = 'REJECT';
            $reason = "Genel skor {$finalScore}% < 60%";
        }

        $decisionColor = match($decision) {
            'HIRE' => "\033[32m",    // green
            'HOLD' => "\033[33m",    // yellow
            'REJECT' => "\033[31m",  // red
            default => "\033[0m",
        };
        $reset = "\033[0m";

        printf("│  Decision Thresholds: HIRE >= %d%%, HOLD >= %d%%, REJECT < %d%%              │\n",
            self::HIRE_THRESHOLD, self::HOLD_THRESHOLD, self::HOLD_THRESHOLD);
        if ($gateOverride) {
            echo "│  *** SKILL GATE OVERRIDE APPLIED ***                                       │\n";
        }
        echo "│                                                                             │\n";
        printf("│  DECISION: %s%s%s                                                          │\n",
            $decisionColor, $decision, $reset);
        printf("│  REASON: %s\n", $reason);
        echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

        return [
            'name' => $candidate['name'],
            'type' => $candidate['type'],
            'base_score' => $baseScore,
            'risk_penalty' => $riskPenalty,
            'red_flag_penalty' => $redFlagPenalty,
            'final_score' => $finalScore,
            'role_competence' => $competencyScores['role_competence'],
            'skill_gate' => $skillGate,
            'decision' => $decision,
            'reason' => $reason,
            'red_flags' => $redFlags,
            'auto_reject' => $autoReject,
            'gate_override' => $gateOverride,
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
        return match($status) {
            'critical' => self::RISK_CRITICAL_PENALTY,
            'warning' => self::RISK_WARNING_PENALTY,
            default => 0,
        };
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                                                                               ║\n";
        echo "║                    TALENTQX DECISION ENGINE AUDIT REPORT                      ║\n";
        echo "║                                                                               ║\n";
        echo "║  Architecture: Single-tier weighted competency model                          ║\n";
        echo "║  Red Flags: Evidence-based text analysis only (strict patterns)               ║\n";
        echo "║  Skill Gates: Per-position configurable role_competence requirements          ║\n";
        echo "║                                                                               ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";
    }

    private function printSkillGatesTable(): void
    {
        echo "\n";
        echo "┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ RECOMMENDED SKILL GATES (Top 15 Categories)                                │\n";
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
        echo "│  Category                   │ Gate │ Action │ Safety-Critical              │\n";
        echo "├─────────────────────────────┼──────┼────────┼──────────────────────────────┤\n";

        foreach (self::RECOMMENDED_GATES as $g) {
            $cat = str_pad($g['category'], 25);
            $gate = str_pad($g['gate'] . '%', 4, ' ', STR_PAD_LEFT);
            $action = str_pad($g['action'], 6);
            $safety = $g['safety'] ? 'Yes' : 'No';
            printf("│  %s │ %s │ %s │ %-28s │\n", $cat, $gate, $action, $safety);
        }

        echo "└─────────────────────────────────────────────────────────────────────────────┘\n";
    }

    private function printWeightTable(): void
    {
        echo "\n";
        echo "┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ COMPETENCY WEIGHTS (Normalized from 130 to 100)                            │\n";
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        $total = 0;
        foreach (self::WEIGHTS as $code => $weight) {
            $original = match($code) {
                'communication' => 15,
                'accountability' => 20,
                'teamwork' => 15,
                'stress_resilience' => 15,
                'adaptability' => 10,
                'learning_agility' => 10,
                'integrity' => 20,
                'role_competence' => 25,
            };
            printf("│  %-20s  Original: %2d  →  Normalized: %5.2f%%                   │\n",
                $code, $original, $weight);
            $total += $weight;
        }

        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
        printf("│  TOTAL:                Original: 130  →  Normalized: %6.2f%%              │\n", $total);
        echo "│  (Note: 99.99% due to rounding, treated as 100%)                           │\n";
        echo "└─────────────────────────────────────────────────────────────────────────────┘\n";
    }

    private function printSummary(array $results): void
    {
        echo "\n\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                              AUDIT SUMMARY                                    ║\n";
        echo "╠═══════════════════════════════════════════════════════════════════════════════╣\n";
        echo "║                                                                               ║\n";

        $hireCount = count(array_filter($results, fn($r) => $r['decision'] === 'HIRE'));
        $holdCount = count(array_filter($results, fn($r) => $r['decision'] === 'HOLD'));
        $rejectCount = count(array_filter($results, fn($r) => $r['decision'] === 'REJECT'));

        printf("║   HIRE:   %d candidate(s)                                                    ║\n", $hireCount);
        printf("║   HOLD:   %d candidate(s)                                                    ║\n", $holdCount);
        printf("║   REJECT: %d candidate(s)                                                    ║\n", $rejectCount);
        echo "║                                                                               ║\n";
        echo "╠═══════════════════════════════════════════════════════════════════════════════╣\n";
        echo "║  TYPE                      │ BASE │ RISK│FLAG │FINAL│ GATE │ DECISION       ║\n";
        echo "╠════════════════════════════╪══════╪═════╪═════╪═════╪══════╪════════════════╣\n";

        foreach ($results as $r) {
            $type = str_pad(substr($r['type'], 0, 24), 24);
            $base = str_pad(number_format($r['base_score'], 0), 4, ' ', STR_PAD_LEFT);
            $risk = str_pad('-' . $r['risk_penalty'], 3, ' ', STR_PAD_LEFT);
            $flag = str_pad('-' . $r['red_flag_penalty'], 3, ' ', STR_PAD_LEFT);
            $final = str_pad($r['final_score'] . '%', 4, ' ', STR_PAD_LEFT);
            $gate = $r['skill_gate']['passed'] ? 'PASS' : 'FAIL';
            $dec = str_pad($r['decision'], 14);
            printf("║  %s │ %s │ %s │ %s │ %s │ %s │ %s ║\n", $type, $base, $risk, $flag, $final, $gate, $dec);
        }

        echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";

        // Expected vs Actual
        echo "\n";
        echo "┌─────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ EXPECTED vs ACTUAL DECISIONS                                               │\n";
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

        $expected = [
            'strong_hire' => 'HIRE',
            'average_hire' => 'HOLD',
            'risky_skilled' => 'HOLD',
            'high_integrity_low_skill' => 'HOLD',
            'toxic_skilled' => 'REJECT',
        ];

        $allCorrect = true;
        foreach ($results as $r) {
            $exp = $expected[$r['type']] ?? '?';
            $match = ($r['decision'] === $exp) ? 'OK' : 'MISMATCH';
            if ($match !== 'OK') $allCorrect = false;
            printf("│  %-26s  Expected: %-6s  Actual: %-6s  [%s]\n",
                $r['type'], $exp, $r['decision'], $match);
        }

        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
        if ($allCorrect) {
            echo "│  STATUS: All decisions match expected outcomes.                            │\n";
        } else {
            echo "│  STATUS: Some decisions do not match. Review thresholds.                   │\n";
        }
        echo "└─────────────────────────────────────────────────────────────────────────────┘\n";
    }
}
