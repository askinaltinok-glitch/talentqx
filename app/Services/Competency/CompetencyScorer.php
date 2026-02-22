<?php

namespace App\Services\Competency;

use App\Models\CompetencyDimension;
use App\Models\CompetencyQuestion;
use Illuminate\Support\Collection;

/**
 * Deterministic rubric-based competency scorer (v1.1 — multi-language).
 *
 * Additive rubric:
 *   Base score from length (0..2)
 *   +1 if example + outcome structure detected
 *   +1 if dimension keywords hit threshold (>=2)
 *   +1 if ownership markers present
 *   Cap at 5
 *
 * Stopword filtering: generic Turkish/English words not counted as keyword hits.
 * Language detection: lightweight heuristic (TR chars / stopwords).
 * Returns language metadata + evidence bullets for fairness guardrail.
 */
class CompetencyScorer
{
    /** Turkish stopwords — must never count as keyword hits */
    private const TR_STOPWORDS = [
        've', 'veya', 'ama', 'fakat', 'çünkü', 'gibi', 'ile', 'olarak', 'şekilde',
        'sonra', 'önce', 'önemli', 'çok', 'daha', 'kadar', 'bir', 'bu', 'şu', 'o',
        'da', 'de', 'mi', 'mı', 'mu', 'mü', 'olan', 'olanı', 'olduğu', 'oldu',
        'olmak', 'sağla', 'sağlamak', 'için', 'hem', 'ancak', 'yani', 'benim',
        'bana', 'beni', 'biz', 'bize', 'bizi', 'onun', 'ona', 'onu', 'bunun',
        'bunu', 'şey', 'var', 'yok', 'ise', 'iyi', 'kötü', 'büyük', 'küçük',
    ];

