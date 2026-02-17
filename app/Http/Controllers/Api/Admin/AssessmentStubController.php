<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormInterview;
use App\Models\ModelPrediction;
use App\Services\ML\ModelFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssessmentStubController extends Controller
{
    public function __construct(
        protected ModelFeatureService $featureService
    ) {
    }

    /**
     * Complete English assessment for an interview.
     *
     * POST /v1/admin/form-interviews/{id}/english-assessment/complete
     */
    public function completeEnglishAssessment(Request $request, string $id): JsonResponse
    {
        $interview = FormInterview::findOrFail($id);

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'provider' => ['nullable', 'string', 'in:manual,ai'],
            'meta' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Update interview record
        $interview->update([
            'english_assessment_status' => 'completed',
            'english_assessment_score' => $data['score'],
        ]);

        // Store meta in interview meta if provided
        if (!empty($data['meta']) || !empty($data['provider']) || !empty($data['notes'])) {
            $meta = $interview->meta ?? [];
            $meta['english_assessment'] = [
                'provider' => $data['provider'] ?? 'manual',
                'completed_at' => now()->toIso8601String(),
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? [],
            ];
            $interview->update(['meta' => $meta]);
        }

        // Update ModelFeature and trigger re-prediction
        $featureResult = null;
        try {
            $featureResult = $this->featureService->updateEnglishAssessment(
                $interview->id,
                $data['score'],
                $data['provider'] ?? 'manual',
                $data['notes'] ?? null
            );

            Log::channel('single')->info('English assessment completed with re-prediction', [
                'interview_id' => $interview->id,
                'score' => $data['score'],
                'new_prediction' => $featureResult['new_prediction'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('single')->warning('English assessment feature update failed', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'English assessment completed',
            'data' => [
                'interview_id' => $interview->id,
                'english_assessment_status' => $interview->english_assessment_status,
                'english_assessment_score' => $interview->english_assessment_score,
                'prediction' => $featureResult['new_prediction'] ?? null,
            ],
        ]);
    }

    /**
     * Attach video assessment to an interview.
     *
     * POST /v1/admin/form-interviews/{id}/video/attach
     */
    public function attachVideo(Request $request, string $id): JsonResponse
    {
        $interview = FormInterview::findOrFail($id);

        $data = $request->validate([
            'url' => ['required', 'url', 'max:2000'],
            'provider' => ['nullable', 'string', 'max:64'],
            'meta' => ['nullable', 'array'],
        ]);

        // Update interview record
        $interview->update([
            'video_assessment_status' => 'pending',
            'video_assessment_url' => $data['url'],
        ]);

        // Store meta in interview meta
        $meta = $interview->meta ?? [];
        $meta['video_assessment'] = [
            'provider' => $data['provider'] ?? 'unknown',
            'attached_at' => now()->toIso8601String(),
            'meta' => $data['meta'] ?? [],
        ];
        $interview->update(['meta' => $meta]);

        // Update ModelFeature and trigger re-prediction
        $featureResult = null;
        try {
            $featureResult = $this->featureService->attachVideoAssessment(
                $interview->id,
                $data['url'],
                $data['provider'] ?? 'manual'
            );

            Log::channel('single')->info('Video attached with re-prediction', [
                'interview_id' => $interview->id,
                'new_prediction' => $featureResult['new_prediction'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('single')->warning('Video attachment feature update failed', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Video attached',
            'data' => [
                'interview_id' => $interview->id,
                'video_assessment_status' => $interview->video_assessment_status,
                'video_assessment_url' => $interview->video_assessment_url,
                'prediction' => $featureResult['new_prediction'] ?? null,
            ],
        ]);
    }

    /**
     * Complete video assessment for an interview.
     *
     * POST /v1/admin/form-interviews/{id}/video/complete
     */
    public function completeVideoAssessment(Request $request, string $id): JsonResponse
    {
        $interview = FormInterview::findOrFail($id);

        $data = $request->validate([
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'meta' => ['nullable', 'array'],
        ]);

        $interview->update([
            'video_assessment_status' => 'completed',
        ]);

        // Store completion meta
        $meta = $interview->meta ?? [];
        $meta['video_assessment']['completed_at'] = now()->toIso8601String();
        $meta['video_assessment']['score'] = $data['score'] ?? null;
        $meta['video_assessment']['notes'] = $data['notes'] ?? null;
        if (!empty($data['meta'])) {
            $meta['video_assessment']['completion_meta'] = $data['meta'];
        }
        $interview->update(['meta' => $meta]);

        return response()->json([
            'success' => true,
            'message' => 'Video assessment completed',
            'data' => [
                'interview_id' => $interview->id,
                'video_assessment_status' => $interview->video_assessment_status,
            ],
        ]);
    }

    /**
     * Get assessment status and prediction info.
     *
     * GET /v1/admin/form-interviews/{id}/assessment-status
     */
    public function assessmentStatus(string $id): JsonResponse
    {
        $interview = FormInterview::findOrFail($id);

        // Get assessment status from feature service
        $assessmentStatus = $this->featureService->getAssessmentStatus($interview->id);

        // Get latest prediction
        $latestPrediction = ModelPrediction::where('form_interview_id', $interview->id)
            ->orderByDesc('created_at')
            ->first();

        // Get post-assessment prediction if exists
        $postAssessmentPrediction = ModelPrediction::where('form_interview_id', $interview->id)
            ->where('prediction_type', ModelPrediction::TYPE_POST_ASSESSMENT)
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'interview_id' => $interview->id,
                'english_assessment' => [
                    'status' => $interview->english_assessment_status,
                    'score' => $interview->english_assessment_score,
                    'in_features' => $assessmentStatus['english_assessment']['completed'] ?? false,
                ],
                'video_assessment' => [
                    'status' => $interview->video_assessment_status,
                    'url' => $interview->video_assessment_url,
                    'in_features' => $assessmentStatus['video_assessment']['completed'] ?? false,
                ],
                'prediction' => [
                    'latest' => $latestPrediction ? [
                        'id' => $latestPrediction->id,
                        'type' => $latestPrediction->prediction_type ?? 'baseline',
                        'predicted_score' => $latestPrediction->predicted_outcome_score,
                        'predicted_label' => $latestPrediction->predicted_label,
                        'model_version' => $latestPrediction->model_version,
                        'created_at' => $latestPrediction->created_at,
                    ] : null,
                    'post_assessment' => $postAssessmentPrediction ? [
                        'id' => $postAssessmentPrediction->id,
                        'predicted_score' => $postAssessmentPrediction->predicted_outcome_score,
                        'predicted_label' => $postAssessmentPrediction->predicted_label,
                        'model_version' => $postAssessmentPrediction->model_version,
                        'reason' => $postAssessmentPrediction->prediction_reason,
                        'created_at' => $postAssessmentPrediction->created_at,
                    ] : null,
                ],
            ],
        ]);
    }
}
