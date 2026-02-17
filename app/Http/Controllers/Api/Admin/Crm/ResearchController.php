<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Jobs\RunResearchAgentJob;
use App\Models\ResearchCompany;
use App\Models\ResearchRun;
use App\Models\ResearchSignal;
use App\Models\ResearchJob;
use App\Models\ResearchCompanyCandidate;
use App\Models\CrmAuditLog;
use App\Services\Research\ResearchService;
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
     * Updated: now includes Sprint-7 research company intelligence stats.
     */
    public function stats(ResearchService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->getStats(),
        ]);
    }

    // =========================================
    // SPRINT-7: Research Company Intelligence
    // =========================================

    /**
     * GET /v1/admin/crm/research/companies
     */
    public function companies(Request $request): JsonResponse
    {
        $query = ResearchCompany::with('signals');

        if ($request->filled('status')) {
            $query->status($request->status);
        }
        if ($request->filled('industry')) {
            $query->industry($request->industry);
        }
        if ($request->filled('maritime')) {
            $query->maritime();
        }
        if ($request->filled('min_score')) {
            $query->minScore((int) $request->min_score);
        }
        if ($request->filled('q')) {
            $query->search($request->q);
        }

        $companies = $query->orderByDesc('hiring_signal_score')
                           ->paginate(min((int) $request->get('per_page', 25), 100));

        return response()->json([
            'success' => true,
            'data' => $companies->items(),
            'meta' => [
                'total' => $companies->total(),
                'page' => $companies->currentPage(),
                'per_page' => $companies->perPage(),
                'last_page' => $companies->lastPage(),
            ],
        ]);
    }

    /**
     * GET /v1/admin/crm/research/companies/{id}
     */
    public function companyDetail(string $id): JsonResponse
    {
        $company = ResearchCompany::with([
            'signals' => fn($q) => $q->orderByDesc('detected_at')->limit(50),
        ])->find($id);

        if (!$company) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Research company not found.']], 404);
        }

        return response()->json(['success' => true, 'data' => $company]);
    }

    /**
     * POST /v1/admin/crm/research/companies/{id}/push
     */
    public function pushCompany(Request $request, string $id, ResearchService $service): JsonResponse
    {
        $company = ResearchCompany::find($id);
        if (!$company) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Research company not found.']], 404);
        }

        if ($company->status === ResearchCompany::STATUS_PUSHED) {
            return response()->json(['success' => false, 'error' => ['code' => 'already_pushed', 'message' => 'Company already pushed to CRM.']], 422);
        }

        $crmCompany = $service->pushToCrm($company, $request->user()?->id);

        CrmAuditLog::log('research.company_pushed', 'research_company', $id, null, [
            'crm_company_id' => $crmCompany?->id,
            'name' => $company->name,
        ], $request->user()?->id, $request->ip());

        return response()->json([
            'success' => true,
            'data' => [
                'research_company' => $company->fresh(),
                'crm_company' => $crmCompany,
            ],
        ]);
    }

    /**
     * POST /v1/admin/crm/research/companies/{id}/ignore
     */
    public function ignoreCompany(Request $request, string $id): JsonResponse
    {
        $company = ResearchCompany::find($id);
        if (!$company) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Research company not found.']], 404);
        }

        $company->update(['status' => ResearchCompany::STATUS_IGNORED]);

        return response()->json(['success' => true, 'data' => $company->fresh()]);
    }

    /**
     * POST /v1/admin/crm/research/companies/import
     */
    public function importCompanies(Request $request, ResearchService $service): JsonResponse
    {
        $v = $request->validate([
            'companies' => ['required', 'array', 'max:500'],
            'companies.*.name' => ['required', 'string', 'max:255'],
            'companies.*.domain' => ['nullable', 'string', 'max:255'],
            'companies.*.country' => ['nullable', 'string', 'max:2'],
            'companies.*.industry' => ['nullable', 'string', 'max:32'],
            'companies.*.description' => ['nullable', 'string'],
            'companies.*.website' => ['nullable', 'string', 'max:500'],
            'companies.*.linkedin_url' => ['nullable', 'string', 'max:500'],
            'companies.*.employee_count_est' => ['nullable', 'integer'],
            'companies.*.fleet_size_est' => ['nullable', 'integer'],
            'source' => ['nullable', 'string', 'max:32'],
        ]);

        $result = $service->importFromJson($v['companies'], $v['source'] ?? 'import');

        CrmAuditLog::log('research.companies_imported', 'research_company', null, null, [
            'count' => count($v['companies']),
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ], $request->user()?->id, $request->ip());

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 201);
    }

    /**
     * POST /v1/admin/crm/research/companies/{id}/classify
     */
    public function classifyCompany(Request $request, string $id, ResearchService $service): JsonResponse
    {
        $company = ResearchCompany::find($id);
        if (!$company) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Research company not found.']], 404);
        }

        $service->classifyCompany($company);

        return response()->json([
            'success' => true,
            'message' => 'Classification job dispatched.',
            'data' => $company->fresh(),
        ]);
    }

    /**
     * GET /v1/admin/crm/research/runs
     */
    public function runs(Request $request): JsonResponse
    {
        $query = ResearchRun::query();

        if ($request->filled('agent')) {
            $query->agent($request->agent);
        }
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        $runs = $query->orderByDesc('created_at')->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $runs->items(),
            'meta' => [
                'total' => $runs->total(),
                'page' => $runs->currentPage(),
                'per_page' => $runs->perPage(),
            ],
        ]);
    }

    /**
     * POST /v1/admin/crm/research/run-agent
     */
    public function runAgent(Request $request): JsonResponse
    {
        $v = $request->validate([
            'agent' => ['required', Rule::in(ResearchRun::AGENTS)],
            'sync' => ['nullable', 'boolean'],
        ]);

        if (!empty($v['sync'])) {
            $job = new RunResearchAgentJob($v['agent']);
            $job->handle();

            return response()->json([
                'success' => true,
                'message' => "Agent {$v['agent']} completed synchronously.",
            ]);
        }

        RunResearchAgentJob::dispatch($v['agent']);

        CrmAuditLog::log('research.agent_triggered', 'research_run', null, null, [
            'agent' => $v['agent'],
        ], $request->user()?->id, $request->ip());

        return response()->json([
            'success' => true,
            'message' => "Agent {$v['agent']} dispatched to queue.",
        ]);
    }
}