    /**
     * Score a set of answers against competency questions.
     *
     * @return array{score_total: float, score_by_dimension: array, answer_scores: array, questions_evaluated: int, language: string, language_confidence: float, coverage: float, evidence_by_dimension: array}
     */
    public function score(
        Collection $answers,
        string $roleScope = 'ALL',
        string $vesselScope = 'all',
        string $operationScope = 'both',
        ?array $dimensionWeightsOverride = null,
    ): array {
        $cfg = config('maritime.competency', []);
        $maxPerQuestion = (int) ($cfg['max_score_per_question'] ?? 5);
        $minAnswerLength = (int) ($cfg['minimum_answer_length'] ?? 10);
        $dimensionWeights = $dimensionWeightsOverride ?? $cfg['dimension_weights'] ?? [];

        // Load applicable questions
        $questions = CompetencyQuestion::query()
            ->active()
            ->forRole($roleScope)
            ->forVessel($vesselScope)
            ->forOperation($operationScope)
            ->with('dimension')
            ->get();

        if ($questions->isEmpty()) {
            return [
                'score_total' => 0,
                'score_by_dimension' => [],
                'answer_scores' => [],
                'questions_evaluated' => 0,
                'language' => 'unknown',
                'language_confidence' => 0.0,
                'coverage' => 0.0,
                'evidence_by_dimension' => [],
            ];
        }

        // Detect language from all answer texts combined
        $allText = $answers->pluck('answer_text')->filter()->implode(' ');
        $langResult = $this->detectLanguage($allText);

        // Load config-driven keyword sets for detected language
        $keywords = $cfg['keywords'] ?? [];
        $markers = $cfg['markers'] ?? [];

        // Legacy competency code → new dimension code mapping
        $legacyMap = [
            'COMMUNICATION'     => 'COMMS',
            'ACCOUNTABILITY'    => 'DISCIPLINE',
            'TEAMWORK'          => 'TEAMWORK',
            'STRESS_RESILIENCE' => 'STRESS',
            'ADAPTABILITY'      => 'TECH_PRACTICAL',
            'LEARNING_AGILITY'  => 'LEADERSHIP',
            'INTEGRITY'         => 'DISCIPLINE',
            'ROLE_COMPETENCE'   => 'TECH_PRACTICAL',
        ];

        // Index answers by competency code (dimension code)
        $answersByCompetency = [];
        foreach ($answers as $answer) {
            $key = strtoupper(trim($answer->competency ?? ''));
            $mapped = $legacyMap[$key] ?? $key;
            if (!isset($answersByCompetency[$mapped])) {
                $answersByCompetency[$mapped] = [];
            }
            $answersByCompetency[$mapped][] = $answer;
        }

        // Score each question
        $answerScores = [];
        $dimensionScores = [];
        $dimensionMaxes = [];
        $totalHits = 0;
        $totalExpectedHits = 0;
        $evidenceByDimension = []; // [dim_code => [matched_keywords]]

        foreach ($questions as $question) {
            $dimCode = $question->dimension->code;

            // NOTE: pass by value — same answer reused for all questions in same dimension
            $answerText = $this->findAnswer($question, $answersByCompetency, $answers);

            // Get dimension-specific keywords for detected language
            $dimKeywords = $this->getDimensionKeywords($dimCode, $langResult['language'], $keywords);

            // Score with additive rubric
            $scoreDetail = $this->scoreAnswer(
                $answerText,
                $dimKeywords,
                $markers,
                $langResult['language'],
                $maxPerQuestion,
                $minAnswerLength,
            );

            $totalHits += $scoreDetail['keyword_hits'];
            $totalExpectedHits += max(count($dimKeywords), 1);

            // Collect evidence keywords (deduplicate per dimension)
            if (!empty($scoreDetail['matched_keywords'])) {
                if (!isset($evidenceByDimension[$dimCode])) {
                    $evidenceByDimension[$dimCode] = [];
                }
                $evidenceByDimension[$dimCode] = array_unique(
                    array_merge($evidenceByDimension[$dimCode], $scoreDetail['matched_keywords'])
                );
            }

            $answerScores[] = [
                'question_id' => $question->id,
                'dimension' => $dimCode,
                'difficulty' => $question->difficulty,
                'score' => $scoreDetail['score'],
                'max_score' => $maxPerQuestion,
                'answer_length' => mb_strlen($answerText ?? ''),
            ];

            if (!isset($dimensionScores[$dimCode])) {
                $dimensionScores[$dimCode] = [];
                $dimensionMaxes[$dimCode] = [];
            }
            $dimensionScores[$dimCode][] = $scoreDetail['score'];
            $dimensionMaxes[$dimCode][] = $maxPerQuestion;
        }

        // Trim evidence to top 3 per dimension
        foreach ($evidenceByDimension as $dimCode => $kws) {
            $evidenceByDimension[$dimCode] = array_slice(array_values($kws), 0, 3);
        }

        // Calculate per-dimension scores (0–100)
        $scoreByDimension = [];
        foreach ($dimensionScores as $dimCode => $scores) {
            $totalScore = array_sum($scores);
            $totalMax = array_sum($dimensionMaxes[$dimCode]);
            $scoreByDimension[$dimCode] = $totalMax > 0
                ? round(($totalScore / $totalMax) * 100, 1)
                : 0;
        }

        // Weighted total score (0–100) — BEFORE depth
        $weightedTotal = $this->calculateWeightedTotal($scoreByDimension, $dimensionWeights);
        $weightedTotalBeforeDepth = $weightedTotal;

        // Technical depth scoring (rank-specific keyword bonus)
        $technicalDepth = $this->scoreTechnicalDepth(
            $allText,
            $roleScope,
            $cfg['technical_depth'] ?? [],
            $scoreByDimension,
        );

        // Apply technical depth bonus to TECH_PRACTICAL dimension
        $depthUplift = 0.0;
        if ($technicalDepth) {
            $tp = $scoreByDimension['TECH_PRACTICAL'] ?? 40;
            $tp = max($tp, $technicalDepth['tech_practical_floor'] ?? 0);
            $tp += $technicalDepth['bonus_points'] ?? 0;
            $tp = min($tp, $technicalDepth['tech_practical_cap'] ?? 100);
            $scoreByDimension['TECH_PRACTICAL'] = round($tp, 1);
            // Recalculate weighted total with boosted TECH_PRACTICAL
            $weightedTotal = $this->calculateWeightedTotal($scoreByDimension, $dimensionWeights);

            // ── Anti-inflation: cap total uplift from depth ──
            $maxUplift = (float) (($cfg['technical_depth'] ?? [])['max_total_score_uplift'] ?? 15);
            $depthUplift = round($weightedTotal - $weightedTotalBeforeDepth, 1);
            if ($depthUplift > $maxUplift) {
                $weightedTotal = $weightedTotalBeforeDepth + $maxUplift;
                $depthUplift = $maxUplift;
            }
        }

        // Coverage: ratio of keyword hits to expected
        $coverage = $totalExpectedHits > 0
            ? round($totalHits / $totalExpectedHits, 3)
            : 0.0;

        return [
            'score_total' => round($weightedTotal, 1),
            'score_before_depth' => round($weightedTotalBeforeDepth, 1),
            'depth_uplift' => $depthUplift,
            'score_by_dimension' => $scoreByDimension,
            'answer_scores' => $answerScores,
            'questions_evaluated' => count($answerScores),
            'language' => $langResult['language'],
            'language_confidence' => $langResult['confidence'],
            'coverage' => $coverage,
            'evidence_by_dimension' => $evidenceByDimension,
            'technical_depth_index' => $technicalDepth['technical_depth_index'] ?? null,
            'technical_depth_detail' => $technicalDepth,
        ];
    }

