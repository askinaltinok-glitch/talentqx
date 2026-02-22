<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\LanguageAssessment;
use App\Services\Maritime\LanguageAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguageAssessmentController extends Controller
{
    public function __construct(
        private readonly LanguageAssessmentService $service,
    ) {}

    /**
     * GET /v1/octopus/admin/candidates/{candidateId}/language-assessment
     */
    public function show(string $candidateId): JsonResponse
    {
        $assessment = LanguageAssessment::forCandidate($candidateId);

        if (!$assessment) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No language assessment exists yet.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatAssessment($assessment),
        ]);
    }

    /**
     * POST /v1/octopus/admin/candidates/{candidateId}/language-assessment/start
     *
     * Returns the question set (idempotent — creates assessment record if needed).
     */
    public function start(string $candidateId): JsonResponse
    {
        try {
            $questions = $this->service->getQuestionSet($candidateId);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    /**
     * POST /v1/octopus/admin/candidates/{candidateId}/language-assessment/submit
     */
    public function submit(string $candidateId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'declared_level' => 'nullable|string|max:5',
            'answers' => 'required|array',
            'answers.*' => 'string|max:5',
            'writing_text' => 'nullable|string|max:2000',
        ]);

        $assessment = $this->service->submitTest(
            $candidateId,
            $data['declared_level'] ?? null,
            $data['answers'],
            $data['writing_text'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => $this->formatAssessment($assessment),
        ]);
    }

    /**
     * POST /v1/octopus/admin/candidates/{candidateId}/language-assessment/interview-verify
     */
    public function interviewVerify(string $candidateId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'rubric' => 'required|array',
            'rubric.clarity' => 'required|integer|min:0|max:5',
            'rubric.accuracy' => 'required|integer|min:0|max:5',
            'rubric.maritime_terms' => 'required|integer|min:0|max:5',
            'rubric.fluency' => 'required|integer|min:0|max:5',
            'answers' => 'nullable|array',
        ]);

        $assessment = $this->service->submitInterviewVerification(
            $candidateId,
            $data['rubric'],
            $data['answers'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => $this->formatAssessment($assessment),
        ]);
    }

    /**
     * POST /v1/octopus/admin/candidates/{candidateId}/language-assessment/lock
     */
    public function lock(string $candidateId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'locked_level' => 'required|string|in:A1,A2,B1,B2,C1,C2',
        ]);

        $assessment = $this->service->lockLevel(
            $candidateId,
            $data['locked_level'],
            $request->user()?->id,
        );

        return response()->json([
            'success' => true,
            'data' => $this->formatAssessment($assessment),
        ]);
    }

    private function formatAssessment(LanguageAssessment $a): array
    {
        return [
            'candidate_id' => $a->candidate_id,
            'assessment_language' => $a->assessment_language,
            'declared_level' => $a->declared_level,
            'declared_confidence' => $a->declared_confidence,
            'mcq_score' => $a->mcq_score,
            'mcq_total' => $a->mcq_total,
            'mcq_correct' => $a->mcq_correct,
            'writing_score' => $a->writing_score,
            'writing_rubric' => $a->writing_rubric,
            'interview_score' => $a->interview_score,
            'overall_score' => $a->overall_score,
            'estimated_level' => $a->estimated_level,
            'confidence' => $a->confidence,
            'locked_level' => $a->locked_level,
            'locked_by' => $a->locked_by,
            'locked_at' => $a->locked_at?->toIso8601String(),
            'signals' => $a->signals,
            'created_at' => $a->created_at?->toIso8601String(),
            'updated_at' => $a->updated_at?->toIso8601String(),
        ];
    }
}
