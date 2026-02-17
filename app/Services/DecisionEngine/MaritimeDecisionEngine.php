<?php

namespace App\Services\DecisionEngine;

use App\Models\FormInterview;

/**
 * Maritime Decision Engine — Deterministic scoring for maritime interviews.
 *
 * Takes a completed form interview (with 8 maritime questions scored 0-5)
 * and produces an investor-grade decision packet:
 *   decision (hire|review|reject), final_score, confidence, risk_flags,
 *   category breakdown, and human-readable explanation.
 *
 * No external ML calls. Fully deterministic.
 */
class MaritimeDecisionEngine
{
    private const SAFETY_CRITICAL_ROLES = [
        'captain', 'chief_officer', 'chief_engineer', 'second_engineer',
    ];

    private const CRITICAL_KEYWORDS = [
        'safety', 'collision', 'bypass', 'tamper', 'stop work ignored',
    ];

    private const MAJOR_KEYWORDS = [
        'procedure', 'chain of command', 'reporting',
    ];

    private const CATEGORY_LABELS = [
        'core_duty'              => 'Core Duty',
        'risk_safety'            => 'Risk & Safety',
        'procedure_discipline'   => 'Procedure Discipline',
        'communication_judgment' => 'Communication & Judgment',
    ];

    private const VALID_CATEGORIES = [
        'core_duty', 'risk_safety', 'procedure_discipline', 'communication_judgment',
    ];

    private const COMPETENCY_TO_CATEGORY = [
        // Communication & Judgment
        'communication'     => 'communication_judgment',
        'teamwork'          => 'communication_judgment',
        'customer_focus'    => 'communication_judgment',
        'integrity'         => 'communication_judgment',
        // Procedure Discipline
        'accountability'    => 'procedure_discipline',
        'responsibility'    => 'procedure_discipline',
        'discipline'        => 'procedure_discipline',
        'adaptability'      => 'procedure_discipline',
        // Risk & Safety
        'safety'            => 'risk_safety',
        'compliance'        => 'risk_safety',
        'risk_awareness'    => 'risk_safety',
        'stress_resilience' => 'risk_safety',
        // Core Duty
        'leadership'        => 'core_duty',
        'decision_making'   => 'core_duty',
        'planning'          => 'core_duty',
        'problem_solving'   => 'core_duty',
        'role_competence'   => 'core_duty',
        'learning_agility'  => 'core_duty',
    ];

    /**
     * Evaluate a completed maritime interview and return decision summary.
     */
    public function evaluate(FormInterview $fi): array
    {
        $meta = $fi->meta ?? [];
        $answers = $this->normalizeAnswers($fi);
        $questionCount = max(count($answers), 1);

        // --- Per-question scores ---
        $scores = array_filter(
            array_map(fn($a) => $a['score'], $answers),
            fn($s) => $s !== null
        );
        $validScoreCount = count($scores);

        // --- Interview score % ---
        $interviewScorePct = $validScoreCount > 0
            ? (int) round(array_sum($scores) / (5 * $questionCount) * 100)
            : 0;

        // --- Missing penalty ---
        $missingPenalty = ($questionCount - $validScoreCount) > 0 ? 10 : 0;

        // --- Consistency bonus ---
        $consistencyBonus = 0;
        if ($validScoreCount >= 2) {
            $mean = array_sum($scores) / $validScoreCount;
            $variance = array_sum(array_map(fn($s) => ($s - $mean) ** 2, $scores)) / $validScoreCount;
            $stdDev = sqrt($variance);
            if ($stdDev <= 1.2) {
                $consistencyBonus = 5;
            }
        }

        // --- Certificate ---
        $certificateStatus = $meta['certificate_status'] ?? 'unknown';
        $certificateBonus = match ($certificateStatus) {
            'verified' => 5,
            'expired'  => -10,
            default    => 0,
        };

        // --- Risk flags ---
        $riskFlags = $this->extractRiskFlags($fi, $answers);
        $riskPenalty = $this->computeRiskPenalty($riskFlags);

        // --- Category scores ---
        $categoryScores = $this->computeCategoryScores($answers);

        // --- Final score (clamped 0-100) ---
        $finalScore = $interviewScorePct
            - $missingPenalty
            + $consistencyBonus
            + $certificateBonus
            - $riskPenalty;
        $finalScore = max(0, min(100, $finalScore));

        // --- Decision ---
        $decision = $this->computeDecision($finalScore, $riskFlags, $certificateStatus, $meta);

        // --- Confidence ---
        $confidencePct = $this->computeConfidence(
            $answers, $questionCount, $certificateStatus,
            $riskFlags, $consistencyBonus > 0
        );

        // --- Explanation ---
        $explanation = $this->generateExplanation(
            $decision, $finalScore, $confidencePct,
            $categoryScores, $riskFlags, $certificateStatus
        );

        return [
            'decision'            => $decision,
            'final_score'         => $finalScore,
            'confidence_pct'      => $confidencePct,
            'interview_score_pct' => $interviewScorePct,
            'category_scores'     => $categoryScores,
            'risk_flags'          => $riskFlags,
            'certificate_status'  => $certificateStatus,
            'explanation'         => $explanation,
            'computed_at'         => now()->toIso8601String(),
            'engine_version'      => 'maritime_v1',
        ];
    }

