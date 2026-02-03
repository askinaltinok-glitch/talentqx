<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeInterviewJob;
use App\Jobs\ProcessOutboxMessagesJob;
use App\Models\Candidate;
use App\Models\ConsentLog;
use App\Models\Interview;
use App\Models\InterviewResponse;
use App\Models\MessageOutbox;
use App\Models\ResponseSimilarity;
use App\Services\AntiCheat\AntiCheatService;
use App\Services\Email\EmailTemplateService;
use App\Services\Interview\AnalysisEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InterviewController extends Controller
{
    public function __construct(
        private AnalysisEngine $analysisEngine,
        private AntiCheatService $antiCheatService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => 'required|uuid|exists:candidates,id',
            'expires_in_hours' => 'nullable|integer|min:1|max:168',
            'send_email' => 'nullable|boolean',
        ]);

        $candidate = Candidate::with(['job', 'job.company', 'job.branch'])
            ->whereHas('job', function ($q) use ($request) {
                $q->where('company_id', $request->user()->company_id);
            })->findOrFail($validated['candidate_id']);

        $expiresInHours = $validated['expires_in_hours'] ?? config('interview.token_expiry_hours', 72);

        $interview = Interview::create([
            'candidate_id' => $candidate->id,
            'job_id' => $candidate->job_id,
            'token_expires_at' => now()->addHours($expiresInHours),
        ]);

        $candidate->updateStatus(Candidate::STATUS_INTERVIEW_PENDING);

        // Send interview invitation email if requested (default: true)
        $emailSent = false;
        $sendEmail = $validated['send_email'] ?? true;

        if ($sendEmail && $candidate->email) {
            $emailSent = $this->sendInterviewInvitationEmail($interview, $candidate);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $interview->id,
                'candidate_id' => $interview->candidate_id,
                'access_token' => $interview->access_token,
                'interview_url' => $interview->getInterviewUrl(),
                'expires_at' => $interview->token_expires_at,
                'status' => $interview->status,
                'email_sent' => $emailSent,
            ],
        ], 201);
    }

    /**
     * Send interview invitation email to candidate.
     */
    private function sendInterviewInvitationEmail(Interview $interview, Candidate $candidate): bool
    {
        try {
            $interview->load(['job', 'job.company', 'job.branch']);

            $company = $interview->job->company;
            $job = $interview->job;
            $branch = $interview->job->branch;

            $emailService = new EmailTemplateService();
            $rendered = $emailService->renderInterviewInvitation([
                'company' => $company,
                'branch' => $branch,
                'job' => $job,
                'candidate' => [
                    'id' => $candidate->id,
                    'name' => trim($candidate->first_name . ' ' . $candidate->last_name),
                    'first_name' => $candidate->first_name,
                    'last_name' => $candidate->last_name,
                ],
                'interview_url' => $interview->getInterviewUrl(),
                'expires_at' => $interview->token_expires_at,
                'duration_minutes' => $job->interview_settings['max_duration_minutes'] ?? 20,
                'locale' => 'tr',
            ]);

            $outbox = MessageOutbox::create([
                'company_id' => $company->id,
                'channel' => MessageOutbox::CHANNEL_EMAIL,
                'recipient' => $candidate->email,
                'recipient_name' => trim($candidate->first_name . ' ' . $candidate->last_name),
                'subject' => $rendered['subject'],
                'body' => $rendered['body'],
                'template_id' => 'interview_invitation',
                'related_type' => 'interview',
                'related_id' => $interview->id,
                'status' => MessageOutbox::STATUS_PENDING,
                'priority' => 10, // High priority
                'metadata' => [
                    'candidate_id' => $candidate->id,
                    'job_id' => $job->id,
                    'interview_url' => $interview->getInterviewUrl(),
                ],
            ]);

            // Process immediately (async via queue in production)
            ProcessOutboxMessagesJob::dispatch(1);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send interview invitation email', [
                'interview_id' => $interview->id,
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function showPublic(string $token): JsonResponse
    {
        $interview = Interview::with(['job.company', 'job.questions', 'candidate'])
            ->where('access_token', $token)
            ->firstOrFail();

        if (!$interview->isTokenValid()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Mulakat linki suresi dolmus veya kullanilamaz durumda.',
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $interview->id,
                'status' => $interview->status,
                'job' => [
                    'title' => $interview->job->title,
                    'company_name' => $interview->job->company->name,
                    'company_logo' => $interview->job->company->logo_url,
                ],
                'candidate' => [
                    'first_name' => $interview->candidate->first_name,
                ],
                'settings' => $interview->job->interview_settings,
                'consent_required' => !$interview->candidate->consent_given,
                'consent_text' => $this->getConsentText(),
            ],
        ]);
    }

    public function startPublic(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'consent_given' => 'required|boolean|accepted',
            'device_info' => 'nullable|array',
        ]);

        $interview = Interview::with(['job.questions', 'candidate'])
            ->where('access_token', $token)
            ->firstOrFail();

        if (!$interview->isTokenValid()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Mulakat linki suresi dolmus.',
                ],
            ], 403);
        }

        if (!$interview->candidate->consent_given) {
            $interview->candidate->update([
                'consent_given' => true,
                'consent_version' => config('kvkk.consent_version', '1.0'),
                'consent_given_at' => now(),
                'consent_ip' => $request->ip(),
            ]);

            ConsentLog::logConsent(
                $interview->candidate,
                ConsentLog::TYPE_KVKK,
                config('kvkk.consent_version', '1.0'),
                $this->getConsentText(),
                ConsentLog::ACTION_GIVEN,
                $request->ip(),
                $request->userAgent()
            );
        }

        $interview->start($validated['device_info'] ?? [], $request->ip());

        return response()->json([
            'success' => true,
            'data' => [
                'interview_id' => $interview->id,
                'questions' => $interview->job->questions->map(fn($q) => [
                    'id' => $q->id,
                    'order' => $q->question_order,
                    'text' => $q->question_text,
                    'time_limit_seconds' => $q->time_limit_seconds,
                ]),
                'started_at' => $interview->started_at,
            ],
        ]);
    }

    public function submitResponse(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'question_id' => 'required|uuid|exists:job_questions,id',
            'video' => 'nullable|file|mimes:webm,mp4|max:102400',
            'audio' => 'nullable|file|mimes:webm,mp3,wav|max:51200',
            'started_at' => 'required|date',
            'ended_at' => 'required|date|after:started_at',
        ]);

        $interview = Interview::where('access_token', $token)
            ->where('status', Interview::STATUS_IN_PROGRESS)
            ->firstOrFail();

        $existingResponse = InterviewResponse::where('interview_id', $interview->id)
            ->where('question_id', $validated['question_id'])
            ->first();

        if ($existingResponse) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_ANSWERED',
                    'message' => 'Bu soru zaten cevaplanmis.',
                ],
            ], 422);
        }

        $videoUrl = null;
        $audioUrl = null;

        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store(
                "interviews/{$interview->id}/responses",
                's3'
            );
            $videoUrl = Storage::disk('s3')->url($videoPath);
        }

        if ($request->hasFile('audio')) {
            $audioPath = $request->file('audio')->store(
                "interviews/{$interview->id}/responses",
                's3'
            );
            $audioUrl = Storage::disk('s3')->url($audioPath);
        }

        $responseOrder = InterviewResponse::where('interview_id', $interview->id)->count() + 1;

        $startedAt = new \DateTime($validated['started_at']);
        $endedAt = new \DateTime($validated['ended_at']);
        $duration = $endedAt->getTimestamp() - $startedAt->getTimestamp();

        $response = InterviewResponse::create([
            'interview_id' => $interview->id,
            'question_id' => $validated['question_id'],
            'response_order' => $responseOrder,
            'video_segment_url' => $videoUrl,
            'audio_segment_url' => $audioUrl,
            'duration_seconds' => $duration,
            'started_at' => $validated['started_at'],
            'ended_at' => $validated['ended_at'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'response_id' => $response->id,
                'response_order' => $response->response_order,
            ],
        ]);
    }

    public function completePublic(string $token): JsonResponse
    {
        $interview = Interview::where('access_token', $token)
            ->where('status', Interview::STATUS_IN_PROGRESS)
            ->firstOrFail();

        $interview->complete();

        AnalyzeInterviewJob::dispatch($interview);

        return response()->json([
            'success' => true,
            'data' => [
                'interview_id' => $interview->id,
                'status' => $interview->status,
                'completed_at' => $interview->completed_at,
                'message' => 'Mulakatiniz basariyla tamamlandi. Tesekkur ederiz!',
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $interview = Interview::with([
            'candidate',
            'job',
            'responses.question',
            'analysis',
        ])->whereHas('job', function ($q) use ($request) {
            $q->where('company_id', $request->user()->company_id);
        })->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $interview->id,
                'status' => $interview->status,
                'candidate' => [
                    'id' => $interview->candidate->id,
                    'first_name' => $interview->candidate->first_name,
                    'last_name' => $interview->candidate->last_name,
                ],
                'job' => [
                    'id' => $interview->job->id,
                    'title' => $interview->job->title,
                ],
                'video_url' => $interview->video_url,
                'duration_seconds' => $interview->video_duration_seconds,
                'responses' => $interview->responses->map(fn($r) => [
                    'id' => $r->id,
                    'question' => [
                        'id' => $r->question->id,
                        'order' => $r->question->question_order,
                        'text' => $r->question->question_text,
                        'competency_code' => $r->question->competency_code,
                    ],
                    'video_segment_url' => $r->video_segment_url,
                    'transcript' => $r->transcript,
                    'duration_seconds' => $r->duration_seconds,
                ]),
                'analysis' => $interview->analysis ? [
                    'overall_score' => $interview->analysis->overall_score,
                    'competency_scores' => $interview->analysis->competency_scores,
                    'behavior_analysis' => $interview->analysis->behavior_analysis,
                    'red_flag_analysis' => $interview->analysis->red_flag_analysis,
                    'culture_fit' => $interview->analysis->culture_fit,
                    'decision_snapshot' => $interview->analysis->decision_snapshot,
                    'question_analyses' => $interview->analysis->question_analyses,
                ] : null,
                'started_at' => $interview->started_at,
                'completed_at' => $interview->completed_at,
            ],
        ]);
    }

    public function analyze(Request $request, string $id): JsonResponse
    {
        $interview = Interview::whereHas('job', function ($q) use ($request) {
            $q->where('company_id', $request->user()->company_id);
        })->findOrFail($id);

        if ($interview->status !== Interview::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_COMPLETED',
                    'message' => 'Mulakat henuz tamamlanmamis.',
                ],
            ], 422);
        }

        $forceReanalyze = $request->boolean('force_reanalyze', false);

        AnalyzeInterviewJob::dispatch($interview, $forceReanalyze);

        return response()->json([
            'success' => true,
            'message' => 'Analiz kuyruga eklendi.',
            'data' => [
                'estimated_seconds' => 45,
            ],
        ], 202);
    }

    private function getConsentText(): string
    {
        return "6698 sayili Kisisel Verilerin Korunmasi Kanunu (KVKK) kapsaminda, mulakat surecinde " .
            "kayit altina alinan video ve ses verilerinizin, is basvurunuzun degerlendirilmesi " .
            "amaciyla islenmesine ve saklanmasina onay veriyorum. Bu verilerin sadece yetkilendirilmis " .
            "IK personeli tarafindan erisileceğini ve yasal saklama suresi sonunda silinecegini anliyorum.";
    }

    // ===========================================
    // ANTI-CHEAT METHODS
    // ===========================================

    /**
     * Analyze interview for cheating indicators
     * POST /interviews/{id}/analyze-cheating
     */
    public function analyzeCheating(Request $request, string $id): JsonResponse
    {
        $interview = Interview::with(['responses', 'analysis'])
            ->whereHas('job', fn($q) => $q->where('company_id', $request->user()->company_id))
            ->findOrFail($id);

        if ($interview->status !== Interview::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_COMPLETED',
                    'message' => 'Mulakat henuz tamamlanmamis.',
                ],
            ], 422);
        }

        $result = $this->antiCheatService->analyzeInterview($interview);

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Kopya analizi tamamlandi.',
        ]);
    }

    /**
     * Get cheating report for interview
     * GET /interviews/{id}/cheating-report
     */
    public function cheatingReport(Request $request, string $id): JsonResponse
    {
        $interview = Interview::with(['analysis', 'candidate', 'job'])
            ->whereHas('job', fn($q) => $q->where('company_id', $request->user()->company_id))
            ->findOrFail($id);

        if (!$interview->analysis) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_ANALYZED',
                    'message' => 'Mulakat henuz analiz edilmemis.',
                ],
            ], 404);
        }

        $analysis = $interview->analysis;

        return response()->json([
            'success' => true,
            'data' => [
                'interview_id' => $interview->id,
                'candidate' => [
                    'id' => $interview->candidate->id,
                    'name' => $interview->candidate->full_name,
                ],
                'job' => [
                    'id' => $interview->job->id,
                    'title' => $interview->job->title,
                ],
                'cheating_risk_score' => $analysis->cheating_risk_score,
                'cheating_level' => $analysis->cheating_level,
                'cheating_flags' => $analysis->cheating_flags ?? [],
                'timing_analysis' => $analysis->timing_analysis,
                'similarity_analysis' => $analysis->similarity_analysis,
                'consistency_analysis' => $analysis->consistency_analysis,
                'analyzed_at' => $analysis->analyzed_at,
            ],
        ]);
    }

    /**
     * Get similar responses across candidates for a job
     * GET /anti-cheat/similar-responses
     */
    public function similarResponses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|uuid|exists:job_postings,id',
            'min_similarity' => 'nullable|numeric|min:0.5|max:1',
            'question_order' => 'nullable|integer|min:1',
        ]);

        $query = ResponseSimilarity::with([
            'responseA.interview.candidate',
            'responseB.interview.candidate',
        ])
            ->where('job_id', $validated['job_id'])
            ->where('cosine_similarity', '>=', $validated['min_similarity'] ?? 0.85);

        if (isset($validated['question_order'])) {
            $query->where('question_order', $validated['question_order']);
        }

        $similarities = $query->orderByDesc('cosine_similarity')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $similarities->map(function ($sim) {
                return [
                    'id' => $sim->id,
                    'question_order' => $sim->question_order,
                    'similarity_percent' => round($sim->cosine_similarity * 100, 1),
                    'candidate_a' => [
                        'id' => $sim->responseA->interview->candidate->id ?? null,
                        'name' => $sim->responseA->interview->candidate->full_name ?? null,
                    ],
                    'candidate_b' => [
                        'id' => $sim->responseB->interview->candidate->id ?? null,
                        'name' => $sim->responseB->interview->candidate->full_name ?? null,
                    ],
                    'flagged' => $sim->flagged,
                ];
            }),
            'meta' => [
                'total' => $similarities->count(),
                'job_id' => $validated['job_id'],
            ],
        ]);
    }
}
