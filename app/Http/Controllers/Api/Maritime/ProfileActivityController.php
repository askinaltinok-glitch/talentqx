<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Services\ProfileActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileActivityController extends Controller
{
    public function __construct(
        private ProfileActivityService $service
    ) {}

    /**
     * GET /v1/maritime/candidates/{id}/profile-activity
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $tier = $request->attributes->get('candidate_tier', 'free');
        $days = min((int) $request->input('days', 30), 90);

        $result = $this->service->getProfileActivity($id, $tier, $days);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
