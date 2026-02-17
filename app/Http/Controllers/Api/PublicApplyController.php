<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeInterviewJob;
use App\Models\Candidate;
use App\Models\ConsentLog;
use App\Models\Interview;
use App\Models\InterviewResponse;
use App\Models\Job;
use App\Services\Billing\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Public Apply Controller
 *
 * Handles QR-based public job applications without authentication.
 * Flow: Landing → KVKK Consent → Interview → Complete
 */
class PublicApplyController extends Controller
{
    public function __construct(
        private CreditService $creditService
    ) {}

    /**
     * Get job info for landing page.
     * GET /qr-apply/{token}
     */
    public function show(string $token): JsonResponse
    {
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
                    'logoUrl' => $job->company->logo_url,
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
     * Start application - create candidate and interview.
     * POST /qr-apply/{token}
     */
    public function start(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'firstName' => 'nullable|string|min:2|max:50',
            'lastName' => 'nullable|string|min:2|max:50',
            'phone' => 'required|string|min:10|max:20',
            'kvkkConsent' => 'required|boolean',
        ]);

        if (!$validated['kvkkConsent']) {
            return response()->json([
                'success' => false,
                'message' => 'KVKK onayı gereklidir.',
            ], 400);
        }

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
                'message' => 'Bu pozisyon için şu anda başvuru alınamıyor. Lütfen daha sonra tekrar deneyin.',
            ], 422);
        }

        // Normalize phone
        $phone = preg_replace('/\D/', '', $validated['phone']);

        // Check for existing candidate with same phone for this job
        $existingCandidate = Candidate::where('job_id', $job->id)
            ->where('phone', $phone)
            ->first();

        if ($existingCandidate) {
            // Check if they have a pending/in_progress interview
            $existingInterview = Interview::where('candidate_id', $existingCandidate->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->first();

            if ($existingInterview) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'applicationId' => $existingInterview->access_token,
                        'isExisting' => true,
                    ],
                    'message' => 'Devam eden başvurunuz bulundu.',
                ]);
            }

            // If completed, don't allow reapply
            $completedInterview = Interview::where('candidate_id', $existingCandidate->id)
                ->where('status', 'completed')
                ->first();

            if ($completedInterview) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu pozisyona zaten başvurdunuz. Teşekkürler!',
                ], 422);
            }
        }

        // Create candidate
        $candidate = Candidate::create([
            'job_id' => $job->id,
            'first_name' => $validated['firstName'] ?? null,
            'last_name' => $validated['lastName'] ?? null,
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
            'version' => config('kvkk.consent_version', '1.0'),
            'action' => ConsentLog::ACTION_GIVEN,
            'consent_text' => $this->getConsentText(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Create interview
        $interview = Interview::create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'token_expires_at' => now()->addHours(24),
        ]);

        // Start interview immediately
        $interview->start([
            'source' => 'qr_apply',
            'user_agent' => $request->userAgent(),
        ], $request->ip());

        return response()->json([
            'success' => true,
            'data' => [
                'applicationId' => $interview->access_token,
                'isExisting' => false,
            ],
            'message' => 'Başvurunuz kaydedildi.',
        ], 201);
    }

    /**
     * Submit answer.
     * POST /qr-apply/{token}/answers
     */
    public function submitAnswer(Request $request, string $token): JsonResponse
    {
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

        return response()->json([
            'success' => true,
            'message' => 'Başvurunuz tamamlandı. Teşekkür ederiz!',
        ]);
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
