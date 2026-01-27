<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\DataErasureRequest;
use App\Services\KVKK\CandidateExportService;
use App\Services\KVKK\DataErasureService;
use App\Services\KVKK\RetentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KVKKController extends Controller
{
    public function __construct(
        protected DataErasureService $erasureService,
        protected CandidateExportService $exportService,
        protected RetentionService $retentionService
    ) {}

    /**
     * Right to be Forgotten - Erase candidate data
     * DELETE /candidates/{id}/erase
     */
    public function eraseCandidate(Request $request, string $id): JsonResponse
    {
        $candidate = Candidate::whereHas('job', fn($q) => $q->where('company_id', $request->user()->company_id))
            ->findOrFail($id);

        if ($candidate->is_erased) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_ERASED',
                    'message' => 'Bu adayin verileri zaten silinmis.',
                ],
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|in:kvkk_request,candidate_request,company_policy',
            'notes' => 'nullable|string|max:500',
        ]);

        // Create erasure request record
        $erasureRequest = DataErasureRequest::create([
            'candidate_id' => $candidate->id,
            'requested_by' => $request->user()->id,
            'request_type' => $validated['reason'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Process immediately
        $result = $this->erasureService->processErasureRequest($erasureRequest);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => [
                    'erased_types' => $result['erased_types'],
                    'request_id' => $erasureRequest->id,
                ],
                'message' => 'Aday verileri basariyla silindi (KVKK uyumlu).',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'ERASURE_FAILED',
                'message' => $result['error'] ?? 'Veri silme islemi basarisiz.',
            ],
        ], 500);
    }

    /**
     * Export candidate data (JSON format)
     * GET /candidates/{id}/export
     */
    public function exportCandidate(Request $request, string $id): JsonResponse
    {
        $candidate = Candidate::whereHas('job', fn($q) => $q->where('company_id', $request->user()->company_id))
            ->findOrFail($id);

        if ($candidate->is_erased) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DATA_ERASED',
                    'message' => 'Bu adayin verileri silinmis.',
                ],
            ], 410);
        }

        $format = $request->get('format', 'json');

        if ($format === 'pdf') {
            $filename = $this->exportService->exportAsPdf($candidate);
            $url = Storage::disk('local')->url($filename);

            return response()->json([
                'success' => true,
                'data' => [
                    'format' => 'pdf',
                    'download_url' => $url,
                    'expires_at' => now()->addHours(24)->toIso8601String(),
                ],
                'message' => 'PDF raporu olusturuldu.',
            ]);
        }

        $data = $this->exportService->exportAsJson($candidate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Create erasure request (for manual/batch processing)
     * POST /kvkk/erasure-requests
     */
    public function createErasureRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_ids' => 'required|array|min:1|max:100',
            'candidate_ids.*' => 'uuid|exists:candidates,id',
            'reason' => 'required|in:kvkk_request,candidate_request,company_policy,retention_expired',
            'notes' => 'nullable|string|max:500',
        ]);

        $created = 0;
        $skipped = 0;

        foreach ($validated['candidate_ids'] as $candidateId) {
            $candidate = Candidate::whereHas('job', fn($q) => $q->where('company_id', $request->user()->company_id))
                ->find($candidateId);

            if (!$candidate || $candidate->is_erased) {
                $skipped++;
                continue;
            }

            // Check for existing pending request
            $exists = DataErasureRequest::where('candidate_id', $candidateId)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            DataErasureRequest::create([
                'candidate_id' => $candidateId,
                'requested_by' => $request->user()->id,
                'request_type' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
            ]);
            $created++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
            ],
            'message' => "{$created} silme talebi olusturuldu.",
        ]);
    }

    /**
     * List erasure requests
     * GET /kvkk/erasure-requests
     */
    public function listErasureRequests(Request $request): JsonResponse
    {
        $query = DataErasureRequest::with(['candidate', 'requestedBy'])
            ->whereHas('candidate.job', fn($q) => $q->where('company_id', $request->user()->company_id));

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Get retention statistics
     * GET /kvkk/retention-stats
     */
    public function retentionStats(Request $request): JsonResponse
    {
        $stats = $this->retentionService->getRetentionStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get audit logs for an entity
     * GET /kvkk/audit-logs
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::where('company_id', $request->user()->company_id);

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->boolean('erasures_only')) {
            $query->erasures();
        }

        $logs = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Update job retention policy
     * PUT /jobs/{id}/retention
     */
    public function updateRetention(Request $request, string $id): JsonResponse
    {
        $job = \App\Models\Job::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'retention_days' => 'required|integer|min:30|max:730', // 30 days to 2 years
        ]);

        $job->update(['retention_days' => $validated['retention_days']]);

        AuditLog::log('update_retention', $job, null, [
            'retention_days' => $validated['retention_days'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $job,
            'message' => 'Veri saklama suresi guncellendi.',
        ]);
    }
}
