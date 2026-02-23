<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\FormInterview;
use App\Models\InterviewInvitation;
use App\Models\InterviewQuestionSet;
use App\Services\Behavioral\BehavioralScoringService;
use App\Services\Behavioral\QuestionSetResolver;
use App\Services\Interview\FormInterviewService;
use App\Services\Maritime\CareerFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CleanInterviewController extends Controller
{
    public function __construct(
        private FormInterviewService $interviewService,
        private BehavioralScoringService $scoringService,
        private QuestionSetResolver $questionSetResolver,
    ) {}

    /**
     * POST /api/v1/maritime/interview/start?invitation_token=xxx
     *
     * Validates the invitation token, creates/resumes a behavioral interview,
     * and returns the 12 questions.
     */
    public function start(Request $request): JsonResponse
    {
        $token = $request->input('invitation_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Missing invitation_token.'], 400);
        }

        $invitation = InterviewInvitation::findByTokenHash($token);
        if (!$invitation) {
            return response()->json(['success' => false, 'message' => 'Invalid invitation token.'], 403);
        }

        // Real-time expiry check
        if ($invitation->isExpired()) {
            if ($invitation->status !== InterviewInvitation::STATUS_EXPIRED) {
                $invitation->markExpired();
            }
            return response()->json([
                'success' => false,
                'message' => 'This invitation has expired.',
                'code' => 'invitation_expired',
            ], 410);
        }

        // Already completed
        if ($invitation->status === InterviewInvitation::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'This assessment has already been completed.',
                'code' => 'already_completed',
            ], 409);
        }

        $candidate = $invitation->candidate;
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found.'], 404);
        }

        // Guard: candidate already has a completed behavioral interview
        $existingInterview = FormInterview::where('pool_candidate_id', $candidate->id)
            ->where('type', 'behavioral')
            ->where('status', FormInterview::STATUS_COMPLETED)
            ->first();

        if ($existingInterview) {
            Log::warning('CleanInterviewController::start: candidate already has completed behavioral interview', [
                'candidate_id' => $candidate->id,
                'existing_interview_id' => $existingInterview->id,
                'invitation_id' => $invitation->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'This candidate has already completed a behavioral assessment.',
                'code' => 'behavioral_already_completed',
                'existing_interview_id' => $existingInterview->id,
            ], 409);
        }

        // Load questions from InterviewQuestionSet
        $locale = $invitation->locale ?? $candidate->preferred_language ?? 'en';
        $positionCode = $invitation->meta['rank'] ?? $candidate->rank ?? '__generic__';
        $countryCode = $candidate->country_code ?? null;

        $questionSet = $this->questionSetResolver->resolve($positionCode, $locale, $countryCode, 'maritime_clean_v1');

        // Fallback: try generic if position-specific not found
        if (!$questionSet && $positionCode !== '__generic__') {
            $questionSet = $this->questionSetResolver->resolve('__generic__', $locale, $countryCode, 'maritime_clean_v1');
        }

        if (!$questionSet) {
            Log::error('CleanInterviewController::start: no question set found', [
                'invitation_id' => $invitation->id,
                'locale' => $locale,
                'position_code' => $positionCode,
            ]);
            return response()->json(['success' => false, 'message' => 'No questions available.'], 500);
        }

        $questions = $this->formatQuestions($questionSet->questions_json ?? []);
        $ip = $request->ip();

        // INVITED → STARTED: create new interview
        if ($invitation->isAccessible()) {
            return DB::transaction(function () use ($invitation, $candidate, $questionSet, $questions, $ip, $locale) {
                $invitation->markStarted($ip);

                $interview = FormInterview::create([
                    'pool_candidate_id' => $candidate->id,
                    'interview_invitation_id' => $invitation->id,
                    'type' => 'behavioral',
                    'version' => 'clean_v1',
                    'language' => $locale,
                    'position_code' => '__behavioral__',
                    'template_position_code' => '__behavioral__',
                    'industry_code' => 'maritime',
                    'status' => FormInterview::STATUS_IN_PROGRESS,
                    'meta' => [
                        'question_set_id' => $questionSet->id,
                        'invitation_id' => $invitation->id,
                        'workflow' => 'clean_v1',
                    ],
                ]);

                $invitation->update(['form_interview_id' => $interview->id]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'interview_id' => $interview->id,
                        'questions' => $questions,
                        'total_questions' => count($questions),
                        'expires_at' => $invitation->expires_at->toIso8601String(),
                        'existing_answers' => [],
                        'resumed' => false,
                        'voice_enabled' => config('interview.voice_enabled', false),
                    ],
                ]);
            });
        }

        // STARTED → resume existing interview
        if ($invitation->canResume()) {
            $invitation->increment('access_count');

            $interview = $invitation->interview;
            if (!$interview) {
                Log::error('CleanInterviewController::start: interview missing for started invitation', [
                    'invitation_id' => $invitation->id,
                ]);
                return response()->json(['success' => false, 'message' => 'Interview not found.'], 500);
            }

            $existingAnswers = $interview->answers()
                ->orderBy('slot')
                ->get()
                ->map(fn ($a) => [
                    'slot' => $a->slot,
                    'question_id' => $a->competency,
                    'text' => $a->answer_text,
                ])
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'interview_id' => $interview->id,
                    'questions' => $questions,
                    'total_questions' => count($questions),
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                    'existing_answers' => $existingAnswers,
                    'resumed' => true,
                    'voice_enabled' => config('interview.voice_enabled', false),
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invitation is not in a valid state.'], 400);
    }

    /**
     * POST /api/v1/maritime/interview/answer
     *
     * Saves a single answer to the in-progress interview.
     */
    public function answer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invitation_token' => 'required|string',
            'slot' => 'required|integer|min:1|max:12',
            'question_id' => 'required|string|max:50',
            'text' => 'required|string|min:1|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $invitation = InterviewInvitation::findByTokenHash($request->invitation_token);
        if (!$invitation) {
            return response()->json(['success' => false, 'message' => 'Invalid invitation token.'], 403);
        }

        // Check expiry
        if ($invitation->isExpired()) {
            if ($invitation->status !== InterviewInvitation::STATUS_EXPIRED) {
                $invitation->markExpired();
            }
            return response()->json([
                'success' => false,
                'message' => 'This invitation has expired.',
                'code' => 'invitation_expired',
            ], 410);
        }

        if ($invitation->status !== InterviewInvitation::STATUS_STARTED) {
            return response()->json(['success' => false, 'message' => 'Interview not started.'], 400);
        }

        $interview = $invitation->interview;
        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Interview not found.'], 404);
        }

        // Upsert answer
        $interview->answers()->updateOrCreate(
            ['slot' => $request->slot],
            [
                'competency' => $request->question_id,
                'answer_text' => $request->text,
            ]
        );

        $answeredCount = $interview->answers()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'answered' => $answeredCount,
                'total' => 12,
            ],
        ]);
    }

    /**
     * POST /api/v1/maritime/interview/complete
     *
     * Finalizes the behavioral interview and triggers scoring.
     */
    public function complete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invitation_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $invitation = InterviewInvitation::findByTokenHash($request->invitation_token);
        if (!$invitation) {
            return response()->json(['success' => false, 'message' => 'Invalid invitation token.'], 403);
        }

        // Check expiry
        if ($invitation->isExpired()) {
            if ($invitation->status !== InterviewInvitation::STATUS_EXPIRED) {
                $invitation->markExpired();
            }
            return response()->json([
                'success' => false,
                'message' => 'This invitation has expired.',
                'code' => 'invitation_expired',
            ], 410);
        }

        if ($invitation->status !== InterviewInvitation::STATUS_STARTED) {
            return response()->json(['success' => false, 'message' => 'Interview not started.'], 400);
        }

        $interview = $invitation->interview;
        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Interview not found.'], 404);
        }

        // Guard: all 12 answers required
        $answeredCount = $interview->answers()->count();
        if ($answeredCount < 12) {
            return response()->json([
                'success' => false,
                'message' => "Please answer all 12 questions. Currently answered: {$answeredCount}.",
            ], 422);
        }

        // Guard: don't overwrite existing FINAL behavioral profile
        $existingProfile = \App\Models\BehavioralProfile::where('candidate_id', $interview->pool_candidate_id)
            ->where('version', 'v1')
            ->where('status', \App\Models\BehavioralProfile::STATUS_FINAL)
            ->first();

        if ($existingProfile && $existingProfile->interview_id !== $interview->id) {
            Log::warning('CleanInterviewController::complete: skipping scoring — FINAL profile exists from different interview', [
                'candidate_id' => $interview->pool_candidate_id,
                'interview_id' => $interview->id,
                'existing_profile_id' => $existingProfile->id,
                'existing_interview_id' => $existingProfile->interview_id,
            ]);

            // Still mark interview + invitation as completed, but don't score
            $interview->updateQuietly([
                'status' => FormInterview::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            $invitation->markCompleted();

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                    'scoring_skipped' => true,
                    'reason' => 'Existing behavioral profile preserved.',
                ],
            ]);
        }

        try {
            // Score via behavioral scoring service
            $answers = $interview->answers()->orderBy('slot')->get();
            $categoryScores = $this->buildCategoryScores($answers);
            $profile = $this->scoringService->scoreStructuredInterview($interview, $categoryScores);

            // Mark interview completed
            $interview->updateQuietly([
                'status' => FormInterview::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            // Mark invitation completed
            $invitation->markCompleted();

            // Trigger vector recompute
            if (config('maritime.vector_v1')) {
                try {
                    \App\Jobs\ComputeCandidateVectorJob::dispatch($invitation->pool_candidate_id)
                        ->delay(now()->addSeconds(30));
                } catch (\Throwable) {}
            }

            // Return career feedback (no raw scores)
            $feedback = $profile?->dimensions_json
                ? app(CareerFeedbackService::class)->fromDimensionScores(
                    collect($profile->dimensions_json)->mapWithKeys(fn ($v, $k) => [$k => (float) ($v['score'] ?? $v)])->toArray(),
                    $profile->confidence ?? null,
                )
                : ['strengths' => [], 'development_areas' => [], 'role_fit_suggestions' => []];

            Log::info('CleanInterviewController::complete: interview completed', [
                'invitation_id' => $invitation->id,
                'interview_id' => $interview->id,
                'candidate_id' => $invitation->pool_candidate_id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                    'career_feedback' => $feedback,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('CleanInterviewController::complete: scoring failed (fail-open)', [
                'invitation_id' => $invitation->id,
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);

            // Still mark complete (fail-open)
            $interview->updateQuietly([
                'status' => FormInterview::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            $invitation->markCompleted();

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                    'scoring_deferred' => true,
                ],
            ]);
        }
    }

    // ── Private Helpers ──

    private function formatQuestions(array $questions): array
    {
        return array_map(fn ($q) => [
            'id' => $q['id'],
            'dimension' => $q['dimension'],
            'difficulty' => $q['difficulty'] ?? 1,
            'prompt' => $q['prompt'],
        ], $questions);
    }

    private function buildCategoryScores(mixed $answers): array
    {
        $categories = [
            'discipline_procedure' => [],
            'stress_crisis' => [],
            'team_compatibility' => [],
            'leadership_responsibility' => [],
        ];

        foreach ($answers as $answer) {
            $slot = $answer->slot;
            $text = $answer->answer_text ?? '';
            $textLength = mb_strlen(trim($text));

            $score = match (true) {
                $textLength >= 300 => 5,
                $textLength >= 200 => 4,
                $textLength >= 100 => 3,
                $textLength >= 40 => 2,
                default => 1,
            };

            if ($slot >= 1 && $slot <= 3) {
                $categories['discipline_procedure']["q{$slot}"] = $score;
            } elseif ($slot >= 4 && $slot <= 6) {
                $categories['stress_crisis']["q{$slot}"] = $score;
            } elseif ($slot >= 7 && $slot <= 9) {
                $categories['team_compatibility']["q{$slot}"] = $score;
            } elseif ($slot >= 10 && $slot <= 12) {
                $categories['leadership_responsibility']["q{$slot}"] = $score;
            }
        }

        return $categories;
    }
}
