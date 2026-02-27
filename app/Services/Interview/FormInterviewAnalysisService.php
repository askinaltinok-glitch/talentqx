<?php

namespace App\Services\Interview;

use App\Models\FormInterview;
use App\Models\FormInterviewAnalysis;
use App\Services\AI\LLMProviderInterface;
use Illuminate\Support\Facades\Log;

class FormInterviewAnalysisService
{
    public function __construct(
        private LLMProviderInterface $llmProvider
    ) {}

    /**
     * Analyze a FormInterview via AI.
     *
     * Returns a pipeline-compatible result array on success, or null on failure
     * (signaling heuristic fallback).
     *
     * Result format matches FormInterviewDecisionEngineAdapter::evaluate():
     *   competency_scores, risk_flags, final_score, decision, decision_reason
     */
    public function analyze(FormInterview $interview): ?array
    {
        $startMs = (int) (microtime(true) * 1000);

        try {
            $responses = $this->prepareResponses($interview);

            // Need at least 2 non-empty answers for meaningful AI analysis
            if (count($responses) < 2) {
                Log::channel('single')->info('FormInterviewAnalysisService: too few responses for AI', [
                    'interview_id' => $interview->id,
                    'response_count' => count($responses),
                ]);
                return null;
            }

            $competencies = $this->extractCompetencies($responses);
            $culturalContext = $this->buildCulturalContext($interview);

            $aiResult = $this->llmProvider->analyzeFormInterview(
                $responses,
                $competencies,
                $culturalContext
            );

            $latencyMs = (int) (microtime(true) * 1000) - $startMs;

            // Validate minimum AI output structure
            if (empty($aiResult['competency_scores']) || !isset($aiResult['overall_score'])) {
                Log::channel('single')->warning('FormInterviewAnalysisService: invalid AI response structure', [
                    'interview_id' => $interview->id,
                    'keys' => array_keys($aiResult),
                ]);
                return null;
            }

            // Persist full analysis to form_interview_analyses
            $modelInfo = $this->llmProvider->getModelInfo();
            $this->saveAnalysis($interview, $aiResult, $modelInfo, $latencyMs);

            // Transform to pipeline-compatible format
            return $this->transformToEngineResult($aiResult);
        } catch (\Throwable $e) {
            $latencyMs = (int) (microtime(true) * 1000) - $startMs;

            Log::channel('single')->warning('FormInterviewAnalysisService: AI analysis failed', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
                'latency_ms' => $latencyMs,
            ]);

            return null;
        }
    }

    /**
     * Extract question text from template_json, match with answers.
     */
    private function prepareResponses(FormInterview $interview): array
    {
        $interview->loadMissing('answers');

        $templateData = $interview->template_json;
        if (is_string($templateData)) {
            $templateData = json_decode($templateData, true);
        }

        $templateQuestions = $templateData['questions'] ?? [];

        // Build a map of slot → template question
        $questionMap = [];
        foreach ($templateQuestions as $index => $tq) {
            $slot = $index + 1;
            $questionMap[$slot] = $tq;
        }

        $responses = [];

        foreach ($interview->answers as $answer) {
            $text = trim($answer->answer_text ?? '');
            if ($text === '') {
                continue;
            }

            $tq = $questionMap[$answer->slot] ?? null;
            $questionText = $tq['question_text'] ?? $tq['text'] ?? "Question #{$answer->slot}";
            // Competency: prefer answer's own competency, then template's
            $competencyCode = $answer->competency
                ?: ($tq['competency_code'] ?? 'general');

            $responses[] = [
                'slot' => $answer->slot,
                'question_text' => $questionText,
                'competency_code' => $competencyCode,
                'answer_text' => $text,
                'answer_length' => mb_strlen($text, 'UTF-8'),
            ];
        }

        return $responses;
    }

    /**
     * Extract unique competency definitions for the prompt.
     */
    private function extractCompetencies(array $responses): array
    {
        $codes = [];
        foreach ($responses as $r) {
            $code = $r['competency_code'];
            if (!isset($codes[$code])) {
                $codes[$code] = [
                    'code' => $code,
                    'weight' => 1, // equal weight — AI decides relative importance
                ];
            }
        }
        return array_values($codes);
    }

    /**
     * Build cultural context from interview metadata.
     */
    private function buildCulturalContext(FormInterview $interview): array
    {
        $meta = $interview->meta ?? [];

        return [
            'locale' => $interview->language ?? 'tr',
            'country' => $meta['country_code'] ?? $meta['country'] ?? 'TR',
            'position' => $interview->position_code ?? null,
            'industry' => $interview->industry_code ?? null,
        ];
    }

    /**
     * Persist full AI analysis to form_interview_analyses table.
     */
    private function saveAnalysis(
        FormInterview $interview,
        array $aiResult,
        array $modelInfo,
        int $latencyMs
    ): FormInterviewAnalysis {
        // Delete any previous analysis for this interview
        FormInterviewAnalysis::where('form_interview_id', $interview->id)->delete();

        $analysis = new FormInterviewAnalysis();
        $analysis->setConnection($interview->getConnectionName());
        $analysis->fill([
            'form_interview_id' => $interview->id,
            'ai_model' => $modelInfo['model'] ?? null,
            'ai_provider' => $modelInfo['provider'] ?? null,
            'analyzed_at' => now(),
            'competency_scores' => $aiResult['competency_scores'] ?? [],
            'overall_score' => $aiResult['overall_score'] ?? 0,
            'behavior_analysis' => $aiResult['behavior_analysis'] ?? null,
            'red_flag_analysis' => $aiResult['red_flag_analysis'] ?? ['flags_detected' => false, 'flags' => []],
            'culture_fit' => $aiResult['culture_fit'] ?? null,
            'decision_snapshot' => $aiResult['decision_snapshot'] ?? null,
            'raw_ai_response' => $aiResult,
            'question_analyses' => $aiResult['question_analyses'] ?? [],
            'scoring_method' => 'ai',
            'latency_ms' => $latencyMs,
        ]);
        $analysis->save();

        return $analysis;
    }

    /**
     * Transform AI result into DecisionEngine-compatible output format.
     *
     * The pipeline (calibration, policy engine, company fit) expects:
     *   competency_scores: {code: int_score}  (flat map)
     *   risk_flags: [{code, severity, penalty, evidence}]
     *   final_score: int
     *   decision: HIRE|HOLD|REJECT
     *   decision_reason: string
     */
    private function transformToEngineResult(array $aiResult): array
    {
        // Flatten competency scores: {code: {score: X}} → {code: X}
        $flatScores = [];
        foreach (($aiResult['competency_scores'] ?? []) as $code => $data) {
            $flatScores[$code] = is_array($data) ? (int) ($data['score'] ?? 0) : (int) $data;
        }

        // Transform red flags
        $riskFlags = [];
        $redFlagPenalty = 0;
        $autoReject = false;
        $flags = $aiResult['red_flag_analysis']['flags'] ?? [];

        foreach ($flags as $flag) {
            $severity = $flag['severity'] ?? 'low';
            $penalty = match ($severity) {
                'critical' => 15,
                'high' => 8,
                'medium' => 4,
                'low' => 2,
                default => 0,
            };

            $riskFlags[] = [
                'code' => $flag['code'] ?? 'AI_FLAG',
                'severity' => $severity,
                'penalty' => $penalty,
                'evidence' => [$flag['detected_phrase'] ?? ''],
            ];

            $redFlagPenalty += $penalty;

            if ($severity === 'critical') {
                $autoReject = true;
            }
        }

        // Map AI decision to uppercase
        $recommendation = strtoupper($aiResult['decision_snapshot']['recommendation'] ?? 'HOLD');
        if (!in_array($recommendation, ['HIRE', 'HOLD', 'REJECT'])) {
            $recommendation = 'HOLD';
        }

        // Use AI's overall_score directly (0-100)
        $overallScore = (float) ($aiResult['overall_score'] ?? 0);
        $finalScore = max(0, min(100, (int) round($overallScore - $redFlagPenalty)));

        if ($autoReject) {
            $recommendation = 'REJECT';
            $finalScore = min($finalScore, 30);
        }

        // Build decision reason from AI
        $reasons = $aiResult['decision_snapshot']['reasons'] ?? [];
        $decisionReason = !empty($reasons) ? implode('; ', $reasons) : "AI analysis: {$recommendation}";

        // Calculate base score (average of all competency scores)
        $baseScore = count($flatScores) > 0
            ? array_sum($flatScores) / count($flatScores)
            : $overallScore;

        return [
            'competency_scores' => $flatScores,
            'base_score' => $baseScore,
            'risk_penalty' => 0,
            'risk_scores' => [],
            'red_flag_penalty' => $redFlagPenalty,
            'risk_flags' => $riskFlags,
            'auto_reject' => $autoReject,
            'final_score' => $finalScore,
            'skill_gate' => ['passed' => true],
            'decision' => $recommendation,
            'decision_reason' => $decisionReason,
            'scoring_method' => 'ai',
            // Pass through question_analyses for per-answer scoring
            'question_analyses' => $aiResult['question_analyses'] ?? [],
        ];
    }
}
