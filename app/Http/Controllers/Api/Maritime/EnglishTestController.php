<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Api\Maritime\Concerns\VerifiesCandidateToken;
use App\Http\Controllers\Controller;
use App\Models\LanguageAssessment;
use App\Models\PoolCandidate;
use App\Services\Maritime\CareerFeedbackService;
use App\Services\Maritime\LanguageAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnglishTestController extends Controller
{
    use VerifiesCandidateToken;

    public function __construct(
        private readonly LanguageAssessmentService $service,
    ) {}

    /**
     * POST /api/maritime/candidates/{id}/english-test/start?t=<token>
     *
     * Returns the role-based MCQ question set for the candidate.
     * Generates a ULID attempt_id that must be passed back on submit.
     */
    public function start(string $id, Request $request): JsonResponse
    {
        [$candidate, $error] = $this->resolveAndVerifyCandidate($id, $request);
        if ($error) {
            return $error;
        }

        if ($candidate->primary_industry !== PoolCandidate::INDUSTRY_MARITIME) {
            return response()->json(['success' => false, 'message' => 'This endpoint is for maritime candidates only.'], 422);
        }

        try {
            $questions = $this->service->getQuestionSet($id);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // Generate ULID attempt_id and persist on assessment
        $attemptId = (string) Str::ulid();
        $assessment = LanguageAssessment::where('candidate_id', $id)->first();
        if ($assessment) {
            $assessment->update([
                'attempt_id' => $attemptId,
                'attempt_started_at' => now(),
            ]);
        }

        // Enrich with role requirements from config
        $rank = $candidate->rank ?? null;
        $roleReqs = $rank ? config("maritime_language.role_english_requirements.{$rank}") : null;

        $questions['role_requirements'] = $roleReqs;
        $questions['role_profile'] = $roleReqs['profile'] ?? null;
        $questions['attempt_id'] = $attemptId;

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    /**
     * POST /api/maritime/candidates/{id}/english-test/submit?t=<token>
     *
     * Submit MCQ answers and optional writing response.
     * Requires attempt_id from start() to prevent replay attacks.
     * Uses lockForUpdate to ensure "first wins" on concurrent double-click.
     */
    public function submit(string $id, Request $request): JsonResponse
    {
        [$candidate, $error] = $this->resolveAndVerifyCandidate($id, $request);
        if ($error) {
            return $error;
        }

        if ($candidate->primary_industry !== PoolCandidate::INDUSTRY_MARITIME) {
            return response()->json(['success' => false, 'message' => 'This endpoint is for maritime candidates only.'], 422);
        }

        $data = $request->validate([
            'attempt_id' => 'required|string|max:64',
            'declared_level' => 'nullable|string|max:5',
            'answers' => 'required|array',
            'answers.*' => 'string|max:5',
            'writing_text' => 'nullable|string|max:2000',
        ]);

        // Transaction with lockForUpdate: first request wins, second gets 422
        return DB::transaction(function () use ($id, $candidate, $data) {
            $assessment = LanguageAssessment::where('candidate_id', $id)
                ->lockForUpdate()
                ->first();

            if (!$assessment || !$assessment->attempt_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired attempt. Please restart the test.',
                ], 422);
            }

            if (!hash_equals($assessment->attempt_id, $data['attempt_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired attempt. Please restart the test.',
                ], 422);
            }

            // Check attempt freshness (max 30 minutes)
            if ($assessment->attempt_started_at && $assessment->attempt_started_at->lt(now()->subMinutes(30))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test attempt has expired. Please restart.',
                ], 422);
            }

            // Clear attempt_id FIRST (one-time use — blocks concurrent requests)
            $assessment->update(['attempt_id' => null, 'attempt_started_at' => null]);

            try {
                $assessment = $this->service->submitTest(
                    $id,
                    $data['declared_level'] ?? $candidate->english_level_self ?? null,
                    $data['answers'],
                    $data['writing_text'] ?? null,
                );

                // Trigger vector recompute (async, fail-open)
                if (config('maritime.vector_v1')) {
                    try {
                        \App\Jobs\ComputeCandidateVectorJob::dispatch($id)
                            ->delay(now()->addSeconds(30));
                    } catch (\Throwable) {}
                }

                // Return career feedback (no raw scores for candidates)
                $feedback = app(CareerFeedbackService::class)->fromEnglishScore(
                    $assessment->overall_score ?? 0,
                    $assessment->estimated_level ?? 'A1',
                );

                return response()->json([
                    'success' => true,
                    'data' => [
                        'estimated_level' => $assessment->estimated_level,
                        'career_feedback' => $feedback,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::channel('single')->warning('EnglishTest::submit failed', [
                    'candidate_id' => $id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process English test submission.',
                ], 500);
            }
        });
    }
}
