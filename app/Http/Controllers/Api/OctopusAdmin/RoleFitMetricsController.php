<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Services\Maritime\RoleFitMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleFitMetricsController extends Controller
{
    public function __invoke(Request $request, RoleFitMetricsService $service): JsonResponse
    {
        $hours = (int) $request->query('hours', 24);

        return response()->json([
            'success' => true,
            'data' => $service->compute($hours),
        ]);
    }
}
