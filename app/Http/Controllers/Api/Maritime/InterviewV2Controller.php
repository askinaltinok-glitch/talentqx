<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Api\Maritime\Concerns\VerifiesCandidateToken;
use App\Http\Controllers\Controller;
use App\Models\CandidateQuestionAttempt;
use App\Services\Behavioral\BehavioralScoringServiceV2;
use App\Services\Behavioral\QuestionSetResolver;
use App\Services\Maritime\CareerFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InterviewV2Controller extends Controller
{
    use VerifiesCandidateToken;

    public function __construct(
        private QuestionSetResolver $resolver,
        private BehavioralScoringServiceV2 $scoringService,
    ) {}

    /**
     * POST /api/maritime/candidates/{id}/interview-v2/start
     *
     * Resolves the best question set and creates an attempt.
     * Returns 12 questions in the candidate's locale.
     */
    public function start(string $id, Request $request): JsonResponse
    {
        if (!config('maritime.interview_engine_v2')) {
            return response()->json(['success' => false, 'message' => 'Interview Engine v2 is not enabled.'], 503);
        }

        [$candidate, $error] = $this->resolveAndVerifyCandidate($id, $request);
        if ($error) {
            return $error;
        }

        $positionCode = $candidate->source_meta['position'] ?? $candidate->rank ?? '__generic__';
        // Locale priority: request override → candidate.preferred_language → 'en'
        $locale = $request->input('locale') ?? $candidate->preferred_language ?? 'en';
        $countryCode = $candidate->source_meta['country'] ?? null;

        // Resolve question set
        $questionSet = $this->resolver->resolve($positionCode, $locale, $countryCode);

        if (!$questionSet) {
            return response()->json([
                'success' => false,
                'message' => 'No question set available for this configuration.',
            ], 404);
        }

        // Check for existing incomplete attempt
        $existing = CandidateQuestionAttempt::where('candidate_id', $candidate->id)
            ->where('question_set_id', $questionSet->id)
            ->whereNull('completed_at')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'data' => [
                    'attempt_id' => $existing->id,
                    'question_set_id' => $questionSet->id,
                    'questions' => $this->formatQuestions($questionSet->questions_json),
                    'existing_answers' => $existing->answers_json ?? [],
                    'resumed' => true,
                ],
            ]);
        }

        // Compute attempt number
        $attemptNo = CandidateQuestionAttempt::where('candidate_id', $candidate->id)
            ->where('question_set_id', $questionSet->id)
            ->count() + 1;

        // Create new attempt
        $attempt = CandidateQuestionAttempt::create([
            'candidate_id' => $candidate->id,
            'question_set_id' => $questionSet->id,
            'attempt_no' => $attemptNo,
            'started_at' => now(),
            'selection_snapshot_json' => [
                'position_code' => $positionCode,
                'country_code' => $countryCode,
                'locale' => $locale,
                'vessel_type' => $candidate->source_meta['vessel_type'] ?? null,
            ],
            'answers_json' => [],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attempt->id,
                'question_set_id' => $questionSet->id,
                'questions' => $this->formatQuestions($questionSet->questions_json),
                'existing_answers' => [],
                'resumed' => false,
            ],
        ]);
    }

    /**
     * POST /api/maritime/candidates/{id}/interview-v2/answer
     *
     * Saves a single answer to an in-progress attempt.
     */
    public function answer(string $id, Request $request): JsonResponse
    {
        if (!config('maritime.interview_engine_v2')) {
            return response()->json(['success' => false, 'message' => 'Interview Engine v2 is not enabled.'], 503);
        }

        [$candidate, $error] = $this->resolveAndVerifyCandidate($id, $request);
        if ($error) {
            return $error;
        }

        $validator = Validator::make($request->all(), [
            'attempt_id' => 'required|uuid',
            'question_id' => 'required|string|max:32',
            'answer_text' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $attempt = CandidateQuestionAttempt::where('id', $request->attempt_id)
            ->where('candidate_id', $candidate->id)
            ->whereNull('completed_at')
            ->first();

        if (!$attempt) {
            return response()->json(['success' => false, 'message' => 'Attempt not found or already completed.'], 404);
        }

        // Append or update answer
        $answers = $attempt->answers_json ?? [];
        $found = false;
        foreach ($answers as &$a) {
            if ($a['question_id'] === $request->question_id) {
                $a['answer_text'] = $request->answer_text;
                $a['answered_at'] = now()->toIso8601String();
                $found = true;
                break;
            }
        }
        unset($a);

        if (!$found) {
            $answers[] = [
                'question_id' => $request->question_id,
                'answer_text' => $request->answer_text,
                'answered_at' => now()->toIso8601String(),
            ];
        }

        $attempt->update(['answers_json' => $answers]);

        return response()->json([
            'success' => true,
            'data' => [
                'answered' => count($answers),
                'total' => 12,
            ],
        ]);
    }

    /**
     * POST /api/maritime/candidates/{id}/interview-v2/complete
     *
     * Finalizes the attempt: scores, computes dimensions, recomputes vector.
     */
    public function complete(string $id, Request $request): JsonResponse
    {
        if (!config('maritime.interview_engine_v2')) {
            return response()->json(['success' => false, 'message' => 'Interview Engine v2 is not enabled.'], 503);
        }

        [$candidate, $error] = $this->resolveAndVerifyCandidate($id, $request);
        if ($error) {
            return $error;
        }

        $validator = Validator::make($request->all(), [
            'attempt_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $attempt = CandidateQuestionAttempt::where('id', $request->attempt_id)
            ->where('candidate_id', $candidate->id)
            ->whereNull('completed_at')
            ->first();

        if (!$attempt) {
            return response()->json(['success' => false, 'message' => 'Attempt not found or already completed.'], 404);
        }

        // Mark complete
        $attempt->update(['completed_at' => now()]);

        // Score (stored internally)
        $scorePayload = $this->scoringService->score($attempt);

        // Return career feedback (no raw scores for candidates)
        $validScores = array_filter($scorePayload['dimension_scores'] ?? [], fn($s) => $s !== null);
        $feedback = !empty($validScores)
            ? app(CareerFeedbackService::class)->fromDimensionScores($validScores, $scorePayload['confidence'] ?? null)
            : ['strengths' => [], 'development_areas' => [], 'role_fit_suggestions' => []];

        return response()->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attempt->id,
                'status' => 'completed',
                'career_feedback' => $feedback,
            ],
        ]);
    }

    /**
     * Strip rubric from questions for candidate-facing response.
     */
    private function formatQuestions(array $questions): array
    {
        return array_map(fn($q) => [
            'id' => $q['id'],
            'dimension' => $q['dimension'],
            'difficulty' => $q['difficulty'],
            'prompt' => $q['prompt'],
        ], $questions);
    }
}
