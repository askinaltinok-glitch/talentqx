<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CandidateHiredMail;
use App\Mail\CandidateRejectedMail;
use App\Mail\CandidateUnderReviewMail;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Job;
use App\Notifications\InterviewInvitationNotification;
use App\Services\AdminNotificationService;
use App\Services\Copilot\RedFlagActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    public function __construct(
        private RedFlagActionService $redFlagActionService,
        private AdminNotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Candidate::with(['job.company', 'latestInterview.analysis']);

        // Platform admin sees all, regular users see only their company
        if (!$user->is_platform_admin) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        } elseif ($request->has('company_id')) {
            $query->whereHas('job', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by interview status (pending, in_progress, completed, cancelled)
        if ($request->has('interview_status')) {
            $interviewStatus = $request->interview_status;
            if ($interviewStatus === 'none') {
                $query->whereDoesntHave('interviews');
            } else {
                $query->whereHas('latestInterview', function ($q) use ($interviewStatus) {
                    $q->where('status', $interviewStatus);
                });
            }
        }

        if ($request->has('has_red_flags') && $request->boolean('has_red_flags')) {
            $query->whereHas('latestInterview.analysis', function ($q) {
                $q->whereJsonContains('red_flag_analysis->flags_detected', true);
            });
        }

        if ($request->has('min_score')) {
            $query->whereHas('latestInterview.analysis', function ($q) use ($request) {
                $q->where('overall_score', '>=', $request->min_score);
            });
        }

        if ($request->has('max_score')) {
            $query->whereHas('latestInterview.analysis', function ($q) use ($request) {
                $q->where('overall_score', '<=', $request->max_score);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        if ($sortField === 'score') {
            $query->leftJoin('interviews', function ($join) {
                $join->on('candidates.id', '=', 'interviews.candidate_id')
                    ->whereRaw('interviews.id = (SELECT id FROM interviews WHERE candidate_id = candidates.id ORDER BY created_at DESC LIMIT 1)');
            })
                ->leftJoin('interview_analyses', 'interviews.id', '=', 'interview_analyses.interview_id')
                ->orderBy('interview_analyses.overall_score', $sortOrder)
                ->select('candidates.*');
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        $candidates = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $candidates->map(fn($candidate) => [
                'id' => $candidate->id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'status' => $candidate->status,
                'cv_match_score' => $candidate->cv_match_score,
                'job' => [
                    'id' => $candidate->job->id,
                    'title' => $candidate->job->title,
                    'company' => [
                        'id' => $candidate->job->company->id ?? null,
                        'name' => $candidate->job->company->name ?? null,
                    ],
                ],
                'company_name' => $candidate->job->company->name ?? null,
                'has_interview' => $candidate->latestInterview !== null,
                'interview_status' => $candidate->latestInterview?->status,
                'interview_url' => $candidate->latestInterview?->status === 'completed'
                    ? null
                    : $candidate->latestInterview?->getInterviewUrl(),
                'interview' => $candidate->latestInterview ? [
                    'id' => $candidate->latestInterview->id,
                    'status' => $candidate->latestInterview->status,
                    'completed_at' => $candidate->latestInterview->completed_at,
                ] : null,
                'analysis' => $candidate->latestInterview?->analysis ? [
                    'overall_score' => $candidate->latestInterview->analysis->overall_score,
                    'recommendation' => $candidate->latestInterview->analysis->getRecommendation(),
                    'confidence_percent' => $candidate->latestInterview->analysis->getConfidencePercent(),
                    'has_red_flags' => $candidate->latestInterview->analysis->hasRedFlags(),
                ] : null,
                'created_at' => $candidate->created_at,
            ]),
            'meta' => [
                'current_page' => $candidates->currentPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
                'last_page' => $candidates->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|uuid|exists:job_postings,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:100',
            'referrer_name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $jobQuery = Job::query();

        if (!$user->is_platform_admin) {
            $jobQuery->where('company_id', $user->company_id);
        }

        $job = $jobQuery->findOrFail($validated['job_id']);

        $candidate = Candidate::create([
            'job_id' => $job->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'source' => $validated['source'] ?? 'manual',
            'referrer_name' => $validated['referrer_name'] ?? null,
            'status' => Candidate::STATUS_APPLIED,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $candidate->id,
                'full_name' => $candidate->full_name,
                'email' => $candidate->email,
                'status' => $candidate->status,
                'created_at' => $candidate->created_at,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $query = Candidate::with(['job.template', 'job.company', 'latestInterview.analysis', 'latestInterview.responses.question']);

        if (!$user->is_platform_admin) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $candidate = $query->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $candidate->id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'status' => $candidate->status,
                'cv_url' => $candidate->cv_url,
                'cv_match_score' => $candidate->cv_match_score,
                'cv_parsed_data' => $candidate->cv_parsed_data,
                'source' => $candidate->source,
                'consent_given' => $candidate->consent_given,
                'consent_version' => $candidate->consent_version,
                'job' => [
                    'id' => $candidate->job->id,
                    'title' => $candidate->job->title,
                ],
                'interview' => $candidate->latestInterview ? [
                    'id' => $candidate->latestInterview->id,
                    'status' => $candidate->latestInterview->status,
                    'video_url' => $candidate->latestInterview->video_url,
                    'completed_at' => $candidate->latestInterview->completed_at,
                    'responses' => $candidate->latestInterview->responses->map(fn($r) => [
                        'id' => $r->id,
                        'question' => [
                            'id' => $r->question->id,
                            'order' => $r->question->question_order,
                            'text' => $r->question->question_text,
                            'competency_code' => $r->question->competency_code,
                        ],
                        'transcript' => $r->transcript,
                        'duration_seconds' => $r->duration_seconds,
                        'video_segment_url' => $r->video_segment_url,
                    ]),
                ] : null,
                'analysis' => $candidate->latestInterview?->analysis ? $this->buildAnalysisResponse($candidate->latestInterview->analysis) : null,
                'internal_notes' => $candidate->internal_notes,
                'tags' => $candidate->tags,
                'created_at' => $candidate->created_at,
            ],
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:applied,interview_pending,interview_completed,under_review,shortlisted,hired,rejected',
            'note' => 'nullable|string',
        ]);

        $user = $request->user();
        $query = Candidate::query();

        if (!$user->is_platform_admin) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $candidate = $query->findOrFail($id);

        $candidate->updateStatus(
            $validated['status'],
            $validated['note'] ?? null,
            $request->user()
        );

        // Send notification emails on terminal status changes
        if ($validated['status'] === 'hired' && !$candidate->hired_email_sent_at && $candidate->email) {
            Mail::to($candidate->email)->send(new CandidateHiredMail($candidate));
            $candidate->update(['hired_email_sent_at' => now()]);
            $this->notificationService->notifyEmailSent(
                'candidate_hired',
                $candidate->email,
                "Hired: {$candidate->full_name}",
                ['candidate_id' => $candidate->id]
            );
        }

        if ($validated['status'] === 'rejected' && !$candidate->rejected_email_sent_at && $candidate->email) {
            Mail::to($candidate->email)->send(new CandidateRejectedMail($candidate));
            $candidate->update(['rejected_email_sent_at' => now()]);
            $this->notificationService->notifyEmailSent(
                'candidate_rejected',
                $candidate->email,
                "Rejected: {$candidate->full_name}",
                ['candidate_id' => $candidate->id]
            );
        }

        if ($validated['status'] === 'under_review' && $candidate->email) {
            Mail::to($candidate->email)->send(new CandidateUnderReviewMail($candidate));
            $this->notificationService->notifyEmailSent(
                'candidate_under_review',
                $candidate->email,
                "Under Review: {$candidate->full_name}",
                ['candidate_id' => $candidate->id]
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $candidate->id,
                'status' => $candidate->status,
                'status_changed_at' => $candidate->status_changed_at,
            ],
        ]);
    }

    public function uploadCv(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $user = $request->user();
        $query = Candidate::query();

        if (!$user->is_platform_admin) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $candidate = $query->findOrFail($id);

        $path = $request->file('cv')->store('cvs/' . $candidate->id, 's3');

        $candidate->update([
            'cv_url' => Storage::disk('s3')->url($path),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'cv_url' => $candidate->cv_url,
            ],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Candidate::query();

        if (!$user->is_platform_admin) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $candidate = $query->findOrFail($id);

        if ($candidate->cv_url) {
            Storage::disk('s3')->delete($candidate->cv_url);
        }

        foreach ($candidate->interviews as $interview) {
            if ($interview->video_url) {
                Storage::disk('s3')->delete($interview->video_url);
            }
        }

        $candidate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Aday ve tum iliskili veriler silindi.',
        ]);
    }

    /**
     * Send interview invitation to a single candidate.
     */
    public function sendInterviewInvite(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Candidate::with(['job']);

        if (!$user->is_platform_admin) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $candidate = $query->findOrFail($id);

        // Check if interview already exists and is completed
        $existingInterview = Interview::where('candidate_id', $candidate->id)->first();
        if ($existingInterview && $existingInterview->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Bu aday mülakatı zaten tamamlamış.',
            ], 422);
        }

        // Create interview if not exists
        if (!$existingInterview) {
            $existingInterview = Interview::create([
                'candidate_id' => $candidate->id,
                'job_id' => $candidate->job_id,
                'status' => 'pending',
            ]);
        }

        // Send invitation email
        Notification::route('mail', $candidate->email)
            ->notify(new InterviewInvitationNotification($existingInterview, 'written'));

        // Update interview
        $existingInterview->update(['invitation_sent_at' => now()]);

        // Update candidate status
        $candidate->update(['status' => Candidate::STATUS_INTERVIEW_PENDING]);

        return response()->json([
            'success' => true,
            'message' => 'Mülakat daveti gönderildi.',
            'data' => [
                'interview_url' => $existingInterview->getInterviewUrl(),
            ],
        ]);
    }

    /**
     * Send interview invitations to multiple candidates.
     */
    public function bulkSendInvites(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'uuid',
        ]);

        $user = $request->user();
        $sentCount = 0;
        $skippedCount = 0;

        foreach ($validated['candidate_ids'] as $candidateId) {
            $query = Candidate::with(['job']);

            if (!$user->is_platform_admin) {
                $query->whereHas('job', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            $candidate = $query->find($candidateId);
            if (!$candidate) {
                $skippedCount++;
                continue;
            }

            // Check if interview already completed
            $existingInterview = Interview::where('candidate_id', $candidate->id)->first();
            if ($existingInterview && $existingInterview->status === 'completed') {
                $skippedCount++;
                continue;
            }

            // Create interview if not exists
            if (!$existingInterview) {
                $existingInterview = Interview::create([
                    'candidate_id' => $candidate->id,
                    'job_id' => $candidate->job_id,
                    'status' => 'pending',
                ]);
            }

            // Send invitation email
            Notification::route('mail', $candidate->email)
                ->notify(new InterviewInvitationNotification($existingInterview, 'written'));

            // Update interview
            $existingInterview->update(['invitation_sent_at' => now()]);

            // Update candidate status
            $candidate->update(['status' => Candidate::STATUS_INTERVIEW_PENDING]);

            $sentCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$sentCount} adaya davet gönderildi.",
            'sent_count' => $sentCount,
            'skipped_count' => $skippedCount,
        ]);
    }

    /**
     * Build analysis response with suggested actions.
     */
    private function buildAnalysisResponse($analysis): array
    {
        $redFlagAnalysis = $analysis->red_flag_analysis;
        $suggestedActions = [];

        // Generate suggested actions if there are red flags
        if ($redFlagAnalysis && isset($redFlagAnalysis['flags']) && is_array($redFlagAnalysis['flags'])) {
            // Map the flags to the format expected by RedFlagActionService
            $mappedFlags = array_map(function ($flag) {
                return [
                    'code' => $flag['code'] ?? '',
                    'level' => $this->mapSeverityToLevel($flag['severity'] ?? 'medium'),
                    'label' => $flag['code'] ?? '',
                    'evidence' => $flag['detected_phrase'] ?? '',
                ];
            }, $redFlagAnalysis['flags']);

            // Determine overall risk level from flags
            $riskLevel = $this->determineRiskLevel($redFlagAnalysis['flags']);

            // Get recommended actions
            $actionResult = $this->redFlagActionService->getRecommendedActions($mappedFlags, $riskLevel);
            $suggestedActions = $actionResult['actions'];
        }

        return [
            'id' => $analysis->id,
            'overall_score' => $analysis->overall_score,
            'competency_scores' => $analysis->competency_scores,
            'behavior_analysis' => $analysis->behavior_analysis,
            'red_flag_analysis' => $redFlagAnalysis,
            'culture_fit' => $analysis->culture_fit,
            'decision_snapshot' => $analysis->decision_snapshot,
            'question_analyses' => $analysis->question_analyses,
            'suggested_actions' => $suggestedActions,
            'ai_model' => $analysis->ai_model,
            'ai_provider' => $analysis->ai_model_version,
            'analyzed_at' => $analysis->analyzed_at,
        ];
    }

    /**
     * Map severity string to numeric level.
     */
    private function mapSeverityToLevel(string $severity): int
    {
        return match (strtolower($severity)) {
            'high', 'critical' => 1,
            'medium', 'moderate' => 2,
            default => 3,
        };
    }

    /**
     * Determine overall risk level from flags.
     */
    private function determineRiskLevel(array $flags): string
    {
        if (empty($flags)) {
            return 'none';
        }

        $hasHigh = collect($flags)->contains(fn($f) =>
            in_array(strtolower($f['severity'] ?? ''), ['high', 'critical'])
        );

        if ($hasHigh) {
            return 'high';
        }

        $hasMedium = collect($flags)->contains(fn($f) =>
            in_array(strtolower($f['severity'] ?? ''), ['medium', 'moderate'])
        );

        return $hasMedium ? 'medium' : 'low';
    }
}
