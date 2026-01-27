<?php

namespace App\Services\AntiCheat;

use App\Models\Interview;
use App\Models\InterviewAnalysis;
use App\Models\InterviewResponse;
use App\Models\ResponseSimilarity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AntiCheatService
{
    protected array $flags = [];
    protected float $riskScore = 0;

    /**
     * Analyze interview for cheating indicators
     */
    public function analyzeInterview(Interview $interview): array
    {
        $this->flags = [];
        $this->riskScore = 0;

        $interview->load(['responses', 'job', 'analysis']);

        // 1. Timing Analysis
        $timingAnalysis = $this->analyzeTimingPatterns($interview);

        // 2. Pattern Similarity (compare with other candidates)
        $similarityAnalysis = $this->analyzePatternSimilarity($interview);

        // 3. Text Consistency Analysis
        $consistencyAnalysis = $this->analyzeTextConsistency($interview);

        // 4. Copy-Paste Heuristics
        $copyPasteAnalysis = $this->analyzeCopyPasteIndicators($interview);

        // Calculate final risk score
        $this->calculateFinalRiskScore();

        $result = [
            'cheating_risk_score' => round($this->riskScore, 2),
            'cheating_flags' => $this->flags,
            'cheating_level' => $this->determineLevel($this->riskScore),
            'timing_analysis' => $timingAnalysis,
            'similarity_analysis' => $similarityAnalysis,
            'consistency_analysis' => $consistencyAnalysis,
            'copy_paste_analysis' => $copyPasteAnalysis,
        ];

        // Update the interview analysis with cheating data
        $this->updateInterviewAnalysis($interview, $result);

        return $result;
    }

    /**
     * Analyze timing patterns
     */
    protected function analyzeTimingPatterns(Interview $interview): array
    {
        $analysis = [
            'response_times' => [],
            'anomalies' => [],
            'risk_contribution' => 0,
        ];

        foreach ($interview->responses as $response) {
            $wordCount = str_word_count($response->transcript ?? '');
            $duration = $response->duration_seconds ?? 0;

            // Calculate words per minute
            $wpm = $duration > 0 ? ($wordCount / $duration) * 60 : 0;

            // Update response with timing data
            $response->update([
                'word_count' => $wordCount,
                'words_per_minute' => round($wpm, 2),
            ]);

            $analysis['response_times'][] = [
                'question_order' => $response->response_order,
                'duration' => $duration,
                'word_count' => $wordCount,
                'wpm' => round($wpm, 2),
            ];

            // Flag: Extremely high WPM (possible reading from script)
            if ($wpm > 200 && $wordCount > 50) {
                $this->addFlag('high_speech_rate', [
                    'question_order' => $response->response_order,
                    'wpm' => round($wpm, 2),
                    'severity' => 'medium',
                    'description' => 'Cok hizli konusma orani - olasi okuma',
                ]);
                $analysis['risk_contribution'] += 15;
            }

            // Flag: Very short response time for long answer
            if ($duration < 30 && $wordCount > 100) {
                $this->addFlag('short_time_long_answer', [
                    'question_order' => $response->response_order,
                    'duration' => $duration,
                    'word_count' => $wordCount,
                    'severity' => 'high',
                    'description' => 'Cok kisa surede uzun cevap - olasi hazir metin',
                ]);
                $analysis['risk_contribution'] += 20;
            }

            // Flag: Very long pause before speaking (possible looking up answer)
            $thinkingPause = $response->thinking_pause_seconds ?? 0;
            if ($thinkingPause > 60) {
                $this->addFlag('long_thinking_pause', [
                    'question_order' => $response->response_order,
                    'pause_seconds' => $thinkingPause,
                    'severity' => 'low',
                    'description' => 'Uzun dusunme suresi',
                ]);
                $analysis['risk_contribution'] += 5;
            }
        }

        $this->riskScore += min($analysis['risk_contribution'], 40);

        return $analysis;
    }

    /**
     * Analyze pattern similarity with other candidates
     */
    protected function analyzePatternSimilarity(Interview $interview): array
    {
        $analysis = [
            'similar_responses' => [],
            'max_similarity' => 0,
            'risk_contribution' => 0,
        ];

        $jobId = $interview->job_id;

        // Get all other completed interviews for the same job
        $otherInterviews = Interview::where('job_id', $jobId)
            ->where('id', '!=', $interview->id)
            ->where('status', 'completed')
            ->with('responses')
            ->get();

        foreach ($interview->responses as $response) {
            if (empty($response->transcript)) continue;

            foreach ($otherInterviews as $otherInterview) {
                $otherResponse = $otherInterview->responses
                    ->firstWhere('response_order', $response->response_order);

                if (!$otherResponse || empty($otherResponse->transcript)) continue;

                // Calculate cosine similarity
                $similarity = $this->calculateCosineSimilarity(
                    $response->transcript,
                    $otherResponse->transcript
                );

                // Store similarity record
                if ($similarity > 0.5) {
                    ResponseSimilarity::updateOrCreate(
                        [
                            'response_id_a' => min($response->id, $otherResponse->id),
                            'response_id_b' => max($response->id, $otherResponse->id),
                        ],
                        [
                            'job_id' => $jobId,
                            'question_order' => $response->response_order,
                            'cosine_similarity' => $similarity,
                            'jaccard_similarity' => $this->calculateJaccardSimilarity(
                                $response->transcript,
                                $otherResponse->transcript
                            ),
                            'flagged' => $similarity >= 0.85,
                        ]
                    );
                }

                // Flag high similarity
                if ($similarity >= 0.85) {
                    $analysis['similar_responses'][] = [
                        'question_order' => $response->response_order,
                        'other_interview_id' => $otherInterview->id,
                        'similarity' => round($similarity * 100, 1),
                    ];

                    $this->addFlag('high_similarity', [
                        'question_order' => $response->response_order,
                        'similarity_percent' => round($similarity * 100, 1),
                        'severity' => $similarity >= 0.95 ? 'critical' : 'high',
                        'description' => 'Baska adayla yuksek benzerlik - olasi kopya',
                    ]);

                    $analysis['risk_contribution'] += $similarity >= 0.95 ? 30 : 20;
                }

                $analysis['max_similarity'] = max($analysis['max_similarity'], $similarity);
            }
        }

        $this->riskScore += min($analysis['risk_contribution'], 40);

        return $analysis;
    }

    /**
     * Analyze text consistency within the interview
     */
    protected function analyzeTextConsistency(Interview $interview): array
    {
        $analysis = [
            'inconsistencies' => [],
            'risk_contribution' => 0,
        ];

        $allTranscripts = $interview->responses
            ->pluck('transcript')
            ->filter()
            ->implode(' ');

        // Check for contradictory statements
        $contradictions = $this->detectContradictions($interview->responses);

        foreach ($contradictions as $contradiction) {
            $this->addFlag('contradiction', [
                'questions' => $contradiction['questions'],
                'severity' => 'medium',
                'description' => $contradiction['description'],
            ]);
            $analysis['inconsistencies'][] = $contradiction;
            $analysis['risk_contribution'] += 10;
        }

        $this->riskScore += min($analysis['risk_contribution'], 20);

        return $analysis;
    }

    /**
     * Analyze copy-paste indicators
     */
    protected function analyzeCopyPasteIndicators(Interview $interview): array
    {
        $analysis = [
            'indicators' => [],
            'risk_contribution' => 0,
        ];

        foreach ($interview->responses as $response) {
            if (empty($response->transcript)) continue;

            $text = $response->transcript;

            // Calculate sentence length variance
            $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            $sentenceLengths = array_map(fn($s) => str_word_count(trim($s)), $sentences);

            if (count($sentenceLengths) >= 3) {
                $variance = $this->calculateVariance($sentenceLengths);
                $avgLength = array_sum($sentenceLengths) / count($sentenceLengths);

                // Update response with variance
                $response->update([
                    'sentence_length_variance' => round($variance, 4),
                ]);

                // Flag: Unnaturally low variance (too uniform = likely scripted)
                if ($variance < 5 && $avgLength > 10 && count($sentences) > 3) {
                    $this->addFlag('uniform_sentence_structure', [
                        'question_order' => $response->response_order,
                        'variance' => round($variance, 2),
                        'severity' => 'low',
                        'description' => 'Asiri duzgun cumle yapisi - olasi hazir metin',
                    ]);
                    $analysis['risk_contribution'] += 10;
                    $analysis['indicators'][] = [
                        'type' => 'uniform_structure',
                        'question_order' => $response->response_order,
                    ];
                }
            }

            // Check for formal/academic writing style unusual for spoken response
            if ($this->hasAcademicWritingStyle($text)) {
                $this->addFlag('academic_writing_style', [
                    'question_order' => $response->response_order,
                    'severity' => 'low',
                    'description' => 'Akademik yazi stili - konusmaya uygun degil',
                ]);
                $analysis['risk_contribution'] += 5;
            }
        }

        $this->riskScore += min($analysis['risk_contribution'], 15);

        return $analysis;
    }

    /**
     * Calculate cosine similarity between two texts
     */
    protected function calculateCosineSimilarity(string $text1, string $text2): float
    {
        $words1 = $this->tokenize($text1);
        $words2 = $this->tokenize($text2);

        $allWords = array_unique(array_merge($words1, $words2));

        $vector1 = [];
        $vector2 = [];

        foreach ($allWords as $word) {
            $vector1[] = in_array($word, $words1) ? 1 : 0;
            $vector2[] = in_array($word, $words2) ? 1 : 0;
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($vector1); $i++) {
            $dotProduct += $vector1[$i] * $vector2[$i];
            $magnitude1 += $vector1[$i] * $vector1[$i];
            $magnitude2 += $vector2[$i] * $vector2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Calculate Jaccard similarity
     */
    protected function calculateJaccardSimilarity(string $text1, string $text2): float
    {
        $words1 = array_unique($this->tokenize($text1));
        $words2 = array_unique($this->tokenize($text2));

        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $union > 0 ? $intersection / $union : 0;
    }

    /**
     * Tokenize text into words
     */
    protected function tokenize(string $text): array
    {
        // Convert to lowercase and remove punctuation
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);

        // Split into words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Remove common Turkish stop words
        $stopWords = ['ve', 'ile', 'bir', 'bu', 'de', 'da', 'ne', 'için', 'gibi', 'ama', 'fakat', 'ancak'];
        return array_diff($words, $stopWords);
    }

    /**
     * Detect contradictions in responses
     */
    protected function detectContradictions(Collection $responses): array
    {
        $contradictions = [];

        // Simple keyword-based contradiction detection
        $contradictionPairs = [
            ['hic', 'her zaman'],
            ['asla', 'surekli'],
            ['hicbir zaman', 'her seferinde'],
        ];

        $texts = $responses->pluck('transcript', 'response_order')->toArray();

        foreach ($texts as $order1 => $text1) {
            foreach ($texts as $order2 => $text2) {
                if ($order1 >= $order2) continue;

                $text1Lower = mb_strtolower($text1 ?? '');
                $text2Lower = mb_strtolower($text2 ?? '');

                foreach ($contradictionPairs as [$word1, $word2]) {
                    if (
                        (str_contains($text1Lower, $word1) && str_contains($text2Lower, $word2)) ||
                        (str_contains($text1Lower, $word2) && str_contains($text2Lower, $word1))
                    ) {
                        $contradictions[] = [
                            'questions' => [$order1, $order2],
                            'description' => "Soru {$order1} ve {$order2} arasinda olasi celiskili ifade",
                        ];
                    }
                }
            }
        }

        return $contradictions;
    }

    /**
     * Check for academic writing style
     */
    protected function hasAcademicWritingStyle(string $text): bool
    {
        $academicIndicators = [
            'sonuc olarak',
            'bu baglamda',
            'ozetle',
            'dolayisiyla',
            'bu nedenle',
            'ilk olarak',
            'ikinci olarak',
            'son olarak',
        ];

        $text = mb_strtolower($text);
        $count = 0;

        foreach ($academicIndicators as $indicator) {
            if (str_contains($text, $indicator)) {
                $count++;
            }
        }

        return $count >= 3;
    }

    /**
     * Calculate variance
     */
    protected function calculateVariance(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;

        $mean = array_sum($values) / $n;
        $sumSquaredDiff = 0;

        foreach ($values as $value) {
            $sumSquaredDiff += ($value - $mean) ** 2;
        }

        return $sumSquaredDiff / ($n - 1);
    }

    /**
     * Add a flag to the collection
     */
    protected function addFlag(string $type, array $data): void
    {
        $this->flags[] = array_merge(['type' => $type], $data);
    }

    /**
     * Calculate final risk score
     */
    protected function calculateFinalRiskScore(): void
    {
        // Ensure score is within 0-100
        $this->riskScore = max(0, min(100, $this->riskScore));

        // Add bonus risk for multiple flags
        if (count($this->flags) >= 5) {
            $this->riskScore = min(100, $this->riskScore + 10);
        }

        // Critical flags automatically raise score
        $hasCritical = collect($this->flags)->contains(fn($f) => ($f['severity'] ?? '') === 'critical');
        if ($hasCritical) {
            $this->riskScore = max($this->riskScore, 70);
        }
    }

    /**
     * Determine risk level from score
     */
    protected function determineLevel(float $score): string
    {
        return match (true) {
            $score >= 70 => 'high',
            $score >= 40 => 'medium',
            default => 'low',
        };
    }

    /**
     * Update interview analysis with cheating data
     */
    protected function updateInterviewAnalysis(Interview $interview, array $result): void
    {
        if ($interview->analysis) {
            $interview->analysis->update([
                'cheating_risk_score' => $result['cheating_risk_score'],
                'cheating_flags' => $result['cheating_flags'],
                'cheating_level' => $result['cheating_level'],
                'timing_analysis' => $result['timing_analysis'],
                'similarity_analysis' => $result['similarity_analysis'],
                'consistency_analysis' => $result['consistency_analysis'],
            ]);
        }
    }
}