    // ─── Language Detection ───────────────────────────────────────

    /**
     * Lightweight language detection heuristic.
     *
     * @return array{language: string, confidence: float}
     */
    private function detectLanguage(string $text): array
    {
        if (mb_strlen($text) < 20) {
            return ['language' => 'unknown', 'confidence' => 0.3];
        }

        $lower = mb_strtolower($text);

        // Turkish detection: special chars + stopwords
        $trChars = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü'];
        $trStopwords = ['ve', 'bir', 'bu', 'için', 'ile', 'ama', 'çünkü', 'gibi', 'olarak', 'olan', 'ancak', 'hem', 'çok', 'daha'];

        $trCharHits = 0;
        foreach ($trChars as $ch) {
            if (mb_strpos($lower, $ch) !== false) {
                $trCharHits++;
            }
        }

        $trStopHits = 0;
        foreach ($trStopwords as $sw) {
            if (preg_match('/(?:^|\s)' . preg_quote($sw, '/') . '(?:\s|$|[,.])/u', $lower)) {
                $trStopHits++;
            }
        }

        // Russian detection: Cyrillic characters
        $cyrillicCount = preg_match_all('/[\p{Cyrillic}]/u', $lower);
        $totalChars = mb_strlen($lower);
        $cyrillicRatio = $totalChars > 0 ? $cyrillicCount / $totalChars : 0;

        if ($cyrillicRatio > 0.3) {
            return ['language' => 'ru', 'confidence' => min(0.9, 0.5 + $cyrillicRatio)];
        }

        if ($trCharHits >= 2 || $trStopHits >= 3) {
            $trScore = ($trCharHits * 0.15) + ($trStopHits * 0.1);
            return ['language' => 'tr', 'confidence' => min(0.95, 0.5 + $trScore)];
        }

        if ($trCharHits >= 1 || $trStopHits >= 1) {
            return ['language' => 'tr', 'confidence' => 0.5];
        }

        // Default to English
        return ['language' => 'en', 'confidence' => 0.8];
    }

    // ─── Dimension Keywords ───────────────────────────────────────

