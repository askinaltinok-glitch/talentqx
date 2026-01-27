<?php

namespace App\Services\Assessment;

use App\Models\AssessmentResponseSimilarity;
use App\Models\AssessmentSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssessmentAntiCheatService
{
    /**
     * Thresholds
     */
    private const SIMILARITY_THRESHOLD = 85; // 85% similarity triggers flag
    private const MIN_WPM_THRESHOLD = 10;    // Less than 10 WPM is suspicious (too fast)
    private const MAX_WPM_THRESHOLD = 150;   // More than 150 WPM is suspicious (copy-paste)
    private const MIN_TIME_PER_QUESTION = 5; // Minimum 5 seconds per question

    /**
     * Analyze a completed assessment session for cheating indicators
     */
    public function analyze(AssessmentSession $session): array
    {
        $session->load(['template', 'employee']);

        $flags = [];
        $riskScores = [];

        // 1. Timing Analysis
        $timingResult = $this->analyzeTimingPatterns($session);
        $flags = array_merge($flags, $timingResult['flags']);
        $riskScores['timing'] = $timingResult['risk_score'];

        // 2. Similar Response Detection (within same role)
        $similarityResult = $this->analyzeSimilarResponses($session);
        $flags = array_merge($flags, $similarityResult['flags']);
        $riskScores['similarity'] = $similarityResult['risk_score'];

        // 3. Response Pattern Analysis
        $patternResult = $this->analyzeResponsePatterns($session);
        $flags = array_merge($flags, $patternResult['flags']);
        $riskScores['pattern'] = $patternResult['risk_score'];

        // 4. Consistency Check
        $consistencyResult = $this->analyzeConsistency($session);
        $flags = array_merge($flags, $consistencyResult['flags']);
        $riskScores['consistency'] = $consistencyResult['risk_score'];

        // Calculate overall risk score (weighted average)
        $overallScore = $this->calculateOverallRiskScore($riskScores);
        $cheatingLevel = $this->determineCheatingLevel($overallScore);

        return [
            'cheating_risk_score' => $overallScore,
            'cheating_level' => $cheatingLevel,
            'cheating_flags' => $flags,
            'risk_breakdown' => $riskScores,
        ];
    }

    /**
     * Analyze timing patterns
     */
    private function analyzeTimingPatterns(AssessmentSession $session): array
    {
        $flags = [];
        $riskScore = 0;
        $responses = $session->responses ?? [];

        if (empty($responses)) {
            return ['flags' => [], 'risk_score' => 0];
        }

        $suspiciousCount = 0;

        foreach ($responses as $response) {
            $timeSpent = $response['time_spent'] ?? 0;
            $answer = $response['answer'] ?? '';
            $questionOrder = $response['question_order'] ?? 0;

            // Calculate word count and WPM
            $wordCount = str_word_count($answer);
            $minutes = $timeSpent / 60;
            $wpm = $minutes > 0 ? $wordCount / $minutes : 0;

            // Check for suspiciously fast responses with long answers
            if ($timeSpent < self::MIN_TIME_PER_QUESTION && $wordCount > 20) {
                $flags[] = [
                    'type' => 'fast_response',
                    'question_order' => $questionOrder,
                    'severity' => 'high',
                    'description' => "Soru {$questionOrder}: {$timeSpent} saniyede {$wordCount} kelimelik cevap (cok hizli)",
                    'time_spent' => $timeSpent,
                    'word_count' => $wordCount,
                ];
                $suspiciousCount++;
            }

            // Check for unrealistic WPM
            if ($wpm > self::MAX_WPM_THRESHOLD && $wordCount > 30) {
                $flags[] = [
                    'type' => 'unrealistic_wpm',
                    'question_order' => $questionOrder,
                    'severity' => 'high',
                    'description' => "Soru {$questionOrder}: {$wpm:.0f} kelime/dakika (kopyala-yapistir olabilir)",
                    'wpm' => round($wpm, 2),
                ];
                $suspiciousCount++;
            }

            // Check for too slow (might be looking up answers)
            if ($timeSpent > 300 && $wordCount < 20) { // 5 minutes for short answer
                $flags[] = [
                    'type' => 'slow_short_response',
                    'question_order' => $questionOrder,
                    'severity' => 'low',
                    'description' => "Soru {$questionOrder}: {$timeSpent} saniye harcandi, sadece {$wordCount} kelime",
                ];
            }
        }

        // Calculate risk score based on suspicious responses
        $totalQuestions = count($responses);
        if ($totalQuestions > 0) {
            $riskScore = min(100, ($suspiciousCount / $totalQuestions) * 150);
        }

        return ['flags' => $flags, 'risk_score' => $riskScore];
    }

    /**
     * Analyze similar responses within the same role
     */
    private function analyzeSimilarResponses(AssessmentSession $session): array
    {
        $flags = [];
        $riskScore = 0;
        $maxSimilarity = 0;

        // Get other sessions with the same template
        $otherSessions = AssessmentSession::where('template_id', $session->template_id)
            ->where('id', '!=', $session->id)
            ->where('status', 'completed')
            ->whereNotNull('responses')
            ->orderByDesc('completed_at')
            ->limit(50) // Check against last 50 sessions
            ->get();

        if ($otherSessions->isEmpty()) {
            return ['flags' => [], 'risk_score' => 0];
        }

        $responses = $session->responses ?? [];

        foreach ($responses as $response) {
            $questionOrder = $response['question_order'] ?? 0;
            $answer = $response['answer'] ?? '';

            if (strlen($answer) < 20) {
                continue; // Skip short answers
            }

            foreach ($otherSessions as $otherSession) {
                $otherResponses = $otherSession->responses ?? [];
                $otherAnswer = collect($otherResponses)->firstWhere('question_order', $questionOrder)['answer'] ?? '';

                if (strlen($otherAnswer) < 20) {
                    continue;
                }

                // Calculate similarity
                $similarity = $this->calculateTextSimilarity($answer, $otherAnswer);

                if ($similarity > $maxSimilarity) {
                    $maxSimilarity = $similarity;
                }

                if ($similarity >= self::SIMILARITY_THRESHOLD) {
                    // Log similarity for tracking
                    AssessmentResponseSimilarity::create([
                        'session_a_id' => $session->id,
                        'session_b_id' => $otherSession->id,
                        'question_order' => $questionOrder,
                        'similarity_score' => $similarity,
                        'similarity_type' => $similarity >= 95 ? 'exact' : 'near_duplicate',
                        'flagged' => true,
                    ]);

                    $flags[] = [
                        'type' => 'similar_response',
                        'question_order' => $questionOrder,
                        'severity' => $similarity >= 95 ? 'critical' : 'high',
                        'description' => "Soru {$questionOrder}: %{$similarity:.1f} benzerlik (Calisan: {$otherSession->employee->full_name})",
                        'similarity_percent' => round($similarity, 2),
                        'other_session_id' => $otherSession->id,
                    ];
                }
            }
        }

        // Risk score based on max similarity
        if ($maxSimilarity >= 95) {
            $riskScore = 100;
        } elseif ($maxSimilarity >= 85) {
            $riskScore = 80;
        } elseif ($maxSimilarity >= 70) {
            $riskScore = 50;
        }

        return ['flags' => $flags, 'risk_score' => $riskScore];
    }

    /**
     * Analyze response patterns (sentence structure, vocabulary)
     */
    private function analyzeResponsePatterns(AssessmentSession $session): array
    {
        $flags = [];
        $riskScore = 0;
        $responses = $session->responses ?? [];

        if (empty($responses)) {
            return ['flags' => [], 'risk_score' => 0];
        }

        $allAnswers = [];
        $sentenceLengths = [];

        foreach ($responses as $response) {
            $answer = $response['answer'] ?? '';
            $allAnswers[] = $answer;

            // Analyze sentence lengths
            $sentences = preg_split('/[.!?]+/', $answer, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($sentences as $sentence) {
                $wordCount = str_word_count(trim($sentence));
                if ($wordCount > 0) {
                    $sentenceLengths[] = $wordCount;
                }
            }
        }

        // Check for unnaturally uniform sentence lengths (copy-paste indicator)
        if (count($sentenceLengths) > 5) {
            $variance = $this->calculateVariance($sentenceLengths);
            $avgLength = array_sum($sentenceLengths) / count($sentenceLengths);

            // Very low variance in sentence lengths is suspicious
            if ($variance < 5 && $avgLength > 10) {
                $flags[] = [
                    'type' => 'uniform_sentence_length',
                    'severity' => 'medium',
                    'description' => "Cumle uzunluklari cok tekduze (varyans: {$variance:.2f})",
                    'variance' => round($variance, 2),
                ];
                $riskScore += 30;
            }
        }

        // Check for academic/formal language patterns (might indicate external sources)
        $fullText = implode(' ', $allAnswers);
        $academicPatterns = [
            'bunun yanı sıra',
            'öte yandan',
            'sonuç olarak',
            'örneğin',
            'dolayısıyla',
            'bu bağlamda',
        ];

        $academicCount = 0;
        foreach ($academicPatterns as $pattern) {
            $academicCount += substr_count(mb_strtolower($fullText), $pattern);
        }

        $totalWords = str_word_count($fullText);
        if ($totalWords > 100 && ($academicCount / ($totalWords / 100)) > 2) {
            $flags[] = [
                'type' => 'academic_language',
                'severity' => 'low',
                'description' => "Akademik/resmi dil kaliplari tespit edildi ({$academicCount} adet)",
                'count' => $academicCount,
            ];
            $riskScore += 15;
        }

        return ['flags' => $flags, 'risk_score' => min(100, $riskScore)];
    }

    /**
     * Analyze consistency across answers
     */
    private function analyzeConsistency(AssessmentSession $session): array
    {
        $flags = [];
        $riskScore = 0;
        $responses = $session->responses ?? [];

        if (count($responses) < 3) {
            return ['flags' => [], 'risk_score' => 0];
        }

        // Check for dramatic style changes between answers
        $styles = [];
        foreach ($responses as $response) {
            $answer = $response['answer'] ?? '';
            $styles[] = [
                'avg_sentence_length' => $this->getAvgSentenceLength($answer),
                'punctuation_density' => $this->getPunctuationDensity($answer),
                'question_order' => $response['question_order'] ?? 0,
            ];
        }

        // Look for outliers in style
        $avgLengths = array_column($styles, 'avg_sentence_length');
        $avgLength = count($avgLengths) > 0 ? array_sum($avgLengths) / count($avgLengths) : 0;
        $stdDev = $this->calculateStdDev($avgLengths);

        foreach ($styles as $style) {
            if ($stdDev > 0 && abs($style['avg_sentence_length'] - $avgLength) > 2 * $stdDev) {
                $flags[] = [
                    'type' => 'style_inconsistency',
                    'question_order' => $style['question_order'],
                    'severity' => 'medium',
                    'description' => "Soru {$style['question_order']}: Yazim stili diger cevaplardan belirgin farkli",
                ];
                $riskScore += 20;
            }
        }

        return ['flags' => $flags, 'risk_score' => min(100, $riskScore)];
    }

    /**
     * Calculate text similarity using multiple methods
     */
    private function calculateTextSimilarity(string $text1, string $text2): float
    {
        // Normalize texts
        $text1 = mb_strtolower(trim($text1));
        $text2 = mb_strtolower(trim($text2));

        // 1. Exact match
        if ($text1 === $text2) {
            return 100.0;
        }

        // 2. Levenshtein-based similarity (for shorter texts)
        if (strlen($text1) < 500 && strlen($text2) < 500) {
            $maxLen = max(strlen($text1), strlen($text2));
            $distance = levenshtein($text1, $text2);
            $levenshteinSimilarity = $maxLen > 0 ? (1 - ($distance / $maxLen)) * 100 : 0;
        } else {
            $levenshteinSimilarity = 0;
        }

        // 3. Jaccard similarity on words
        $words1 = array_unique(preg_split('/\s+/', $text1));
        $words2 = array_unique(preg_split('/\s+/', $text2));

        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        $jaccardSimilarity = $union > 0 ? ($intersection / $union) * 100 : 0;

        // 4. N-gram similarity (trigrams)
        $ngrams1 = $this->getNgrams($text1, 3);
        $ngrams2 = $this->getNgrams($text2, 3);

        if (count($ngrams1) > 0 && count($ngrams2) > 0) {
            $ngramIntersection = count(array_intersect($ngrams1, $ngrams2));
            $ngramUnion = count(array_unique(array_merge($ngrams1, $ngrams2)));
            $ngramSimilarity = $ngramUnion > 0 ? ($ngramIntersection / $ngramUnion) * 100 : 0;
        } else {
            $ngramSimilarity = 0;
        }

        // Weighted average of similarity methods
        return ($levenshteinSimilarity * 0.2) + ($jaccardSimilarity * 0.4) + ($ngramSimilarity * 0.4);
    }

    /**
     * Get n-grams from text
     */
    private function getNgrams(string $text, int $n): array
    {
        $ngrams = [];
        $len = mb_strlen($text);

        for ($i = 0; $i <= $len - $n; $i++) {
            $ngrams[] = mb_substr($text, $i, $n);
        }

        return array_unique($ngrams);
    }

    /**
     * Calculate variance
     */
    private function calculateVariance(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $values);

        return array_sum($squaredDiffs) / $count;
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStdDev(array $values): float
    {
        return sqrt($this->calculateVariance($values));
    }

    /**
     * Get average sentence length
     */
    private function getAvgSentenceLength(string $text): float
    {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($sentences)) {
            return 0;
        }

        $totalWords = 0;
        foreach ($sentences as $sentence) {
            $totalWords += str_word_count(trim($sentence));
        }

        return $totalWords / count($sentences);
    }

    /**
     * Get punctuation density (punctuation per 100 chars)
     */
    private function getPunctuationDensity(string $text): float
    {
        $len = strlen($text);
        if ($len === 0) {
            return 0;
        }

        $punctuationCount = preg_match_all('/[.,;:!?\-()"]/', $text);

        return ($punctuationCount / $len) * 100;
    }

    /**
     * Calculate overall risk score from components
     */
    private function calculateOverallRiskScore(array $riskScores): int
    {
        // Weighted average
        $weights = [
            'timing' => 0.25,
            'similarity' => 0.40,
            'pattern' => 0.20,
            'consistency' => 0.15,
        ];

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($riskScores as $key => $score) {
            $weight = $weights[$key] ?? 0.25;
            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? (int) round($totalScore / $totalWeight * ($totalWeight / array_sum($weights))) : 0;
    }

    /**
     * Determine cheating level from score
     */
    private function determineCheatingLevel(int $score): string
    {
        return match (true) {
            $score >= 70 => 'high',
            $score >= 40 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get similar responses for a session
     */
    public function getSimilarResponses(string $sessionId): array
    {
        return AssessmentResponseSimilarity::where('session_a_id', $sessionId)
            ->orWhere('session_b_id', $sessionId)
            ->with(['sessionA.employee', 'sessionB.employee'])
            ->orderByDesc('similarity_score')
            ->get()
            ->toArray();
    }
}
