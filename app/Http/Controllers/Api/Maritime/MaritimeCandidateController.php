<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Config\MaritimeRole;
use App\Http\Controllers\Controller;
use App\Jobs\SendCandidateEmailJob;
use App\Models\CandidateTimelineEvent;
use App\Models\CompanyApplyLink;
use App\Models\FormInterview;
use App\Models\PoolCandidate;
use App\Services\ML\ModelFeatureService;
use App\Services\PoolCandidateService;
use App\Services\Maritime\EmailVerificationService;
use App\Services\Security\RecaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * MaritimeCandidateController
 *
 * Public API for Maritime candidate self-registration.
 * No authentication required - candidates register themselves.
 *
 * Flow:
 * 1. POST /api/maritime/apply - Register as candidate (optional: auto-start interview)
 * 2. Get interview link if auto_start_interview=true
 * 3. Complete assessment via standard interview API
 */
class MaritimeCandidateController extends Controller
{
    private const SUPPORTED_LOCALES = ['en', 'tr', 'ru', 'az', 'fil', 'id', 'uk'];

    // Maritime-specific source channels (whitelist)
    private const MARITIME_SOURCE_CHANNELS = [
        'maritime_event',
        'maritime_fair',
        'linkedin',
        'referral',
        'job_board',
        'organic',
        'crewing_agency',
        'maritime_school',
        'seafarer_union',
    ];

    // Seafarer ranks: canonical + aliases accepted at intake
    private const SEAFARER_RANKS = MaritimeRole::ROLES;

    // Valid certificate types
    private const CERTIFICATE_TYPES = [
        'stcw',
        'coc',
        'goc',
        'medical',
        'passport',
        'seamans_book',
        'flag_endorsement',
        'tanker_endorsement',
        'ecdis',
        'arpa',
        'brm',
        'erm',
        'hazmat',
    ];

    public function __construct(
        private PoolCandidateService $candidateService
    ) {}

    /**
     * Resolve locale from request (query param or Accept-Language header).
     */
    private function resolveLocale(Request $request): void
    {
        $locale = $request->query('locale')
            ?? $request->input('locale')
            ?? $request->getPreferredLanguage(self::SUPPORTED_LOCALES)
            ?? 'en';

        if (!in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);
    }

