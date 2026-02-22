<?php

namespace App\Services\Maritime;

use App\Models\LanguageAssessment;
use App\Models\PoolCandidate;
use Illuminate\Support\Facades\DB;

class LanguageAssessmentService
{
    private const LEVEL_ORDER = ['A1' => 1, 'A2' => 2, 'B1' => 3, 'B2' => 4, 'C1' => 5, 'C2' => 6];

    /**
     * Role-based English question section distribution.
     * Maps role profiles to section weights (number of questions per section).
     */
    /**
     * Section codes in question bank: READING, VOCABULARY, GRAMMAR, listening, situational, operational_vocab, safety_comm
     * Total questions selected: ~14 (varies by role profile).
     */
    private const ROLE_ENGLISH_PROFILES = [
        'deck_ratings' => [
            'sections' => [
                'READING' => 2, 'VOCABULARY' => 2, 'GRAMMAR' => 1,
                'operational_vocab' => 3, 'safety_comm' => 3, 'listening' => 1, 'situational' => 1,
            ],
            'focus' => 'basic_operational',
        ],
        'engine' => [
            'sections' => [
                'READING' => 2, 'VOCABULARY' => 2, 'GRAMMAR' => 1,
                'operational_vocab' => 3, 'safety_comm' => 2, 'listening' => 2, 'situational' => 1,
            ],
            'focus' => 'technical_comprehension',
        ],
        'officers' => [
            'sections' => [
                'READING' => 2, 'VOCABULARY' => 1, 'GRAMMAR' => 2,
                'situational' => 3, 'listening' => 2, 'operational_vocab' => 2, 'safety_comm' => 1,
            ],
            'focus' => 'communication_decision',
        ],
        'command' => [
            'sections' => [
                'READING' => 1, 'VOCABULARY' => 1, 'GRAMMAR' => 2,
                'situational' => 3, 'listening' => 3, 'safety_comm' => 2, 'operational_vocab' => 1,
            ],
            'focus' => 'leadership_situational',
        ],
    ];

    /**
     * Get or create a language assessment for a candidate.
     */
    public function getOrCreate(string $candidateId): LanguageAssessment
    {
        return DB::transaction(function () use ($candidateId) {
            $existing = LanguageAssessment::where('candidate_id', $candidateId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $candidate = PoolCandidate::find($candidateId);
            $meta = $candidate?->source_meta ?? [];
            $declaredLevel = $this->normalizeCefrLevel(data_get($meta, 'english_level'));

            return LanguageAssessment::create([
                'candidate_id' => $candidateId,
                'assessment_language' => 'en',
                'declared_level' => $declaredLevel,
            ]);
        });
    }

    /**
     * Check retake eligibility. Returns null if allowed, or error message.
     */
    public function checkRetakeEligibility(string $candidateId): ?string
    {
        $assessment = LanguageAssessment::where('candidate_id', $candidateId)->first();
        if (!$assessment) {
            return null; // first test, always allowed
        }

        $retakeCfg = config('maritime_language.retake');

        // Locked level blocks retake
        if ($retakeCfg['locked_blocks_retake'] && $assessment->locked_level) {
            return 'Language level is locked. Retake not allowed.';
        }

        // Max retakes per 30 days
        $max = $retakeCfg['max_per_30_days'];
        if ($assessment->last_test_at && $assessment->last_test_at->gte(now()->subDays(30))) {
            if ($assessment->retake_count >= $max) {
                $nextDate = $assessment->last_test_at->addDays(30)->toDateString();
                return "Maximum {$max} retakes per 30 days reached. Next eligible: {$nextDate}";
            }
        }

        return null;
    }

    /**
     * Get role-based English question set for a candidate.
     * If role requirements config exists for the candidate's rank, uses role-weighted sections.
     * Otherwise falls back to standard even distribution.
     */
    public function getRoleBasedQuestionSet(string $candidateId): array
    {
        $result = $this->getQuestionSet($candidateId);
        $result['role_profile'] = $this->resolveRoleProfile($candidateId);
        $result['role_requirements'] = $this->resolveRoleRequirements($candidateId);
        return $result;
    }

    /**
     * Resolve the English question profile name for a candidate based on rank.
     */
    public function resolveRoleProfile(string $candidateId): string
    {
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return 'deck_ratings';
        }

        $rank = strtolower(trim(data_get($candidate->source_meta, 'rank', '')));
        $requirements = config('maritime_language.role_english_requirements', []);

        if (isset($requirements[$rank])) {
            return $requirements[$rank]['profile'] ?? 'deck_ratings';
        }

        // Fallback by rank keywords
        if (str_contains($rank, 'captain') || str_contains($rank, 'master') || str_contains($rank, 'chief')) {
            return 'command';
        }
        if (str_contains($rank, 'officer') || str_contains($rank, 'cadet')) {
            return 'officers';
        }
        if (str_contains($rank, 'engineer') || str_contains($rank, 'motorman') || str_contains($rank, 'oiler') || str_contains($rank, 'electrician')) {
            return 'engine';
        }

        return 'deck_ratings';
    }

