<?php

namespace App\Services\ML;

use App\Models\FormInterview;
use App\Models\ModelFeature;
use App\Models\ModelPrediction;
use App\Models\ModelWeight;
use App\Models\PoolCandidate;

class ModelFeatureService
{
    public function __construct(
        protected ?MlScoringService $scoringService = null
    ) {
        $this->scoringService = $scoringService ?? app(MlScoringService::class);
    }
    /**
     * Upsert features for a completed interview.
     */
    public function upsertForInterview(FormInterview $interview): ModelFeature
    {
        // Calculate answers meta
        $answersMeta = $this->calculateAnswersMeta($interview);

        // Get source channel from pool candidate if linked
        $sourceChannel = null;
        $countryCode = null;
        if ($interview->pool_candidate_id) {
            $candidate = PoolCandidate::find($interview->pool_candidate_id);
            if ($candidate) {
                $sourceChannel = $candidate->source_channel;
                $countryCode = $candidate->country_code;
            }
        }

        // Extract meta for country if not from candidate
        if (!$countryCode && is_array($interview->meta)) {
            $countryCode = $interview->meta['country_code'] ?? null;
        }

        // Risk flags as array
        $riskFlags = $interview->risk_flags;
        if (is_string($riskFlags)) {
            $riskFlags = json_decode($riskFlags, true) ?: [];
        }

        return ModelFeature::updateOrCreate(
            ['form_interview_id' => $interview->id],
            [
                'industry_code' => $interview->industry_code ?? 'general',
                'position_code' => $interview->position_code,
                'language' => $interview->language ?? 'tr',
                'country_code' => $countryCode,
                'source_channel' => $sourceChannel,
                'competency_scores_json' => $interview->competency_scores,
                'risk_flags_json' => $riskFlags,
                'raw_final_score' => $interview->raw_final_score ?? $interview->final_score,
                'calibrated_score' => $interview->calibrated_score ?? $interview->final_score,
                'z_score' => $interview->z_score,
                'policy_decision' => $interview->decision,
                'policy_code' => $interview->policy_code,
                'template_json_sha256' => $interview->template_json_sha256,
                'answers_meta_json' => $answersMeta,
            ]
        );
    }

    /**
     * Calculate meta information about interview answers.
     */
    protected function calculateAnswersMeta(FormInterview $interview): array
    {
        $answers = $interview->answers()->get();

        if ($answers->isEmpty()) {
            return [
                'answer_count' => 0,
                'answers_with_text' => 0,
                'avg_answer_len' => 0,
                'min_len' => 0,
                'max_len' => 0,
                'rf_incomplete' => true,
                'rf_sparse' => true,
                'filled_slots' => 0,
                'total_slots' => 0,
            ];
        }

        $answersWithText = $answers->filter(fn($a) => !empty($a->answer) && strlen(trim($a->answer)) > 0);
        $lengths = $answersWithText->map(fn($a) => strlen($a->answer ?? ''));

        $totalSlots = $answers->count();
        $filledSlots = $answersWithText->count();

        // Determine structural flags
        $rfIncomplete = $filledSlots < ($totalSlots * 0.7); // Less than 70% filled
        $rfSparse = $lengths->count() > 0 && $lengths->avg() < 50; // Avg < 50 chars

        return [
            'answer_count' => $totalSlots,
            'answers_with_text' => $filledSlots,
            'avg_answer_len' => $lengths->count() > 0 ? round($lengths->avg(), 1) : 0,
            'min_len' => $lengths->count() > 0 ? $lengths->min() : 0,
            'max_len' => $lengths->count() > 0 ? $lengths->max() : 0,
            'rf_incomplete' => $rfIncomplete,
            'rf_sparse' => $rfSparse,
            'filled_slots' => $filledSlots,
            'total_slots' => $totalSlots,
        ];
    }

    /**
     * Batch process existing interviews to populate feature store.
     */
    public function backfillFeatures(int $limit = 1000, ?string $fromDate = null): int
    {
        $query = FormInterview::where('status', FormInterview::STATUS_COMPLETED)
            ->whereDoesntHave('modelFeature');

        if ($fromDate) {
            $query->where('completed_at', '>=', $fromDate);
        }

        $processed = 0;
        $query->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->chunk(100, function ($interviews) use (&$processed) {
                foreach ($interviews as $interview) {
                    $this->upsertForInterview($interview);
                    $processed++;
                }
            });

        return $processed;
    }

