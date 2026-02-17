<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\DemoCandidateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    public function __construct(
        private DemoCandidateService $demoService
    ) {}

    /**
     * POST /v1/admin/demo/create-candidate
     *
     * Create a demo candidate with full pipeline.
     * Gated by DEMO_MODE env flag.
     */
    public function createCandidate(Request $request): JsonResponse
    {
        if (!config('app.demo_mode')) {
            return response()->json([
                'success' => false,
                'error' => 'Demo mode is disabled',
            ], 403);
        }

        $data = $request->validate([
            'profile_index' => ['nullable', 'integer', 'min:0', 'max:4'],
        ]);

        try {
            $result = $this->demoService->createDemoCandidate(
                $data['profile_index'] ?? null
            );

            if (!$result['success']) {
                return response()->json($result, 422);
            }

            return response()->json($result, 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Demo candidate creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /v1/admin/demo/candidates
     *
     * List all demo candidates.
     */
    public function candidates(): JsonResponse
    {
        $candidates = $this->demoService->listDemoCandidates();

        return response()->json([
            'success' => true,
            'data' => $candidates,
            'count' => count($candidates),
        ]);
    }

    /**
     * DELETE /v1/admin/demo/cleanup
     *
     * Remove all demo candidates.
     */
    public function cleanup(): JsonResponse
    {
        if (!config('app.demo_mode')) {
            return response()->json([
                'success' => false,
                'error' => 'Demo mode is disabled',
            ], 403);
        }

        try {
            $result = $this->demoService->cleanupDemoCandidates();
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Cleanup failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