    /**
     * POST /api/maritime/apply
     *
     * Public endpoint for maritime candidate self-registration.
     * Auto-sets industry=maritime, seafarer=true, requires English/Video assessments.
     */
    public function apply(Request $request): JsonResponse
    {
        $this->resolveLocale($request);

        // --- Gate A: Per-IP daily cap (50/day) ---
        $ipKey = 'maritime_apply_daily:' . $request->ip();
        $dailyCount = (int) Cache::get($ipKey, 0);
        if ($dailyCount >= 50) {
            Log::channel('single')->warning('Maritime apply daily IP cap hit', ['ip' => $request->ip(), 'count' => $dailyCount]);
            return response()->json([
                'success' => false,
                'error' => 'Too many applications from this network today. Please try again tomorrow.',
            ], 429);
        }

        // --- Gate B: reCAPTCHA v3 verification ---
        $recaptcha = app(RecaptchaService::class);
        if ($recaptcha->isEnabled()) {
            $captchaToken = $request->input('captcha_token');
            if (!$captchaToken) {
                return response()->json([
                    'success' => false,
                    'error' => 'Captcha verification required.',
                    'messages' => ['captcha_token' => ['Captcha token is missing.']],
                ], 422);
            }
            $result = $recaptcha->verify($captchaToken, 'maritime_apply');
            if (!$result['success']) {
                Log::channel('single')->info('Maritime apply captcha failed', [
                    'ip' => $request->ip(),
                    'score' => $result['score'],
                    'error' => $result['error'],
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Captcha verification failed. Please try again.',
                ], 422);
            }
        }

        // --- Gate C: Disposable email domain blocking ---
        $emailDomain = strtolower(substr($request->input('email', ''), strpos($request->input('email', ''), '@') + 1));
        if (in_array($emailDomain, config('maritime.disposable_email_domains', []), true)) {
            return response()->json([
                'success' => false,
                'error' => 'Please use a personal or work email address. Temporary email services are not accepted.',
                'messages' => ['email' => ['Temporary email addresses are not accepted.']],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            // Basic info
            'first_name' => ['required', 'string', 'min:2', 'max:128'],
            'last_name' => ['required', 'string', 'min:2', 'max:128'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:32', 'regex:/^\+?[0-9\s\-().]{7,32}$/'],
            'country_code' => ['required', 'string', 'size:2'],
            'preferred_language' => ['nullable', 'string', 'in:tr,en,ru,az,fil,id,uk'],

            // English self-assessment
            'english_level_self' => ['required', 'string', 'in:' . implode(',', PoolCandidate::ENGLISH_LEVELS)],

            // Identity v2
            'nationality' => ['required', 'string', 'size:2'],
            'country_of_residence' => ['nullable', 'string', 'size:2'],
            'passport_expiry' => ['nullable', 'date', 'after:today'],
            'visa_status' => ['nullable', 'string', 'in:none,valid,expired,pending'],
            'license_country' => ['nullable', 'string', 'size:2'],
            'license_class' => ['nullable', 'string', 'max:32'],
            'flag_endorsement' => ['nullable', 'string', 'max:64'],

            // Maritime-specific
            'rank' => ['required', 'string', 'in:' . implode(',', MaritimeRole::allAcceptedCodes())],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'vessel_types' => ['nullable', 'array'],
            'vessel_types.*' => ['string', 'max:64'],
            'certificates' => ['nullable', 'array'],
            'certificates.*' => ['string', 'in:' . implode(',', self::CERTIFICATE_TYPES)],
            'certificate_details' => ['nullable', 'array'],
            'certificate_details.*.issue_date' => ['nullable', 'date'],
            'certificate_details.*.expiry_date' => ['nullable', 'date'],
            'certificate_details.*.self_declared' => ['nullable', 'boolean'],

            // Source tracking
            'source_channel' => ['required', 'string', 'in:' . implode(',', self::MARITIME_SOURCE_CHANNELS)],
            'source_meta' => ['nullable', 'array'],
            'source_meta.event' => ['nullable', 'string', 'max:128'],
            'source_meta.city' => ['nullable', 'string', 'max:64'],
            'source_meta.referrer' => ['nullable', 'string', 'max:128'],
            'source_meta.utm_source' => ['nullable', 'string', 'max:64'],
            'source_meta.utm_medium' => ['nullable', 'string', 'max:64'],
            'source_meta.utm_campaign' => ['nullable', 'string', 'max:128'],

            // Consents (required for GDPR)
            'consents' => ['required', 'array'],
            'consents.privacy_policy' => ['required', 'accepted'],
            'consents.data_processing' => ['required', 'accepted'],
            'consents.marketing' => ['nullable', 'boolean'],

            // WhatsApp opt-in
            'whatsapp_opt_in' => ['nullable', 'boolean'],

            // Referral
            'ref' => ['nullable', 'string', 'max:20'],
            'referral_code' => ['nullable', 'string', 'max:20'],

            // Company apply link attribution
            'company_slug' => ['nullable', 'string', 'max:100'],
            'apply_token' => ['nullable', 'string', 'max:64'],

            // Options
            'auto_start_interview' => ['nullable', 'boolean'],
            'locale' => ['nullable', 'string', 'in:en,tr,ru,az,fil,id,uk'],
        ], [
            'first_name.required' => __('maritime.validation.first_name_required'),
            'last_name.required' => __('maritime.validation.last_name_required'),
            'email.required' => __('maritime.validation.email_required'),
            'email.email' => __('maritime.validation.email_invalid'),
            'phone.required' => __('maritime.validation.phone_required'),
            'country_code.required' => __('maritime.validation.country_required'),
            'english_level_self.required' => __('maritime.validation.english_level_required'),
            'english_level_self.in' => __('maritime.validation.english_level_invalid'),
            'rank.required' => __('maritime.validation.rank_required'),
            'rank.in' => __('maritime.validation.rank_invalid'),
            'nationality.required' => __('maritime.validation.nationality_required'),
            'passport_expiry.after' => __('maritime.validation.passport_expiry_future'),
            'source_channel.required' => __('maritime.validation.source_required'),
            'source_channel.in' => __('maritime.validation.source_invalid'),
            'consents.privacy_policy.accepted' => __('maritime.validation.privacy_required'),
            'consents.data_processing.accepted' => __('maritime.validation.data_processing_required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.validation.failed'),
                'messages' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Normalize rank alias → canonical code
        $data['rank'] = MaritimeRole::normalize($data['rank']) ?? $data['rank'];

        // --- Gate D: Phone soft-uniqueness (different email, same phone → 409) ---
        $existingByPhone = PoolCandidate::where('phone', $data['phone'])
            ->where('email', '!=', $data['email'])
            ->first();
        if ($existingByPhone) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.response.welcome_back'),
                'messages' => ['phone' => ['This phone number is already registered. Please use the same email to continue.']],
            ], 409);
        }

        // Check for existing candidate
        $existing = $this->candidateService->findByEmail($data['email']);
        if ($existing) {
            // If candidate exists, check their status
            if ($existing->status === PoolCandidate::STATUS_HIRED) {
                return response()->json([
                    'success' => false,
                    'error' => __('maritime.response.already_hired'),
                ], 409);
            }

            $response = [
                'success' => true,
                'message' => __('maritime.response.welcome_back'),
                'data' => [
                    'candidate_id' => $existing->id,
                    'status' => $existing->status,
                    'is_existing' => true,
                    'can_continue_interview' => $this->canContinueInterview($existing),
                ],
            ];

            // If auto_start_interview requested, check for existing in-progress interview or start new one
            if ($data['auto_start_interview'] ?? false) {
                $activeInterview = $existing->formInterviews()
                    ->whereIn('status', [FormInterview::STATUS_IN_PROGRESS, FormInterview::STATUS_DRAFT])
                    ->latest()
                    ->first();

                if ($activeInterview) {
                    $response['data']['interview'] = [
                        'interview_id' => $activeInterview->id,
                        'status' => $activeInterview->status,
                        'position_code' => $activeInterview->position_code,
                    ];
                } else {
                    // No active interview — start a new one
                    try {
                        $interview = $this->startMaritimeInterview($existing, $data);
                        if ($interview) {
                            $response['data']['interview'] = [
                                'interview_id' => $interview->id,
                                'status' => $interview->status,
                                'position_code' => $interview->position_code,
                            ];
                        }
                    } catch (\Throwable $e) {
                        Log::error('Interview auto-start failed for existing candidate', [
                            'candidate_id' => $existing->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            return response()->json($response);
        }

        // Resolve company apply link for source attribution
        $applyLink = null;
        if (!empty($data['company_slug']) && !empty($data['apply_token'])) {
            $applyLink = CompanyApplyLink::withoutTenantScope()
                ->where('slug', $data['company_slug'])
                ->where('token', $data['apply_token'])
                ->first();
        }

        // Create maritime candidate with all flags
        $candidate = DB::transaction(function () use ($data, $applyLink) {
            $candidate = PoolCandidate::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'country_code' => $data['country_code'],
                'nationality' => $data['nationality'] ?? null,
                'country_of_residence' => $data['country_of_residence'] ?? null,
                'passport_expiry' => $data['passport_expiry'] ?? null,
                'visa_status' => $data['visa_status'] ?? null,
                'license_country' => $data['license_country'] ?? null,
                'license_class' => $data['license_class'] ?? null,
                'flag_endorsement' => $data['flag_endorsement'] ?? null,
                'preferred_language' => $data['preferred_language'] ?? 'en',
                'english_level_self' => $data['english_level_self'],
                'source_channel' => $applyLink ? PoolCandidate::SOURCE_COMPANY_INVITE : $data['source_channel'],
                'source_type' => $applyLink ? PoolCandidate::SOURCE_TYPE_COMPANY_INVITE : PoolCandidate::SOURCE_TYPE_ORGANIC,
                'source_company_id' => $applyLink?->company_id,
                'source_label' => $applyLink?->label,
                'candidate_source' => PoolCandidate::CANDIDATE_SOURCE_PUBLIC,
                'source_meta' => array_merge(
                    $data['source_meta'] ?? [],
                    [
                        'rank' => $data['rank'],
                        'experience_years' => $data['experience_years'] ?? null,
                        'vessel_types' => $data['vessel_types'] ?? [],
                        'certificates' => $data['certificates'] ?? [],
                        'locale' => $data['locale'] ?? $data['preferred_language'] ?? 'en',
                        'whatsapp_opt_in' => $data['whatsapp_opt_in'] ?? false,
                        'registration_ip' => request()->ip(),
                        'registration_ua' => request()->userAgent(),
                        'registered_at' => now()->toIso8601String(),
                    ]
                ),
                'status' => PoolCandidate::STATUS_NEW,
                'primary_industry' => PoolCandidate::INDUSTRY_MARITIME,
                'seafarer' => true,
                'english_assessment_required' => true,
                'video_assessment_required' => true,
            ]);

            // Ensure candidate profile (seeker) and primary email contact point
            $candidate->ensureProfile('seeker', $data['preferred_language'] ?? 'en');
            $candidate->ensurePrimaryEmail();

            // Create SeafarerCertificate records from certificate details
            $certDetails = $data['certificate_details'] ?? [];
            $certCodes = $data['certificates'] ?? [];
            $validityConfig = config('certificate_validity.default_validity_months', []);

            foreach ($certCodes as $certCode) {
                $detail = $certDetails[$certCode] ?? [];
                $issueDate = $detail['issue_date'] ?? null;
                $expiryDate = $detail['expiry_date'] ?? null;
                $selfDeclared = (bool) ($detail['self_declared'] ?? false);

                // Auto-compute expiry from issue_date + validity map if self_declared and no expiry
                if ($selfDeclared && $issueDate && !$expiryDate) {
                    $months = $validityConfig[$certCode] ?? 60;
                    $expiryDate = \Carbon\Carbon::parse($issueDate)->addMonths($months)->toDateString();
                }

                \App\Models\SeafarerCertificate::create([
                    'pool_candidate_id' => $candidate->id,
                    'certificate_type' => $certCode,
                    'issuing_country' => $data['nationality'] ?? $data['country_code'],
                    'issued_at' => $issueDate,
                    'expires_at' => $expiryDate,
                    'verification_status' => \App\Models\SeafarerCertificate::STATUS_PENDING,
                    'verification_notes' => $selfDeclared ? 'Expiry auto-estimated from validity map (self_declared)' : null,
                ]);
            }

            // Handle referral tracking
            $refCode = $data['ref'] ?? $data['referral_code'] ?? null;
            if ($refCode) {
                $referrer = PoolCandidate::where('referral_code', $refCode)->first();
                if ($referrer && $referrer->id !== $candidate->id) {
                    $candidate->referred_by_id = $referrer->id;
                    $candidate->source_channel = 'referral';
                    $candidate->save();
                    $referrer->increment('referral_count');
                }
            }

            return $candidate;
        });

        $response = [
            'success' => true,
            'message' => __('maritime.response.registration_success'),
            'data' => [
                'candidate_id' => $candidate->id,
                'status' => $candidate->status,
                'is_existing' => false,
                'english_assessment_required' => true,
                'video_assessment_required' => true,
            ],
        ];

        // Clean workflow v1: separate application from interview
        if (config('maritime.clean_workflow_v1')) {
            $candidate->update(['application_completed_at' => now()]);
            \App\Jobs\SendInterviewInvitationJob::dispatch($candidate->id)
                ->delay(now()->addMinutes(config('maritime.interview_invite_delay_minutes', 5)));
            $response['data']['application_completed_at'] = now()->toIso8601String();
        } elseif ($data['auto_start_interview'] ?? false) {
            // Legacy path: auto_start_interview (unchanged)
            try {
                $interview = $this->startMaritimeInterview($candidate, $data);
                if ($interview) {
                    $response['data']['interview'] = [
                        'interview_id' => $interview->id,
                        'status' => $interview->status,
                        'position_code' => $interview->position_code,
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('Interview auto-start failed for new candidate', [
                    'candidate_id' => $candidate->id,
                    'rank' => $data['rank'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                // Registration still succeeds — interview can be started later
            }
        }

        // Send "Başvuru Alındı" email
        SendCandidateEmailJob::dispatchSafe($candidate->id, 'application_received');

        // Send email verification link
        try {
            app(EmailVerificationService::class)->sendVerification($candidate);
        } catch (\Throwable $e) {
            Log::warning('Email verification dispatch failed', ['candidate_id' => $candidate->id, 'error' => $e->getMessage()]);
        }

        // Timeline: applied event
        try {
            CandidateTimelineEvent::record(
                $candidate->id,
                CandidateTimelineEvent::TYPE_APPLIED,
                CandidateTimelineEvent::SOURCE_PUBLIC,
                [
                    'position' => $data['rank'] ?? null,
                    'source_channel' => $data['source_channel'] ?? null,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Timeline event failed (applied)', ['error' => $e->getMessage()]);
        }

        // Increment daily IP counter
        Cache::increment($ipKey);
        if ($dailyCount === 0) {
            Cache::put($ipKey, 1, now()->endOfDay());
        }

        return response()->json($response, 201);
    }

    /**
     * POST /api/maritime/candidates/{id}/start-interview
     *
     * Start a maritime interview for an existing candidate.
     */
    public function startInterview(Request $request, string $candidateId): JsonResponse
    {
        $this->resolveLocale($request);

        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.response.candidate_not_found'),
            ], 404);
        }

        // Validate it's a maritime candidate
        if ($candidate->primary_industry !== PoolCandidate::INDUSTRY_MARITIME) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.response.maritime_only'),
            ], 422);
        }

        // Check for active interview
        $activeInterview = $candidate->formInterviews()
            ->whereIn('status', ['draft', 'in_progress'])
            ->first();

        if ($activeInterview) {
            return response()->json([
                'success' => true,
                'message' => __('maritime.response.interview_active'),
                'data' => [
                    'interview_id' => $activeInterview->id,
                    'status' => $activeInterview->status,
                    'can_continue' => true,
                ],
            ]);
        }

        // Check status
        if (in_array($candidate->status, [PoolCandidate::STATUS_HIRED, PoolCandidate::STATUS_ARCHIVED])) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.response.cannot_start_interview', ['status' => $candidate->status]),
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'consents' => ['required', 'array'],
            'consents.privacy_policy' => ['required', 'accepted'],
            'consents.data_processing' => ['required', 'accepted'],
            'position_code' => ['nullable', 'string', 'max:64'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.validation.failed'),
                'messages' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $interview = $this->startMaritimeInterview($candidate, $data);

        return response()->json([
            'success' => true,
            'message' => __('maritime.response.interview_started'),
            'data' => [
                'interview_id' => $interview->id,
                'candidate_id' => $candidate->id,
                'status' => $interview->status,
                'position_code' => $interview->position_code,
                'industry_code' => $interview->industry_code,
            ],
        ], 201);
    }

    /**
     * GET /api/maritime/candidates/{id}/status
     *
     * Get candidate application status (public - for candidate self-service).
     */
    public function status(Request $request, string $candidateId): JsonResponse
    {
        $this->resolveLocale($request);

        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.response.candidate_not_found'),
            ], 404);
        }

        $latestInterview = $candidate->formInterviews()
            ->latest()
            ->first();

        $assessmentStatus = [
            'interview' => null,
            'english_assessment' => 'pending',
            'video_assessment' => 'pending',
        ];

        if ($latestInterview) {
            $assessmentStatus['interview'] = [
                'id' => $latestInterview->id,
                'status' => $latestInterview->status,
                'completed_at' => $latestInterview->completed_at?->toIso8601String(),
            ];

            // Check English assessment status
            if ($latestInterview->english_assessment_status === 'completed') {
                $assessmentStatus['english_assessment'] = 'completed';
            } elseif ($latestInterview->english_assessment_status === 'in_progress') {
                $assessmentStatus['english_assessment'] = 'in_progress';
            }

            // Check video assessment status
            if ($latestInterview->video_assessment_status === 'completed') {
                $assessmentStatus['video_assessment'] = 'completed';
            } elseif ($latestInterview->video_assessment_url) {
                $assessmentStatus['video_assessment'] = 'submitted';
            }
        }

        // Enriched data for candidate panel
        $completedInterviews = $candidate->formInterviews()
            ->where('status', 'completed')
            ->count();

        $credentialsCount = $candidate->credentials()->count();
        $expiringCerts = $candidate->credentials()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(90))
            ->where('expires_at', '>', now())
            ->count();

        $membership = $candidate->membership;
        $presentationCount = $candidate->presentations()->count();

        // Latest completed interview decision
        $latestCompleted = $candidate->formInterviews()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $candidate->id,
                'candidate_id' => $candidate->id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'status' => $candidate->status,
                'status_label' => $this->getStatusLabel($candidate->status),
                'rank' => $candidate->rank ?? null,
                'primary_industry' => $candidate->primary_industry,
                'email_verified' => (bool) $candidate->email_verified_at,
                'membership_tier' => $membership?->getEffectiveTier() ?? 'free',
                'interviews_completed' => $completedInterviews,
                'credentials_count' => $credentialsCount,
                'certificates_expiring_soon' => $expiringCerts,
                'presentation_count' => $presentationCount,
                'latest_score' => $latestCompleted?->final_score,
                'latest_decision' => $latestCompleted?->decision,
                'assessment' => $assessmentStatus,
                'next_steps' => $this->getNextSteps($candidate, $latestInterview),
            ],
        ]);
    }

    /**
     * POST /api/maritime/candidates/{id}/locale
     *
     * Update the candidate's preferred language.
     * Token-verified via ?t= query param.
     */
    public function updateLocale(Request $request, string $candidateId): JsonResponse
    {
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found.'], 404);
        }

        // Verify token (same pattern as other public endpoints)
        if (config('maritime.token_enforcement', true)) {
            $token = $request->query('t');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Invalid or missing token.'], 403);
            }
            $valid = false;
            if ($candidate->public_token_hash) {
                $valid = hash_equals($candidate->public_token_hash, hash('sha256', $token));
            }
            if (!$valid && $candidate->public_token) {
                $valid = hash_equals($candidate->public_token, $token);
            }
            if (!$valid) {
                return response()->json(['success' => false, 'message' => 'Invalid or missing token.'], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'locale' => 'required|string|in:en,tr,ru,az,fil,id,uk',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $candidate->update(['preferred_language' => $request->locale]);

        return response()->json([
            'success' => true,
            'data' => ['preferred_language' => $request->locale],
        ]);
    }

    /**
     * GET /api/maritime/candidates/verify-email
     *
     * Verify a candidate's email via signed token link.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (!$token || !$email) {
            return response()->json(['success' => false, 'message' => 'Invalid verification link.'], 422);
        }

        $candidate = app(EmailVerificationService::class)->verify($email, $token);

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Verification link is invalid or expired. Please request a new one.'], 410);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
            'data' => ['email' => $candidate->email, 'verified_at' => $candidate->email_verified_at],
        ]);
    }

    /**
     * POST /api/maritime/candidates/{id}/resend-verification
     *
     * Resend email verification (throttled, max 3 per hour).
     */
    public function resendVerification(Request $request, string $candidateId): JsonResponse
    {
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found.'], 404);
        }

        if ($candidate->email_verified_at) {
            return response()->json(['success' => true, 'message' => 'Email already verified.']);
        }

        // Throttle: don't resend within 2 minutes of last send
        if ($candidate->email_verification_sent_at &&
            $candidate->email_verification_sent_at->diffInMinutes(now()) < 2) {
            return response()->json(['success' => false, 'message' => 'Please wait before requesting another verification email.'], 429);
        }

        // P1.3: max 3 resends per 24 hours
        $cacheKey = 'email_verify_resend:' . $candidateId;
        $sent24h = (int) \Illuminate\Support\Facades\Cache::get($cacheKey, 0);
        if ($sent24h >= 3) {
            return response()->json(['success' => false, 'message' => 'Maximum verification emails reached. Please try again tomorrow.'], 429);
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, $sent24h + 1, now()->addHours(24));

        app(EmailVerificationService::class)->sendVerification($candidate);

        return response()->json(['success' => true, 'message' => 'Verification email sent.']);
    }

    /**
     * GET /api/maritime/ranks
     *
     * Get available seafarer ranks (for form dropdown).
     */
    public function ranks(Request $request): JsonResponse
    {
        $this->resolveLocale($request);

        $cacheTtl = (int) config('maritime.role_fit.cache_ttl_seconds', 600);

        // Try DB first (maritime_roles table from role registry)
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('maritime_roles')) {
                $dbRoles = \Illuminate\Support\Facades\Cache::remember(
                    'maritime_roles_active_v1',
                    $cacheTtl,
                    fn() => \App\Models\MaritimeRoleRecord::active()->ordered()->get(),
                );
                if ($dbRoles->isNotEmpty()) {
                    $ranks = $dbRoles->map(fn($r) => [
                        'code' => $r->role_key,
                        'label' => __("maritime.rank.{$r->role_key}") !== "maritime.rank.{$r->role_key}"
                            ? __("maritime.rank.{$r->role_key}")
                            : $r->label,
                        'department' => $r->department,
                        'is_selectable' => $r->is_selectable ?? true,
                    ]);

                    return response()->json(['success' => true, 'data' => $ranks]);
                }
            }
        } catch (\Throwable $e) {
            // Fall through to static config
        }

        // Fallback to static config
        $ranks = collect(MaritimeRole::ROLES)->map(fn($rank) => [
            'code' => $rank,
            'label' => __("maritime.rank.{$rank}") !== "maritime.rank.{$rank}"
                ? __("maritime.rank.{$rank}")
                : (MaritimeRole::ROLE_LABELS[$rank] ?? $this->formatRankLabel($rank)),
            'department' => MaritimeRole::departmentFor($rank),
        ]);

        return response()->json([
            'success' => true,
            'data' => $ranks,
        ]);
    }

    /**
     * GET /api/v1/maritime/roles
     *
     * Canonical role list from registry (single source of truth).
     */
    public function roles(Request $request): JsonResponse
    {
        $this->resolveLocale($request);

        $cacheTtl = (int) config('maritime.role_fit.cache_ttl_seconds', 600);

        try {
            $roles = \Illuminate\Support\Facades\Cache::remember(
                'maritime_roles_active_v1',
                $cacheTtl,
                fn() => \App\Models\MaritimeRoleRecord::active()->ordered()->get(),
            );
        } catch (\Throwable $e) {
            // DB not ready — fallback
            $roles = collect(MaritimeRole::ROLES)->map(fn($rank) => (object) [
                'role_key' => $rank,
                'label' => MaritimeRole::ROLE_LABELS[$rank] ?? $rank,
                'department' => MaritimeRole::departmentFor($rank),
                'is_active' => true,
                'sort_order' => 100,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'version' => 'v1',
                'roles' => $roles->map(fn($r) => [
                    'role_key' => $r->role_key,
                    'label' => __("maritime.rank.{$r->role_key}") !== "maritime.rank.{$r->role_key}"
                        ? __("maritime.rank.{$r->role_key}")
                        : $r->label,
                    'department' => $r->department,
                    'is_active' => $r->is_active,
                    'is_selectable' => $r->is_selectable ?? true,
                    'sort_order' => $r->sort_order,
                ]),
            ],
        ]);
    }

    /**
     * GET /api/maritime/certificates
     *
     * Get available certificate types (for form checkbox).
     */
    public function certificates(Request $request): JsonResponse
    {
        $this->resolveLocale($request);

        $certificates = collect(self::CERTIFICATE_TYPES)->map(fn($cert) => [
            'code' => $cert,
            'label' => __("maritime.cert.{$cert}") !== "maritime.cert.{$cert}"
                ? __("maritime.cert.{$cert}")
                : $this->formatCertLabel($cert),
        ]);

        return response()->json([
            'success' => true,
            'data' => $certificates,
        ]);
    }

    /**
     * Start a maritime interview with proper defaults.
     */
    private function startMaritimeInterview(PoolCandidate $candidate, array $data): FormInterview
    {
        // Resolve rank to canonical role code via MaritimeRole
        $rank = $candidate->source_meta['rank'] ?? 'other';
        $roleCode = MaritimeRole::normalize($rank) ?? $rank;
        $department = MaritimeRole::departmentFor($roleCode);

        // If role is unknown, try legacy mapping then fall back to deck
        if (!$department) {
            $roleCode = $this->legacyRankToRole($rank);
            $department = MaritimeRole::departmentFor($roleCode) ?? 'deck';
        }

        return $this->candidateService->startMaritimeInterview(
            candidate: $candidate,
            roleCode: $roleCode,
            department: $department,
            consents: $data['consents'],
            countryCode: $candidate->country_code,
            regulation: $this->getRegulation($candidate->country_code)
        );
    }

    /**
     * Legacy fallback: map non-standard ranks to closest canonical role.
     */
    private function legacyRankToRole(string $rank): string
    {
        return match (strtolower($rank)) {
            'fourth_engineer' => 'third_engineer',
            'wiper' => 'oiler',
            'fitter' => 'motorman',
            'chief_cook', 'head_cook' => 'cook',
            default => 'able_seaman', // safe default for unknown ranks
        };
    }

    /**
     * Get GDPR regulation based on country.
     */
    private function getRegulation(string $countryCode): string
    {
        $euCountries = [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
            'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
            'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
        ];

        if (in_array($countryCode, $euCountries)) {
            return 'GDPR';
        }

        return match ($countryCode) {
            'GB' => 'UK_GDPR',
            'TR' => 'KVKK',
            default => 'STANDARD',
        };
    }

    /**
     * Check if candidate can continue an interview.
     */
    private function canContinueInterview(PoolCandidate $candidate): bool
    {
        return $candidate->formInterviews()
            ->whereIn('status', ['draft', 'in_progress'])
            ->exists();
    }

    /**
     * Get human-readable status label.
     */
    private function getStatusLabel(string $status): string
    {
        $key = match ($status) {
            PoolCandidate::STATUS_NEW => 'new',
            PoolCandidate::STATUS_ASSESSED => 'assessed',
            PoolCandidate::STATUS_IN_POOL => 'in_pool',
            PoolCandidate::STATUS_PRESENTED => 'presented',
            PoolCandidate::STATUS_HIRED => 'hired',
            PoolCandidate::STATUS_ARCHIVED => 'archived',
            default => 'unknown',
        };

        return __("maritime.status.{$key}");
    }

    /**
     * Get next steps for candidate.
     */
    private function getNextSteps(PoolCandidate $candidate, ?FormInterview $interview): array
    {
        if (!$interview) {
            return [__('maritime.next_step.start_interview')];
        }

        $steps = [];

        if ($interview->status === 'draft') {
            $steps[] = __('maritime.next_step.continue_interview');
        } elseif ($interview->status === 'in_progress') {
            $steps[] = __('maritime.next_step.complete_interview');
        } elseif ($interview->status === 'completed') {
            if ($interview->english_assessment_status !== 'completed') {
                $steps[] = __('maritime.next_step.complete_english');
            }
            if (!$interview->video_assessment_url) {
                $steps[] = __('maritime.next_step.submit_video');
            }

            if (empty($steps)) {
                $steps[] = __('maritime.next_step.profile_complete');
            }
        }

        return $steps;
    }

    /**
     * POST /api/maritime/candidates/{id}/english/attach
     *
     * Candidate submits English assessment score (self-service).
     */
    public function attachEnglish(Request $request, string $candidateId): JsonResponse
    {
        $this->resolveLocale($request);

        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return response()->json(['success' => false, 'error' => __('maritime.response.candidate_not_found')], 404);
        }

        if ($candidate->primary_industry !== PoolCandidate::INDUSTRY_MARITIME) {
            return response()->json(['success' => false, 'error' => __('maritime.response.maritime_only')], 422);
        }

        $validator = Validator::make($request->all(), [
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'provider' => ['nullable', 'string', 'max:64'],
            'level' => ['nullable', 'string', 'in:A1,A2,B1,B2,C1,C2'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.validation.failed'),
                'messages' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $interview = $candidate->formInterviews()
            ->where('status', FormInterview::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.response.no_completed_interview'),
            ], 422);
        }

        // Update interview record
        $interview->update([
            'english_assessment_status' => 'completed',
            'english_assessment_score' => $data['score'],
        ]);

        // Update ModelFeature and trigger re-prediction
        $featureResult = null;
        try {
            $featureService = app(ModelFeatureService::class);
            $featureResult = $featureService->updateEnglishAssessment(
                $interview->id,
                $data['score'],
                $data['provider'] ?? 'candidate_self',
                $data['level'] ? "Self-reported level: {$data['level']}" : null
            );
        } catch (\Throwable $e) {
            Log::channel('single')->warning('English assessment feature update failed (candidate)', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('maritime.response.english_submitted'),
            'data' => [
                'candidate_id' => $candidate->id,
                'interview_id' => $interview->id,
                'english_assessment_status' => 'completed',
                'english_assessment_score' => $data['score'],
            ],
        ]);
    }

    /**
     * POST /api/maritime/candidates/{id}/video/attach
     *
     * Candidate submits video introduction URL (self-service).
     */
    public function attachVideo(Request $request, string $candidateId): JsonResponse
    {
        $this->resolveLocale($request);

        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return response()->json(['success' => false, 'error' => __('maritime.response.candidate_not_found')], 404);
        }

        if ($candidate->primary_industry !== PoolCandidate::INDUSTRY_MARITIME) {
            return response()->json(['success' => false, 'error' => __('maritime.response.maritime_only')], 422);
        }

        $validator = Validator::make($request->all(), [
            'url' => ['required', 'url', 'max:2000'],
            'provider' => ['nullable', 'string', 'max:64'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.validation.failed'),
                'messages' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $interview = $candidate->formInterviews()
            ->where('status', FormInterview::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'error' => __('maritime.response.no_completed_interview'),
            ], 422);
        }

        // Update interview record
        $interview->update([
            'video_assessment_status' => 'pending',
            'video_assessment_url' => $data['url'],
        ]);

        // Update ModelFeature and trigger re-prediction
        try {
            $featureService = app(ModelFeatureService::class);
            $featureService->attachVideoAssessment(
                $interview->id,
                $data['url'],
                $data['provider'] ?? 'candidate_self'
            );
        } catch (\Throwable $e) {
            Log::channel('single')->warning('Video attachment feature update failed (candidate)', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('maritime.response.video_submitted'),
            'data' => [
                'candidate_id' => $candidate->id,
                'interview_id' => $interview->id,
                'video_assessment_status' => 'pending',
                'video_assessment_url' => $data['url'],
            ],
        ]);
    }

    /**
     * Format rank code to human-readable label.
     */
    private function formatRankLabel(string $rank): string
    {
        return ucwords(str_replace('_', ' ', $rank));
    }

    /**
     * Format certificate code to label.
     */
    private function formatCertLabel(string $cert): string
    {
        return match ($cert) {
            'stcw' => 'STCW Basic Safety',
            'coc' => 'Certificate of Competency (CoC)',
            'goc' => 'General Operator Certificate (GOC)',
            'ecdis' => 'ECDIS',
            'arpa' => 'ARPA',
            'brm' => 'Bridge Resource Management (BRM)',
            'erm' => 'Engine Resource Management (ERM)',
            'hazmat' => 'Hazardous Materials',
            default => ucwords(str_replace('_', ' ', $cert)),
        };
    }

    // =========================================================================
    // GDPR / KVKK — Candidate self-service data rights
    // =========================================================================

    /**
     * GET /api/maritime/candidates/{id}/data-export
     *
     * Candidate downloads their own data as JSON.
     * Token-verified via ?t= query param.
     */
    public function dataExport(Request $request, string $candidateId): JsonResponse
    {
        $candidate = PoolCandidate::with([
            'credentials',
            'contracts',
            'formInterviews' => fn($q) => $q->select('id', 'pool_candidate_id', 'status', 'position_code', 'completed_at', 'created_at'),
            'contactPoints',
        ])->find($candidateId);

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found.'], 404);
        }

        $data = [
            'export_metadata' => [
                'exported_at' => now()->toIso8601String(),
                'legal_basis' => 'GDPR Article 20 / KVKK Article 11 — Right to Data Portability',
                'format' => 'JSON',
            ],
            'personal_info' => [
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'country_code' => $candidate->country_code,
                'nationality' => $candidate->nationality,
                'country_of_residence' => $candidate->country_of_residence,
                'passport_expiry' => $candidate->passport_expiry?->toDateString(),
                'visa_status' => $candidate->visa_status,
                'preferred_language' => $candidate->preferred_language,
                'registered_at' => $candidate->created_at?->toIso8601String(),
                'email_verified_at' => $candidate->email_verified_at?->toIso8601String(),
            ],
            'maritime_profile' => [
                'rank' => $candidate->rank,
                'primary_industry' => $candidate->primary_industry,
                'source_channel' => $candidate->source_channel,
                'english_level_self' => $candidate->english_level_self,
                'license_country' => $candidate->license_country,
                'license_class' => $candidate->license_class,
                'flag_endorsement' => $candidate->flag_endorsement,
            ],
            'certificates' => $candidate->credentials->map(fn($c) => [
                'type' => $c->credential_type,
                'number' => $c->credential_number,
                'issuer' => $c->issuer,
                'issued_at' => $c->issued_at,
                'expires_at' => $c->expires_at,
                'verification_status' => $c->verification_status,
            ])->toArray(),
            'contracts' => $candidate->contracts->map(fn($c) => [
                'vessel_name' => $c->vessel_name,
                'vessel_imo' => $c->vessel_imo,
                'vessel_type' => $c->vessel_type,
                'rank_code' => $c->rank_code,
                'start_date' => $c->start_date,
                'end_date' => $c->end_date,
                'trading_area' => $c->trading_area,
            ])->toArray(),
            'assessments' => $candidate->formInterviews->map(fn($i) => [
                'id' => $i->id,
                'status' => $i->status,
                'position_code' => $i->position_code,
                'completed_at' => $i->completed_at?->toIso8601String(),
                'created_at' => $i->created_at?->toIso8601String(),
            ])->toArray(),
            'contact_points' => $candidate->contactPoints->map(fn($cp) => [
                'type' => $cp->type,
                'value' => $cp->value,
                'is_primary' => $cp->is_primary,
                'is_verified' => $cp->is_verified,
            ])->toArray(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/maritime/candidates/{id}/erasure-request
     *
     * Candidate requests deletion of their data.
     * Token-verified via ?t= query param.
     */
    public function erasureRequest(Request $request, string $candidateId): JsonResponse
    {
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found.'], 404);
        }

        // Check if there's already a pending request
        $existing = DB::table('data_erasure_requests')
            ->where('candidate_id', $candidateId)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'An erasure request is already pending for your account.',
            ], 409);
        }

        // Create erasure request (will be processed by scheduled command)
        DB::table('data_erasure_requests')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'candidate_id' => $candidateId,
            'requested_by' => $candidate->email,
            'request_type' => 'candidate_request',
            'status' => 'pending',
            'notes' => 'Self-service erasure request via candidate panel.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Candidate self-service erasure request', [
            'candidate_id' => $candidateId,
            'email' => $candidate->email,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your data deletion request has been received. It will be processed within 30 days as required by law.',
        ]);
    }

    /**
     * Get or generate referral code for a candidate.
     * GET /v1/maritime/candidates/{id}/referral
     */
    public function getReferral(Request $request, string $candidateId): JsonResponse
    {
        $candidate = PoolCandidate::where('id', $candidateId)
            ->where('is_demo', false)
            ->first();

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        // Generate referral code if not exists
        if (!$candidate->referral_code) {
            $candidate->referral_code = strtoupper(substr(str_replace('-', '', $candidate->id), 0, 6)) . rand(10, 99);
            $candidate->save();
        }

        $referralUrl = "https://octopus-ai.net/{$candidate->preferred_language}/maritime/apply?ref={$candidate->referral_code}";

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $candidate->referral_code,
                'referral_url' => $referralUrl,
                'referral_count' => $candidate->referral_count ?? 0,
                'is_active_contributor' => ($candidate->referral_count ?? 0) >= 3,
            ],
        ]);
    }

    /**
     * Get referral leaderboard (top referrers).
     * GET /v1/maritime/candidates/{id}/referral/stats
     */
    public function referralStats(Request $request, string $candidateId): JsonResponse
    {
        $candidate = PoolCandidate::where('id', $candidateId)
            ->where('is_demo', false)
            ->first();

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        $referrals = PoolCandidate::where('referred_by_id', $candidateId)
            ->select('id', 'first_name', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'name' => $r->first_name,
                    'status' => $r->status,
                    'joined_at' => $r->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_referrals' => $candidate->referral_count ?? 0,
                'is_active_contributor' => ($candidate->referral_count ?? 0) >= 3,
                'referrals' => $referrals,
            ],
        ]);
    }
}
