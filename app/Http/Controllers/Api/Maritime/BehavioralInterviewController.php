<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\BehavioralProfile;
use App\Models\FormInterview;
use App\Models\InterviewTemplate;
use App\Models\PoolCandidate;
use App\Services\Behavioral\BehavioralScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BehavioralInterviewController extends Controller
{
    public function __construct(
        private BehavioralScoringService $scoringService,
    ) {}

    /**
     * GET /api/maritime/candidates/{id}/behavioral/template
     *
     * Returns the 12 behavioral questions in the candidate's locale.
     */
    public function template(string $id): JsonResponse
    {
        $candidate = PoolCandidate::find($id);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found.'], 404);
        }

        $locale = $candidate->preferred_language ?? 'en';

        // Try candidate's locale first, fall back to EN
        $template = InterviewTemplate::where('type', 'behavioral')
            ->where('position_code', '__behavioral__')
            ->where('language', $locale)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            $template = InterviewTemplate::where('type', 'behavioral')
                ->where('position_code', '__behavioral__')
                ->where('language', 'en')
                ->where('is_active', true)
                ->first();
        }

        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Behavioral template not found.'], 404);
        }

        $decoded = $template->template;

        // Load existing answers if any
        $existingAnswers = $this->loadExistingAnswers($candidate->id);

        return response()->json([
            'success' => true,
            'data' => [
                'template_id' => $template->id,
                'language' => $decoded['language'] ?? $locale,
                'version' => $decoded['version'] ?? 'v1',
                'categories' => $decoded['categories'] ?? [],
                'scoring' => [
                    'scale' => $decoded['scoring']['scale'] ?? ['min' => 1, 'max' => 5],
                ],
                'existing_answers' => $existingAnswers,
                'total_questions' => 12,
            ],
        ]);
    }

    /**
     * POST /api/maritime/candidates/{id}/behavioral/answers
     *
     * Submit/upsert answers for behavioral questions.
     * Supports progressive saving (one or multiple answers at a time).
     */
    public function answers(string $id, Request $request): JsonResponse
    {
        $candidate = PoolCandidate::find($id);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'answers' => 'required|array|min:1',
            'answers.*.slot' => 'required|integer|min:1|max:12',
            'answers.*.question_id' => 'required|string|max:50',
            'answers.*.text' => 'required|string|min:1|max:5000',
            'answers.*.category' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $interview = $this->getOrCreateBehavioralInterview($candidate);

        DB::transaction(function () use ($interview, $request) {
            foreach ($request->input('answers') as $answer) {
                $interview->answers()->updateOrCreate(
                    ['slot' => $answer['slot']],
                    [
                        'competency' => $answer['category'] ?? $answer['question_id'] ?? null,
                        'answer_text' => $answer['text'],
                    ]
                );
            }
        });

        $answeredCount = $interview->answers()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'answered' => $answeredCount,
                'total' => 12,
                'complete' => $answeredCount >= 12,
            ],
        ]);
    }

    /**
     * POST /api/maritime/candidates/{id}/behavioral/complete
     *
     * Finalize the behavioral interview and trigger scoring.
     */
    public function complete(string $id): JsonResponse
    {
        $candidate = PoolCandidate::find($id);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found.'], 404);
        }

        $interview = FormInterview::where('pool_candidate_id', $candidate->id)
            ->where('type', 'behavioral')
            ->where('status', 'in_progress')
            ->first();

        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'No active behavioral interview found.'], 404);
        }

        $answeredCount = $interview->answers()->count();
        if ($answeredCount < 12) {
            return response()->json([
                'success' => false,
                'message' => "Please answer all 12 questions. Currently answered: {$answeredCount}.",
            ], 422);
        }

        // Score the behavioral interview
        $answers = $interview->answers()->orderBy('slot')->get();
        $categoryScores = $this->buildCategoryScores($answers);

        try {
            $profile = $this->scoringService->scoreStructuredInterview($interview, $categoryScores);

            // Mark interview as completed
            $interview->updateQuietly([
                'status' => FormInterview::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            // Trigger vector recompute (async, fail-open)
            if (config('maritime.vector_v1')) {
                try {
                    \App\Jobs\ComputeCandidateVectorJob::dispatch($candidate->id)
                        ->delay(now()->addSeconds(30));
                } catch (\Throwable) {}
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                    'profile_id' => $profile?->id,
                    'dimensions' => $profile?->dimensions_json,
                    'confidence' => $profile?->confidence,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::channel('single')->warning('BehavioralInterview::complete scoring failed', [
                'candidate_id' => $candidate->id,
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);

            // Still mark complete even if scoring fails (fail-open)
            $interview->updateQuietly([
                'status' => FormInterview::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                    'profile_id' => null,
                    'scoring_deferred' => true,
                ],
            ]);
        }
    }

    // ─── Private helpers ───

    private function getOrCreateBehavioralInterview(PoolCandidate $candidate): FormInterview
    {
        $existing = FormInterview::where('pool_candidate_id', $candidate->id)
            ->where('type', 'behavioral')
            ->whereIn('status', ['draft', 'in_progress'])
            ->first();

        if ($existing) {
            if ($existing->status === 'draft') {
                $existing->updateQuietly(['status' => 'in_progress']);
            }
            return $existing;
        }

        return FormInterview::create([
            'pool_candidate_id' => $candidate->id,
            'type' => 'behavioral',
            'version' => 'v1',
            'language' => $candidate->preferred_language ?? 'en',
            'position_code' => '__behavioral__',
            'template_position_code' => '__behavioral__',
            'industry_code' => 'maritime',
            'status' => 'in_progress',
        ]);
    }

    private function loadExistingAnswers(string $candidateId): array
    {
        $interview = FormInterview::where('pool_candidate_id', $candidateId)
            ->where('type', 'behavioral')
            ->whereIn('status', ['draft', 'in_progress'])
            ->first();

        if (!$interview) {
            return [];
        }

        return $interview->answers()
            ->orderBy('slot')
            ->get()
            ->map(fn($a) => [
                'slot' => $a->slot,
                'category' => $a->competency,
                'text' => $a->answer_text,
            ])
            ->toArray();
    }

    private function buildCategoryScores($answers): array
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

            // Score 1-5 based on response quality (text length + substance)
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