    /**
     * Get keywords for a specific dimension and language from config.
     * Always includes EN technical terms (ISM, SOLAS etc are universal).
     * Filters out stopwords.
     */
    private function getDimensionKeywords(string $dimCode, string $lang, array $keywordsConfig): array
    {
        $dimConfig = $keywordsConfig[$dimCode] ?? [];

        if ($lang === 'unknown') {
            $merged = array_merge($dimConfig['en'] ?? [], $dimConfig['tr'] ?? []);
        } else {
            // Primary language + always include English technical terms
            $merged = $dimConfig[$lang] ?? [];
            if ($lang !== 'en') {
                $merged = array_merge($merged, $dimConfig['en'] ?? []);
            }
        }

        // Filter out stopwords from single-word keywords
        $stopwords = self::TR_STOPWORDS;
        $filtered = array_filter($merged, function ($kw) use ($stopwords) {
            // Multi-word phrases always pass
            if (str_contains($kw, ' ')) {
                return true;
            }
            return !in_array($kw, $stopwords, true);
        });

        return array_values(array_unique($filtered));
    }

    // ─── Answer Scoring (Additive Rubric) ─────────────────────────

    /**
     * Score a single answer using additive rubric.
     *
     * Base (0..2): length-based
     * +1: example + outcome markers
     * +1: dimension keyword hits >= 2
     * +1: ownership markers
     * Cap at 5
     *
     * @return array{score: int, keyword_hits: int, matched_keywords: array}
     */
    private function scoreAnswer(
        ?string $answerText,
        array $dimKeywords,
        array $markers,
        string $lang,
        int $maxScore,
        int $minLength,
    ): array {
        if (!$answerText || mb_strlen(trim($answerText)) < $minLength) {
            return ['score' => 0, 'keyword_hits' => 0, 'matched_keywords' => []];
        }

        $text = mb_strtolower(trim($answerText));
        $wordCount = count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));

        // ── Base score from length ──
        $score = 1;
        if ($wordCount >= 20) {
            $score = 2;
        }

        // ── +1: Example + Outcome structure ──
        $exampleKw = $this->mergeMarkers($markers, 'example', $lang);
        $outcomeKw = $this->mergeMarkers($markers, 'outcome', $lang);

        $exampleHits = $this->countKeywords($text, $exampleKw);
        $outcomeHits = $this->countKeywords($text, $outcomeKw);

        if ($exampleHits >= 1 && $outcomeHits >= 1) {
            $score++;
        }

        // ── +1: Dimension keyword hits >= 2 ──
        $dimHitResult = $this->matchKeywords($text, $dimKeywords);
        if ($dimHitResult['count'] >= 2) {
            $score++;
        }

        // ── +1: Ownership markers ──
        $ownershipKw = $this->mergeMarkers($markers, 'ownership', $lang);
        $ownershipHits = $this->countKeywords($text, $ownershipKw);

        if ($ownershipHits >= 1) {
            $score++;
        }

        return [
            'score' => min($score, $maxScore),
            'keyword_hits' => $dimHitResult['count'],
            'matched_keywords' => $dimHitResult['matched'],
        ];
    }

    // ─── Answer Matching ──────────────────────────────────────────

    /**
     * Find the best matching answer for a question.
     * NOTE: $answersByCompetency is passed by VALUE — same answer
     *       is reused for all questions in the same dimension.
     */
    private function findAnswer(CompetencyQuestion $question, array $answersByCompetency, Collection $allAnswers): ?string
    {
        $dimCode = $question->dimension->code;

        if (isset($answersByCompetency[$dimCode]) && !empty($answersByCompetency[$dimCode])) {
            // Take the first answer (not shifting — pass by value preserves the array)
            return $answersByCompetency[$dimCode][0]->answer_text;
        }

        return null;
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Merge marker arrays for a specific type and language.
     * Always includes English markers + detected language.
     * Filters out stopwords.
     */
    private function mergeMarkers(array $markers, string $type, string $lang): array
    {
        $en = $markers[$type]['en'] ?? [];
        $other = ($lang !== 'en' && $lang !== 'unknown') ? ($markers[$type][$lang] ?? []) : [];
        $merged = array_unique(array_merge($en, $other));

        // Filter out single-word stopwords
        $stopwords = self::TR_STOPWORDS;
        return array_values(array_filter($merged, function ($kw) use ($stopwords) {
            if (str_contains($kw, ' ')) return true;
            return !in_array($kw, $stopwords, true);
        }));
    }

    /**
     * Count how many keywords from a list appear in the text.
     */
    private function countKeywords(string $text, array $keywords): int
    {
        $count = 0;
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Match keywords and return both count and matched list.
     */
    private function matchKeywords(string $text, array $keywords): array
    {
        $matched = [];
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                $matched[] = $kw;
            }
        }
        return ['count' => count($matched), 'matched' => $matched];
    }

    // ─── Technical Depth Scoring ─────────────────────────────────

    /**
     * Score technical depth based on rank-specific keyword packs.
     *
     * Returns null if role is excluded or no rank pack matches.
     *
     * @return array|null {technical_depth_index, tech_practical_floor, bonus_points, tech_practical_cap, matched_by_category, total_signals}
     */
    private function scoreTechnicalDepth(
        string $allText,
        string $roleScope,
        array $depthCfg,
        array $scoreByDimension,
    ): ?array {
        if (empty($depthCfg)) {
            return null;
        }

        $excluded = $depthCfg['excluded_role_scopes'] ?? ['AB', 'OILER', 'COOK', 'ALL'];
        if (in_array($roleScope, $excluded, true)) {
            return null;
        }

        $rankPacks = $depthCfg['rank_packs'] ?? [];
        $pack = $rankPacks[$roleScope] ?? null;
        if (!$pack) {
            return null;
        }

        $phraseWeight = (int) ($depthCfg['phrase_weight'] ?? 2);
        $textLower = mb_strtolower($allText);

        // Scan each category for keyword matches
        $matchedByCategory = [];
        $totalSignals = 0;
        $totalPossibleKeywords = 0;
        $phraseHits = 0;
        $singleHits = 0;
        $categoryNames = array_keys($pack);

        foreach ($pack as $categoryName => $keywords) {
            $categoryMatches = [];
            foreach ($keywords as $kw) {
                $totalPossibleKeywords++;
                if (str_contains($textLower, $kw)) {
                    $categoryMatches[] = $kw;
                    $isPhrase = str_contains($kw, ' ');
                    if ($isPhrase) {
                        $totalSignals += $phraseWeight;
                        $phraseHits++;
                    } else {
                        $totalSignals += 1;
                        $singleHits++;
                    }
                }
            }
            $matchedByCategory[$categoryName] = $categoryMatches;
        }

        $totalHitsRaw = $phraseHits + $singleHits;

        // Primary category is the first one in the pack
        $primaryCategory = $categoryNames[0] ?? null;
        $primaryHits = $primaryCategory ? count($matchedByCategory[$primaryCategory] ?? []) : 0;

        // Secondary and tertiary hits
        $secondaryCats = array_slice($categoryNames, 1);
        $secondaryHits = 0;
        $tertiaryHits = 0;
        if (isset($secondaryCats[0])) {
            $secondaryHits = count($matchedByCategory[$secondaryCats[0]] ?? []);
        }
        if (isset($secondaryCats[1])) {
            $tertiaryHits = count($matchedByCategory[$secondaryCats[1]] ?? []);
        }

        // ── Compute technical_depth_index FIRST (needed for guardrails) ──
        $weights = $depthCfg['depth_index_weights'] ?? [
            'keyword_density' => 0.40,
            'category_diversity' => 0.35,
            'specificity' => 0.25,
        ];

        $keywordDensity = $totalPossibleKeywords > 0
            ? min(100, ($totalSignals / $totalPossibleKeywords) * 100)
            : 0;

        $categoriesHit = 0;
        foreach ($matchedByCategory as $matches) {
            if (!empty($matches)) {
                $categoriesHit++;
            }
        }
        $totalCategories = count($categoryNames);
        $categoryDiversity = $totalCategories > 0
            ? ($categoriesHit / $totalCategories) * 100
            : 0;

        $specificity = $totalHitsRaw > 0
            ? ($phraseHits / $totalHitsRaw) * 100
            : 0;

        $technicalDepthIndex = round(
            ($keywordDensity * ($weights['keyword_density'] ?? 0.40))
            + ($categoryDiversity * ($weights['category_diversity'] ?? 0.35))
            + ($specificity * ($weights['specificity'] ?? 0.25)),
            1
        );

        // ── Apply bonus rules WITH depth-index guardrails ──────────
        $minSignals = (int) ($depthCfg['min_signals_for_bonus'] ?? 3);
        $primaryBonusScore = (int) ($depthCfg['primary_bonus_score'] ?? 70);
        $secondaryBonusPoints = (int) ($depthCfg['secondary_bonus_points'] ?? 10);
        $secondaryBonusRule = $depthCfg['secondary_bonus_rule'] ?? [2, 1];
        $totalSignalsForCap = (int) ($depthCfg['total_signals_for_cap'] ?? 5);
        $capScore = (int) ($depthCfg['cap_score'] ?? 85);

        $depthBoostMinIndex = (float) ($depthCfg['depth_boost_min_index'] ?? 40);
        $depthCapTiers = $depthCfg['depth_boost_cap_tiers'] ?? [75 => 85, 60 => 75, 40 => 60];

        $techPracticalFloor = 0;
        $bonusPoints = 0;
        $techPracticalCap = 100;

        if ($technicalDepthIndex < $depthBoostMinIndex) {
            // Insufficient depth evidence — no floor boost, no bonus
            // TECH_PRACTICAL keeps base rubric score (cap stays 100)
        } else {
            // Primary bonus: enough hits from main category → floor score
            if ($primaryHits >= $minSignals) {
                $techPracticalFloor = $primaryBonusScore;
            }

            // Secondary bonus: cross-category depth
            if ($secondaryHits >= ($secondaryBonusRule[0] ?? 2) && $tertiaryHits >= ($secondaryBonusRule[1] ?? 1)) {
                $bonusPoints = $secondaryBonusPoints;
            }

            // Signal-based cap
            if ($totalSignals >= $totalSignalsForCap) {
                $techPracticalCap = $capScore;
            }

            // Depth-tier cap (stricter of signal cap and tier cap)
            krsort($depthCapTiers);
            foreach ($depthCapTiers as $minDepth => $tierCap) {
                if ($technicalDepthIndex >= (float) $minDepth) {
                    $techPracticalCap = min($techPracticalCap, $tierCap);
                    break;
                }
            }
        }

        return [
            'technical_depth_index' => $technicalDepthIndex,
            'tech_practical_floor' => $techPracticalFloor,
            'bonus_points' => $bonusPoints,
            'tech_practical_cap' => $techPracticalCap,
            'matched_by_category' => $matchedByCategory,
            'total_signals' => $totalSignals,
            'primary_hits' => $primaryHits,
            'categories_hit' => $categoriesHit,
        ];
    }

    /**
     * Calculate weighted total from dimension scores and config weights.
     */
    private function calculateWeightedTotal(array $scoreByDimension, array $dimensionWeights): float
    {
        if (empty($scoreByDimension)) {
            return 0;
        }

        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($scoreByDimension as $dimCode => $dimScore) {
            $weight = $dimensionWeights[$dimCode] ?? 0.15;
            $weightedSum += $dimScore * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }
}
