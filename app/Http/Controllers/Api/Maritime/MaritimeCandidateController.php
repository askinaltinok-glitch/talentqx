<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Config\MaritimeRole;
use App\Http\Controllers\Controller;
use App\Jobs\SendCandidateEmailJob;
use App\Models\CandidateTimelineEvent;
use App\Models\FormInterview;
use App\Models\PoolCandidate;
use App\Services\ML\ModelFeatureService;
use App\Services\PoolCandidateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $validator = Validator::make($request->all(), [
            // Basic info
            'first_name' => ['required', 'string', 'max:128'],
            'last_name' => ['required', 'string', 'max:128'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'country_code' => ['required', 'string', 'size:2'],
            'preferred_language' => ['nullable', 'string', 'in:tr,en,ru,az,fil,id,uk'],

            // English self-assessment
            'english_level_self' => ['required', 'string', 'in:' . implode(',', PoolCandidate::ENGLISH_LEVELS)],

            // Maritime-specific
            'rank' => ['required', 'string', 'in:' . implode(',', MaritimeRole::allAcceptedCodes())],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'vessel_types' => ['nullable', 'array'],
            'vessel_types.*' => ['string', 'max:64'],
            'certificates' => ['nullable', 'array'],
            'certificates.*' => ['string', 'in:' . implode(',', self::CERTIFICATE_TYPES)],

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

        // Create maritime candidate with all flags
        $candidate = DB::transaction(function () use ($data) {
            $candidate = PoolCandidate::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'country_code' => $data['country_code'],
                'preferred_language' => $data['preferred_language'] ?? 'en',
                'english_level_self' => $data['english_level_self'],
                'source_channel' => $data['source_channel'],
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

        // Auto-start interview if requested
        if ($data['auto_start_interview'] ?? false) {
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

        return response()->json([
            'success' => true,
            'data' => [
                'candidate_id' => $candidate->id,
                'status' => $candidate->status,
                'status_label' => $this->getStatusLabel($candidate->status),
                'assessment' => $assessmentStatus,
                'next_steps' => $this->getNextSteps($candidate, $latestInterview),
            ],
        ]);
    }

    /**
     * GET /api/maritime/ranks
     *
     * Get available seafarer ranks (for form dropdown).
     */
    public function ranks(Request $request): JsonResponse
    {
        $this->resolveLocale($request);

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
}
