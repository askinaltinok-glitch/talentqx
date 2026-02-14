<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\ResearchJob;
use App\Models\ResearchCompanyCandidate;
use App\Models\CrmAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResearchController extends Controller
{
    /**
     * GET /v1/admin/crm/research/jobs
     */
    public function jobs(Request $request): JsonResponse
    {
        $query = ResearchJob::withCount('candidates');

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        $jobs = $query->orderByDesc('created_at')->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'meta' => [
                'total' => $jobs->total(),
                'page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
            ],
        ]);
    }

    /**
     * POST /v1/admin/crm/research/jobs
     */
    public function createJob(Request $request): JsonResponse
    {
        $v = $request->validate([
            'industry_code' => ['nullable', 'string', 'max:32'],
            'query' => ['required', 'string', 'max:500'],
            'meta' => ['nullable', 'array'],
        ]);

        $v['created_by'] = $request->user()?->id;

        $job = ResearchJob::create($v);

        CrmAuditLog::log('research.job_created', 'research_job', $job->id, null, $v, $request->user()?->id, $request->ip());

        return response()->json(['success' => true, 'data' => $job], 201);
    }

    /**
     * GET /v1/admin/crm/research/jobs/{id}
     */
    public function showJob(string $id): JsonResponse
    {
        $job = ResearchJob::withCount([
            'candidates',
            'candidates as pending_count' => fn($q) => $q->pending(),
            'candidates as accepted_count' => fn($q) => $q->where('status', 'accepted'),
        ])->find($id);

        if (!$job) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Research job not found.']], 404);
        }

        return response()->json(['success' => true, 'data' => $job]);
    }

    /**
     * GET /v1/admin/crm/research/jobs/{id}/candidates
     */
    public function candidates(Request $request, string $id): JsonResponse
    {
        $job = ResearchJob::find($id);
        if (!$job) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Research job not found.']], 404);
        }

        $query = $job->candidates();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $candidates = $query->orderByDesc('confidence')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $candidates->items(),
            'meta' => [
                'total' => $candidates->total(),
                'page' => $candidates->currentPage(),
            ],
        ]);
    }

    /**
     * POST /v1/admin/crm/research/jobs/{id}/candidates
     * Manually add candidate to a research job (or via import).
     */
    public function addCandidate(Request $request, string $id): JsonResponse
    {
        $job = ResearchJob::find($id);
        if (!$job) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Research job not found.']], 404);
        }

        $v = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:128'],
            'company_type' => ['nullable', 'string', 'max:64'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'raw' => ['nullable', 'array'],
            'contact_hints' => ['nullable', 'array'],
        ]);

        $v['job_id'] = $id;

        $candidate = ResearchCompanyCandidate::create($v);
        $job->increment('result_count');

        return response()->json(['success' => true, 'data' => $candidate], 201);
    }

    /**
     * POST /v1/admin/crm/research/candidates/{id}/accept
     */
    public function acceptCandidate(Request $request, string $id): JsonResponse
    {
        $candidate = ResearchCompanyCandidate::find($id);
        if (!$candidate) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Candidate not found.']], 404);
        }

        if ($candidate->status !== ResearchCompanyCandidate::STATUS_PENDING) {
            return response()->json(['success' => false, 'error' => ['code' => 'already_reviewed', 'message' => "Candidate already {$candidate->status}."]], 422);
        }

        $company = $candidate->accept($request->user()?->id);

        CrmAuditLog::log('research.candidate_accepted', 'research_candidate', $id, null, [
            'company_id' => $company->id,
            'name' => $candidate->name,
        ], $request->user()?->id, $request->ip());

        return response()->json([
            'success' => true,
            'data' => [
                'candidate' => $candidate->fresh(),
                'company' => $company->load('contacts'),
            ],
        ]);
    }

    /**
     * POST /v1/admin/crm/research/candidates/{id}/reject
     */
    public function rejectCandidate(Request $request, string $id): JsonResponse
    {
        $candidate = ResearchCompanyCandidate::find($id);
        if (!$candidate) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Candidate not found.']], 404);
        }

        $candidate->reject($request->user()?->id);

        return response()->json(['success' => true, 'data' => $candidate->fresh()]);
    }

    /**
     * GET /v1/admin/crm/research/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_jobs' => ResearchJob::count(),
                'running' => ResearchJob::status('running')->count(),
                'completed' => ResearchJob::status('completed')->count(),
                'total_candidates' => ResearchCompanyCandidate::count(),
                'pending' => ResearchCompanyCandidate::pending()->count(),
                'accepted' => ResearchCompanyCandidate::where('status', 'accepted')->count(),
            ],
        ]);
    }
}
