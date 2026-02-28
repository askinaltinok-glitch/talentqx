<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeInterviewJob;
use App\Jobs\SendQrApplyInterviewReminderJob;
use App\Jobs\TranscribeVoiceAnswerJob;
use App\Mail\QrApplyCompletedMail;
use App\Mail\QrApplyContinueMail;
use App\Mail\QrApplyInterviewScheduledMail;
use App\Models\Candidate;
use App\Models\CompanyApplyLink;
use App\Models\ConsentLog;
use App\Models\Interview;
use App\Models\InterviewResponse;
use App\Models\Job;
use App\Models\VoiceTranscription;
use App\Services\AdminNotificationService;
use App\Services\Billing\CreditService;
use App\Services\QrApply\QrApplyEmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Public Apply Controller
 *
 * Handles QR-based public job applications without authentication.
 * Flow: Landing → KVKK Consent → Email Verification → Schedule → Interview → Complete
 */
class PublicApplyController extends Controller
{
    public function __construct(
        private CreditService $creditService,
        private QrApplyEmailVerificationService $verificationService,
    ) {}

    /**
     * Resolve the correct DB connection for a company apply link slug.
     */
    private function resolveConnectionForSlug(string $slug): void
    {
        if (DB::table('company_apply_links')->where('slug', $slug)->exists()) {
            return;
        }

        if (config('database.connections.mysql_talentqx') &&
            DB::connection('mysql_talentqx')->table('company_apply_links')->where('slug', $slug)->exists()) {
            config(['database.default' => 'mysql_talentqx']);
            DB::purge('mysql');
        }
    }

