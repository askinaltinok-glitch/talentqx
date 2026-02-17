<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\PoolCandidate;
use App\Services\MaritimeJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaritimeJobController extends Controller
{
    public function __construct(
        private MaritimeJobService $service
    ) {}

    /**
     * GET /v1/maritime/jobs
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['rank', 'vessel_type']);
        $perPage = (int) $request->input('per_page', 20);

        $paginated = $this->service->listJobs($filters, $perPage);

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
     * GET /v1/maritime/jobs/{id}
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
     * POST /v1/maritime/jobs/{id}/apply
     */
    public function apply(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'candidate_id' => ['required', 'uuid'],
            'candidate_email' => ['required', 'email'],
        ]);

        // Verify candidate identity
        $candidate = PoolCandidate::where('id', $data['candidate_id'])
            ->where('email', $data['candidate_email'])
            ->firstOrFail();

        $tier = $candidate->membership_tier;

        $application = $this->service->apply($id, $candidate->id, $tier);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
            'data' => [
                'id' => $application->id,
                'status' => $application->status,
                'created_at' => $application->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
