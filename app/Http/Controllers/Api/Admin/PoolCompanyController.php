<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoolCompany;
use App\Services\ConsumptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PoolCompanyController extends Controller
{
    public function __construct(
        private ConsumptionService $service
    ) {}

    /**
     * GET /v1/admin/pool-companies
     * List all pool companies.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PoolCompany::query()
            ->withCount('talentRequests');

        // Filters
        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }
        if ($request->filled('industry')) {
            $query->industry($request->input('industry'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /v1/admin/pool-companies
     * Create a new pool company.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'size:2'],
            'size' => ['nullable', 'string', 'in:small,medium,enterprise'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255', 'unique:pool_companies,contact_email'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'meta' => ['nullable', 'array'],
        ]);

        $company = $this->service->createCompany($data);

        return response()->json([
            'success' => true,
            'message' => 'Company created successfully',
            'data' => $company,
        ], 201);
    }

    /**
     * GET /v1/admin/pool-companies/{id}
     * Get company details.
     */
    public function show(PoolCompany $poolCompany): JsonResponse
    {
        $poolCompany->load(['talentRequests' => function ($q) {
            $q->withCount('presentations')->latest()->limit(10);
        }]);

        return response()->json([
            'success' => true,
            'data' => [
                ...$poolCompany->toArray(),
                'total_hired' => $poolCompany->total_hired,
            ],
        ]);
    }

    /**
     * PUT /v1/admin/pool-companies/{id}
     * Update company.
     */
    public function update(Request $request, PoolCompany $poolCompany): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['sometimes', 'string', 'max:255'],
            'industry' => ['sometimes', 'string', 'max:64'],
            'country' => ['sometimes', 'string', 'size:2'],
            'size' => ['sometimes', 'string', 'in:small,medium,enterprise'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'email', 'max:255', 'unique:pool_companies,contact_email,' . $poolCompany->id],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'meta' => ['nullable', 'array'],
        ]);

        $poolCompany->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully',
            'data' => $poolCompany->fresh(),
        ]);
    }

    /**
     * GET /v1/admin/pool-companies/stats
     * Get company statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getStats(),
        ]);
    }
}