    /**
     * Get company positions for a company apply link slug.
     * Public endpoint: GET /qr-apply/company/{slug}
     */
    public function companyPositions(string $slug): JsonResponse
    {
        $this->resolveConnectionForSlug($slug);

        // Look up without tenant scope (public endpoint)
        $link = CompanyApplyLink::withoutTenantScope()
            ->where('slug', $slug)
            ->first();

        if (!$link || !$link->isValid()) {
            return response()->json([
                'success' => false,
                'code' => 'invalid_link',
                'message' => 'Bu bağlantı geçersiz veya süresi dolmuş.',
            ], 404);
        }

        // Increment click count
        $link->incrementClicks();

        $company = $link->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'code' => 'invalid_link',
                'message' => 'Bu bağlantı geçersiz.',
            ], 404);
        }

        // Get active jobs for this company
        $jobs = Job::withoutTenantScope()
            ->where('company_id', $company->id)
            ->active()
            ->with('branch')
            ->orderBy('created_at', 'desc')
            ->get();

        // Resolve frontend domain from company's platform
        $platform = $company->platform ?? 'talentqx';
        $frontendBase = match ($platform) {
            'octopus' => 'https://octopus-ai.net',
            default   => 'https://app.talentqx.com',
        };

        $positions = $jobs->map(fn(Job $job) => [
            'id' => $job->id,
            'title' => $job->title,
            'description' => $job->description,
            'location' => $job->location ?? ($job->branch ? "{$job->branch->district}, {$job->branch->city}" : null),
            'employment_type' => $job->employment_type,
            'public_token' => $job->public_token,
            'apply_url' => "{$frontendBase}/i/{$job->public_token}",
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'company' => [
                    'name' => $company->name,
                    'logo_url' => $company->getLogoUrl(),
                    'brand_primary_color' => $company->getBrandColor(),
                ],
                'hasActivePositions' => $positions->isNotEmpty(),
                'positions' => $positions->values(),
            ],
        ]);
    }

    /**
     * Resolve the correct DB connection for a public_token.
     * Checks default DB first, then mysql_talentqx.
     */
    private function resolveConnectionForToken(string $token): void
    {
        // Already on the correct connection if found in default
        if (DB::table('job_postings')->where('public_token', $token)->exists()) {
            return;
        }

        // Try talentqx database
        if (config('database.connections.mysql_talentqx') &&
            DB::connection('mysql_talentqx')->table('job_postings')->where('public_token', $token)->exists()) {
            config(['database.default' => 'mysql_talentqx']);
            DB::purge('mysql');
        }
    }

    /**
     * Mask email for display: o***@email.com
     */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $masked = substr($local, 0, 1) . str_repeat('*', max(strlen($local) - 1, 2));
        return $masked . '@' . $domain;
    }

    /**
     * Resolve the correct DB connection for an access_token (interview token).
     */
    private function resolveConnectionForAccessToken(string $accessToken): void
    {
        if (DB::table('interviews')->where('access_token', $accessToken)->exists()) {
            return;
        }

        if (config('database.connections.mysql_talentqx') &&
            DB::connection('mysql_talentqx')->table('interviews')->where('access_token', $accessToken)->exists()) {
            config(['database.default' => 'mysql_talentqx']);
            DB::purge('mysql');
            return;
        }

        if (config('database.default') !== 'mysql' &&
            DB::connection('mysql')->table('interviews')->where('access_token', $accessToken)->exists()) {
            config(['database.default' => 'mysql']);
            DB::purge('mysql_talentqx');
        }
    }

    /**
     * Resume interview from access_token link.
     * GET /qr-apply/resume/{accessToken}
     */
    public function resume(string $accessToken): JsonResponse
    {
        $this->resolveConnectionForAccessToken($accessToken);

        $interview = Interview::with(['candidate', 'job.company', 'job.questions', 'job.branch', 'responses'])
            ->where('access_token', $accessToken)
            ->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Başvuru bulunamadı veya link geçersiz.',
            ], 404);
        }

        $job = $interview->job;
        $candidate = $interview->candidate;

        $status = $interview->status;
        if ($status !== Interview::STATUS_COMPLETED && !$interview->isTokenValid()) {
            $status = 'expired';
        }

        $questions = $job->questions->map(fn($q) => [
            'id' => $q->id,
            'questionId' => $q->question_order ?? $q->id,
            'group' => $q->category ?? 'A',
            'textTr' => $q->question_text,
            'text' => $q->question_text,
            'dimension' => $q->dimension ?? 'general',
        ]);

        $answeredQuestionIds = $interview->responses->pluck('question_id')->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $status,
                'emailVerified' => $interview->isEmailVerified(),
                'applicationId' => $interview->access_token,
                'job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                    'description' => $job->description,
                    'tenant' => [
                        'id' => $job->company->id,
                        'name' => $job->company->name,
                        'logoUrl' => $job->company->getLogoUrl(),
                        'primaryColor' => $job->company->primary_color ?? '#2563eb',
                    ],
                    'role' => [
                        'id' => $job->id,
                        'name' => $job->title,
                        'nameTr' => $job->title,
                    ],
                    'branch' => $job->branch ? [
                        'id' => $job->branch->id,
                        'name' => $job->branch->name,
                        'city' => $job->branch->city,
                        'district' => $job->branch->district,
                    ] : null,
                    'questions' => $questions,
                ],
                'answeredQuestionIds' => $answeredQuestionIds,
                'scheduledAt' => $interview->scheduled_at?->toIso8601String(),
                'maskedEmail' => $candidate ? $this->maskEmail($candidate->email) : null,
                'publicToken' => $job->public_token,
            ],
        ]);
    }

    /**
     * Get job info for landing page.
     * GET /qr-apply/{token}
     */
    public function show(string $token): JsonResponse
    {
        $this->resolveConnectionForToken($token);

        $job = Job::with(['company', 'questions', 'branch'])
            ->where('public_token', $token)
            ->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'İş ilanı bulunamadı veya link geçersiz.',
            ], 404);
        }

        if (!$job->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan için başvurular kapatılmıştır.',
            ], 403);
        }

        // Format questions for frontend
        $questions = $job->questions->map(fn($q) => [
            'id' => $q->id,
            'questionId' => $q->question_order ?? $q->id,
            'group' => $q->category ?? 'A',
            'textTr' => $q->question_text,
            'text' => $q->question_text,
            'dimension' => $q->dimension ?? 'general',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'title' => $job->title,
                'description' => $job->description,
                'tenant' => [
                    'id' => $job->company->id,
                    'name' => $job->company->name,
                    'logoUrl' => $job->company->getLogoUrl(),
                    'primaryColor' => $job->company->primary_color ?? '#2563eb',
                ],
                'role' => [
                    'id' => $job->id,
                    'name' => $job->title,
                    'nameTr' => $job->title,
                ],
                'branch' => $job->branch ? [
                    'id' => $job->branch->id,
                    'name' => $job->branch->name,
                    'city' => $job->branch->city,
                    'district' => $job->branch->district,
                ] : null,
                'questions' => $questions,
            ],
        ]);
    }

    /**
     * Start application - create candidate and interview (status stays pending).
     * POST /qr-apply/{token}
     */
    public function start(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'firstName' => 'required|string|min:2|max:50',
            'lastName' => 'required|string|min:2|max:50',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|min:10|max:20',
            'kvkkConsent' => 'required|boolean',
        ]);

        if (!$validated['kvkkConsent']) {
            return response()->json([
                'success' => false,
                'message' => 'KVKK onayı gereklidir.',
            ], 400);
        }

        $this->resolveConnectionForToken($token);

        $job = Job::with(['company', 'questions'])
            ->where('public_token', $token)
            ->first();

        if (!$job || !$job->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu ilan için başvuru alınamıyor.',
            ], 403);
        }

        // Credit check: verify company has available credits
        if (!$this->creditService->canUseCredit($job->company)) {
            return response()->json([
                'success' => false,
                'code' => 'credits_exhausted',
                'message' => 'Interview quota exhausted. Please contact support.',
            ], 402);
        }

        // Normalize phone
        $phone = preg_replace('/\D/', '', $validated['phone']);

        // Check for existing candidate with same email or phone for this job
        $existingCandidate = Candidate::where('job_id', $job->id)
            ->where(function ($q) use ($phone, $validated) {
                $q->where('phone', $phone)
                  ->orWhere('email', $validated['email']);
            })
            ->first();

        if ($existingCandidate) {
            // If completed, don't allow reapply
            $completedInterview = Interview::where('candidate_id', $existingCandidate->id)
                ->where('status', 'completed')
                ->first();

            if ($completedInterview) {
                return response()->json([
                    'success' => false,
                    'code' => 'already_completed',
                    'message' => 'Bu pozisyona zaten başvurdunuz. Teşekkürler!',
                ], 422);
            }

            // Check for active (pending/in_progress) interview created today
            $existingInterview = Interview::where('candidate_id', $existingCandidate->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->whereDate('created_at', now()->toDateString())
                ->first();

            if ($existingInterview) {
                // Send "continue your application" email
                Mail::to($existingCandidate->email)
                    ->queue(new QrApplyContinueMail($existingCandidate, $job, $existingInterview));

                return response()->json([
                    'success' => false,
                    'code' => 'already_applied_today',
                    'message' => 'Bugün bu pozisyon için başvurunuz alınmıştır.',
                    'data' => [
                        'maskedEmail' => $this->maskEmail($existingCandidate->email),
                    ],
                ], 422);
            }
        }

        // Create candidate
        $candidate = Candidate::create([
            'company_id' => $job->company_id,
            'branch_id' => $job->branch_id ?? null,
            'job_id' => $job->id,
            'first_name' => $validated['firstName'] ?? null,
            'last_name' => $validated['lastName'] ?? null,
            'email' => $validated['email'],
            'phone' => $phone,
            'status' => 'new',
            'source' => 'qr_apply',
            'consent_given' => true,
            'consent_version' => config('kvkk.consent_version', '1.0'),
            'consent_given_at' => now(),
            'consent_ip' => $request->ip(),
        ]);

        // Log consent
        ConsentLog::create([
            'candidate_id' => $candidate->id,
            'consent_type' => ConsentLog::TYPE_KVKK,
            'consent_version' => config('kvkk.consent_version', '1.0'),
            'action' => ConsentLog::ACTION_GIVEN,
            'consent_text' => $this->getConsentText(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Create interview — status stays 'pending', NOT started yet
        $interview = Interview::create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'token_expires_at' => now()->addHours(24),
        ]);

        // Send verification code instead of starting interview
        $this->verificationService->sendCode($interview, $candidate, $job);

        return response()->json([
            'success' => true,
            'data' => [
                'applicationId' => $interview->access_token,
                'isExisting' => false,
                'requiresVerification' => true,
                'maskedEmail' => $this->maskEmail($validated['email']),
            ],
            'message' => 'Başvurunuz kaydedildi.',
        ], 201);
    }

    /**
     * Verify email with 6-digit OTP code.
     * POST /qr-apply/{token}/verify-email
     */
    public function verifyEmail(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'applicationId' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $this->resolveConnectionForToken($token);

        $job = Job::where('public_token', $token)->first();
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz başvuru linki.',
            ], 404);
        }

        $interview = Interview::where('access_token', $validated['applicationId'])
            ->where('job_id', $job->id)
            ->whereIn('status', [Interview::STATUS_PENDING, Interview::STATUS_IN_PROGRESS])
            ->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Başvuru bulunamadı.',
            ], 404);
        }

        $candidate = $interview->candidate;
        $result = $this->verificationService->verifyCode($interview, $candidate, $validated['code']);

        if ($result === true) {
            return response()->json([
                'success' => true,
                'data' => ['emailVerified' => true],
                'message' => 'E-posta doğrulandı.',
            ]);
        }

        $messages = [
            'invalid_code' => 'Geçersiz doğrulama kodu.',
            'code_expired' => 'Doğrulama kodunun süresi doldu. Lütfen yeni kod isteyin.',
            'max_attempts_exceeded' => 'Deneme hakkınız tükendi. Lütfen yeni kod isteyin.',
        ];

        $interview->refresh();

        return response()->json([
            'success' => false,
            'code' => strtoupper($result),
            'message' => $messages[$result] ?? 'Doğrulama hatası.',
            'data' => [
                'attemptsLeft' => max(0, 5 - $interview->email_verification_attempts),
            ],
        ], 422);
    }

    /**
     * Resend verification code.
     * POST /qr-apply/{token}/resend-code
     */
    public function resendCode(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'applicationId' => 'required|string',
        ]);

        $this->resolveConnectionForToken($token);

        $job = Job::with('company')->where('public_token', $token)->first();
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz başvuru linki.',
            ], 404);
        }

        $interview = Interview::where('access_token', $validated['applicationId'])
            ->where('job_id', $job->id)
            ->where('status', Interview::STATUS_PENDING)
            ->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Başvuru bulunamadı.',
            ], 404);
        }

        $candidate = $interview->candidate;

        // sendCode generates new code and resets attempts
        $this->verificationService->sendCode($interview, $candidate, $job);

        return response()->json([
            'success' => true,
            'data' => [
                'maskedEmail' => $this->maskEmail($candidate->email),
            ],
            'message' => 'Yeni doğrulama kodu gönderildi.',
        ]);
    }

    /**
     * Schedule interview (start now or pick future date).
     * POST /qr-apply/{token}/schedule
     */
    public function schedule(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'applicationId' => 'required|string',
            'startNow' => 'required|boolean',
            'scheduledAt' => 'nullable|date|after:+29 minutes|before:+7 days',
        ]);

        $this->resolveConnectionForToken($token);

        $job = Job::with('company')->where('public_token', $token)->first();
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz başvuru linki.',
            ], 404);
        }

        $interview = Interview::where('access_token', $validated['applicationId'])
            ->where('job_id', $job->id)
            ->where('status', Interview::STATUS_PENDING)
            ->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Başvuru bulunamadı.',
            ], 404);
        }

        // Guard: email must be verified
        if (!$interview->isEmailVerified()) {
            return response()->json([
                'success' => false,
                'code' => 'EMAIL_NOT_VERIFIED',
                'message' => 'E-posta doğrulaması gereklidir.',
            ], 403);
        }

        $candidate = $interview->candidate;

        if ($validated['startNow']) {
            // Start interview immediately
            $interview->start([
                'source' => 'qr_apply',
                'user_agent' => $request->userAgent(),
            ], $request->ip());

            return response()->json([
                'success' => true,
                'data' => ['startNow' => true],
                'message' => 'Mülakat başlatıldı.',
            ]);
        }

        // Schedule for later
        $scheduledAt = \Carbon\Carbon::parse($validated['scheduledAt']);

        $interview->update([
            'scheduled_at' => $scheduledAt,
        ]);

        // Send scheduled confirmation email
        if ($candidate->email) {
            try {
                Mail::to($candidate->email)
                    ->queue(new QrApplyInterviewScheduledMail($candidate, $job, $interview));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Dispatch reminder job (1 hour before)
        $reminderAt = $scheduledAt->copy()->subHour();
        if ($reminderAt->isFuture()) {
            SendQrApplyInterviewReminderJob::dispatch($interview)->delay($reminderAt);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'scheduled' => true,
                'scheduledAt' => $scheduledAt->toIso8601String(),
            ],
            'message' => 'Mülakatınız planlandı.',
        ]);
    }

    /**
     * Submit answer.
     * POST /qr-apply/{token}/answers
     */
    public function submitAnswer(Request $request, string $token): JsonResponse
    {
        $this->resolveConnectionForToken($token);

        $validated = $request->validate([
            'applicationId' => 'required|string',
            'questionId' => 'required|uuid|exists:job_questions,id',
            'textResponse' => 'required|string|min:20|max:5000',
        ]);

        // Verify job token matches
        $job = Job::where('public_token', $token)->first();
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz başvuru linki.',
            ], 404);
        }

        $interview = Interview::where('access_token', $validated['applicationId'])
            ->where('job_id', $job->id)
            ->whereIn('status', [Interview::STATUS_PENDING, Interview::STATUS_IN_PROGRESS])
            ->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Başvuru bulunamadı veya zaten tamamlandı.',
            ], 404);
        }

        // Guard: email must be verified before answering
        if (!$interview->isEmailVerified()) {
            return response()->json([
                'success' => false,
                'code' => 'EMAIL_NOT_VERIFIED',
                'message' => 'E-posta doğrulaması gereklidir.',
            ], 403);
        }

        // Ensure interview is in progress
        if ($interview->status === Interview::STATUS_PENDING) {
            $interview->start([
                'source' => 'qr_apply',
                'user_agent' => $request->userAgent(),
            ], $request->ip());
        }

        // Check if already answered - if so, update it
        $existing = InterviewResponse::where('interview_id', $interview->id)
            ->where('question_id', $validated['questionId'])
            ->first();

        if ($existing) {
            $existing->update([
                'transcript' => $validated['textResponse'],
                'ended_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Yanıt güncellendi.',
            ]);
        }

        $responseOrder = InterviewResponse::where('interview_id', $interview->id)->count() + 1;

        InterviewResponse::create([
            'interview_id' => $interview->id,
            'question_id' => $validated['questionId'],
            'response_order' => $responseOrder,
            'transcript' => $validated['textResponse'],
            'transcript_confidence' => 1.0000,
            'duration_seconds' => 60, // Default
            'started_at' => now()->subSeconds(60),
            'ended_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Yanıt kaydedildi.',
        ]);
    }

    /**
     * Complete interview.
     * POST /qr-apply/{token}/complete
     */
    public function complete(Request $request, string $token): JsonResponse
    {
        $this->resolveConnectionForToken($token);

        $validated = $request->validate([
            'applicationId' => 'required|string',
        ]);

        // Verify job token matches
        $job = Job::with('company')->where('public_token', $token)->first();
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz başvuru linki.',
            ], 404);
        }

        $interview = Interview::where('access_token', $validated['applicationId'])
            ->where('job_id', $job->id)
            ->whereIn('status', [Interview::STATUS_PENDING, Interview::STATUS_IN_PROGRESS])
            ->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Başvuru bulunamadı veya zaten tamamlandı.',
            ], 404);
        }

        $interview->complete();

        // Deduct credit when interview is completed
        $this->creditService->deductCredit($job->company, $interview);

        // Trigger AI analysis
        AnalyzeInterviewJob::dispatch($interview);

        // Send completion email to candidate
        $candidate = $interview->candidate;
        if ($candidate && $candidate->email) {
            try {
                Mail::to($candidate->email)
                    ->queue(new QrApplyCompletedMail($candidate, $job));

                $interview->update(['completion_email_sent_at' => now()]);

                app(AdminNotificationService::class)->notifyEmailSent(
                    'qr_apply_completed',
                    $candidate->email,
                    "QR Apply completed: {$candidate->first_name} {$candidate->last_name}",
                    ['candidate_id' => $candidate->id, 'job_id' => $job->id]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Başvurunuz tamamlandı. Teşekkür ederiz!',
        ]);
    }

    // ── Voice Answer Endpoints ─────────────────────────────────────────

    private const ALLOWED_AUDIO_MIMES = [
        'audio/webm', 'video/webm', 'audio/wav', 'audio/x-wav', 'audio/mpeg',
        'audio/mp3', 'audio/mp4', 'audio/m4a', 'audio/ogg', 'application/octet-stream',
    ];

    /**
     * Upload voice answer for transcription.
     * POST /qr-apply/{token}/voice-answers
     */
    public function voiceUpload(Request $request, string $token): JsonResponse
    {
        $this->resolveConnectionForToken($token);

        $job = Job::where('public_token', $token)->first();
        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Geçersiz link.'], 404);
        }

        $applicationId = $request->input('application_id') ?? $request->input('applicationId');
        if (!$applicationId) {
            return response()->json(['success' => false, 'message' => 'applicationId required.'], 422);
        }

        $interview = Interview::where('access_token', $applicationId)
            ->where('job_id', $job->id)
            ->whereIn('status', [Interview::STATUS_PENDING, Interview::STATUS_IN_PROGRESS])
            ->first();

        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Interview not found.'], 404);
        }

        $maxMb = config('services.voice.max_upload_mb', 12);

        $validator = Validator::make($request->all(), [
            'slot'        => 'required|integer|min:1|max:25',
            'question_id' => 'required|string|max:80',
            'file'        => "required|file|max:" . ($maxMb * 1024),
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $mime = $file->getMimeType();

        if (!in_array($mime, self::ALLOWED_AUDIO_MIMES, true)) {
            return response()->json([
                'success' => false, 'message' => 'Unsupported audio format.', 'code' => 'INVALID_AUDIO_TYPE',
            ], 422);
        }

        // Remove existing transcription for this slot
        $existing = VoiceTranscription::where('interview_id', $interview->id)
            ->where('slot', $request->slot)
            ->first();

        if ($existing) {
            Storage::delete($existing->audio_path);
            $existing->delete();
        }

        // Duration check
        $durationMs = $this->getAudioDurationMs($file->getRealPath());

        if ($durationMs !== null && $durationMs < 2000) {
            return response()->json([
                'success' => false, 'message' => 'Audio too short.', 'code' => 'AUDIO_TOO_SHORT',
            ], 422);
        }

        // Store audio
        $fileContents = file_get_contents($file->getRealPath());
        $sha256 = hash('sha256', $fileContents);
        $ext = $file->getClientOriginalExtension() ?: 'webm';
        $storagePath = "private/qr-interviews/{$interview->id}/voice/" . Str::uuid() . ".{$ext}";

        Storage::put($storagePath, $fileContents);

        // Create VoiceTranscription record
        $transcription = VoiceTranscription::create([
            'company_id'       => $job->company_id,
            'interview_id'     => $interview->id,
            'candidate_id'     => $interview->candidate_id,
            'question_id'      => $request->question_id,
            'slot'             => (int) $request->slot,
            'audio_path'       => $storagePath,
            'audio_mime'       => $mime,
            'audio_size_bytes' => strlen($fileContents),
            'audio_sha256'     => $sha256,
            'duration_ms'      => $durationMs,
            'provider'         => 'ai_models_panel',
            'model'            => config('services.whisper.model'),
            'language'         => 'tr',
            'status'           => VoiceTranscription::STATUS_PENDING,
        ]);

        TranscribeVoiceAnswerJob::dispatch($transcription->id);

        Log::info('PublicApplyController::voiceUpload: voice queued', [
            'transcription_id' => $transcription->id,
            'interview_id'     => $interview->id,
            'slot'             => $request->slot,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'transcription_id' => $transcription->id,
                'status'           => 'pending',
            ],
        ], 202);
    }

    /**
     * Poll voice transcription status.
     * GET /qr-apply/{token}/voice-answers/{questionId}
     */
    public function voicePoll(Request $request, string $token, string $questionId): JsonResponse
    {
        $this->resolveConnectionForToken($token);

        $job = Job::where('public_token', $token)->first();
        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Geçersiz link.'], 404);
        }

        $applicationId = $request->query('applicationId');
        if (!$applicationId) {
            return response()->json(['success' => false, 'message' => 'applicationId required.'], 422);
        }

        $interview = Interview::where('access_token', $applicationId)
            ->where('job_id', $job->id)
            ->first();

        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Interview not found.'], 404);
        }

        $transcription = VoiceTranscription::where('interview_id', $interview->id)
            ->where('question_id', $questionId)
            ->latest()
            ->first();

        if (!$transcription) {
            return response()->json(['success' => false, 'message' => 'No voice answer found.'], 404);
        }

        $data = [
            'transcription_id' => $transcription->id,
            'status'           => $transcription->status,
            'updated_at'       => $transcription->updated_at->toIso8601String(),
        ];

        if ($transcription->isDone()) {
            $data['transcript_text'] = $transcription->transcript_text;
            $data['confidence']      = $transcription->confidence;
        }

        if ($transcription->isFailed()) {
            $data['error'] = $transcription->error_message;
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function getAudioDurationMs(string $filePath): ?int
    {
        try {
            $process = new \Symfony\Component\Process\Process([
                '/usr/bin/ffprobe', '-v', 'quiet',
                '-show_entries', 'format=duration',
                '-of', 'csv=p=0',
                $filePath,
            ]);
            $process->setTimeout(5);
            $process->run();
            $output = trim($process->getOutput());
            return ($output !== '' && is_numeric($output)) ? (int) round((float) $output * 1000) : null;
        } catch (\Throwable $e) {
            Log::warning('PublicApplyController: ffprobe failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get consent text.
     */
    private function getConsentText(): string
    {
        return "6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamında, " .
            "başvuru sürecinde paylaştığınız kişisel verilerinizin işe alım değerlendirmesi " .
            "amacıyla işlenmesine onay veriyorsunuz.\n\n" .
            "Yanıtlarınız yapay zeka destekli sistemimiz tarafından analiz edilecek ve " .
            "değerlendirme sonuçları işverenle paylaşılacaktır. Nihai işe alım kararı " .
            "insan değerlendirmesi ile verilecektir.";
    }
}
