<?php

namespace App\Services\Maritime;

use App\Config\MaritimeRole;
use App\Models\MaritimeRoleDna;
use App\Models\RoleFitEvaluation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * RoleFitEngine v1
 *
 * Evaluates whether a candidate's behavioral interview responses
 * match their applied role based on the ROLE_DNA_MATRIX.
 *
 * Rules:
 * - NEVER changes applied_role_key
 * - MAY suggest adjacent roles (same department only)
 * - Cross-domain suggestions are FORBIDDEN
 * - mismatch_level=strong → label becomes "role_mismatch"
 *
 * All thresholds are driven by config('maritime.role_fit.*').
 */
class RoleFitEngine
{
    /**
     * Adjacency map: role → adjacent roles (same department ladder only).
     * NEVER cross-domain. Order matters (closer = more relevant).
     */
    private const ADJACENCY_MAP = [
        // Deck command/operational ladder
        'captain'         => ['chief_officer'],
        'chief_officer'   => ['captain', 'second_officer'],
        'second_officer'  => ['chief_officer', 'third_officer'],
        'third_officer'   => ['second_officer'],

        // Deck execution
        'bosun'           => ['able_seaman'],
        'able_seaman'     => ['bosun', 'ordinary_seaman'],
        'ordinary_seaman' => ['able_seaman'],

        // Deck cadet
        'deck_cadet'      => ['ordinary_seaman'],

        // Engine command/operational ladder
        'chief_engineer'  => ['second_engineer'],
        'second_engineer' => ['chief_engineer', 'third_engineer'],
        'third_engineer'  => ['second_engineer'],

        // Engine execution
        'motorman'        => ['oiler', 'fitter'],
        'oiler'           => ['motorman'],
        'fitter'          => ['motorman'],
        'electrician'     => ['third_engineer'],

        // Engine cadet
        'engine_cadet'    => ['oiler'],

        // Service
        'cook'            => ['steward'],
        'steward'         => ['cook', 'messman'],
        'messman'         => ['steward'],

        // Specialized
        'pumpman'         => ['able_seaman', 'bosun'],
        'dp_operator'     => ['second_officer', 'third_officer'],
        'crane_operator'  => ['bosun', 'able_seaman'],
    ];

    /**
     * Get relevance weights from config (with hardcoded fallback).
     */
    private function relevanceWeights(): array
    {
        return config('maritime.role_fit.relevance_weight', [
            'critical' => 1.0,
            'high'     => 0.75,
            'moderate' => 0.50,
            'low'      => 0.25,
        ]);
    }

    /**
     * Get relevance thresholds from config (with hardcoded fallback).
     */
    private function relevanceThresholds(): array
    {
        return config('maritime.role_fit.relevance_threshold', [
            'critical' => 0.40,
            'high'     => 0.30,
            'moderate' => 0.20,
            'low'      => 0.10,
        ]);
    }

    /**
     * Get cache TTL in seconds from config.
     */
    private function cacheTtl(): int
    {
        return (int) config('maritime.role_fit.cache_ttl_seconds', 600);
    }

    /**
     * Get cached DNA for a single role.
     */
    private function getCachedDna(string $roleKey, string $version = 'v1'): ?MaritimeRoleDna
    {
        $ttl = $this->cacheTtl();
        if ($ttl <= 0) {
            return MaritimeRoleDna::forRole($roleKey, $version);
        }

        return Cache::remember(
            "maritime_role_dna_{$version}_{$roleKey}",
            $ttl,
            fn() => MaritimeRoleDna::forRole($roleKey, $version),
        );
    }

    /**
     * Get cached all DNA entries for a version.
     */
    private function getCachedAllDna(string $version = 'v1')
    {
        $ttl = $this->cacheTtl();
        if ($ttl <= 0) {
            return MaritimeRoleDna::where('version', $version)->get();
        }

        return Cache::remember(
            "maritime_role_dna_{$version}_all",
            $ttl,
            fn() => MaritimeRoleDna::where('version', $version)->get(),
        );
    }