    /**
     * Update English assessment score for an interview.
     * Triggers re-prediction with updated features.
     */
    public function updateEnglishAssessment(
        string $formInterviewId,
        int $score,
        string $provider = 'manual',
        ?string $notes = null
    ): array {
        $feature = ModelFeature::where('form_interview_id', $formInterviewId)->first();

        if (!$feature) {
            $interview = FormInterview::find($formInterviewId);
            if (!$interview) {
                return ['success' => false, 'reason' => 'Interview not found'];
            }
            $feature = $this->upsertForInterview($interview);
        }

        // Update features with English score
        $feature->update([
            'english_score' => $score,
            'english_provider' => $provider,
        ]);

        // Trigger re-prediction
        $prediction = $this->triggerRePredict($formInterviewId, 'english_assessment_update');

        return [
            'success' => true,
            'feature_id' => $feature->id,
            'english_score' => $score,
            'new_prediction' => $prediction ? [
                'id' => $prediction->id,
                'predicted_score' => $prediction->predicted_outcome_score,
                'predicted_label' => $prediction->predicted_label,
                'model_version' => $prediction->model_version,
            ] : null,
        ];
    }

    /**
     * Attach video assessment to an interview.
     * Triggers re-prediction with updated features.
     */
    public function attachVideoAssessment(
        string $formInterviewId,
        string $videoUrl,
        string $provider = 'manual'
    ): array {
        $feature = ModelFeature::where('form_interview_id', $formInterviewId)->first();

        if (!$feature) {
            $interview = FormInterview::find($formInterviewId);
            if (!$interview) {
                return ['success' => false, 'reason' => 'Interview not found'];
            }
            $feature = $this->upsertForInterview($interview);
        }

        // Update features with video info
        $feature->update([
            'video_present' => true,
            'video_url' => $videoUrl,
            'video_provider' => $provider,
        ]);

        // Trigger re-prediction
        $prediction = $this->triggerRePredict($formInterviewId, 'video_attachment');

        return [
            'success' => true,
            'feature_id' => $feature->id,
            'video_url' => $videoUrl,
            'new_prediction' => $prediction ? [
                'id' => $prediction->id,
                'predicted_score' => $prediction->predicted_outcome_score,
                'predicted_label' => $prediction->predicted_label,
                'model_version' => $prediction->model_version,
            ] : null,
        ];
    }

    /**
     * Trigger a re-prediction after assessment update.
     * Creates a new ModelPrediction with type=post_assessment.
     */
    protected function triggerRePredict(string $formInterviewId, string $reason): ?ModelPrediction
    {
        $interview = FormInterview::find($formInterviewId);
        if (!$interview) {
            return null;
        }

        $feature = ModelFeature::where('form_interview_id', $formInterviewId)->first();
        if (!$feature) {
            return null;
        }

        $weights = ModelWeight::active() ?? ModelWeight::latest();
        if (!$weights) {
            return null;
        }

        // Calculate new prediction using MlScoringService
        $predictionData = $this->scoringService->calculatePrediction($interview, $feature, $weights);

        // Create post-assessment prediction
        return ModelPrediction::create([
            'form_interview_id' => $formInterviewId,
            'model_version' => $weights->model_version,
            'predicted_outcome_score' => $predictionData['predicted_score'],
            'predicted_label' => $predictionData['predicted_label'],
            'explain_json' => $predictionData['explain'],
            'prediction_type' => ModelPrediction::TYPE_POST_ASSESSMENT,
            'prediction_reason' => $reason,
            'created_at' => now(),
        ]);
    }

    /**
     * Get assessment status for an interview.
     */
    public function getAssessmentStatus(string $formInterviewId): array
    {
        $feature = ModelFeature::where('form_interview_id', $formInterviewId)->first();

        if (!$feature) {
            return [
                'has_features' => false,
                'english_assessment' => null,
                'video_assessment' => null,
            ];
        }

        return [
            'has_features' => true,
            'english_assessment' => [
                'completed' => $feature->english_score !== null,
                'score' => $feature->english_score,
                'provider' => $feature->english_provider,
            ],
            'video_assessment' => [
                'completed' => $feature->video_present,
                'url' => $feature->video_url,
                'provider' => $feature->video_provider,
            ],
        ];
    }
}