    // ─── Category resolver ─────────────────────────────────────────

    private function resolveCategory(string $category, ?string $competency = null): string
    {
        if (in_array($category, self::VALID_CATEGORIES, true)) {
            return $category;
        }

        $key = strtolower(trim($competency ?? $category));

        return self::COMPETENCY_TO_CATEGORY[$key] ?? 'communication_judgment';
    }

    // ─── Answer normalizer ───────────────────────────────────────

    private function normalizeAnswers(FormInterview $fi): array
    {
        $meta = $fi->meta ?? [];

        // Priority 1: meta.answers (explicit maritime format)
        if (!empty($meta['answers']) && is_array($meta['answers'])) {
            return $this->normalizeAnswerArray($meta['answers']);
        }

        // Priority 2: decision_packet.answers
        if (!empty($meta['decision_packet']['answers']) && is_array($meta['decision_packet']['answers'])) {
            return $this->normalizeAnswerArray($meta['decision_packet']['answers']);
        }

        // Priority 3: meta.interview.answers
        if (!empty($meta['interview']['answers']) && is_array($meta['interview']['answers'])) {
            return $this->normalizeAnswerArray($meta['interview']['answers']);
        }

        // Priority 4: FormInterviewAnswer relationship
        return $this->answersFromRelationship($fi);
    }

    private function normalizeAnswerArray(array $raw): array
    {
        return array_map(fn($a) => [
            'question_id' => $a['question_id'] ?? $a['id'] ?? null,
            'category'    => $this->resolveCategory(
                $a['category'] ?? 'unknown',
                $a['competency'] ?? null,
            ),
            'score'       => isset($a['score']) ? (int) $a['score'] : null,
            'text'        => $a['text'] ?? $a['answer'] ?? '',
            'red_flags'   => $a['red_flags'] ?? [],
        ], $raw);
    }

    private function answersFromRelationship(FormInterview $fi): array
    {
        $fiAnswers = $fi->relationLoaded('answers')
            ? $fi->answers
            : $fi->answers()->get();

        $categoryMap = $this->buildCategoryMap($fi);

        return $fiAnswers->map(function ($a) use ($categoryMap) {
            $score = $a->score ?? null;

            // If score looks like 0-100 heuristic range, normalize to 0-5
            if ($score !== null && $score > 5) {
                $score = (int) round($score / 20);
                $score = max(0, min(5, $score));
            }

            // Fallback: score column missing or null — derive 0-5 from answer text length
            if ($score === null) {
                $score = $this->scoreFromTextLength($a->answer_text ?? '');
            }

            $redFlags = $a->red_flags ?? null;
            if (is_string($redFlags)) {
                $redFlags = json_decode($redFlags, true) ?: [];
            }

            $rawCategory = $categoryMap[$a->competency] ?? $categoryMap[$a->slot] ?? 'unknown';

            return [
                'question_id' => $a->competency ?? "q_{$a->slot}",
                'category'    => $this->resolveCategory($rawCategory, $a->competency),
                'score'       => $score !== null ? (int) $score : null,
                'text'        => $a->answer_text ?? '',
                'red_flags'   => is_array($redFlags) ? $redFlags : [],
            ];
        })->toArray();
    }

