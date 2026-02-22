<?php

namespace App\Http\Controllers\Api;

use App\Config\MaritimeRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFormInterviewRequest;
use App\Models\BehavioralProfile;
use App\Models\Company;
use App\Models\FormInterview;
use App\Models\PoolCandidate;
use App\Services\Consent\ConsentService;
use App\Services\Interview\FormInterviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormInterviewController extends Controller
{
    public function __construct(
        private readonly FormInterviewService $service,
        private readonly ConsentService $consentService
    ) {}

    /**
     * Create a new form interview session
     * POST /v1/form-interviews
     *
     * Required:
     * - version, language, country_code, consents (array)
     *
     * Consents validated per regulation:
     * - KVKK (TR): data_processing + data_retention
     * - GDPR (EU): data_processing + data_retention
     * - GENERIC: data_processing
     */
    public function create(CreateFormInterviewRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Get regulation from FormRequest
        $regulation = $request->getRegulation();

        // ── Industry auto-detect: maritime role → force industry_code=maritime ──
        $position = $data['position_code'] ?? null;
        $industry = $data['industry_code'] ?? null;

        // 1) If position_code is a known maritime role/alias → force maritime
        if ((!$industry || $industry === 'general') && $position && MaritimeRole::isValid($position)) {
            $industry = 'maritime';
        }

        // 2) If pool_candidate is a seafarer → force maritime
        if ((!$industry || $industry === 'general') && !empty($data['pool_candidate_id'])) {
            $cand = PoolCandidate::query()
                ->select('seafarer', 'primary_industry')
                ->find($data['pool_candidate_id']);

            if ($cand && ($cand->seafarer || $cand->primary_industry === 'maritime')) {
                $industry = 'maritime';
            }
        }

        $industry = $industry ?: 'general';

        // 3) Hard guard: maritime industry but unrecognized role → 422 (no silent fallback)
        if ($industry === 'maritime' && $position && $position !== '__generic__') {
            $normalized = MaritimeRole::normalize($position);
            if (!$normalized) {
                return response()->json([
                    'error' => 'unknown_maritime_role',
                    'message' => "Role '{$position}' is not a recognized maritime position. Check MaritimeRole for valid codes.",
                ], 422);
            }
        }

        // Create the interview
        try {
            $interview = $this->service->create(
                $data['version'],
                $data['language'],
                $position ?? '__generic__',
                $data['meta'] ?? [],
                $industry,
                $data['role_code'] ?? null,
                $data['department'] ?? null,
                $data['operation_type'] ?? null
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => match ($e->getMessage()) {
                    'department_required' => 'role_code and department are required for maritime interviews.',
                    'no_template_for_role_department' => 'No interview template found for this role/department combination.',
                    default => $e->getMessage(),
                },
            ], 422);
        }

        // Record consents (array of consent type strings)
        $this->consentService->recordConsents(
            $interview,
            $data['consents'],
            $regulation,
            $request
        );

        return response()->json([
            'id' => $interview->id,
            'status' => $interview->status,
            'version' => $interview->version,
            'language' => $interview->language,
            'position_code' => $interview->position_code,
            'template_position_code' => $interview->template_position_code,
            'industry_code' => $interview->industry_code,
            'template_json_sha256' => $interview->template_json_sha256,
            'consents_recorded' => true,
            'regulation' => $regulation,
            'created_at' => $interview->created_at,
        ], 201);
    }

    /**
     * Get a form interview by ID
     * GET /v1/form-interviews/{id}
     */
    public function show(string $id): JsonResponse
    {
        $interview = FormInterview::with('answers')->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $interview->id,
                'status' => $interview->status,
                'version' => $interview->version,
                'language' => $interview->language,
                'position_code' => $interview->position_code,
                'template_position_code' => $interview->template_position_code,
                'template_json' => $interview->template_json,
                'meta' => $interview->meta,
                'answers' => $interview->answers->map(fn($a) => [
                    'slot' => $a->slot,
                    'competency' => $a->competency,
                    'answer_text' => $a->answer_text,
                ]),
                'competency_scores' => $interview->competency_scores,
                'risk_flags' => $interview->risk_flags,
                'final_score' => $interview->final_score,
                'decision' => $interview->decision,
                'decision_reason' => $interview->decision_reason,
                'completed_at' => $interview->completed_at,
                'created_at' => $interview->created_at,
                'updated_at' => $interview->updated_at,
            ],
        ]);
    }

    /**
     * Add or update answers for a form interview
     * POST /v1/form-interviews/{id}/answers
     */
    public function addAnswers(Request $request, string $id): JsonResponse
    {
        $interview = FormInterview::findOrFail($id);

        if ($interview->isCompleted()) {
            return response()->json([
                'error' => 'Interview already completed',
                'message' => 'Cannot add answers to a completed interview',
            ], 400);
        }

        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.slot' => ['required', 'integer', 'min:1', 'max:8'],
            'answers.*.competency' => ['required', 'string', 'max:64'],
            'answers.*.answer_text' => ['required', 'string', 'min:1'],
        ]);

        $this->service->upsertAnswers($interview, $data['answers']);

        $interview->refresh();

        return response()->json([
            'ok' => true,
            'interview_id' => $interview->id,
            'answers_count' => $interview->answers()->count(),
        ]);
    }

    /**
     * Complete the interview and calculate scores
     * POST /v1/form-interviews/{id}/complete
     */
    public function complete(string $id): JsonResponse
    {
        $interview = FormInterview::with('answers')->findOrFail($id);

        if ($interview->isCompleted()) {
            return response()->json([
                'error' => 'Interview already completed',
                'message' => 'This interview has already been completed',
            ], 400);
        }

        $scored = $this->service->completeAndScore($interview);

        return response()->json([
            'id' => $scored->id,
            'status' => $scored->status,
            'final_score' => $scored->final_score,
            'decision' => $scored->decision,
            'decision_reason' => $scored->decision_reason,
            'competency_scores' => $scored->competency_scores,
            'risk_flags' => $scored->risk_flags,
            'completed_at' => $scored->completed_at,
        ]);
    }

    /**
     * Customer-facing Decision Packet PDF download.
     * GET /v1/form-interviews/{id}/decision-packet.pdf
     *
     * Auth: sanctum + customer.scope (tenant-scoped)
     * Tenant check: interview.company_id == user.company_id
     */
    public function decisionPacketPdf(Request $request, string $id)
    {
        $user = $request->user();
        $interview = FormInterview::with(['answers', 'outcome', 'behavioralProfile'])->find($id);

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Interview not found',
            ], 404);
        }

        if (!$interview->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Decision packet only available for completed interviews',
            ], 400);
        }

        // Tenant check: company_id must match authenticated user's company
        if (!$user->is_platform_admin) {
            if (!$interview->company_id || $interview->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }
        }

        // Build checksum for integrity verification
        $packetData = [
            'id' => $interview->id,
            'final_score' => $interview->final_score,
            'decision' => $interview->decision,
            'template_sha256' => $interview->template_json_sha256,
            'completed_at' => $interview->completed_at?->toIso8601String(),
        ];
        $checksum = hash('sha256', json_encode($packetData));

        $generatedAt = now()->toIso8601String();
        $generatedBy = $user->email ?? 'customer';

        // Behavioral data for PDF
        $company = $user->company_id ? Company::find($user->company_id) : null;
        $behavioralPdf = $this->behavioralSnapshotForPortal($interview, $company);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.decision-packet', [
            'interview' => $interview,
            'outcome' => $interview->outcome,
            'checksum' => $checksum,
            'generatedAt' => $generatedAt,
            'generatedBy' => $generatedBy,
            'behavioralSnapshot' => $behavioralPdf,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);

        $filename = sprintf(
            'decision-packet-%s-%s.pdf',
            $interview->id,
            now()->format('Ymd-His')
        );

        return $pdf->download($filename);
    }

    /**
     * Get the score/decision for a completed interview
     * GET /v1/form-interviews/{id}/score
     */
    public function score(Request $request, string $id): JsonResponse
    {
        $interview = FormInterview::with('behavioralProfile')->findOrFail($id);

        if (!$interview->isCompleted()) {
            return response()->json([
                'error' => 'Interview not completed',
                'message' => 'Please complete the interview first',
            ], 400);
        }

        $data = [
            'id' => $interview->id,
            'status' => $interview->status,
            'final_score' => $interview->final_score,
            'decision' => $interview->decision,
            'decision_reason' => $interview->decision_reason,
            'competency_scores' => $interview->competency_scores,
            'risk_flags' => $interview->risk_flags,
            'completed_at' => $interview->completed_at,
        ];

        // Behavioral snapshot: gated by company setting
        $company = $request->user()?->company_id
            ? Company::find($request->user()->company_id)
            : null;

        $behavioralSnapshot = $this->behavioralSnapshotForPortal($interview, $company);
        if ($behavioralSnapshot !== null) {
            $data['behavioral_snapshot'] = $behavioralSnapshot;
        }

        // AIS trust evidence: gated by company setting
        if ($company?->showTrustEvidence()) {
            $poolCandidate = $interview->poolCandidate;
            if ($poolCandidate) {
                $contracts = $poolCandidate->contracts()
                    ->with('aisVerification')
                    ->get();
                $aisKpis = \App\Presenters\CandidateContractAisPresenter::aggregateKpis($contracts);
                $aisFlags = [];
                if ($aisKpis['total_contracts'] - $aisKpis['with_imo'] > 0) {
                    $aisFlags[] = 'AIS_MISSING_IMO';
                }
                if ($aisKpis['failed'] > 0) {
                    $aisFlags[] = 'AIS_DATA_ANOMALY';
                }
                $data['ais_trust'] = [
                    'ais_verified_contracts_count' => $aisKpis['verified'],
                    'total_contracts' => $aisKpis['total_contracts'],
                    'ais_flags' => $aisFlags,
                ];
            }
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Build public-safe behavioral snapshot for HR portal.
     * Default (show_behavioral_details=false): no dimension names, only fit/confidence/flags.
     * Detailed (show_behavioral_details=true): full dimensions included.
     */
    private function behavioralSnapshotForPortal(FormInterview $interview, ?Company $company): ?array
    {
        $profile = $interview->behavioralProfile;
        if (!$profile || $profile->status !== BehavioralProfile::STATUS_FINAL) {
            return null;
        }

        $showDetails = $company?->showBehavioralDetails() ?? false;

        // Top-3 fit (always shown)
        $fitTop3 = null;
        if ($profile->fit_json) {
            $fitTop3 = collect($profile->fit_json)
                ->map(fn($v, $k) => [
                    'class' => $k,
                    'fit' => $v['normalized_fit'] ?? 0,
                    'risk_flag' => $v['risk_flag'] ?? false,
                    'friction_flag' => $v['friction_flag'] ?? false,
                ])
                ->sortByDesc('fit')
                ->take(3)
                ->values()
                ->toArray();
        }

        $snapshot = [
            'status' => $profile->status,
            'confidence' => (float) $profile->confidence,
            'fit_top3' => $fitTop3,
            'flags' => $profile->flags_json,
            'computed_at' => $profile->computed_at?->toIso8601String(),
        ];

        // If company opted in, include full dimensions
        if ($showDetails) {
            $snapshot['dimensions'] = $profile->dimensions_json;
        }

        return $snapshot;
    }
}
