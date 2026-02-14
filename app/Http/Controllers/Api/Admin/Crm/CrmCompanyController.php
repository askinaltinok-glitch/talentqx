<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCompany;
use App\Models\CrmAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmCompanyController extends Controller
{
    /**
     * GET /v1/admin/crm/companies
     */
    public function index(Request $request): JsonResponse
    {
        $query = CrmCompany::query()->withCount('contacts', 'leads');

        if ($request->filled('q')) {
            $query->search($request->q);
        }
        if ($request->filled('industry')) {
            $query->industry($request->industry);
        }
        if ($request->filled('status')) {
            $query->status($request->status);
        }
        if ($request->filled('country')) {
            $query->country($request->country);
        }
        if ($request->filled('company_type')) {
            $query->where('company_type', $request->company_type);
        }

        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('dir', 'desc');
        $allowed = ['name', 'created_at', 'status', 'country_code', 'company_type'];
        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $companies = $query->paginate(min((int) $request->get('per_page', 25), 100));

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
     * POST /v1/admin/crm/companies
     */
    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['required', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:128'],
            'website' => ['nullable', 'url', 'max:500'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'company_type' => ['nullable', Rule::in(CrmCompany::COMPANY_TYPES)],
            'size_band' => ['nullable', Rule::in(CrmCompany::SIZE_BANDS)],
            'tags' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(CrmCompany::STATUSES)],
        ]);

        $v['domain'] = CrmCompany::extractDomain($v['website'] ?? null);

        // Dedup by domain
        if ($v['domain'] && CrmCompany::findByDomain($v['domain'])) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'duplicate_domain', 'message' => "A company with domain {$v['domain']} already exists."],
            ], 422);
        }

        $company = CrmCompany::create($v);

        CrmAuditLog::log('company.created', 'company', $company->id, null, $v, $request->user()?->id, $request->ip());

        return response()->json(['success' => true, 'data' => $company], 201);
    }

    /**
     * GET /v1/admin/crm/companies/{id}
     */
    public function show(string $id): JsonResponse
    {
        $company = CrmCompany::with('contacts', 'leads')->withCount('contacts', 'leads')->find($id);

        if (!$company) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Company not found.']], 404);
        }

        return response()->json(['success' => true, 'data' => $company]);
    }

    /**
     * PUT /v1/admin/crm/companies/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $company = CrmCompany::find($id);
        if (!$company) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Company not found.']], 404);
        }

        $old = $company->toArray();

        $v = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'industry_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['sometimes', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:128'],
            'website' => ['nullable', 'url', 'max:500'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'company_type' => ['nullable', Rule::in(CrmCompany::COMPANY_TYPES)],
            'size_band' => ['nullable', Rule::in(CrmCompany::SIZE_BANDS)],
            'tags' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(CrmCompany::STATUSES)],
            'owner_user_id' => ['nullable', 'uuid'],
        ]);

        if (isset($v['website'])) {
            $v['domain'] = CrmCompany::extractDomain($v['website']);
        }

        $company->update($v);

        CrmAuditLog::log('company.updated', 'company', $company->id, $old, $v, $request->user()?->id, $request->ip());

        return response()->json(['success' => true, 'data' => $company->fresh()]);
    }
}
