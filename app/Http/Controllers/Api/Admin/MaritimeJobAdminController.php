<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\MaritimeJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaritimeJobAdminController extends Controller
{
    public function __construct(
        private MaritimeJobService $service
    ) {}

    /**
     * GET /v1/admin/maritime-jobs
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['is_active', 'rank']);
        $perPage = (int) $request->input('per_page', 20);

        $paginated = $this->service->getAdminList($filters, $perPage);

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
     * POST /v1/admin/maritime-jobs
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pool_company_id' => ['required', 'uuid', 'exists:pool_companies,id'],
            'vessel_type' => ['nullable', 'string', 'max:64'],
            'rank' => ['required', 'string', 'max:64'],
            'salary_range' => ['nullable', 'string', 'max:128'],
            'contract_length' => ['nullable', 'string', 'max:64'],
            'rotation' => ['nullable', 'string', 'max:64'],
            'internet_policy' => ['nullable', 'string', 'max:128'],
            'bonus_policy' => ['nullable', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'operation_type' => ['nullable', 'string', 'in:sea,river'],
        ]);

        $job = $this->service->createJob($data);

        return response()->json([
            'success' => true,
            'message' => 'Maritime job created.',
            'data' => $job->load('company:id,company_name'),
        ], 201);
    }

    /**
     * GET /v1/admin/maritime-jobs/{id}
     */
    public function show(string $id): JsonResponse
    {
        $result = $this->service->getJob($id);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * PUT /v1/admin/maritime-jobs/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'vessel_type' => ['nullable', 'string', 'max:64'],
            'rank' => ['string', 'max:64'],
            'salary_range' => ['nullable', 'string', 'max:128'],
            'contract_length' => ['nullable', 'string', 'max:64'],
            'rotation' => ['nullable', 'string', 'max:64'],
            'internet_policy' => ['nullable', 'string', 'max:128'],
            'bonus_policy' => ['nullable', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'operation_type' => ['nullable', 'string', 'in:sea,river'],
        ]);

        $job = $this->service->updateJob($id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Maritime job updated.',
            'data' => $job->load('company:id,company_name'),
        ]);
    }
}