    /**
     * Heuristic 0-5 score from answer text length.
     * Used when the DB has no score column (relationship fallback).
     */
    private function scoreFromTextLength(string $text): ?int
    {
        $len = mb_strlen(trim($text), 'UTF-8');
        if ($len === 0) {
            return null;  // truly missing answer
        }

        return match (true) {
            $len < 30  => 1,
            $len < 80  => 2,
            $len < 180 => 3,
            $len < 350 => 4,
            default    => 5,
        };
    }

    private function buildCategoryMap(FormInterview $fi): array
    {
        $map = [];
        $templateJson = $fi->template_json;
        if (!$templateJson) {
            return $map;
        }

        $template = is_string($templateJson) ? json_decode($templateJson, true) : $templateJson;
        if (!is_array($template)) {
            return $map;
        }

        $questions = $template['questions'] ?? [];
        if (empty($questions) && isset($template[0]['questions'])) {
            $questions = $template[0]['questions'];
        }
        if (empty($questions) && isset($template[0]['id'])) {
            $questions = $template;
        }

        foreach ($questions as $idx => $q) {
            $id = $q['id'] ?? null;
            $category = $q['category'] ?? 'unknown';
            if ($id) {
                $map[$id] = $category;
            }
            $map[$idx] = $category;
        }

        return $map;
    }

    // ─── Category scores ─────────────────────────────────────────

    private function computeCategoryScores(array $answers): array
    {
        $buckets = [
            'core_duty'              => [],
            'risk_safety'            => [],
            'procedure_discipline'   => [],
            'communication_judgment' => [],
        ];

        foreach ($answers as $a) {
            $cat = $a['category'] ?? 'unknown';
            if (isset($buckets[$cat]) && $a['score'] !== null) {
                $buckets[$cat][] = $a['score'];
            }
        }

        // Sanity: if all buckets empty but scored answers exist, round-robin distribute
        $allEmpty = empty(array_filter($buckets, fn($b) => !empty($b)));
        if ($allEmpty) {
            $scoredAnswers = array_filter($answers, fn($a) => $a['score'] !== null);
            $catKeys = array_keys($buckets);
            $i = 0;
            foreach ($scoredAnswers as $a) {
                $buckets[$catKeys[$i % count($catKeys)]][] = $a['score'];
                $i++;
            }
        }

        $result = [];
        foreach ($buckets as $cat => $scores) {
            $result[$cat] = count($scores) > 0
                ? (int) round(array_sum($scores) / (5 * count($scores)) * 100)
                : 0;
        }

        return $result;
    }

    // ─── Risk flags ──────────────────────────────────────────────

    private function extractRiskFlags(FormInterview $fi, array $answers): array
    {
        $meta = $fi->meta ?? [];

        // From meta.risk_flags if structured flags already exist
        if (!empty($meta['risk_flags']) && is_array($meta['risk_flags'])) {
            return array_values(array_map(fn($f) => [
                'code'     => $f['code'] ?? 'RISK_FLAG',
                'severity' => $f['severity'] ?? 'minor',
                'message'  => $f['message'] ?? $f['name'] ?? 'Risk flag detected',
            ], $meta['risk_flags']));
        }

        // Derive from per-answer red_flags
        $flags = [];
        foreach ($answers as $a) {
            foreach (($a['red_flags'] ?? []) as $rf) {
                $rfText = is_string($rf) ? $rf : ($rf['text'] ?? ($rf['message'] ?? ''));
                if (empty($rfText)) {
                    continue;
                }

                $severity = $this->classifyFlagSeverity($rfText);
                $code = 'RF_' . strtoupper(substr(
                    preg_replace('/[^a-z0-9]/', '_', strtolower($rfText)),
                    0,
                    30
                ));

                $flags[] = [
                    'code'     => $code,
                    'severity' => $severity,
                    'message'  => mb_substr($rfText, 0, 120),
                ];
            }
        }

        return $flags;
    }

    private function classifyFlagSeverity(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');

        foreach (self::CRITICAL_KEYWORDS as $kw) {
            if (str_contains($lower, $kw)) {
                return 'critical';
            }
        }

        foreach (self::MAJOR_KEYWORDS as $kw) {
            if (str_contains($lower, $kw)) {
                return 'major';
            }
        }

        return 'minor';
    }

