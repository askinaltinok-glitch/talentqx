<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeInterviewJob;
use App\Models\Candidate;
use App\Models\ConsentLog;
use App\Models\Interview;
use App\Models\InterviewResponse;
use App\Models\ResponseSimilarity;
use App\Services\AntiCheat\AntiCheatService;
use App\Services\Billing\CreditService;
use App\Services\Interview\AnalysisEngine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InterviewController extends Controller
{
    public function __construct(
        private AnalysisEngine $analysisEngine,
        private AntiCheatService $antiCheatService,
        private CreditService $creditService
    ) {}

    /**
     * List interviews with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Interview::with([
            'candidate:id,first_name,last_name,email,phone,job_id',
            'candidate.job:id,title,company_id',
            'candidate.job.company:id,name',
            'analysis:id,interview_id,overall_score,decision_snapshot'
        ]);

        // Platform admin sees all, regular users see only their company
        if (!$user->is_platform_admin) {
            $query->whereHas('candidate.job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        } elseif ($request->has('company_id')) {
            $query->whereHas('candidate.job', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        $query->orderByDesc('created_at');

        $interviews = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $interviews->map(fn($interview) => [
                'id' => $interview->id,
                'status' => $interview->status,
                'created_at' => $interview->created_at,
                'started_at' => $interview->started_at,
                'completed_at' => $interview->completed_at,
                'duration_minutes' => $interview->duration_minutes,
                'candidate' => $interview->candidate ? [
                    'id' => $interview->candidate->id,
                    'first_name' => $interview->candidate->first_name,
                    'last_name' => $interview->candidate->last_name,
                    'email' => $interview->candidate->email,
                    'job' => $interview->candidate->job ? [
                        'id' => $interview->candidate->job->id,
                        'title' => $interview->candidate->job->title,
                        'company' => $interview->candidate->job->company ? [
                            'id' => $interview->candidate->job->company->id,
                            'name' => $interview->candidate->job->company->name,
                        ] : null,
                    ] : null,
                ] : null,
                'analysis' => $interview->analysis ? [
                    'overall_score' => $interview->analysis->overall_score,
                    'recommendation' => $interview->analysis->decision_snapshot['recommendation'] ?? null,
                ] : null,
            ]),
            'meta' => [
                'current_page' => $interviews->currentPage(),
                'per_page' => $interviews->perPage(),
                'total' => $interviews->total(),
                'last_page' => $interviews->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => 'required|uuid|exists:candidates,id',
            'expires_in_hours' => 'nullable|integer|min:1|max:168',
        ]);

        $user = $request->user();
        $query = Candidate::query();

        if (!$user->is_platform_admin) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $candidate = $query->findOrFail($validated['candidate_id']);

        // Credit check: verify company has available credits
        $company = $candidate->job->company;
        if (!$this->creditService->canUseCredit($company)) {
            return response()->json([
                'success' => false,
                'code' => 'credits_exhausted',
                'message' => 'Interview quota exhausted. Please contact support.',
            ], 402);
        }

        $expiresInHours = $validated['expires_in_hours'] ?? config('interview.token_expiry_hours', 72);

        $interview = Interview::create([
            'candidate_id' => $candidate->id,
            'job_id' => $candidate->job_id,
            'token_expires_at' => now()->addHours($expiresInHours),
        ]);

        $candidate->updateStatus(Candidate::STATUS_INTERVIEW_PENDING);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $interview->id,
                'candidate_id' => $interview->candidate_id,
                'access_token' => $interview->access_token,
                'interview_url' => $interview->getInterviewUrl(),
                'expires_at' => $interview->token_expires_at,
                'status' => $interview->status,
            ],
        ], 201);
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
                // Interview mode based on feature flags (v1.0: text-only, v2: voice/video)
                'interview_mode' => $this->getInterviewMode(),
                'voice_enabled' => config('interview.voice_enabled', false),
                'video_enabled' => config('interview.video_enabled', false),
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

        // Track join time and lateness (for scheduled interviews)
        if ($interview->scheduled_at && !$interview->joined_at) {
            $now = now();
            $lateMinutes = max(0, (int) $interview->scheduled_at->diffInMinutes($now, false));
            $interview->forceFill([
                'joined_at' => $now,
                'late_minutes' => $lateMinutes,
            ])->save();
        }

        $interview->start($validated['device_info'] ?? [], $request->ip());

        // Get already answered question IDs for resume support
        $answeredQuestionIds = $interview->responses()->pluck('question_id')->toArray();

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
                'answered_question_ids' => $answeredQuestionIds,
                'started_at' => $interview->started_at,
            ],
        ]);
    }

    public function submitResponse(Request $request, string $token): JsonResponse
    {
        // Check feature flags: reject audio/video if disabled
        if ($request->hasFile('video') || $request->hasFile('audio')) {
            if (!$this->isVoiceVideoEnabled()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'FEATURE_DISABLED',
                        'message' => 'Sesli/görüntülü mülakat şu an devre dışı. Lütfen yazılı yanıt verin.',
                        'message_en' => 'Voice/video interviews are currently disabled. Please provide text response.',
                    ],
                ], 422);
            }
            // TODO v2: Handle audio/video uploads when enabled
        }

        $validated = $request->validate([
            'question_id' => 'required|uuid|exists:job_questions,id',
            'text_response' => 'required|string|min:10|max:5000',
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

        $responseOrder = InterviewResponse::where('interview_id', $interview->id)->count() + 1;

        $startedAt = new \DateTime($validated['started_at']);
        $endedAt = new \DateTime($validated['ended_at']);
        $duration = $endedAt->getTimestamp() - $startedAt->getTimestamp();

        $response = InterviewResponse::create([
            'interview_id' => $interview->id,
            'question_id' => $validated['question_id'],
            'response_order' => $responseOrder,
            'video_segment_url' => null,
            'audio_segment_url' => null,
            'transcript' => $validated['text_response'],
            'transcript_confidence' => 1.0000, // Direct text input = 100% confidence
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
        $interview = Interview::with('job.company')
            ->where('access_token', $token)
            ->where('status', Interview::STATUS_IN_PROGRESS)
            ->firstOrFail();

        $interview->complete();

        // Deduct credit when interview is completed
        $company = $interview->job->company;
        $this->creditService->deductCredit($company, $interview);

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
        $user = $request->user();

        $query = Interview::with([
            'candidate',
            'job.company',
            'responses.question',
            'analysis',
        ]);

        if (!$user->is_platform_admin) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $interview = $query->findOrFail($id);

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
                // Timing / punctuality
                'scheduled_at' => $interview->scheduled_at,
                'joined_at' => $interview->joined_at,
                'late_minutes' => $interview->late_minutes,
                'no_show_marked_at' => $interview->no_show_marked_at,
                // Company Competency Model fit (computed on-the-fly)
                ...$this->computeCompanyFitForInterview($interview),
            ],
        ]);
    }

    /**
     * Compute company fit score from interview analysis + company competency model.
     */
    private function computeCompanyFitForInterview(Interview $interview): array
    {
        if (!config('features.competency_model_v1')) {
            return [];
        }

        try {
            $companyId = $interview->job?->company_id;
            $competencyScores = $interview->analysis?->competency_scores;

            if (!$companyId || !$competencyScores) {
                return [];
            }

            $result = app(\App\Services\CompanyCompetencyService::class)
                ->computeFromScores($competencyScores, $companyId);

            if (!$result) {
                return [];
            }

            return [
                'company_fit_score' => $result['company_fit_score'],
                'company_competency_scores' => $result['company_competency_scores'],
            ];
        } catch (\Throwable $e) {
            \Log::warning('Company fit score computation failed for interview', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Download interview report as PDF.
     * GET /interviews/{id}/report.pdf
     */
    public function reportPdf(Request $request, string $id)
    {
        $user = $request->user();

        $query = Interview::with([
            'candidate',
            'job.company',
            'responses.question',
            'analysis',
        ]);

        if (!$user->is_platform_admin) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $interview = $query->find($id);

        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Mülakat bulunamadı'], 404);
        }

        if ($interview->status !== Interview::STATUS_COMPLETED) {
            return response()->json(['success' => false, 'message' => 'Rapor sadece tamamlanmış mülakatlar için oluşturulabilir'], 400);
        }

        $analysis = $interview->analysis;
        $candidate = $interview->candidate;
        $job = $interview->job;
        $company = $job?->company;

        // Parse JSON fields
        $competencyScores = is_string($analysis?->competency_scores) ? json_decode($analysis->competency_scores, true) : ($analysis?->competency_scores ?? []);
        $behaviorAnalysis = is_string($analysis?->behavior_analysis) ? json_decode($analysis->behavior_analysis, true) : ($analysis?->behavior_analysis ?? []);
        $redFlagAnalysis = is_string($analysis?->red_flag_analysis) ? json_decode($analysis->red_flag_analysis, true) : ($analysis?->red_flag_analysis ?? []);
        $cultureFit = is_string($analysis?->culture_fit) ? json_decode($analysis->culture_fit, true) : ($analysis?->culture_fit ?? []);
        $decisionSnapshot = is_string($analysis?->decision_snapshot) ? json_decode($analysis->decision_snapshot, true) : ($analysis?->decision_snapshot ?? []);
        $questionAnalyses = is_string($analysis?->question_analyses) ? json_decode($analysis->question_analyses, true) : ($analysis?->question_analyses ?? []);

        $pdf = Pdf::loadView('reports.interview-report', [
            'interview' => $interview,
            'candidate' => $candidate,
            'job' => $job,
            'company' => $company,
            'analysis' => $analysis,
            'competencyScores' => $competencyScores,
            'behaviorAnalysis' => $behaviorAnalysis,
            'redFlagAnalysis' => $redFlagAnalysis,
            'cultureFit' => $cultureFit,
            'decisionSnapshot' => $decisionSnapshot,
            'questionAnalyses' => $questionAnalyses,
            'generatedAt' => now()->format('d.m.Y H:i'),
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isPhpEnabled', true);

        $candidateName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')) ?: 'Aday';
        $filename = "Mulakat-Raporu-{$candidateName}-" . now()->format('Ymd') . ".pdf";

        return $pdf->download($filename);
    }

    public function analyze(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Interview::query();

        if (!$user->is_platform_admin) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $interview = $query->findOrFail($id);

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
        $provider = $request->input('provider'); // 'openai' or 'kimi'

        AnalyzeInterviewJob::dispatch($interview, $forceReanalyze, $provider);

        $providerName = $provider === 'kimi' ? 'Moonshot AI' : 'OpenAI';
        return response()->json([
            'success' => true,
            'message' => "{$providerName} ile analiz kuyruga eklendi.",
            'data' => [
                'estimated_seconds' => 45,
                'provider' => $provider,
            ],
        ], 202);
    }

    private function getConsentText(): string
    {
        return "6698 sayili Kisisel Verilerin Korunmasi Kanunu (KVKK) kapsaminda, mulakat surecinde " .
            "kayit altina alinan video ve ses verilerinizin, is basvurunuzun degerlendirilmesi " .
            "amaciyla islenmesine ve saklanmasina onay veriyorum. Bu verilerin sadece yetkilendirilmis " .
            "IK personeli tarafindan erisileceğini ve yasal saklama suresi sonunda silinecegini anliyorum.\n\n" .
            "Ayrica, yazili mulakat yanitlarimin yapay zeka tarafindan analiz edilecegini ve " .
            "yapay zeka degerlendirmesinin yalnizca verdigim yanitlarla sinirli oldugunu; " .
            "nihai ise alim kararinin insan degerlendirmesi ile verilecegini kabul ediyorum.";
    }

    /**
     * Determine interview mode based on feature flags.
     */
    private function getInterviewMode(): string
    {
        $voiceEnabled = config('interview.voice_enabled', false);
        $videoEnabled = config('interview.video_enabled', false);

        if ($videoEnabled) {
            return 'video';
        }
        if ($voiceEnabled) {
            return 'voice';
        }
        return 'text_only';
    }

    /**
     * Check if voice/video features are enabled.
     */
    private function isVoiceVideoEnabled(): bool
    {
        return config('interview.voice_enabled', false) || config('interview.video_enabled', false);
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
        $user = $request->user();
        $query = Interview::with(['responses', 'analysis']);

        if (!$user->is_platform_admin) {
            $query->whereHas('job', fn($q) => $q->where('company_id', $user->company_id));
        }

        $interview = $query->findOrFail($id);

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
        $user = $request->user();
        $query = Interview::with(['analysis', 'candidate', 'job']);

        if (!$user->is_platform_admin) {
            $query->whereHas('job', fn($q) => $q->where('company_id', $user->company_id));
        }

        $interview = $query->findOrFail($id);

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

        // Verify user has access to this job
        $user = $request->user();
        $jobQuery = \App\Models\Job::query();

        if (!$user->is_platform_admin) {
            $jobQuery->where('company_id', $user->company_id);
        }

        $jobQuery->where('id', $validated['job_id'])->firstOrFail();

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

    /**
     * Get report data for interview
     * GET /interviews/{id}/report
     */
    public function report(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Interview::with([
            'candidate',
            'job',
            'analysis',
            'responses.question',
        ]);

        if (!$user->is_platform_admin) {
            $query->whereHas('candidate', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $interview = $query->find($id);

        if (!$interview) {
            return response()->json([
                'success' => false,
                'error' => 'Interview not found or access denied',
            ], 404);
        }

        if (!$interview->analysis) {
            return response()->json([
                'success' => false,
                'error' => 'Interview analysis not completed yet',
            ], 400);
        }

        $analysis = $interview->analysis;
        $candidate = $interview->candidate;
        $job = $interview->job;

        // Build report data
        $reportData = [
            'generated_at' => now()->toIso8601String(),
            'interview' => [
                'id' => $interview->id,
                'status' => $interview->status,
                'started_at' => $interview->started_at?->toIso8601String(),
                'completed_at' => $interview->completed_at?->toIso8601String(),
                'duration_minutes' => $interview->getDurationInMinutes(),
            ],
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
            ],
            'position' => [
                'title' => $job->title,
                'location' => $job->location,
                'company' => $job->company->name ?? null,
            ],
            'assessment' => [
                'overall_score' => $analysis->overall_score,
                'recommendation' => $analysis->getRecommendation(),
                'confidence' => $analysis->getConfidencePercent(),
                'competency_scores' => $analysis->competency_scores ?? [],
                'behavior_analysis' => $analysis->behavior_analysis ?? [],
                'culture_fit' => $analysis->culture_fit ?? [],
                'reasons' => $analysis->getReasons(),
                'suggested_questions' => $analysis->getSuggestedQuestions(),
                'red_flags' => $analysis->red_flag_analysis ?? [],
            ],
            'responses' => $interview->responses->map(fn($r) => [
                'order' => $r->response_order,
                'question' => $r->question?->question_text,
                'transcript' => $r->transcript,
                'duration_seconds' => $r->duration_seconds,
            ])->toArray(),
        ];

        return response()->json([
            'success' => true,
            'data' => $reportData,
        ]);
    }
}