    /**
     * Evaluate role-fit for a candidate.
     *
     * @param string $appliedRoleKey  Canonical role code (e.g. "oiler")
     * @param array  $traitScores     Behavioral dimension scores (0..1) keyed by dimension name
     * @param string|null $poolCandidateId
     * @param string|null $formInterviewId
     * @return array
     */
    public function evaluate(
        string $appliedRoleKey,
        array $traitScores,
        ?string $poolCandidateId = null,
        ?string $formInterviewId = null,
    ): array {
        $dna = $this->getCachedDna($appliedRoleKey);

        // No DNA → return neutral result
        if (!$dna) {
            return $this->neutralResult($appliedRoleKey);
        }

        $behavioralProfile = $dna->behavioral_profile ?? [];
        $mismatchSignals = $dna->mismatch_signals ?? [];
        $dimensions = $dna->dna_dimensions ?? [];

        // 1) Compute role-fit score based on behavioral profile match
        $fitScore = $this->computeBehavioralFit($traitScores, $behavioralProfile);

        // 2) Detect department-level mismatch by checking which department's DNA best matches
        $departmentAnalysis = $this->analyzeDepartmentFit($appliedRoleKey, $traitScores);

        // 3) Detect specific mismatch signals
        $triggeredFlags = $this->detectMismatchFlags($traitScores, $behavioralProfile, $mismatchSignals);

        // 4) Determine mismatch level
        $mismatchLevel = $this->determineMismatchLevel($fitScore, $triggeredFlags, $departmentAnalysis);

        // 5) Infer best-fit role (only if mismatch detected)
        $inferredRoleKey = null;
        if ($mismatchLevel !== 'none') {
            $inferredRoleKey = $departmentAnalysis['best_fit_role'] ?? null;
            // If inferred = applied, clear it
            if ($inferredRoleKey === $appliedRoleKey) {
                $inferredRoleKey = null;
            }
        }

        // 6) Generate suggestions (ONLY adjacent roles, NEVER cross-domain)
        $suggestions = $this->generateSuggestions($appliedRoleKey, $traitScores, $mismatchLevel);

        // 7) Compute final role_fit_score with leadership + safety components
        $roleFitScore = $this->computeCompositeFit(
            $fitScore,
            $traitScores,
            $dimensions
        );

        $result = [
            'applied_role_key' => $appliedRoleKey,
            'inferred_role_key' => $inferredRoleKey,
            'role_fit_score' => round($roleFitScore, 4),
            'mismatch_level' => $mismatchLevel,
            'mismatch_flags' => $triggeredFlags,
            'suggestions' => $suggestions,
            'evidence' => [
                'behavioral_fit' => round($fitScore, 4),
                'department_analysis' => $departmentAnalysis,
                'trait_scores_used' => $traitScores,
                'dna_version' => $dna->version,
            ],
        ];

        // Persist evaluation
        if ($poolCandidateId) {
            try {
                RoleFitEvaluation::create([
                    'pool_candidate_id' => $poolCandidateId,
                    'form_interview_id' => $formInterviewId,
                    'applied_role_key' => $appliedRoleKey,
                    'inferred_role_key' => $inferredRoleKey,
                    'role_fit_score' => $roleFitScore,
                    'mismatch_level' => $mismatchLevel,
                    'mismatch_flags' => $triggeredFlags,
                    'evidence' => $result['evidence'],
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('RoleFitEngine: failed to persist evaluation', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Compute behavioral fit score: how well trait scores match the role's behavioral profile.
     */
    private function computeBehavioralFit(array $traitScores, array $behavioralProfile): float
    {
        if (empty($behavioralProfile)) {
            return 0.50;
        }

        $weights = $this->relevanceWeights();
        $thresholds = $this->relevanceThresholds();

        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($behavioralProfile as $dim => $relevance) {
            $weight = $weights[$relevance] ?? 0.25;
            $score = $traitScores[$dim] ?? 0.50; // neutral if missing
            $threshold = $thresholds[$relevance] ?? 0.20;

            // Score above threshold gets full credit, below gets penalized proportionally
            if ($score >= $threshold) {
                $dimScore = min(1.0, $score);
            } else {
                $dimScore = $score / max(0.01, $threshold) * 0.5; // penalty scale
            }

            $weightedSum += $dimScore * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0.50;
    }

    /**
     * Analyze which department's DNA best matches the candidate's trait scores.
     * Used for cross-department mismatch detection.
     */
    private function analyzeDepartmentFit(string $appliedRoleKey, array $traitScores): array
    {
        $appliedDept = MaritimeRole::departmentFor($appliedRoleKey);
        $allDna = $this->getCachedAllDna();

        $bestScore = 0.0;
        $bestRole = $appliedRoleKey;
        $appliedScore = 0.0;
        $departmentScores = [];

        foreach ($allDna as $dna) {
            $profile = $dna->behavioral_profile ?? [];
            $fit = $this->computeBehavioralFit($traitScores, $profile);

            $dept = MaritimeRole::departmentFor($dna->role_key) ?? 'unknown';

            // Track best per department
            if (!isset($departmentScores[$dept]) || $fit > $departmentScores[$dept]['score']) {
                $departmentScores[$dept] = [
                    'score' => round($fit, 4),
                    'best_role' => $dna->role_key,
                ];
            }

            if ($dna->role_key === $appliedRoleKey) {
                $appliedScore = $fit;
            }

            if ($fit > $bestScore) {
                $bestScore = $fit;
                $bestRole = $dna->role_key;
            }
        }

        $bestDept = MaritimeRole::departmentFor($bestRole) ?? 'unknown';
        $isCrossDepartment = $bestDept !== $appliedDept;

        return [
            'applied_department' => $appliedDept,
            'applied_score' => round($appliedScore, 4),
            'best_fit_role' => $bestRole,
            'best_fit_department' => $bestDept,
            'best_fit_score' => round($bestScore, 4),
            'is_cross_department' => $isCrossDepartment,
            'score_gap' => round($bestScore - $appliedScore, 4),
        ];
    }

    /**
     * Detect triggered mismatch flags based on low-scoring critical/high dimensions.
     */
    private function detectMismatchFlags(
        array $traitScores,
        array $behavioralProfile,
        array $mismatchSignals,
    ): array {
        $flags = [];
        $thresholds = $this->relevanceThresholds();

        // Check each dimension against its relevance threshold
        foreach ($behavioralProfile as $dim => $relevance) {
            $threshold = $thresholds[$relevance] ?? 0.20;
            $score = $traitScores[$dim] ?? null;

            if ($score !== null && $score < $threshold) {
                $flags[] = "below_threshold_{$dim}";
            }
        }

        // Check critical dimensions specifically
        $criticalDims = array_keys(array_filter($behavioralProfile, fn($v) => $v === 'critical'));
        $criticalFailCount = 0;
        foreach ($criticalDims as $dim) {
            $score = $traitScores[$dim] ?? 0.50;
            if ($score < ($thresholds['critical'] ?? 0.40)) {
                $criticalFailCount++;
            }
        }

        if ($criticalFailCount >= 2) {
            $flags[] = 'multiple_critical_failures';
        }

        // If behavioral_weight is high but scores are generally low → behavioral_mismatch
        $avgScore = !empty($traitScores) ? array_sum($traitScores) / count($traitScores) : 0.50;
        if ($avgScore < 0.30) {
            $flags[] = 'overall_low_behavioral';
        }

        return array_unique($flags);
    }

    /**
     * Determine mismatch level: none, weak, or strong.
     * All thresholds read from config('maritime.role_fit.*').
     */
    private function determineMismatchLevel(
        float $fitScore,
        array $triggeredFlags,
        array $departmentAnalysis,
    ): string {
        $flagCount = count($triggeredFlags);

        $strongMinFlags = (int) config('maritime.role_fit.mismatch_strong_min_flags', 3);
        $crossDeptGapStrong = (float) config('maritime.role_fit.cross_dept_gap_strong', 0.15);
        $fitScoreStrongBelow = (float) config('maritime.role_fit.fit_score_strong_below', 0.25);
        $fitScoreWeakBelow = (float) config('maritime.role_fit.fit_score_weak_below', 0.40);

        // Strong mismatch conditions (any one is enough)
        if ($departmentAnalysis['is_cross_department'] && $departmentAnalysis['score_gap'] > $crossDeptGapStrong) {
            return 'strong';
        }
        if ($flagCount >= $strongMinFlags) {
            return 'strong';
        }
        if (in_array('multiple_critical_failures', $triggeredFlags)) {
            return 'strong';
        }
        if ($fitScore < $fitScoreStrongBelow) {
            return 'strong';
        }

        // Weak mismatch
        if ($flagCount >= 1) {
            return 'weak';
        }
        if ($fitScore < $fitScoreWeakBelow) {
            return 'weak';
        }

        return 'none';
    }

    /**
     * Generate adjacent role suggestions. NEVER cross-domain.
     */
    private function generateSuggestions(
        string $appliedRoleKey,
        array $traitScores,
        string $mismatchLevel,
    ): array {
        if ($mismatchLevel === 'none') {
            return [];
        }

        $adjacentRoles = self::ADJACENCY_MAP[$appliedRoleKey] ?? [];
        $appliedDept = MaritimeRole::departmentFor($appliedRoleKey);
        $suggestions = [];

        foreach ($adjacentRoles as $roleKey) {
            // Strict cross-domain prevention
            $roleDept = MaritimeRole::departmentFor($roleKey);
            if ($roleDept !== $appliedDept) {
                continue;
            }

            $dna = $this->getCachedDna($roleKey);
            if (!$dna) continue;

            $fit = $this->computeBehavioralFit($traitScores, $dna->behavioral_profile ?? []);

            $suggestions[] = [
                'role_key' => $roleKey,
                'confidence' => round($fit, 4),
                'department' => $roleDept,
            ];
        }

        // Sort by confidence desc
        usort($suggestions, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        $maxSuggestions = (int) config('maritime.role_fit.max_suggestions', 3);
        return array_slice($suggestions, 0, $maxSuggestions);
    }

    /**
     * Composite fit = behavioral_match × behavioral_weight + technical_proxy + leadership + safety
     * Per ROLE_DNA_MATRIX integration rules.
     */
    private function computeCompositeFit(
        float $behavioralFit,
        array $traitScores,
        array $dimensions,
    ): float {
        $bw = $dimensions['behavioral_weight'] ?? 0.30;
        $tw = $dimensions['technical_weight'] ?? 0.30;
        $leadershipExp = $dimensions['leadership_expectation'] ?? 0.50;
        $safetyOwn = $dimensions['safety_ownership'] ?? 0.50;

        // Technical match proxy: for now, average of discipline + initiative (best proxy from behavioral)
        $techProxy = (($traitScores['discipline'] ?? 0.5) + ($traitScores['initiative'] ?? 0.5)) / 2;

        // Leadership match: initiative + communication + conflict_handling vs expectation
        $leadershipEvidence = (
            ($traitScores['initiative'] ?? 0.5)
            + ($traitScores['communication'] ?? 0.5)
            + ($traitScores['conflict_handling'] ?? 0.5)
        ) / 3;
        $leadershipMatch = $leadershipExp > 0 ? min(1.0, $leadershipEvidence / max(0.1, $leadershipExp)) : 1.0;

        // Safety match: discipline + stress_tolerance vs ownership
        $safetyEvidence = (
            ($traitScores['discipline'] ?? 0.5)
            + ($traitScores['stress_tolerance'] ?? 0.5)
        ) / 2;
        $safetyMatch = $safetyOwn > 0 ? min(1.0, $safetyEvidence / max(0.1, $safetyOwn)) : 1.0;

        // role_fit = (behavioral_match × bw) + (technical_match × tw) + (leadership_match × 0.15) + (safety_match × 0.10)
        $roleFit = ($behavioralFit * $bw)
                 + ($techProxy * $tw)
                 + ($leadershipMatch * 0.15)
                 + ($safetyMatch * 0.10);

        return max(0.0, min(1.0, $roleFit));
    }

    /**
     * Neutral result when no DNA exists for the role.
     */
    private function neutralResult(string $appliedRoleKey): array
    {
        return [
            'applied_role_key' => $appliedRoleKey,
            'inferred_role_key' => null,
            'role_fit_score' => 0.5000,
            'mismatch_level' => 'none',
            'mismatch_flags' => [],
            'suggestions' => [],
            'evidence' => [
                'reason' => 'no_dna_available',
                'dna_version' => null,
            ],
        ];
    }

    /**
     * Check if a role key is in the same department as another.
     */
    public static function isSameDepartment(string $roleA, string $roleB): bool
    {
        return MaritimeRole::departmentFor($roleA) === MaritimeRole::departmentFor($roleB);
    }

    /**
     * Get the adjacency list for a role.
     */
    public static function getAdjacentRoles(string $roleKey): array
    {
        return self::ADJACENCY_MAP[$roleKey] ?? [];
    }

    /**
     * Invalidate all role-fit related caches.
     */
    public static function clearCache(): void
    {
        Cache::forget('maritime_roles_active_v1');
        Cache::forget('maritime_role_dna_v1_all');

        // Clear individual role DNA caches
        foreach (MaritimeRole::ROLES as $role) {
            Cache::forget("maritime_role_dna_v1_{$role}");
        }
    }
}
