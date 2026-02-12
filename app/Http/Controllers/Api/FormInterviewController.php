<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormInterview;
use App\Services\Interview\FormInterviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormInterviewController extends Controller
{
    public function __construct(
        private readonly FormInterviewService $service
    ) {}

    /**
     * Create a new form interview session
     * POST /v1/form-interviews
     */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:32'],
            'language' => ['required', 'string', 'max:8'],
            'position_code' => ['nullable', 'string', 'max:128'],
            'meta' => ['nullable', 'array'],
        ]);

        $interview = $this->service->create(
            $data['version'],
            $data['language'],
            $data['position_code'] ?? '__generic__',
            $data['meta'] ?? []
        );

        return response()->json([
            'id' => $interview->id,
            'status' => $interview->status,
            'version' => $interview->version,
            'language' => $interview->language,
            'position_code' => $interview->position_code,
            'template_position_code' => $interview->template_position_code,
            'template_json_sha256' => $interview->template_json_sha256,
            'created_at' => $interview->created_at,
        ], 201);
    }

    /**
     * Get a form interview by ID
     * GET /v1/form-interviews/{id}
     */
    public function show(string $id): JsonResponse
    {
        $interview = FormInterview::with('answers')->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $interview->id,
                'status' => $interview->status,
                'version' => $interview->version,
                'language' => $interview->language,
                'position_code' => $interview->position_code,
                'template_position_code' => $interview->template_position_code,
                'template_json' => $interview->template_json,
                'meta' => $interview->meta,
                'answers' => $interview->answers->map(fn($a) => [
                    'slot' => $a->slot,
                    'competency' => $a->competency,
                    'answer_text' => $a->answer_text,
                ]),
                'competency_scores' => $interview->competency_scores,
                'risk_flags' => $interview->risk_flags,
                'final_score' => $interview->final_score,
                'decision' => $interview->decision,
                'decision_reason' => $interview->decision_reason,
                'completed_at' => $interview->completed_at,
                'created_at' => $interview->created_at,
                'updated_at' => $interview->updated_at,
            ],
        ]);
    }

    /**
     * Add or update answers for a form interview
     * POST /v1/form-interviews/{id}/answers
     */
    public function addAnswers(Request $request, string $id): JsonResponse
    {
        $interview = FormInterview::findOrFail($id);

        if ($interview->isCompleted()) {
            return response()->json([
                'error' => 'Interview already completed',
                'message' => 'Cannot add answers to a completed interview',
            ], 400);
        }

        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.slot' => ['required', 'integer', 'min:1', 'max:8'],
            'answers.*.competency' => ['required', 'string', 'max:64'],
            'answers.*.answer_text' => ['required', 'string', 'min:1'],
        ]);

        $this->service->upsertAnswers($interview, $data['answers']);

        $interview->refresh();

        return response()->json([
            'ok' => true,
            'interview_id' => $interview->id,
            'answers_count' => $interview->answers()->count(),
        ]);
    }

    /**
     * Complete the interview and calculate scores
     * POST /v1/form-interviews/{id}/complete
     */
    public function complete(string $id): JsonResponse
    {
        $interview = FormInterview::with('answers')->findOrFail($id);

        if ($interview->isCompleted()) {
            return response()->json([
                'error' => 'Interview already completed',
                'message' => 'This interview has already been completed',
            ], 400);
        }

        $scored = $this->service->completeAndScore($interview);

        return response()->json([
            'id' => $scored->id,
            'status' => $scored->status,
            'final_score' => $scored->final_score,
            'decision' => $scored->decision,
            'decision_reason' => $scored->decision_reason,
            'competency_scores' => $scored->competency_scores,
            'risk_flags' => $scored->risk_flags,
            'completed_at' => $scored->completed_at,
        ]);
    }

    /**
     * Get the score/decision for a completed interview
     * GET /v1/form-interviews/{id}/score
     */
    public function score(string $id): JsonResponse
    {
        $interview = FormInterview::findOrFail($id);

        if (!$interview->isCompleted()) {
            return response()->json([
                'error' => 'Interview not completed',
                'message' => 'Please complete the interview first',
            ], 400);
        }

        return response()->json([
            'data' => [
                'id' => $interview->id,
                'status' => $interview->status,
                'final_score' => $interview->final_score,
                'decision' => $interview->decision,
                'decision_reason' => $interview->decision_reason,
                'competency_scores' => $interview->competency_scores,
                'risk_flags' => $interview->risk_flags,
                'completed_at' => $interview->completed_at,
            ],
        ]);
    }
}
