<?php

namespace App\Services\Behavioral;

use App\Models\BehavioralEvent;
use App\Models\BehavioralProfile;
use App\Models\FormInterview;
use App\Models\FormInterviewAnswer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Behavioral Matching Engine v1 — deterministic heuristics only.
 *
 * Produces an advisory behavioral profile independent from technical scores.
 * Fail-open: errors never block the interview flow.
 */
class BehavioralScoringService
{
    // ─── Keyword dictionaries (EN-primary, lang-agnostic counts) ───

    private const DISCIPLINE_POS = ['procedure', 'checklist', 'ism', 'protocol', 'regulation', 'compliance', 'solas', 'stcw', 'marpol', 'ptw', 'permit', 'sop', 'standing order', 'bridge order'];
    private const DISCIPLINE_NEG = ['skip', 'shortcut', 'ignore the rule', 'unnecessary paperwork', 'waste of time', 'nobody follows'];

    private const TEAM_POS = ['team', 'crew', 'together', 'cooperat', 'collaborat', 'support', 'assist', 'help', 'brief', 'debrief', 'share', 'delegate'];
    private const TEAM_NEG = ['alone', 'my way', 'i don\'t need', 'incompetent crew', 'useless', 'they never', 'their fault', 'blame'];

    private const COMM_POS = ['communicate', 'inform', 'report', 'vts', 'vhf', 'notify', 'coordinate', 'brief', 'advise', 'confirm', 'acknowledge', 'closed-loop', 'read back'];
    private const COMM_NEG = ['no need to inform', 'didn\'t report', 'handle quietly', 'no one needs to know'];

    private const STRESS_POS = ['calm', 'systematic', 'step by step', 'prioritize', 'assess first', 'structured', 'plan', 'sequence', 'controlled', 'methodical'];
    private const STRESS_NEG = ['panic', 'rush', 'immediately without', 'chaos', 'confused', 'overwhelm', 'freeze', 'no idea'];

    private const CONFLICT_NEG = ['argue', 'fight', 'threaten', 'refuse', 'confront', 'aggressive', 'yell', 'shout', 'force', 'demand', 'lawsuit', 'sue', 'punch'];
    private const CONFLICT_POS = ['resolve', 'mediate', 'discuss', 'listen', 'compromise', 'de-escalat', 'understand', 'perspective', 'empathy'];

    private const LEARN_POS = ['learn', 'improve', 'training', 'feedback', 'develop', 'course', 'certif', 'experience taught', 'lesson', 'adapt', 'update knowledge'];
    private const LEARN_NEG = ['already know', 'nothing to learn', 'waste of time', 'old way works', 'i know better'];

    private const RELIABILITY_POS = ['responsibility', 'accountab', 'my decision', 'i take', 'own', 'ensure', 'verify', 'double-check', 'follow up', 'document', 'log'];
    private const RELIABILITY_NEG = ['not my fault', 'they told me', 'someone else', 'wasn\'t my job', 'they should have', 'blame'];

    // ─── Vessel-type fit expectations (score thresholds) ───

