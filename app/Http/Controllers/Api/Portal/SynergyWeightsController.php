<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Services\Fleet\MemoryLearningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SynergyWeightsController extends Controller
{
    public function __construct(
        private MemoryLearningService $learning,
    ) {}

    /**
     * GET /v1/portal/synergy/weights
     * Read-only: show current weights + sample size + learning status.
     */
    public function show(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $globalStatus = $this->learning->getStatus('global');
        $companyStatus = $this->learning->getStatus('company', $companyId);

        // Effective weights for this company
        $effectiveWeights = $this->learning->resolveWeights($companyId);

        return response()->json([
            'success' => true,
            'data' => [
                'effective_weights' => $effectiveWeights,
                'global' => $globalStatus,
                'company' => $companyStatus,
            ],
        ]);
    }

    /**
     * POST /v1/portal/synergy/retrain
     * Rate limited. Company owner / admin only.
     */
    public function retrain(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $request->validate([
            'window_days' => 'integer|min:30|max:365',
        ]);

        $windowDays = (int) $request->input('window_days', 90);

        $result = $this->learning->retrain('company', $companyId, $windowDays);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
