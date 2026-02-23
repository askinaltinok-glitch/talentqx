<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormInterview;
use App\Services\Consent\ConsentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminFormInterviewController extends Controller
{
    public function __construct(
        private readonly ConsentService $consentService
    ) {}
    /**
     * List form interviews with filtering.
     *
     * GET /v1/admin/form-interviews
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int)($request->input('per_page', 20)), 100);

        $query = FormInterview::query();

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('decision')) {
            $query->where('decision', strtoupper($request->input('decision')));
        }

        if ($request->filled('position_code')) {
            $query->where('position_code', 'like', '%' . $request->input('position_code') . '%');
        }

        if ($request->filled('language')) {
            $query->where('language', strtolower($request->input('language')));
        }

        if ($request->filled('version')) {
            $query->where('version', $request->input('version'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', '%' . $search . '%')
                  ->orWhere('position_code', 'like', '%' . $search . '%');
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'completed_at', 'final_score', 'status'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderByDesc('created_at');
        }

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(function ($interview) {
            $riskFlagsCount = 0;
            if (is_array($interview->risk_flags)) {
                $riskFlagsCount = count($interview->risk_flags);
            }

            $answersCount = $interview->answers()->count();

            return [
                'id' => $interview->id,
                'version' => $interview->version,
                'language' => $interview->language,
                'position_code' => $interview->position_code,
                'template_position_code' => $interview->template_position_code,
                'status' => $interview->status,
                'final_score' => $interview->final_score,
                'decision' => $interview->decision,
                'risk_flags_count' => $riskFlagsCount,
                'answers_count' => $answersCount,
                'created_at' => $interview->created_at->toIso8601String(),
                'completed_at' => $interview->completed_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Get form interview detail.
     *
     * GET /v1/admin/form-interviews/{id}
     */
    public function show(string $id): JsonResponse
    {
        $interview = FormInterview::with(['answers', 'consents'])->find($id);

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Interview not found',
            ], 404);
        }

        // Get consent status
        $consentStatus = $this->consentService->getConsentStatus($interview);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $interview->id,
                'version' => $interview->version,
                'language' => $interview->language,
                'position_code' => $interview->position_code,
                'template_position_code' => $interview->template_position_code,
                'industry_code' => $interview->industry_code,
                'status' => $interview->status,
                'template_json_sha256' => $interview->template_json_sha256,
                'meta' => $interview->meta,
                'admin_notes' => $interview->admin_notes,
                'competency_scores' => $interview->competency_scores,
                'risk_flags' => $interview->risk_flags,
                'final_score' => $interview->final_score,
                'decision' => $interview->decision,
                'decision_reason' => $interview->decision_reason,
                // Calibration data
                'raw_final_score' => $interview->raw_final_score,
                'calibrated_score' => $interview->calibrated_score,
                'z_score' => $interview->z_score,
                'policy_code' => $interview->policy_code,
                // Consent status
                'consent_status' => $consentStatus,
                // Timestamps
                'created_at' => $interview->created_at->toIso8601String(),
                'updated_at' => $interview->updated_at->toIso8601String(),
                'completed_at' => $interview->completed_at?->toIso8601String(),
                'answers' => $interview->answers->map(fn($a) => [
                    'id' => $a->id,
                    'slot' => $a->slot,
                    'competency' => $a->competency,
                    'question' => $a->question,
                    'answer' => $a->answer,
                    'score' => $a->score,
                    'score_reason' => $a->score_reason,
                    'red_flags' => $a->red_flags,
                    'positive_signals' => $a->positive_signals,
                    'created_at' => $a->created_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Delete form interview.
     *
     * DELETE /v1/admin/form-interviews/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $interview = FormInterview::find($id);

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Interview not found',
            ], 404);
        }

        // Delete answers first
        $interview->answers()->delete();
        $interview->delete();

        return response()->json([
            'success' => true,
            'message' => 'Interview deleted successfully',
        ]);
    }

    /**
     * Get statistics summary.
     *
     * GET /v1/admin/form-interviews/stats
     */
    public function stats(): JsonResponse
    {
        $total = FormInterview::count();
        $completed = FormInterview::where('status', FormInterview::STATUS_COMPLETED)->count();
        $inProgress = FormInterview::where('status', FormInterview::STATUS_IN_PROGRESS)->count();
        $draft = FormInterview::where('status', FormInterview::STATUS_DRAFT)->count();

        $byDecision = FormInterview::where('status', FormInterview::STATUS_COMPLETED)
            ->selectRaw('decision, COUNT(*) as count')
            ->whereNotNull('decision')
            ->groupBy('decision')
            ->pluck('count', 'decision')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'draft' => $draft,
                'by_decision' => array_merge([
                    'HIRE' => 0,
                    'HOLD' => 0,
                    'REJECT' => 0,
                ], $byDecision),
            ],
        ]);
    }

    /**
     * Get Decision Packet for audit/compliance.
     * Comprehensive JSON export of all interview data.
     *
     * GET /v1/admin/form-interviews/{id}/decision-packet
     */
    public function decisionPacket(Request $request, string $id): JsonResponse
    {
        $interview = FormInterview::with(['answers', 'outcome'])->find($id);

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

        // Build the decision packet
        $packet = [
            'packet_version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'exported_by' => $request->user()?->email ?? 'system',

            // Interview identification
            'interview' => [
                'id' => $interview->id,
                'version' => $interview->version,
                'language' => $interview->language,
                'position_code' => $interview->position_code,
                'template_position_code' => $interview->template_position_code,
                'industry_code' => $interview->industry_code,
                'status' => $interview->status,
                'created_at' => $interview->created_at->toIso8601String(),
                'completed_at' => $interview->completed_at?->toIso8601String(),
            ],

            // Template snapshot
            'template' => [
                'json_sha256' => $interview->template_json_sha256,
                'json' => $interview->template_json,
            ],

            // Candidate answers
            'answers' => $interview->answers->map(fn($a) => [
                'slot' => $a->slot,
                'competency' => $a->competency,
                'answer_text' => $a->answer_text,
                'created_at' => $a->created_at?->toIso8601String(),
            ])->values()->toArray(),

            // DecisionEngine raw output
            'decision_engine' => [
                'competency_scores' => $interview->competency_scores,
                'risk_flags' => $interview->risk_flags,
                'raw_final_score' => $interview->raw_final_score,
                'raw_decision' => $interview->raw_decision,
                'raw_decision_reason' => $interview->raw_decision_reason,
            ],

            // Calibration layer
            'calibration' => [
                'version' => $interview->calibration_version,
                'position_mean_score' => $interview->position_mean_score,
                'position_std_dev_score' => $interview->position_std_dev_score,
                'z_score' => $interview->z_score,
                'calibrated_score' => $interview->calibrated_score,
            ],

            // Policy layer (final decision)
            'policy' => [
                'version' => $interview->policy_version,
                'code' => $interview->policy_code,
                'final_score' => $interview->final_score,
                'decision' => $interview->decision,
                'decision_reason' => $interview->decision_reason,
            ],

            // Ground-truth outcome (if available)
            'outcome' => $interview->outcome ? [
                'hired' => $interview->outcome->hired,
                'started' => $interview->outcome->started,
                'retained_90d' => $interview->outcome->retained_90d,
                'performance_score' => $interview->outcome->performance_score,
                'outcome_score' => $interview->outcome->outcome_score,
                'notes' => $interview->outcome->notes,
                'recorded_at' => $interview->outcome->created_at?->toIso8601String(),
                'recorded_by' => $interview->outcome->recorded_by,
            ] : null,

            // Admin notes
            'admin_notes' => $interview->admin_notes,

            // Meta (candidate-provided context)
            'meta' => $interview->meta,
        ];

        // Add SHA-256 checksum of the packet content (excluding checksum itself)
        $packetJson = json_encode($packet, JSON_UNESCAPED_UNICODE);
        $packet['checksum'] = hash('sha256', $packetJson);

        return response()->json([
            'success' => true,
            'data' => $packet,
        ]);
    }

    /**
     * Update admin notes for an interview.
     * This is the only field that can be modified after completion.
     *
     * PATCH /v1/admin/form-interviews/{id}/notes
     */
    public function updateNotes(Request $request, string $id): JsonResponse
    {
        $interview = FormInterview::find($id);

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Interview not found',
            ], 404);
        }

        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $interview->admin_notes = $data['admin_notes'];
        $interview->save();

        return response()->json([
            'success' => true,
            'message' => 'Admin notes updated',
            'data' => [
                'id' => $interview->id,
                'admin_notes' => $interview->admin_notes,
            ],
        ]);
    }

    /**
     * Generate PDF Decision Packet for download.
     *
     * GET /v1/admin/form-interviews/{id}/decision-packet.pdf
     */
    public function decisionPacketPdf(Request $request, string $id)
    {
        $interview = FormInterview::with(['answers', 'outcome'])->find($id);

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
        $adminUser = $request->user();
        if (!$adminUser) {
            $generatedBy = 'Platform Admin';
        } elseif ($adminUser->is_octopus_admin) {
            $generatedBy = 'Platform admin tarafından oluşturulmuştur';
        } else {
            $generatedBy = ($adminUser->name ?? $adminUser->email) . ' (' . $adminUser->email . ')';
        }

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.decision-packet', [
            'interview' => $interview,
            'outcome' => $interview->outcome,
            'checksum' => $checksum,
            'generatedAt' => $generatedAt,
            'generatedBy' => $generatedBy,
        ]);

        // Set paper size and options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);

        // Generate filename
        $filename = sprintf(
            'decision-packet-%s-%s.pdf',
            $interview->id,
            now()->format('Ymd-His')
        );

        return $pdf->download($filename);
    }

    /**
     * Candidate Assessment Report PDF (with radar chart).
     * GET /v1/admin/form-interviews/{id}/candidate-report.pdf
     */
    public function candidateReportPdf(Request $request, string $id)
    {
        $interview = FormInterview::with('answers')->find($id);

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Interview not found',
            ], 404);
        }

        if (!$interview->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Report only available for completed interviews',
            ], 400);
        }

        $candidate = $interview->pool_candidate_id
            ? \App\Models\PoolCandidate::find($interview->pool_candidate_id)
            : null;

        // Build a fallback candidate object if no pool candidate linked
        if (!$candidate) {
            $meta = $interview->meta ?? [];
            $candidate = (object) [
                'id' => null,
                'first_name' => $meta['candidate_name'] ?? 'Unknown',
                'last_name' => '',
                'email' => $meta['candidate_email'] ?? '',
                'phone' => '',
                'country_code' => '',
                'english_level_self' => $meta['english_level_self'] ?? '',
                'source_channel' => '',
                'status' => $interview->status,
                'source_meta' => [],
            ];
        }

        $generatedAt = now()->format('d.m.Y H:i');
        $adminUser = $request->user();
        if (!$adminUser) {
            $generatedBy = 'Platform Admin';
        } elseif ($adminUser->is_octopus_admin) {
            $generatedBy = 'Platform admin tarafından oluşturulmuştur';
        } else {
            $generatedBy = ($adminUser->name ?? $adminUser->email) . ' (' . $adminUser->email . ')';
        }

        // Certificate lifecycle risk data
        $certificateRisks = [];
        if ($candidate->id ?? null) {
            $realCandidate = \App\Models\PoolCandidate::with('certificates')->find($candidate->id);
            if ($realCandidate && $realCandidate->certificates->isNotEmpty()) {
                $certService = app(\App\Services\Maritime\CertificateLifecycleService::class);
                $certificateRisks = $certService->enrichWithRiskLevels($realCandidate->certificates);
            }
        }

        // Behavioral snapshot (if available)
        $behavioralSnapshot = null;
        $profile = $interview->behavioralProfile;
        if ($profile && $profile->status === \App\Models\BehavioralProfile::STATUS_FINAL) {
            $behavioralSnapshot = [
                'status' => $profile->status,
                'confidence' => (float) $profile->confidence,
                'fit_top3' => $profile->fit_json ? collect($profile->fit_json)
                    ->map(fn($v, $k) => ['class' => $k, 'fit' => $v['normalized_fit'] ?? 0, 'risk_flag' => $v['risk_flag'] ?? false, 'friction_flag' => $v['friction_flag'] ?? false])
                    ->sortByDesc('fit')->take(3)->values()->toArray() : null,
                'flags' => $profile->flags_json,
                'dimensions' => $profile->dimensions_json,
                'computed_at' => $profile->computed_at?->toIso8601String(),
            ];
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.candidate-report', [
            'candidate' => $candidate,
            'interview' => $interview,
            'answers' => $interview->answers,
            'generatedAt' => $generatedAt,
            'generatedBy' => $generatedBy,
            'behavioralSnapshot' => $behavioralSnapshot,
            'certificateRisks' => $certificateRisks,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);

        $name = $candidate->first_name ?? 'candidate';
        $filename = sprintf(
            'assessment-report-%s-%s.pdf',
            \Illuminate\Support\Str::slug($name),
            now()->format('Ymd-His')
        );

        return $pdf->download($filename);
    }
}
