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
use App\Services\Maritime\EnglishSpeakingScorer;
use App\Services\Maritime\QuestionBankAssembler;
use App\Services\Voice\VoiceBehavioralSignalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\AI\OpenAIProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CleanInterviewController extends Controller
{
    public function __construct(
        private FormInterviewService $interviewService,
        private BehavioralScoringService $scoringService,
        private QuestionSetResolver $questionSetResolver,
        private QuestionBankAssembler $questionBankAssembler,
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

        // Resolve locale and position
        $locale = $invitation->locale ?? $candidate->preferred_language ?? 'en';
        $positionCode = $invitation->meta['rank'] ?? $candidate->rank ?? '__generic__';
        $countryCode = $candidate->country_code ?? null;

        // ── Question Bank v1: 25 questions from assembler ──
        $useQuestionBank = config('maritime.question_bank_v1') && $positionCode !== '__generic__';
        $bankMeta = null;
        $questionSet = null;

        if ($useQuestionBank) {
            try {
                $bank = $this->questionBankAssembler->forRole($positionCode, $locale);
                $flatQuestions = $this->flattenQuestionBank($bank);
                $questions = $this->formatBankQuestions($flatQuestions);

                // Audit trail: find seeded question set if it exists
                $questionSet = InterviewQuestionSet::active()
                    ->where('code', 'role_question_bank_v1')
                    ->where('position_code', $positionCode)
                    ->where('locale', $locale)
                    ->first();

                $bankMeta = [
                    'question_bank_version' => $bank['version'],
                    'question_bank_role' => $positionCode,
                    'question_bank_department' => $bank['department'],
                    'english_min_level' => $bank['english_min_level'],
                    'total_behavioral' => 22,
                    'total_english_gate' => 3,
                    'question_id_dimension_map' => collect($flatQuestions)
                        ->filter(fn($q) => $q['block'] !== 'english_gate')
                        ->mapWithKeys(fn($q) => [$q['id'] => $q['dimension']])
                        ->toArray(),
                ];
            } catch (\Throwable $e) {
                Log::warning('CleanInterviewController::start: question bank failed, falling back to legacy', [
                    'role' => $positionCode, 'locale' => $locale, 'error' => $e->getMessage(),
                ]);
                $useQuestionBank = false;
            }
        }

        // ── Legacy path: 12 questions from QuestionSetResolver ──
        if (!$useQuestionBank) {
            $questionSet = $this->questionSetResolver->resolve($positionCode, $locale, $countryCode, 'maritime_clean_v1');

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
        }

        $totalQuestions = count($questions);
        $ip = $request->ip();

        // INVITED → STARTED: create new interview
        if ($invitation->isAccessible()) {
            return DB::transaction(function () use ($invitation, $candidate, $questionSet, $questions, $ip, $locale, $useQuestionBank, $bankMeta, $totalQuestions) {
                $invitation->markStarted($ip);

                $interview = FormInterview::create([
                    'pool_candidate_id' => $candidate->id,
                    'interview_invitation_id' => $invitation->id,
                    'type' => 'behavioral',
                    'version' => $useQuestionBank ? 'question_bank_v1' : 'clean_v1',
                    'language' => $locale,
                    'position_code' => '__behavioral__',
                    'template_position_code' => '__behavioral__',
                    'industry_code' => 'maritime',
                    'status' => FormInterview::STATUS_IN_PROGRESS,
                    'meta' => array_filter(array_merge([
                        'question_set_id' => $questionSet?->id,
                        'invitation_id' => $invitation->id,
                        'workflow' => $useQuestionBank ? 'question_bank_v1' : 'clean_v1',
                    ], (array) $bankMeta)),
                ]);

                $invitation->update(['form_interview_id' => $interview->id]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'interview_id' => $interview->id,
                        'questions' => $questions,
                        'total_questions' => $totalQuestions,
                        'expires_at' => $invitation->expires_at->toIso8601String(),
                        'existing_answers' => [],
                        'resumed' => false,
                        'voice_enabled' => config('interview.voice_enabled', false),
                        'question_bank' => $useQuestionBank,
                        'english_min_level' => $bankMeta['english_min_level'] ?? null,
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
                    'total_questions' => $totalQuestions,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                    'existing_answers' => $existingAnswers,
                    'resumed' => true,
                    'voice_enabled' => config('interview.voice_enabled', false),
                    'question_bank' => $useQuestionBank,
                    'english_min_level' => $bankMeta['english_min_level'] ?? null,
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
            'slot' => 'required|integer|min:1|max:25',
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

        // Guard: duplicate question_id at different slot
        $existingAtOtherSlot = $interview->answers()
            ->where('competency', $request->question_id)
            ->where('slot', '!=', $request->slot)
            ->exists();

        if ($existingAtOtherSlot) {
            return response()->json([
                'success' => false,
                'message' => 'This question has already been answered at a different slot.',
                'code' => 'DUPLICATE_QUESTION',
            ], 422);
        }

        // Guard: minimum answer length (10 chars = at least a short sentence)
        if (mb_strlen(trim($request->text)) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Answer is too short. Please provide a meaningful response.',
                'code' => 'ANSWER_TOO_SHORT',
            ], 422);
        }

        // Upsert answer (same slot = overwrite, which is fine for re-edits)
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
                'total' => $this->getExpectedQuestionCount($interview),
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

        // Guard: minimum answers required (count UNIQUE question IDs, not rows)
        $answers = $interview->answers()->get();
        $uniqueQuestionIds = $answers->pluck('competency')->unique()->count();
        $answeredCount = $answers->count();
        $isQuestionBank = ($interview->meta['workflow'] ?? null) === 'question_bank_v1';
        $englishGateEnabled = $isQuestionBank && config('maritime.english_gate.enabled');
        $minRequired = $isQuestionBank ? ($englishGateEnabled ? 25 : 22) : 12;

        if ($uniqueQuestionIds < $minRequired) {
            return response()->json([
                'success' => false,
                'message' => "Please answer all required questions. Minimum: {$minRequired}, unique answered: {$uniqueQuestionIds}.",
                'code' => 'INCOMPLETE_ANSWERS',
            ], 422);
        }

        // Guard: reject if too many empty/short answers (>= 30% of answers < 10 chars)
        $shortAnswers = $answers->filter(fn($a) => mb_strlen(trim($a->answer_text ?? '')) < 10)->count();
        if ($shortAnswers > 0 && ($shortAnswers / $answeredCount) >= 0.3) {
            return response()->json([
                'success' => false,
                'message' => "Too many short or empty answers ({$shortAnswers}/{$answeredCount}). Please provide meaningful responses.",
                'code' => 'LOW_QUALITY_ANSWERS',
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
            $categoryScores = $isQuestionBank
                ? $this->buildCategoryScoresFromBank($answers, $interview->meta['question_id_dimension_map'] ?? [])
                : $this->buildCategoryScores($answers);
            $profile = $this->scoringService->scoreStructuredInterview($interview, $categoryScores);

            // English gate: GPT rubric scoring → CEFR level → LanguageAssessment
            if ($isQuestionBank && config('maritime.english_gate.enabled')) {
                $englishAnswers = $answers->filter(fn($a) => $a->slot > 22);
                if ($englishAnswers->isNotEmpty()) {
                    try {
                        // Build transcript payload from answers + question bank prompts
                        $bankRole = $interview->meta['question_bank_role'] ?? 'oiler';
                        $bank = $this->questionBankAssembler->forRole($bankRole, 'en');
                        $prompts = $bank['blocks']['english_gate']['prompts'] ?? [];
                        $promptMap = collect($prompts)->keyBy('id')->toArray();

                        $transcriptPayload = $englishAnswers->map(fn($a) => [
                            'prompt_id'   => $a->competency,
                            'prompt_text' => $promptMap[$a->competency]['prompt'] ?? '',
                            'transcript'  => $a->answer_text ?? '',
                        ])->values()->toArray();

                        // GPT rubric scoring
                        $scorer = app(EnglishSpeakingScorer::class);
                        $rubricScores = $scorer->scoreTranscripts($transcriptPayload);

                        // CEFR scoring + LanguageAssessment store
                        $candidateId = $interview->pool_candidate_id;
                        $roleCode = $interview->meta['question_bank_role'] ?? 'oiler';
                        $cefrResult = $scorer->scoreAndStore($candidateId, $roleCode, $rubricScores);

                        // Store in interview meta
                        $interview->updateQuietly([
                            'meta' => array_merge($interview->meta ?? [], [
                                'english_gate_answered'   => true,
                                'english_gate_scored'     => true,
                                'english_gate_cefr'       => $cefrResult['estimated_level'] ?? null,
                                'english_gate_pass'       => $cefrResult['pass'] ?? null,
                                'english_gate_confidence' => $cefrResult['confidence'] ?? null,
                                'english_gate_answers'    => $englishAnswers->map(fn($a) => [
                                    'slot' => $a->slot,
                                    'question_id' => $a->competency,
                                    'transcript' => $a->answer_text,
                                ])->values()->toArray(),
                            ]),
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('English gate scoring failed (fail-open)', [
                            'interview_id' => $interview->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Fail-open: store that scoring was attempted but failed
                        $interview->updateQuietly([
                            'meta' => array_merge($interview->meta ?? [], [
                                'english_gate_answered' => true,
                                'english_gate_scored'   => false,
                                'english_gate_error'    => $e->getMessage(),
                            ]),
                        ]);
                    }
                }
            }

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

            // Compute voice behavioral signals (fail-open)
            if (config('maritime.voice_behavioral_signals_v1')) {
                try {
                    app(VoiceBehavioralSignalService::class)->compute($interview);
                } catch (\Throwable $e) {
                    Log::warning('Voice behavioral signal computation failed', [
                        'interview_id' => $interview->id,
                        'error' => $e->getMessage(),
                    ]);
                }
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

    /**
     * POST /api/v1/maritime/interview/voice
     *
     * Upload a voice recording for English gate questions (slots 23-25).
     * Transcribes via Whisper, stores transcript as answer text.
     */
    public function voice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invitation_token' => 'required|string',
            'slot'             => 'required|integer|min:23|max:25',
            'question_id'      => 'required|string|max:50',
            'audio'            => 'required|file|mimes:webm,ogg,mp3,wav,m4a|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $invitation = InterviewInvitation::findByTokenHash($request->invitation_token);
        if (!$invitation) {
            return response()->json(['success' => false, 'message' => 'Invalid invitation token.'], 403);
        }

        if ($invitation->isExpired()) {
            if ($invitation->status !== InterviewInvitation::STATUS_EXPIRED) {
                $invitation->markExpired();
            }
            return response()->json(['success' => false, 'message' => 'Invitation expired.', 'code' => 'invitation_expired'], 410);
        }

        if ($invitation->status !== InterviewInvitation::STATUS_STARTED) {
            return response()->json(['success' => false, 'message' => 'Interview not started.'], 400);
        }

        $interview = $invitation->interview;
        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Interview not found.'], 404);
        }

        $tempPath = null;
        try {
            // Store temp file
            $file = $request->file('audio');
            $tempPath = $file->store('voice-uploads');

            // Transcribe via Whisper
            $llm = app(OpenAIProvider::class);
            $result = $llm->transcribeAudio($tempPath, 'en');

            $transcript = $result['transcript'] ?? '';
            $duration = $result['duration'] ?? null;
            $confidence = $result['confidence'] ?? null;

            // Guard: empty or too-short transcript → fail (don't accept silence/noise)
            $trimmedTranscript = trim($transcript);
            if (mb_strlen($trimmedTranscript) < 5) {
                Log::info('CleanInterviewController::voice: transcript too short, rejecting', [
                    'interview_id' => $interview->id,
                    'slot' => $request->slot,
                    'transcript_length' => mb_strlen($trimmedTranscript),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Could not transcribe your answer. Please speak clearly and try again.',
                    'code' => 'TRANSCRIPT_TOO_SHORT',
                ], 422);
            }

            // Guard: duplicate question_id at different slot (voice)
            $existingAtOtherSlot = $interview->answers()
                ->where('competency', $request->question_id)
                ->where('slot', '!=', $request->slot)
                ->exists();
            if ($existingAtOtherSlot) {
                return response()->json([
                    'success' => false,
                    'message' => 'This question has already been answered at a different slot.',
                    'code' => 'DUPLICATE_QUESTION',
                ], 422);
            }

            // Upsert answer (transcript becomes the answer text)
            $interview->answers()->updateOrCreate(
                ['slot' => $request->slot],
                [
                    'competency' => $request->question_id,
                    'answer_text' => $trimmedTranscript,
                ]
            );

            // Store voice upload metadata in interview meta
            $meta = $interview->meta ?? [];
            $voiceUploads = $meta['voice_uploads'] ?? [];
            $voiceUploads[$request->slot] = [
                'slot'       => (int) $request->slot,
                'question_id' => $request->question_id,
                'duration'   => $duration,
                'confidence' => $confidence,
                'uploaded_at' => now()->toIso8601String(),
            ];
            $meta['voice_uploads'] = $voiceUploads;
            $interview->updateQuietly(['meta' => $meta]);

            return response()->json([
                'success' => true,
                'data' => [
                    'transcript' => $transcript,
                    'duration'   => $duration,
                    'confidence' => $confidence,
                    'slot'       => (int) $request->slot,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('CleanInterviewController::voice: transcription failed', [
                'interview_id' => $interview->id,
                'slot' => $request->slot,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Voice transcription failed. Please try again.',
            ], 500);
        } finally {
            // Clean up temp file
            if ($tempPath && Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
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

    // ── Question Bank v1 Helpers ──

    /**
     * Dimension-to-category mapping for question bank v1.
     * Maps all behavioral dimensions from CORE, ROLE_SPECIFIC, and DEPT_SAFETY
     * blocks to the 4 scoring categories used by BehavioralScoringService.
     */
    private const DIMENSION_CATEGORY_MAP = [
        // ── CORE dimensions ──
        'discipline'              => 'discipline_procedure',
        'rule_compliance'         => 'discipline_procedure',
        'accountability'          => 'discipline_procedure',
        'safety_mindset'          => 'discipline_procedure',
        'integrity'               => 'discipline_procedure',
        'stress_control'          => 'stress_crisis',
        'problem_solving'         => 'stress_crisis',
        'teamwork'                => 'team_compatibility',
        'communication'           => 'team_compatibility',
        'leadership_followership' => 'leadership_responsibility',
        'learning_agility'        => 'leadership_responsibility',
        'motivation_retention'    => 'leadership_responsibility',

        // ── ROLE_SPECIFIC dimension (generic key for all role questions) ──
        'role_specific'           => 'leadership_responsibility',

        // ── DEPT_SAFETY: deck ──
        'deck_operations'         => 'discipline_procedure',
        'deck_safety'             => 'discipline_procedure',
        'deck_compliance'         => 'discipline_procedure',
        'deck_ism'                => 'discipline_procedure',

        // ── DEPT_SAFETY: engine ──
        'engine_compliance'       => 'discipline_procedure',
        'engine_operations'       => 'discipline_procedure',
        'engine_safety'           => 'discipline_procedure',
        'engine_emergency'        => 'stress_crisis',

        // ── DEPT_SAFETY: galley ──
        'galley_inventory'        => 'discipline_procedure',
        'galley_food_safety'      => 'discipline_procedure',
        'galley_hygiene'          => 'discipline_procedure',
        'galley_fire_safety'      => 'stress_crisis',
    ];

    /**
     * Build category scores from question bank answers using dimension mapping.
     * Only processes behavioral answers (slots 1-22), excludes English gate.
     */
    private function buildCategoryScoresFromBank(mixed $answers, array $dimensionMap): array
    {
        $categories = [
            'discipline_procedure' => [],
            'stress_crisis' => [],
            'team_compatibility' => [],
            'leadership_responsibility' => [],
        ];

        foreach ($answers as $answer) {
            if ($answer->slot > 22) continue; // English gate — skip

            $questionId = $answer->competency;
            $dimension = $dimensionMap[$questionId] ?? null;
            $category = self::DIMENSION_CATEGORY_MAP[$dimension] ?? 'team_compatibility';

            $text = $answer->answer_text ?? '';
            $textLength = mb_strlen(trim($text));

            $score = match (true) {
                $textLength >= 300 => 5,
                $textLength >= 200 => 4,
                $textLength >= 100 => 3,
                $textLength >= 40 => 2,
                default => 1,
            };

            $categories[$category]["q{$answer->slot}"] = $score;
        }

        return $categories;
    }

    /**
     * Flatten question bank blocks into a sequential array with slot numbers.
     * Order: CORE(1-12) → ROLE(13-18) → DEPT(19-22) → ENGLISH(23-25)
     */
    private function flattenQuestionBank(array $bank): array
    {
        $questions = [];
        $slot = 1;

        foreach ($bank['blocks']['core'] as $q) {
            $questions[] = array_merge($q, ['slot' => $slot++]);
        }
        foreach ($bank['blocks']['role_specific'] as $q) {
            $questions[] = array_merge($q, ['slot' => $slot++]);
        }
        foreach ($bank['blocks']['dept_safety'] as $q) {
            $questions[] = array_merge($q, ['slot' => $slot++]);
        }
        foreach ($bank['blocks']['english_gate']['prompts'] as $q) {
            $questions[] = array_merge($q, ['slot' => $slot++]);
        }

        return $questions;
    }

    /**
     * Format question bank questions for API response.
     * Includes block and voice_only fields for frontend rendering.
     */
    private function formatBankQuestions(array $flatQuestions): array
    {
        return array_map(fn($q) => [
            'id' => $q['id'],
            'slot' => $q['slot'],
            'block' => $q['block'],
            'dimension' => $q['dimension'],
            'difficulty' => $q['difficulty'] ?? 1,
            'prompt' => $q['prompt'],
            'voice_only' => $q['block'] === 'english_gate',
            'max_seconds' => $q['max_seconds'] ?? null,
        ], $flatQuestions);
    }

    /**
     * Get expected total question count for an interview.
     */
    private function getExpectedQuestionCount(FormInterview $interview): int
    {
        return ($interview->meta['workflow'] ?? null) === 'question_bank_v1' ? 25 : 12;
    }
}