    /**
     * Get role-specific minimum level requirements for a candidate.
     */
    public function resolveRoleRequirements(string $candidateId): ?array
    {
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return null;
        }

        $rank = strtolower(trim(data_get($candidate->source_meta, 'rank', '')));
        return config("maritime_language.role_english_requirements.{$rank}");
    }

    /**
     * Load randomized MCQ question set for a candidate (deterministic per candidate+day).
     * Selects `select` questions per section from the full bank.
     * Uses role-based section weighting when available.
     * Enforces retake policy.
     */
    public function getQuestionSet(string $candidateId): array
    {
        // Enforce retake policy
        $retakeError = $this->checkRetakeEligibility($candidateId);
        if ($retakeError) {
            throw new \DomainException($retakeError);
        }

        $cfg = config('maritime_language.question_bank');
        $data = $this->loadQuestionBank();
        $selectCount = $cfg['select_count'] ?? 12;
        $perSection = (int) floor($selectCount / count($data['sections']));

        // Resolve role-based section weights if available
        $roleProfile = $this->resolveRoleProfile($candidateId);
        $roleSectionWeights = self::ROLE_ENGLISH_PROFILES[$roleProfile]['sections'] ?? null;

        // Deterministic seed: candidate_id + today's date
        $seed = crc32($candidateId . now()->format('Y-m-d'));

        $clientSections = [];
        $selectedIds = [];

        foreach ($data['sections'] as $section) {
            $sectionCode = $section['code'] ?? '';
            // Use role-based count if available, else default
            $sectionSelect = $roleSectionWeights[$sectionCode] ?? $section['select'] ?? $perSection;
            $pool = $section['questions'];

            // Seed-based shuffle
            mt_srand($seed + crc32($section['code']));
            $indices = range(0, count($pool) - 1);
            shuffle($indices);
            $picked = array_slice($indices, 0, min($sectionSelect, count($pool)));
            mt_srand(); // reset

            $questions = [];
            foreach ($picked as $idx) {
                $q = $pool[$idx];
                $selectedIds[] = $q['id'];
                $questions[] = [
                    'id' => $q['id'],
                    'prompt' => $q['prompt'],
                    'options' => $q['options'],
                ];
            }

            $clientSections[] = [
                'code' => $section['code'],
                'title' => $section['title'],
                'questions' => $questions,
            ];
        }

        // Store selected question IDs + update retake tracking
        $assessment = $this->getOrCreate($candidateId);
        $assessment->selected_questions = $selectedIds;

        // Reset retake counter if outside 30-day window
        if (!$assessment->last_test_at || $assessment->last_test_at->lt(now()->subDays(30))) {
            $assessment->retake_count = 1;
        } else {
            $assessment->retake_count = ($assessment->retake_count ?? 0) + 1;
        }
        $assessment->last_test_at = now();
        $assessment->save();

        return [
            'sections' => $clientSections,
            'writing_prompt' => [
                'id' => $data['writing_prompt']['id'],
                'prompt' => $data['writing_prompt']['prompt'],
                'min_words' => $data['writing_prompt']['min_words'],
                'max_words' => $data['writing_prompt']['max_words'],
            ],
            'time_limit_minutes' => $cfg['time_limit_minutes'] ?? 12,
            'total_questions' => count($selectedIds),
        ];
    }

    /**
     * Grade MCQ answers and compute scores.
     */
    public function submitTest(
        string $candidateId,
        ?string $declaredLevel,
        array $answers,
        ?string $writingText
    ): LanguageAssessment {
        return DB::transaction(function () use ($candidateId, $declaredLevel, $answers, $writingText) {
            $assessment = $this->getOrCreate($candidateId);

            // Build answer key from full bank
            $fullKey = $this->getAnswerKey();

            // Only grade questions that were actually selected for this candidate
            $selectedIds = $assessment->selected_questions ?? array_keys($fullKey);
            $gradableKey = array_intersect_key($fullKey, array_flip($selectedIds));

            $total = count($gradableKey);
            $correct = 0;
            foreach ($gradableKey as $qId => $correctAnswer) {
                if (isset($answers[$qId]) && strtoupper(trim($answers[$qId])) === $correctAnswer) {
                    $correct++;
                }
            }

            $mcqScore = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

            // Writing score estimate
            $writingScore = null;
            if ($writingText && trim($writingText) !== '') {
                $wordCount = str_word_count(trim($writingText));
                $writingScore = $this->estimateWritingScore($wordCount);
            }

            $assessment->declared_level = $declaredLevel ? $this->normalizeCefrLevel($declaredLevel) : $assessment->declared_level;
            $assessment->mcq_score = $mcqScore;
            $assessment->mcq_total = $total;
            $assessment->mcq_correct = $correct;
            $assessment->writing_text = $writingText;
            $assessment->writing_score = $writingScore;

            $this->recomputeEstimate($assessment);
            $assessment->save();

            return $assessment;
        });
    }

    /**
     * Submit interview verification scores.
     */
    public function submitInterviewVerification(
        string $candidateId,
        array $rubric,
        ?array $answers = null
    ): LanguageAssessment {
        return DB::transaction(function () use ($candidateId, $rubric, $answers) {
            $assessment = $this->getOrCreate($candidateId);

            $rubricValues = array_values(array_filter($rubric, 'is_numeric'));
            $maxPossible = count($rubricValues) * 5;
            $interviewScore = $maxPossible > 0
                ? (int) round((array_sum($rubricValues) / $maxPossible) * 100)
                : null;

            $assessment->interview_score = $interviewScore;
            $assessment->interview_evidence = [
                'answers' => $answers,
                'rubric' => $rubric,
                'submitted_at' => now()->toIso8601String(),
            ];

            $this->recomputeEstimate($assessment);
            $assessment->save();

            return $assessment;
        });
    }

    /**
     * Lock a verified level (admin action).
     */
    public function lockLevel(string $candidateId, string $level, ?string $adminId): LanguageAssessment
    {
        $assessment = $this->getOrCreate($candidateId);
        $assessment->locked_level = $this->normalizeCefrLevel($level);
        $assessment->locked_by = $adminId;
        $assessment->locked_at = now();
        $assessment->save();

        return $assessment;
    }

    /**
     * Update writing rubric (from AI-assist or manual grading).
     */
    public function updateWritingRubric(string $candidateId, array $rubric): LanguageAssessment
    {
        return DB::transaction(function () use ($candidateId, $rubric) {
            $assessment = $this->getOrCreate($candidateId);

            $rubricValues = array_values(array_filter($rubric, 'is_numeric'));
            $maxPossible = count($rubricValues) * 5;
            $writingScore = $maxPossible > 0
                ? (int) round((array_sum($rubricValues) / $maxPossible) * 100)
                : $assessment->writing_score;

            $assessment->writing_rubric = $rubric;
            $assessment->writing_score = $writingScore;

            $this->recomputeEstimate($assessment);
            $assessment->save();

            return $assessment;
        });
    }

    // ──────────────────────────────────────────────────────────
    // Scoring engine
    // ──────────────────────────────────────────────────────────

    /**
     * Recompute overall_score, estimated_level, confidence from available signals.
     * Uses config-driven weights, role-aware overrides, and writing-based level caps.
     */
    private function recomputeEstimate(LanguageAssessment $a): void
    {
        $isCommand = $this->isCommandRole($a->candidate_id);
        $weights = $this->resolveWeights($isCommand);

        $scores = [];
        $activeWeightKeys = [];
        $activeWeightValues = [];

        if ($a->mcq_score !== null) {
            $scores[] = $a->mcq_score;
            $activeWeightKeys[] = 'mcq';
            $activeWeightValues[] = $weights['mcq'];
        }
        if ($a->writing_score !== null) {
            $scores[] = $a->writing_score;
            $activeWeightKeys[] = 'writing';
            $activeWeightValues[] = $weights['writing'];
        }
        if ($a->interview_score !== null) {
            $scores[] = $a->interview_score;
            $activeWeightKeys[] = 'interview';
            $activeWeightValues[] = $weights['interview'];
        }

        if (empty($scores)) {
            $a->overall_score = null;
            $a->estimated_level = null;
            $a->confidence = null;
            $a->signals = null;
            return;
        }

        // Normalize and blend
        $weightSum = array_sum($activeWeightValues);
        $overall = 0;
        foreach ($scores as $i => $score) {
            $overall += $score * ($activeWeightValues[$i] / $weightSum);
        }
        $overall = (int) round($overall);

        // Map to CEFR level via config cutoffs
        $level = $this->scoreToLevel($overall);

        // Apply writing-based level cap
        $level = $this->applyWritingLevelCap($level, $a->writing_score);

        // Confidence calculation
        $confCfg = config('maritime_language.confidence');
        $confidence = $confCfg['base'];
        $flags = [];

        if ($a->writing_text && str_word_count(trim($a->writing_text)) < $confCfg['writing_short_words']) {
            $confidence -= $confCfg['writing_short_penalty'];
            $flags[] = 'writing_too_short';
        }

        $mismatch = $confCfg['mcq_writing_mismatch'];
        if ($a->mcq_score !== null && $a->writing_score !== null) {
            if ($a->mcq_score >= $mismatch['mcq_min'] && $a->writing_score <= $mismatch['writing_max']) {
                $confidence -= $mismatch['penalty'];
                $flags[] = 'mcq_writing_mismatch';
            }
        }

        if ($a->declared_level && isset(self::LEVEL_ORDER[$a->declared_level], self::LEVEL_ORDER[$level])) {
            $gap = abs(self::LEVEL_ORDER[$a->declared_level] - self::LEVEL_ORDER[$level]);
            if ($gap >= $confCfg['declared_gap_levels']) {
                $confidence -= $confCfg['declared_gap_penalty'];
                $flags[] = 'declared_estimated_gap';
            }
        }

        if ($a->interview_score === null) {
            $penalty = $isCommand
                ? $confCfg['no_interview_command_penalty']
                : $confCfg['no_interview_penalty'];
            $confidence -= $penalty;
            $flags[] = 'no_interview_verification';
        }

        // Writing level cap flag
        if ($a->writing_score !== null) {
            foreach (config('maritime_language.writing_level_caps', []) as $rule) {
                if ($a->writing_score <= $rule['writing_max']) {
                    $flags[] = 'writing_level_capped';
                    break;
                }
            }
        }

        $confidence = max($confCfg['floor'], min(1.00, round($confidence, 2)));

        $normalizedWeights = [];
        foreach ($activeWeightKeys as $i => $k) {
            $normalizedWeights[$k] = round($activeWeightValues[$i] / $weightSum, 2);
        }

        $a->overall_score = min(100, max(0, $overall));
        $a->estimated_level = $level;
        $a->confidence = $confidence;
        $a->signals = [
            'weights' => $weights,
            'active_weights' => $normalizedWeights,
            'is_command_role' => $isCommand,
            'flags' => $flags,
        ];
    }

    /**
     * Resolve weights: command role override or global default.
     */
    private function resolveWeights(bool $isCommand): array
    {
        if ($isCommand) {
            return config('maritime_language.role_weights.command', config('maritime_language.weights'));
        }
        return config('maritime_language.weights');
    }

    /**
     * Check if candidate holds a command rank.
     */
    private function isCommandRole(string $candidateId): bool
    {
        static $cache = [];
        if (isset($cache[$candidateId])) {
            return $cache[$candidateId];
        }

        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return $cache[$candidateId] = false;
        }

        $rank = data_get($candidate->source_meta, 'rank', '');
        $commandRanks = config('maritime_language.command_ranks', []);

        $normalized = strtolower(trim($rank));
        foreach ($commandRanks as $cr) {
            if (strtolower($cr) === $normalized) {
                return $cache[$candidateId] = true;
            }
        }

        return $cache[$candidateId] = false;
    }

    /**
     * Map overall score → CEFR level using config cutoffs.
     */
    private function scoreToLevel(int $score): string
    {
        foreach (config('maritime_language.level_cutoffs') as $band) {
            if ($score >= $band['min'] && $score <= $band['max']) {
                return $band['level'];
            }
        }
        return 'A1';
    }

    /**
     * Apply writing-based level cap.
     * If writing_score is low, the estimated level cannot exceed the cap.
     */
    private function applyWritingLevelCap(string $level, ?int $writingScore): string
    {
        if ($writingScore === null) {
            return $level;
        }

        foreach (config('maritime_language.writing_level_caps', []) as $rule) {
            if ($writingScore <= $rule['writing_max']) {
                $cap = $rule['cap'];
                // Only cap downward
                if (isset(self::LEVEL_ORDER[$level], self::LEVEL_ORDER[$cap])) {
                    if (self::LEVEL_ORDER[$level] > self::LEVEL_ORDER[$cap]) {
                        return $cap;
                    }
                }
                break; // first matching rule wins
            }
        }

        return $level;
    }

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    private function estimateWritingScore(int $wordCount): int
    {
        foreach (config('maritime_language.writing_estimate', []) as $band) {
            if ($wordCount < $band['max_words']) {
                return $band['score'];
            }
        }
        return 70;
    }

    private function loadQuestionBank(): array
    {
        $path = resource_path(config('maritime_language.question_bank.path'));
        return json_decode(file_get_contents($path), true);
    }

    private function getAnswerKey(): array
    {
        $data = $this->loadQuestionBank();
        $key = [];
        foreach ($data['sections'] as $section) {
            foreach ($section['questions'] as $q) {
                $key[$q['id']] = $q['correct'];
            }
        }
        return $key;
    }

    private function normalizeCefrLevel(?string $level): ?string
    {
        if (!$level) return null;

        $upper = strtoupper(trim($level));
        $valid = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

        if (in_array($upper, $valid)) return $upper;

        foreach ($valid as $v) {
            if (str_starts_with($upper, $v)) return $v;
        }

        return null;
    }
}
