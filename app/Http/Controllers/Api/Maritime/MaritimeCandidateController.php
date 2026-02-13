<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\FormInterview;
use App\Models\PoolCandidate;
use App\Services\PoolCandidateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    // Seafarer ranks for validation
    private const SEAFARER_RANKS = [
        // Deck Department
        'master',
        'chief_officer',
        'second_officer',
        'third_officer',
        'deck_cadet',
        'bosun',
        'ab_seaman',
        'ordinary_seaman',
        // Engine Department
        'chief_engineer',
        'second_engineer',
        'third_engineer',
        'fourth_engineer',
        'engine_cadet',
        'motorman',
        'oiler',
        'wiper',
        // Hotel/Catering
        'chief_cook',
        'cook',
        'messman',
        'steward',
        // Other
        'electrician',
        'fitter',
        'pumpman',
        'radio_officer',
        'trainee',
        'other',
    ];

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
     * POST /api/maritime/apply
     *
     * Public endpoint for maritime candidate self-registration.
     * Auto-sets industry=maritime, seafarer=true, requires English/Video assessments.
     */
    public function apply(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Basic info
            'first_name' => ['required', 'string', 'max:128'],
            'last_name' => ['required', 'string', 'max:128'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'country_code' => ['required', 'string', 'size:2'],
            'preferred_language' => ['nullable', 'string', 'in:tr,en,ru,fil,id,uk'],

            // English self-assessment
            'english_level_self' => ['required', 'string', 'in:' . implode(',', PoolCandidate::ENGLISH_LEVELS)],

            // Maritime-specific
            'rank' => ['required', 'string', 'in:' . implode(',', self::SEAFARER_RANKS)],
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

            // Options
            'auto_start_interview' => ['nullable', 'boolean'],
        ], [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'phone.required' => 'Phone number is required for maritime candidates.',
            'country_code.required' => 'Country code is required.',
            'english_level_self.required' => 'Please select your English level.',
            'english_level_self.in' => 'Invalid English level. Options: A1, A2, B1, B2, C1, C2.',
            'rank.required' => 'Please select your seafarer rank.',
            'rank.in' => 'Invalid rank selected.',
            'source_channel.required' => 'Source channel is required.',
            'source_channel.in' => 'Invalid source channel.',
            'consents.privacy_policy.accepted' => 'You must accept the privacy policy.',
            'consents.data_processing.accepted' => 'You must consent to data processing.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Check for existing candidate
        $existing = $this->candidateService->findByEmail($data['email']);
        if ($existing) {
            // If candidate exists, check their status
            if ($existing->status === PoolCandidate::STATUS_HIRED) {
                return response()->json([
                    'success' => false,
                    'error' => 'This candidate has already been hired.',
                ], 409);
            }

            // Return existing candidate info
            return response()->json([
                'success' => true,
                'message' => 'Welcome back! Candidate profile found.',
                'data' => [
                    'candidate_id' => $existing->id,
                    'status' => $existing->status,
                    'is_existing' => true,
                    'can_continue_interview' => $this->canContinueInterview($existing),
                ],
            ]);
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
                'source_meta' => array_merge(
                    $data['source_meta'] ?? [],
                    [
                        'rank' => $data['rank'],
                        'experience_years' => $data['experience_years'] ?? null,
                        'vessel_types' => $data['vessel_types'] ?? [],
                        'certificates' => $data['certificates'] ?? [],
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

            return $candidate;
        });

        $response = [
            'success' => true,
            'message' => 'Registration successful. Welcome aboard!',
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
            $interview = $this->startMaritimeInterview($candidate, $data);
            if ($interview) {
                $response['data']['interview'] = [
                    'interview_id' => $interview->id,
                    'status' => $interview->status,
                    'position_code' => $interview->position_code,
                ];
            }
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
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return response()->json([
                'success' => false,
                'error' => 'Candidate not found.',
            ], 404);
        }

        // Validate it's a maritime candidate
        if ($candidate->primary_industry !== PoolCandidate::INDUSTRY_MARITIME) {
            return response()->json([
                'success' => false,
                'error' => 'This endpoint is only for maritime candidates.',
            ], 422);
        }

        // Check for active interview
        $activeInterview = $candidate->formInterviews()
            ->whereIn('status', ['draft', 'in_progress'])
            ->first();

        if ($activeInterview) {
            return response()->json([
                'success' => true,
                'message' => 'Candidate has an active interview.',
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
                'error' => 'Cannot start interview for candidate with status: ' . $candidate->status,
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
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $interview = $this->startMaritimeInterview($candidate, $data);

        return response()->json([
            'success' => true,
            'message' => 'Interview started successfully.',
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
    public function status(string $candidateId): JsonResponse
    {
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return response()->json([
                'success' => false,
                'error' => 'Candidate not found.',
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
    public function ranks(): JsonResponse
    {
        $ranks = collect(self::SEAFARER_RANKS)->map(fn($rank) => [
            'code' => $rank,
            'label' => $this->formatRankLabel($rank),
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
    public function certificates(): JsonResponse
    {
        $certificates = collect(self::CERTIFICATE_TYPES)->map(fn($cert) => [
            'code' => $cert,
            'label' => $this->formatCertLabel($cert),
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
        // Determine position code based on rank
        $rank = $candidate->source_meta['rank'] ?? 'other';
        $positionCode = $data['position_code'] ?? $this->mapRankToPosition($rank);

        return $this->candidateService->startInterview(
            candidate: $candidate,
            positionCode: $positionCode,
            industryCode: PoolCandidate::INDUSTRY_MARITIME,
            consents: $data['consents'],
            countryCode: $candidate->country_code,
            regulation: $this->getRegulation($candidate->country_code)
        );
    }

    /**
     * Map seafarer rank to position code.
     */
    private function mapRankToPosition(string $rank): string
    {
        return match (true) {
            in_array($rank, ['master', 'chief_officer', 'second_officer', 'third_officer']) => 'deck_officer',
            in_array($rank, ['chief_engineer', 'second_engineer', 'third_engineer', 'fourth_engineer']) => 'engineer_officer',
            in_array($rank, ['deck_cadet', 'engine_cadet']) => 'cadet',
            in_array($rank, ['bosun', 'ab_seaman', 'ordinary_seaman']) => 'deck_rating',
            in_array($rank, ['motorman', 'oiler', 'wiper', 'fitter']) => 'engine_rating',
            in_array($rank, ['chief_cook', 'cook', 'messman', 'steward']) => 'catering',
            default => '__maritime_generic__',
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
        return match ($status) {
            PoolCandidate::STATUS_NEW => 'Registered',
            PoolCandidate::STATUS_ASSESSED => 'Assessment Complete',
            PoolCandidate::STATUS_IN_POOL => 'In Talent Pool',
            PoolCandidate::STATUS_PRESENTED => 'Presented to Companies',
            PoolCandidate::STATUS_HIRED => 'Hired',
            PoolCandidate::STATUS_ARCHIVED => 'Archived',
            default => 'Unknown',
        };
    }

    /**
     * Get next steps for candidate.
     */
    private function getNextSteps(PoolCandidate $candidate, ?FormInterview $interview): array
    {
        if (!$interview) {
            return ['Start your assessment interview'];
        }

        $steps = [];

        if ($interview->status === 'draft') {
            $steps[] = 'Continue your interview';
        } elseif ($interview->status === 'in_progress') {
            $steps[] = 'Complete your interview';
        } elseif ($interview->status === 'completed') {
            if ($interview->english_assessment_status !== 'completed') {
                $steps[] = 'Complete English assessment';
            }
            if (!$interview->video_assessment_url) {
                $steps[] = 'Submit video introduction';
            }

            if (empty($steps)) {
                $steps[] = 'Your profile is complete - we will contact you with opportunities';
            }
        }

        return $steps;
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