    private const FIT_PROFILES = [
        'TANKER' => [
            'DISCIPLINE_COMPLIANCE' => ['min' => 65, 'weight' => 'critical'],
            'STRESS_CONTROL' => ['min' => 60, 'weight' => 'high'],
            'CONFLICT_RISK' => ['max' => 40, 'weight' => 'critical'],
            'RELIABILITY_STABILITY' => ['min' => 60, 'weight' => 'high'],
        ],
        'PASSENGER' => [
            'TEAM_COOPERATION' => ['min' => 65, 'weight' => 'critical'],
            'COMM_CLARITY' => ['min' => 60, 'weight' => 'high'],
            'CONFLICT_RISK' => ['max' => 35, 'weight' => 'critical'],
            'STRESS_CONTROL' => ['min' => 60, 'weight' => 'high'],
        ],
        'CONTAINER_ULCS' => [
            'DISCIPLINE_COMPLIANCE' => ['min' => 60, 'weight' => 'high'],
            'STRESS_CONTROL' => ['min' => 55, 'weight' => 'high'],
            'RELIABILITY_STABILITY' => ['min' => 55, 'weight' => 'high'],
        ],
        'LNG' => [
            'DISCIPLINE_COMPLIANCE' => ['min' => 70, 'weight' => 'critical'],
            'STRESS_CONTROL' => ['min' => 65, 'weight' => 'critical'],
            'CONFLICT_RISK' => ['max' => 35, 'weight' => 'critical'],
            'RELIABILITY_STABILITY' => ['min' => 65, 'weight' => 'high'],
        ],
        'OFFSHORE' => [
            'TEAM_COOPERATION' => ['min' => 60, 'weight' => 'high'],
            'STRESS_CONTROL' => ['min' => 60, 'weight' => 'critical'],
            'COMM_CLARITY' => ['min' => 60, 'weight' => 'high'],
            'LEARNING_GROWTH' => ['min' => 50, 'weight' => 'medium'],
        ],
        'RIVER' => [
            'DISCIPLINE_COMPLIANCE' => ['min' => 60, 'weight' => 'critical'],
            'RELIABILITY_STABILITY' => ['min' => 55, 'weight' => 'high'],
            'CONFLICT_RISK' => ['max' => 45, 'weight' => 'high'],
        ],
        'COASTAL' => [
            'STRESS_CONTROL' => ['min' => 55, 'weight' => 'high'],
            'COMM_CLARITY' => ['min' => 55, 'weight' => 'high'],
            'RELIABILITY_STABILITY' => ['min' => 50, 'weight' => 'medium'],
        ],
        'SHORT_SEA' => [
            'TEAM_COOPERATION' => ['min' => 55, 'weight' => 'high'],
            'STRESS_CONTROL' => ['min' => 55, 'weight' => 'high'],
            'DISCIPLINE_COMPLIANCE' => ['min' => 55, 'weight' => 'medium'],
        ],
        'DEEP_SEA' => [
            'STRESS_CONTROL' => ['min' => 60, 'weight' => 'critical'],
            'TEAM_COOPERATION' => ['min' => 55, 'weight' => 'high'],
            'RELIABILITY_STABILITY' => ['min' => 55, 'weight' => 'high'],
            'LEARNING_GROWTH' => ['min' => 50, 'weight' => 'medium'],
        ],
    ];

