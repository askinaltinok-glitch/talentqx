<?php

namespace App\Services\Voice;

use App\Models\FormInterview;
use App\Models\VoiceBehavioralSignal;
use App\Models\VoiceBehaviorProfile;
use Illuminate\Support\Facades\Log;

class VoiceBehavioralSignalService
{
    private const VERSION = 'v1.0';
    private const MIN_QUESTIONS_WITH_SIGNALS = 3;

    // WPM bell curve: optimal range 120-160 for natural speech
    private const WPM_OPTIMAL_CENTER = 140;
    private const WPM_OPTIMAL_RANGE = 40;

    // Index weights for overall voice score
    private const INDEX_WEIGHTS = [
        'confidence' => 0.20,
        'communication_clarity' => 0.20,
        'stress' => 0.15,           // inverted: lower stress = better
        'decisiveness' => 0.15,
        'emotional_stability' => 0.15,
        'leadership_tone' => 0.10,
        'hesitation' => 0.05,       // inverted: lower hesitation = better
    ];

    /**
     * Compute behavioral indices from per-question voice signals.
     * Fail-open: returns null on error or insufficient data.
     */
    public function compute(FormInterview $interview): ?VoiceBehaviorProfile
    {
        try {
            return $this->doCompute($interview);
        } catch (\Throwable $e) {
            Log::warning('Voice behavioral signal computation failed', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function doCompute(FormInterview $interview): ?VoiceBehaviorProfile
    {
        $signals = VoiceBehavioralSignal::where('form_interview_id', $interview->id)
            ->orderBy('question_slot')
            ->get();

        if ($signals->count() < self::MIN_QUESTIONS_WITH_SIGNALS) {
            return null;
        }

        // Aggregate per-question metrics for cross-question analysis
        $confidences = [];
        $wpms = [];
        $totalWords = 0;
        $totalDuration = 0;
        $totalPauses = 0;
        $totalLongPauses = 0;
        $totalFillers = 0;
        $fillerRatios = [];
        $confidenceStds = [];
        $avgPauses = [];

        foreach ($signals as $signal) {
            $confidences[] = $signal->avg_confidence;
            $wpms[] = $signal->avg_wpm;
            $totalWords += $signal->total_word_count;
            $totalDuration += $signal->total_duration_s;
            $totalPauses += $signal->total_pause_count;
            $totalLongPauses += $signal->total_long_pause_count;
            $totalFillers += $signal->total_filler_count;
            $fillerRatios[] = $signal->avg_filler_ratio;
            $avgPauses[] = $signal->total_pause_count > 0
                ? $signal->total_duration_s / max($signal->total_pause_count, 1)
                : 0;

            // Per-utterance confidence std from raw data
            $utterances = $signal->utterance_signals_json ?? [];
            foreach ($utterances as $u) {
                $confidenceStds[] = $u['confidence_std'] ?? 0;
            }
        }

        $n = $signals->count();
        $avgConf = array_sum($confidences) / $n;
        $avgWpm = array_sum($wpms) / $n;
        $avgFillerRatio = array_sum($fillerRatios) / $n;
        $avgConfStd = count($confidenceStds) > 0 ? array_sum($confidenceStds) / count($confidenceStds) : 0;
        $avgPauseS = $totalPauses > 0 ? $totalDuration / $totalPauses : 0;
        $longPausePct = $totalPauses > 0 ? $totalLongPauses / $totalPauses : 0;

        // Cross-question variance (emotional stability measure)
        $confStdCross = $this->std($confidences);
        $wpmStdCross = $this->std($wpms);

        // WPM score: bell curve around optimal center
        $wpmScore = $this->wpmScore($avgWpm);

        // Verbosity score: moderate word count per question is ideal
        $wordsPerQ = $totalWords / $n;
        $verbosityScore = $this->clamp(min($wordsPerQ / 80, 1.0), 0, 1); // 80+ words/question = 1.0

        // ── Compute 7 indices ──
        $stress = $this->computeStress($avgConf, $avgConfStd, $longPausePct, $avgFillerRatio);
        $confidence = $this->computeConfidence($avgConf, $wpmScore, $avgFillerRatio);
        $decisiveness = $this->computeDecisiveness($avgPauseS, $longPausePct, $wpmScore);
        $hesitation = $this->computeHesitation($avgFillerRatio, $longPausePct, $avgConfStd);
        $commClarity = $this->computeCommunicationClarity($avgConf, $wpmScore, $avgFillerRatio, $verbosityScore);
        $emotionalStability = $this->computeEmotionalStability($confStdCross, $wpmStdCross);
        $leadershipTone = $this->computeLeadershipTone($wpmScore, $avgConf, $decisiveness, $verbosityScore);

        // Overall voice score (weighted, with inversions for stress/hesitation)
        $overall = $this->computeOverallScore([
            'confidence' => $confidence,
            'communication_clarity' => $commClarity,
            'stress' => 1.0 - $stress,              // inverted: low stress = good
            'decisiveness' => $decisiveness,
            'emotional_stability' => $emotionalStability,
            'leadership_tone' => $leadershipTone,
            'hesitation' => 1.0 - $hesitation,       // inverted: low hesitation = good
        ]);

        return VoiceBehaviorProfile::updateOrCreate(
            ['form_interview_id' => $interview->id],
            [
                'pool_candidate_id' => $interview->pool_candidate_id,
                'stress_index' => round($stress, 3),
                'confidence_index' => round($confidence, 3),
                'decisiveness_index' => round($decisiveness, 3),
                'hesitation_index' => round($hesitation, 3),
                'communication_clarity_index' => round($commClarity, 3),
                'emotional_stability_index' => round($emotionalStability, 3),
                'leadership_tone_index' => round($leadershipTone, 3),
                'overall_voice_score' => round($overall, 3),
                'computation_meta' => [
                    'version' => self::VERSION,
                    'algorithm' => 'rule_based_transcript_timing',
                    'questions_with_signals' => $n,
                    'total_utterances' => $signals->sum('utterance_count'),
                    'total_words' => $totalWords,
                    'total_duration_s' => round($totalDuration, 2),
                    'avg_wpm' => round($avgWpm, 1),
                    'avg_confidence' => round($avgConf, 4),
                    'computed_at' => now()->toIso8601String(),
                ],
            ]
        );
    }

    // ── Index computations ──

    private function computeStress(float $avgConf, float $confStd, float $longPausePct, float $fillerRatio): float
    {
        // Higher stress = lower confidence, higher variance, more fillers, more long pauses
        $raw = 1.0 - ($avgConf - 0.3 * $confStd - 0.1 * $fillerRatio - 0.05 * $longPausePct);
        return $this->clamp($raw, 0, 1);
    }

    private function computeConfidence(float $avgConf, float $wpmScore, float $fillerRatio): float
    {
        return $this->clamp(
            $avgConf * 0.5 + $wpmScore * 0.3 + (1 - $fillerRatio) * 0.2,
            0, 1
        );
    }

    private function computeDecisiveness(float $avgPauseS, float $longPausePct, float $wpmScore): float
    {
        // Normalize avg pause: 0s → 1.0, 3s+ → 0.0
        $pauseNorm = $this->clamp($avgPauseS / 3.0, 0, 1);
        return $this->clamp(
            1.0 - $pauseNorm * 0.5 - $longPausePct * 0.3 + $wpmScore * 0.2,
            0, 1
        );
    }

    private function computeHesitation(float $fillerRatio, float $longPausePct, float $confStd): float
    {
        return $this->clamp(
            $fillerRatio * 0.4 + $longPausePct * 0.3 + $confStd * 0.3,
            0, 1
        );
    }

    private function computeCommunicationClarity(float $avgConf, float $wpmScore, float $fillerRatio, float $verbosity): float
    {
        return $this->clamp(
            $avgConf * 0.4 + $wpmScore * 0.3 + (1 - $fillerRatio) * 0.2 + $verbosity * 0.1,
            0, 1
        );
    }

    private function computeEmotionalStability(float $confStdCross, float $wpmStdCross): float
    {
        // Normalize: confStdCross 0→stable, 0.2→very unstable
        $confNorm = $this->clamp($confStdCross / 0.2, 0, 1);
        // Normalize: wpmStdCross 0→stable, 40→very unstable
        $wpmNorm = $this->clamp($wpmStdCross / 40.0, 0, 1);
        return $this->clamp(1.0 - $confNorm * 0.5 - $wpmNorm * 0.5, 0, 1);
    }

    private function computeLeadershipTone(float $wpmScore, float $avgConf, float $decisiveness, float $verbosity): float
    {
        return $this->clamp(
            $wpmScore * 0.3 + $avgConf * 0.3 + $decisiveness * 0.2 + $verbosity * 0.2,
            0, 1
        );
    }

    private function computeOverallScore(array $indices): float
    {
        $score = 0;
        foreach (self::INDEX_WEIGHTS as $key => $weight) {
            $score += ($indices[$key] ?? 0.5) * $weight;
        }
        return $this->clamp($score, 0, 1);
    }

    // ── Helpers ──

    private function wpmScore(float $wpm): float
    {
        // Bell curve: 1.0 at center, drops off outside range
        $deviation = abs($wpm - self::WPM_OPTIMAL_CENTER);
        if ($deviation <= self::WPM_OPTIMAL_RANGE / 2) {
            return 1.0;
        }
        return $this->clamp(1.0 - ($deviation - self::WPM_OPTIMAL_RANGE / 2) / self::WPM_OPTIMAL_RANGE, 0, 1);
    }

    private function std(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }
        $mean = array_sum($values) / $n;
        $variance = 0;
        foreach ($values as $v) {
            $variance += ($v - $mean) ** 2;
        }
        return sqrt($variance / $n);
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