    private function computeRiskPenalty(array $flags): int
    {
        $total = 0;
        foreach ($flags as $f) {
            $total += match ($f['severity']) {
                'critical' => 25,
                'major'    => 15,
                'minor'    => 5,
                default    => 0,
            };
        }
        return min(50, $total);
    }

    // ─── Decision ────────────────────────────────────────────────

    private function computeDecision(int $finalScore, array $riskFlags, string $certStatus, array $meta): string
    {
        $hasCritical = collect($riskFlags)->contains(fn($f) => $f['severity'] === 'critical');

        // Score-based thresholds
        if ($finalScore >= 80) {
            $decision = 'hire';
        } elseif ($finalScore >= 55) {
            $decision = 'review';
        } else {
            $decision = 'reject';
        }

        // Overrule 1: Critical risk flag => cannot hire
        if ($hasCritical && $decision === 'hire') {
            $decision = 'review';
        }

        // Overrule 2: Expired cert on safety-critical role => cannot hire
        $roleCode = $meta['role_code'] ?? '';
        if (
            $certStatus === 'expired'
            && in_array($roleCode, self::SAFETY_CRITICAL_ROLES, true)
            && $decision === 'hire'
        ) {
            $decision = 'review';
        }

        return $decision;
    }

    // ─── Confidence ──────────────────────────────────────────────

    private function computeConfidence(
        array $answers,
        int $questionCount,
        string $certStatus,
        array $riskFlags,
        bool $hasConsistencyBonus
    ): int {
        $confidence = 60;

        $validScores = array_filter(
            array_map(fn($a) => $a['score'], $answers),
            fn($s) => $s !== null
        );
        $allPresent = count($validScores) === $questionCount && $questionCount > 0;
        $anyMissing = count($validScores) < $questionCount;
        $hasCritical = collect($riskFlags)->contains(fn($f) => $f['severity'] === 'critical');
        $hasMajor = collect($riskFlags)->contains(fn($f) => $f['severity'] === 'major');

        // Additions
        if ($allPresent) {
            $confidence += 10;
        }
        if ($certStatus === 'verified') {
            $confidence += 10;
        }
        if (!$hasCritical && !$hasMajor) {
            $confidence += 10;
        }
        if ($hasConsistencyBonus) {
            $confidence += 5;
        }

        // Subtractions
        if ($anyMissing) {
            $confidence -= 10;
        }
        if ($hasCritical) {
            $confidence -= 15;
        }

        return max(30, min(95, $confidence));
    }

    // ─── Explanation ─────────────────────────────────────────────

    private function generateExplanation(
        string $decision,
        int $finalScore,
        int $confidencePct,
        array $categoryScores,
        array $riskFlags,
        string $certStatus
    ): string {
        $label = strtoupper($decision);

        // Top category strengths
        arsort($categoryScores);
        $strengths = array_slice(
            array_keys(array_filter($categoryScores, fn($s) => $s > 0)),
            0,
            2
        );
        $strengthNames = array_map(
            fn($c) => self::CATEGORY_LABELS[$c] ?? $c,
            $strengths
        );

        // Concerns
        $concerns = [];
        if (collect($riskFlags)->contains(fn($f) => $f['severity'] === 'critical')) {
            $concerns[] = 'critical risk flags';
        }
        if (collect($riskFlags)->contains(fn($f) => $f['severity'] === 'major')) {
            $concerns[] = 'major risk concerns';
        }
        if ($certStatus === 'expired') {
            $concerns[] = 'expired certificate';
        }
        if (in_array($certStatus, ['unknown', 'missing'], true)) {
            $concerns[] = 'unverified certificate';
        }

        $parts = ["{$label} recommendation (score: {$finalScore}/100, confidence: {$confidencePct}%)."];

        if (!empty($strengthNames)) {
            $parts[] = 'Strengths: ' . implode(', ', $strengthNames) . '.';
        }

        if (!empty($concerns)) {
            $parts[] = 'Concerns: ' . implode(', ', array_slice($concerns, 0, 2)) . '.';
        }

        return mb_substr(implode(' ', $parts), 0, 320);
    }
}
