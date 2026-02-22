<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Services\Maritime\CandidateDecisionPanelService;
use Illuminate\Http\JsonResponse;

class CandidateDecisionPanelController extends Controller
{
    public function __construct(
        private readonly CandidateDecisionPanelService $service,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $data = $this->service->build($id);

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