    /**
     * Light incremental update after each answer submit.
     */
    public function updateIncremental(FormInterview $interview, FormInterviewAnswer $answer): ?BehavioralProfile
    {
        if (!config('maritime.behavioral_v1') || !config('maritime.behavioral_incremental')) {
            return null;
        }

        try {
            $candidateId = $interview->pool_candidate_id;
            if (!$candidateId) {
                return null;
            }

            $profile = BehavioralProfile::firstOrCreate(
                ['candidate_id' => $candidateId, 'version' => 'v1'],
                [
                    'interview_id' => $interview->id,
                    'company_id' => $interview->company_id,
                    'language' => $interview->language ?? 'en',
                    'status' => BehavioralProfile::STATUS_PARTIAL,
                    'confidence' => 0.00,
                    'dimensions_json' => BehavioralProfile::emptyDimensions(),
                ]
            );

            // Link to interview if not yet
            if (!$interview->behavioral_profile_id) {
                $interview->updateQuietly(['behavioral_profile_id' => $profile->id]);
            }

            // Extract signals from this answer
            $text = trim($answer->answer_text ?? '');
            if (mb_strlen($text) < 10) {
                return $profile;
            }

            $signals = $this->extractSignalsFromText($text, $interview->language ?? 'en');

            // Merge into existing dimensions
            $allAnswers = $interview->answers()->pluck('answer_text')->toArray();
            $allText = implode(' ', array_filter($allAnswers));
            $allSignals = $this->extractSignalsFromText($allText, $interview->language ?? 'en');

            $dimensions = $this->compute7Dimensions($allSignals, count($allAnswers));
            $confidence = $this->computeConfidence($allSignals, count($allAnswers), []);

            $profile->update([
                'interview_id' => $interview->id,
                'dimensions_json' => $dimensions,
                'confidence' => $confidence,
            ]);

            // Log event
            BehavioralEvent::create([
                'candidate_id' => $candidateId,
                'interview_id' => $interview->id,
                'event_type' => 'answer_submitted',
                'payload_json' => [
                    'slot' => $answer->slot,
                    'text_length' => mb_strlen($text),
                    'signals_found' => count(array_filter($signals, fn($s) => !empty($s['hits']))),
                    'confidence' => $confidence,
                ],
                'created_at' => now(),
            ]);

            return $profile;
        } catch (\Throwable $e) {
            Log::channel('single')->warning('BehavioralScoringService::updateIncremental failed', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Full compute on interview completion.
     */
    public function finalize(FormInterview $interview): ?BehavioralProfile
    {
        if (!config('maritime.behavioral_v1')) {
            return null;
        }

        try {
            $candidateId = $interview->pool_candidate_id;
            if (!$candidateId) {
                return null;
            }

            $profile = BehavioralProfile::firstOrCreate(
                ['candidate_id' => $candidateId, 'version' => 'v1'],
                [
                    'interview_id' => $interview->id,
                    'company_id' => $interview->company_id,
                    'language' => $interview->language ?? 'en',
                    'status' => BehavioralProfile::STATUS_PARTIAL,
                    'confidence' => 0.00,
                    'dimensions_json' => BehavioralProfile::emptyDimensions(),
                ]
            );

            // Collect all answer texts
            $answers = $interview->answers()->orderBy('slot')->get();
            $allTexts = $answers->pluck('answer_text')->filter()->toArray();
            $combinedText = implode(' ', $allTexts);
            $answerCount = count($allTexts);

            if ($answerCount === 0) {
                return $profile;
            }

            $lang = $interview->language ?? 'en';
            $signals = $this->extractSignalsFromText($combinedText, $lang);

            // Anti-manipulation: detect contradictions and copy-paste
            $flags = $this->detectManipulation($answers, $signals);

            $dimensions = $this->compute7Dimensions($signals, $answerCount);
            $confidence = $this->computeConfidence($signals, $answerCount, $flags);

            // Compute fit map across all command classes
            $commandClass = $interview->command_class_detected;
            $fitMap = $this->computeFitMap($dimensions, $commandClass);

            $profile->update([
                'interview_id' => $interview->id,
                'status' => BehavioralProfile::STATUS_FINAL,
                'confidence' => $confidence,
                'dimensions_json' => $dimensions,
                'fit_json' => $fitMap,
                'flags_json' => !empty($flags) ? $flags : null,
                'computed_at' => now(),
            ]);

            // Link to interview
            if (!$interview->behavioral_profile_id) {
                $interview->updateQuietly(['behavioral_profile_id' => $profile->id]);
            }

            // Log finalize event
            BehavioralEvent::create([
                'candidate_id' => $candidateId,
                'interview_id' => $interview->id,
                'event_type' => 'finalized',
                'payload_json' => [
                    'answer_count' => $answerCount,
                    'total_text_length' => mb_strlen($combinedText),
                    'confidence' => $confidence,
                    'dimensions_summary' => collect($dimensions)->mapWithKeys(fn($d, $k) => [$k => $d['score']])->toArray(),
                    'flags_count' => count($flags),
                    'fit_primary' => $commandClass ? ($fitMap[$commandClass] ?? null) : null,
                ],
                'created_at' => now(),
            ]);

            // Log manipulation flags separately
            if (!empty($flags)) {
                BehavioralEvent::create([
                    'candidate_id' => $candidateId,
                    'interview_id' => $interview->id,
                    'event_type' => 'flagged',
                    'payload_json' => ['flags' => $flags],
                    'created_at' => now(),
                ]);
            }

            return $profile;
        } catch (\Throwable $e) {
            Log::channel('single')->warning('BehavioralScoringService::finalize failed', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Score a structured behavioral interview (12 questions, 4 categories).
     *
     * Takes per-question 1-5 scores and maps to 7 BehavioralProfile dimensions.
     * Also runs keyword extraction on free-text answers for evidence.
     */
    public function scoreStructuredInterview(
        FormInterview $interview,
        array $categoryScores
    ): ?BehavioralProfile {
        if (!config('maritime.behavioral_interview_v1')) {
            return null;
        }

        try {
            $candidateId = $interview->pool_candidate_id;
            if (!$candidateId) {
                return null;
            }

            $profile = BehavioralProfile::firstOrCreate(
                ['candidate_id' => $candidateId, 'version' => 'v1'],
                [
                    'interview_id' => $interview->id,
                    'company_id' => $interview->company_id,
                    'language' => $interview->language ?? 'en',
                    'status' => BehavioralProfile::STATUS_PARTIAL,
                    'confidence' => 0.00,
                    'dimensions_json' => BehavioralProfile::emptyDimensions(),
                ]
            );

            // Compute category averages (1-5 scale)
            $categoryAvgs = [];
            foreach ($categoryScores as $cat => $questions) {
                if (!empty($questions)) {
                    $categoryAvgs[$cat] = array_sum($questions) / count($questions);
                } else {
                    $categoryAvgs[$cat] = 3.0; // neutral
                }
            }

            // Map category averages to 7 dimensions (normalized 0-100)
            $dimensions = $this->mapCategoriesToDimensions($categoryAvgs);

            // Also run keyword extraction on all answer texts for evidence enrichment
            $answers = $interview->answers()->orderBy('slot')->get();
            $allTexts = $answers->pluck('answer_text')->filter()->toArray();
            $combinedText = implode(' ', $allTexts);
            $answerCount = count($allTexts);

            if ($answerCount > 0) {
                $lang = $interview->language ?? 'en';
                $signals = $this->extractSignalsFromText($combinedText, $lang);
                $flags = $this->detectManipulation($answers, $signals);

                // Merge keyword evidence into dimension scores
                foreach ($dimensions as $dim => &$dimData) {
                    if (isset($signals[$dim])) {
                        $dimData['evidence'] = array_slice($signals[$dim]['hits'] ?? [], 0, 5);
                    }
                }
                unset($dimData);
            } else {
                $flags = [];
            }

            // Compute confidence: structured interview gives higher base confidence
            $confidence = $this->computeStructuredConfidence($categoryScores, $answerCount, $flags);

            // Compute fit map
            $commandClass = $interview->command_class_detected;
            $fitMap = $this->computeFitMap($dimensions, $commandClass);

            $profile->update([
                'interview_id' => $interview->id,
                'status' => BehavioralProfile::STATUS_FINAL,
                'confidence' => $confidence,
                'dimensions_json' => $dimensions,
                'fit_json' => $fitMap,
                'flags_json' => !empty($flags) ? $flags : null,
                'computed_at' => now(),
            ]);

            // Link to interview
            if (!$interview->behavioral_profile_id) {
                $interview->updateQuietly(['behavioral_profile_id' => $profile->id]);
            }

            // Log
            BehavioralEvent::create([
                'candidate_id' => $candidateId,
                'interview_id' => $interview->id,
                'event_type' => 'structured_interview_scored',
                'payload_json' => [
                    'category_averages' => $categoryAvgs,
                    'answer_count' => $answerCount,
                    'confidence' => $confidence,
                    'dimensions_summary' => collect($dimensions)->mapWithKeys(fn($d, $k) => [$k => $d['score']])->toArray(),
                    'flags_count' => count($flags),
                ],
                'created_at' => now(),
            ]);

            return $profile;
        } catch (\Throwable $e) {
            Log::channel('single')->warning('BehavioralScoringService::scoreStructuredInterview failed', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Map 4 category averages (1-5) to 7 BehavioralProfile dimensions (0-100).
     */
    private function mapCategoriesToDimensions(array $categoryAvgs): array
    {
        $dp = $categoryAvgs['discipline_procedure'] ?? 3.0;
        $sc = $categoryAvgs['stress_crisis'] ?? 3.0;
        $tc = $categoryAvgs['team_compatibility'] ?? 3.0;
        $lr = $categoryAvgs['leadership_responsibility'] ?? 3.0;

        // Normalize 1-5 → 0-100
        $norm = fn(float $val) => (int) round(($val - 1) / 4 * 100);

        $dimensions = [];

        $dimensions['DISCIPLINE_COMPLIANCE'] = [
            'score' => $norm($dp),
            'level' => $this->scoreToLevel($norm($dp)),
            'evidence' => [],
            'flags' => [],
        ];

        $dimensions['TEAM_COOPERATION'] = [
            'score' => $norm($tc),
            'level' => $this->scoreToLevel($norm($tc)),
            'evidence' => [],
            'flags' => [],
        ];

        $dimensions['COMM_CLARITY'] = [
            'score' => $norm($tc * 0.8 + $lr * 0.2), // primarily team, some leadership
            'level' => $this->scoreToLevel($norm($tc * 0.8 + $lr * 0.2)),
            'evidence' => [],
            'flags' => [],
        ];

        $dimensions['STRESS_CONTROL'] = [
            'score' => $norm($sc),
            'level' => $this->scoreToLevel($norm($sc)),
            'evidence' => [],
            'flags' => [],
        ];

        // CONFLICT_RISK: inverse of team_compatibility
        $conflictScore = max(0, min(100, 100 - $norm($tc)));
        $dimensions['CONFLICT_RISK'] = [
            'score' => $conflictScore,
            'level' => $this->scoreToLevel($conflictScore),
            'evidence' => [],
            'flags' => [],
        ];

        $dimensions['LEARNING_GROWTH'] = [
            'score' => $norm($lr),
            'level' => $this->scoreToLevel($norm($lr)),
            'evidence' => [],
            'flags' => [],
        ];

        // RELIABILITY_STABILITY: blend of discipline + leadership
        $reliabilityScore = $norm($dp * 0.6 + $lr * 0.4);
        $dimensions['RELIABILITY_STABILITY'] = [
            'score' => $reliabilityScore,
            'level' => $this->scoreToLevel($reliabilityScore),
            'evidence' => [],
            'flags' => [],
        ];

        return $dimensions;
    }

    private function scoreToLevel(int $score): string
    {
        return match (true) {
            $score >= 67 => 'high',
            $score >= 34 => 'mid',
            default => 'low',
        };
    }

    /**
     * Structured interview confidence: higher base since questions are standardized.
     */
    private function computeStructuredConfidence(array $categoryScores, int $answerCount, array $flags): float
    {
        // Base: 0.50 for structured format (vs 0.00 for unstructured)
        $base = 0.50;

        // Answer count contribution: each answer adds ~0.035, max 0.42 (for 12 answers)
        $answerConf = min(0.42, $answerCount * 0.035);

        // Category coverage: bonus for all 4 categories answered
        $categoriesFilled = count(array_filter($categoryScores, fn($qs) => !empty($qs)));
        $catConf = min(0.08, ($categoriesFilled / 4) * 0.08);

        // Flag penalty
        $flagPenalty = min(0.15, count($flags) * 0.05);

        $confidence = round($base + $answerConf + $catConf - $flagPenalty, 2);
        return max(0.00, min(1.00, $confidence));
    }

    /**
     * Deterministic keyword extraction from answer text.
     */
    private function extractSignalsFromText(string $text, string $lang): array
    {
        $lower = mb_strtolower($text, 'UTF-8');

        return [
            'DISCIPLINE_COMPLIANCE' => [
                'pos' => $this->countHits($lower, self::DISCIPLINE_POS),
                'neg' => $this->countHits($lower, self::DISCIPLINE_NEG),
                'hits' => $this->findHits($lower, array_merge(self::DISCIPLINE_POS, self::DISCIPLINE_NEG)),
            ],
            'TEAM_COOPERATION' => [
                'pos' => $this->countHits($lower, self::TEAM_POS),
                'neg' => $this->countHits($lower, self::TEAM_NEG),
                'hits' => $this->findHits($lower, array_merge(self::TEAM_POS, self::TEAM_NEG)),
            ],
            'COMM_CLARITY' => [
                'pos' => $this->countHits($lower, self::COMM_POS),
                'neg' => $this->countHits($lower, self::COMM_NEG),
                'hits' => $this->findHits($lower, array_merge(self::COMM_POS, self::COMM_NEG)),
            ],
            'STRESS_CONTROL' => [
                'pos' => $this->countHits($lower, self::STRESS_POS),
                'neg' => $this->countHits($lower, self::STRESS_NEG),
                'hits' => $this->findHits($lower, array_merge(self::STRESS_POS, self::STRESS_NEG)),
            ],
            'CONFLICT_RISK' => [
                'pos' => $this->countHits($lower, self::CONFLICT_NEG), // pos = conflict indicators (higher = more conflict)
                'neg' => $this->countHits($lower, self::CONFLICT_POS), // neg = de-escalation (reduces conflict score)
                'hits' => $this->findHits($lower, array_merge(self::CONFLICT_NEG, self::CONFLICT_POS)),
            ],
            'LEARNING_GROWTH' => [
                'pos' => $this->countHits($lower, self::LEARN_POS),
                'neg' => $this->countHits($lower, self::LEARN_NEG),
                'hits' => $this->findHits($lower, array_merge(self::LEARN_POS, self::LEARN_NEG)),
            ],
            'RELIABILITY_STABILITY' => [
                'pos' => $this->countHits($lower, self::RELIABILITY_POS),
                'neg' => $this->countHits($lower, self::RELIABILITY_NEG),
                'hits' => $this->findHits($lower, array_merge(self::RELIABILITY_POS, self::RELIABILITY_NEG)),
            ],
        ];
    }

    /**
     * Compute 7 dimension scores from signals.
     */
    private function compute7Dimensions(array $signals, int $answerCount): array
    {
        $dimensions = [];

        foreach (BehavioralProfile::DIMENSIONS as $dim) {
            $s = $signals[$dim] ?? ['pos' => 0, 'neg' => 0, 'hits' => []];
            $pos = $s['pos'];
            $neg = $s['neg'];
            $total = $pos + $neg;

            if ($dim === 'CONFLICT_RISK') {
                // Inverted: high pos = high conflict = high score (bad)
                // We want: low conflict score = good
                if ($total === 0) {
                    $score = 30; // neutral baseline — low conflict assumed
                } else {
                    $ratio = $pos / max($total, 1);
                    $score = (int) round($ratio * 100);
                }
            } else {
                // Normal: high pos = good = high score
                if ($total === 0) {
                    $score = 50; // neutral baseline
                } else {
                    $ratio = $pos / max($total, 1);
                    $score = (int) round(30 + ($ratio * 70)); // 30-100 range
                }
            }

            // Scale by evidence density (more answers = more confident scoring)
            $densityFactor = min(1.0, $answerCount / 6);
            if ($total === 0) {
                $score = (int) round($score * $densityFactor + 50 * (1 - $densityFactor));
            }

            $score = max(0, min(100, $score));

            $level = match (true) {
                $score >= 67 => 'high',
                $score >= 34 => 'mid',
                default => 'low',
            };

            $dimensions[$dim] = [
                'score' => $score,
                'level' => $level,
                'evidence' => array_slice($s['hits'], 0, 5), // top 5 evidence items
                'flags' => [],
            ];
        }

        return $dimensions;
    }

    /**
     * Compute confidence 0.00-1.00 based on signal density and answer count.
     */
    private function computeConfidence(array $signals, int $answerCount, array $flags): float
    {
        // Base: answer count contribution (each answer adds ~0.10, max 0.60)
        $answerConf = min(0.60, $answerCount * 0.10);

        // Signal density: how many dimensions have evidence
        $dimsWithEvidence = 0;
        foreach ($signals as $s) {
            if (($s['pos'] + $s['neg']) > 0) {
                $dimsWithEvidence++;
            }
        }
        $signalConf = min(0.30, ($dimsWithEvidence / 7) * 0.30);

        // Flag penalty: contradictions reduce confidence
        $flagPenalty = min(0.20, count($flags) * 0.05);

        $confidence = round($answerConf + $signalConf - $flagPenalty, 2);
        return max(0.00, min(1.00, $confidence));
    }

    /**
     * Compute fit map across command classes.
     */
    private function computeFitMap(array $dimensions, ?string $primaryClass): array
    {
        $fitMap = [];

        foreach (self::FIT_PROFILES as $classCode => $expectations) {
            $totalChecks = 0;
            $passedChecks = 0;
            $riskFlags = [];
            $frictionFlags = [];
            $leadershipFlags = [];

            foreach ($expectations as $dim => $spec) {
                $totalChecks++;
                $score = $dimensions[$dim]['score'] ?? 50;

                if (isset($spec['min'])) {
                    if ($score >= $spec['min']) {
                        $passedChecks++;
                    } else {
                        $gap = $spec['min'] - $score;
                        if ($spec['weight'] === 'critical' && $gap > 15) {
                            $riskFlags[] = "{$dim}: {$score}/{$spec['min']} (critical gap)";
                        }
                        if (in_array($dim, ['CONFLICT_RISK', 'TEAM_COOPERATION'])) {
                            $frictionFlags[] = "{$dim}: {$score}";
                        }
                        if (in_array($dim, ['STRESS_CONTROL', 'COMM_CLARITY', 'DISCIPLINE_COMPLIANCE'])) {
                            $leadershipFlags[] = "{$dim}: {$score}";
                        }
                    }
                }

                if (isset($spec['max'])) {
                    if ($score <= $spec['max']) {
                        $passedChecks++;
                    } else {
                        $gap = $score - $spec['max'];
                        if ($spec['weight'] === 'critical' && $gap > 15) {
                            $riskFlags[] = "{$dim}: {$score}/{$spec['max']} max (critical excess)";
                        }
                        if ($dim === 'CONFLICT_RISK') {
                            $frictionFlags[] = "CONFLICT_RISK: {$score} (above {$spec['max']})";
                        }
                    }
                }
            }

            $normalizedFit = $totalChecks > 0 ? (int) round(($passedChecks / $totalChecks) * 100) : 50;

            $fitMap[$classCode] = [
                'normalized_fit' => $normalizedFit,
                'risk_flag' => !empty($riskFlags),
                'friction_flag' => !empty($frictionFlags),
                'leadership_flag' => !empty($leadershipFlags),
                'details' => array_merge($riskFlags, $frictionFlags, $leadershipFlags),
            ];
        }

        return $fitMap;
    }

    /**
     * Detect manipulation: copy-paste, contradictions.
     */
    private function detectManipulation($answers, array $signals): array
    {
        $flags = [];

        // 1. Copy-paste detection: same paragraph appears in multiple answers
        $texts = [];
        foreach ($answers as $a) {
            $text = trim($a->answer_text ?? '');
            if (mb_strlen($text) > 50) {
                $texts[$a->slot] = $text;
            }
        }

        $slots = array_keys($texts);
        for ($i = 0; $i < count($slots); $i++) {
            for ($j = $i + 1; $j < count($slots); $j++) {
                $a = $texts[$slots[$i]];
                $b = $texts[$slots[$j]];
                // Check if >60% of shorter text appears in longer text
                $shorter = mb_strlen($a) < mb_strlen($b) ? $a : $b;
                $longer = mb_strlen($a) >= mb_strlen($b) ? $a : $b;
                if (mb_strlen($shorter) > 80) {
                    $chunk = mb_substr($shorter, 0, (int)(mb_strlen($shorter) * 0.6));
                    if (mb_stripos($longer, $chunk) !== false) {
                        $flags[] = [
                            'type' => 'copy_paste',
                            'detail' => "Slots {$slots[$i]} and {$slots[$j]} share >60% text overlap",
                            'severity' => 'medium',
                        ];
                    }
                }
            }
        }

        // 2. Extreme contrast: high conflict keywords but also high de-escalation claims
        $conflictSignals = $signals['CONFLICT_RISK'] ?? ['pos' => 0, 'neg' => 0];
        if ($conflictSignals['pos'] >= 3 && $conflictSignals['neg'] >= 3) {
            $flags[] = [
                'type' => 'sentiment_contradiction',
                'detail' => 'Both aggressive language and de-escalation claims detected',
                'severity' => 'low',
            ];
        }

        // 3. Very short answers across the board (minimal engagement)
        $shortCount = 0;
        foreach ($answers as $a) {
            if (mb_strlen(trim($a->answer_text ?? '')) < 30) {
                $shortCount++;
            }
        }
        if ($shortCount > 0 && $shortCount >= count($answers->toArray()) * 0.6) {
            $flags[] = [
                'type' => 'minimal_engagement',
                'detail' => "{$shortCount} of " . count($answers->toArray()) . " answers are very short (<30 chars)",
                'severity' => 'medium',
            ];
        }

        return $flags;
    }

    // ─── Helpers ───

    private function countHits(string $text, array $keywords): int
    {
        $count = 0;
        foreach ($keywords as $kw) {
            $count += mb_substr_count($text, $kw);
        }
        return $count;
    }

    private function findHits(string $text, array $keywords): array
    {
        $found = [];
        foreach ($keywords as $kw) {
            if (mb_stripos($text, $kw) !== false) {
                $found[] = $kw;
            }
        }
        return $found;
    }
}
