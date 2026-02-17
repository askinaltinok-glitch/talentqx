<?php

namespace App\Services\Interview;

use App\Models\AiSetting;
use App\Models\Interview;
use App\Models\InterviewAnalysis;
use App\Models\InterviewResponse;
use App\Services\AI\LLMProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalysisEngine
{
    public function __construct(
        private LLMProviderInterface $llmProvider
    ) {}

    public function analyzeInterview(Interview $interview, bool $forceReanalyze = false): InterviewAnalysis
    {
        if (!$forceReanalyze && $interview->analysis()->exists()) {
            return $interview->analysis;
        }

        $interview->load(['responses.question', 'job']);

        $responses = $this->prepareResponsesForAnalysis($interview);
        $competencies = $interview->job->getEffectiveCompetencies();
        $redFlags = $interview->job->getEffectiveRedFlags();

        try {
            $analysisResult = $this->llmProvider->analyzeInterview(
                $responses,
                $competencies,
                $redFlags
            );

            return $this->saveAnalysis($interview, $analysisResult, $competencies);
        } catch (\Exception $e) {
            Log::error('Interview analysis failed', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function prepareResponsesForAnalysis(Interview $interview): array
    {
        $responses = [];

        foreach ($interview->responses as $response) {
            $responses[] = [
                'question_order' => $response->response_order,
                'question_text' => $response->question->question_text,
                'question_type' => $response->question->question_type,
                'competency_code' => $response->question->competency_code,
                'ideal_answer_points' => $response->question->ideal_answer_points,
                'transcript' => $response->transcript ?? '[Transkript mevcut degil]',
                'duration_seconds' => $response->duration_seconds ?? 0,
            ];
        }

        return $responses;
    }

    private function saveAnalysis(Interview $interview, array $result, array $competencies): InterviewAnalysis
    {
        return DB::transaction(function () use ($interview, $result, $competencies) {
            $interview->analysis()->delete();

            $overallScore = $result['overall_score'] ?? $this->calculateOverallScore(
                $result['competency_scores'] ?? [],
                $competencies
            );

            // Get the AI model info from provider
            $aiModelInfo = $this->llmProvider->getModelInfo();

            return InterviewAnalysis::create([
                'interview_id' => $interview->id,
                'ai_model' => $aiModelInfo['model'] ?? config('services.openai.model'),
                'ai_model_version' => $aiModelInfo['provider'] ?? 'openai',
                'analyzed_at' => now(),
                'competency_scores' => $result['competency_scores'] ?? [],
                'overall_score' => $overallScore,
                'behavior_analysis' => $result['behavior_analysis'] ?? null,
                'red_flag_analysis' => $result['red_flag_analysis'] ?? ['flags_detected' => false, 'flags' => []],
                'culture_fit' => $result['culture_fit'] ?? null,
                'decision_snapshot' => $result['decision_snapshot'] ?? null,
                'raw_ai_response' => $result,
                'question_analyses' => $result['question_analyses'] ?? [],
            ]);
        });
    }

    private function calculateOverallScore(array $competencyScores, array $competencies): float
    {
        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($competencies as $competency) {
            $code = $competency['code'];
            $weight = $competency['weight'] ?? 0;

            if (isset($competencyScores[$code]['score'])) {
                $totalWeightedScore += $competencyScores[$code]['score'] * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? round($totalWeightedScore / $totalWeight, 2) : 0;
    }

    public function getAnalysisSummary(InterviewAnalysis $analysis): array
    {
        return [
            'overall_score' => $analysis->overall_score,
            'recommendation' => $analysis->getRecommendation(),
            'confidence' => $analysis->getConfidencePercent(),
            'has_red_flags' => $analysis->hasRedFlags(),
            'red_flags_count' => $analysis->getRedFlagsCount(),
            'culture_fit' => $analysis->getCultureFitScore(),
            'top_strengths' => $this->getTopStrengths($analysis),
            'improvement_areas' => $this->getImprovementAreas($analysis),
            'reasons' => $analysis->getReasons(),
        ];
    }

    private function getTopStrengths(InterviewAnalysis $analysis): array
    {
        $scores = $analysis->competency_scores ?? [];
        $strengths = [];

        foreach ($scores as $code => $data) {
            if (($data['score'] ?? 0) >= 80) {
                $strengths[] = [
                    'competency' => $code,
                    'score' => $data['score'],
                    'evidence' => $data['evidence'] ?? [],
                ];
            }
        }

        usort($strengths, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($strengths, 0, 3);
    }

    private function getImprovementAreas(InterviewAnalysis $analysis): array
    {
        $scores = $analysis->competency_scores ?? [];
        $areas = [];

        foreach ($scores as $code => $data) {
            if (($data['score'] ?? 0) < 60) {
                $areas[] = [
                    'competency' => $code,
                    'score' => $data['score'],
                    'improvement_areas' => $data['improvement_areas'] ?? [],
                ];
            }
        }

        usort($areas, fn($a, $b) => $a['score'] <=> $b['score']);

        return array_slice($areas, 0, 3);
    }
}
