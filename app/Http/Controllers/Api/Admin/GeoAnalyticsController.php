<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\GeoAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoAnalyticsController extends Controller
{
    public function __construct(
        private readonly GeoAnalyticsService $service
    ) {}

    /**
     * GET /v1/admin/analytics/geo/dashboard
     * Combined geo analytics + drop-off dashboard.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $industry = $request->input('industry');

        return response()->json([
            'success' => true,
            'data' => $this->service->dashboard($from, $to, $industry),
            'meta' => ['from' => $from, 'to' => $to],
        ]);
    }

    /**
     * GET /v1/admin/analytics/geo/candidates
     */
    public function candidatesByCountry(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $industry = $request->input('industry');

        return response()->json([
            'success' => true,
            'data' => $this->service->candidatesByCountry($from, $to, $industry),
        ]);
    }

    /**
     * GET /v1/admin/analytics/geo/companies
     */
    public function companiesByCountry(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->companiesByCountry(),
        ]);
    }

    /**
     * GET /v1/admin/analytics/geo/hourly
     */
    public function hourlyDistribution(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->service->hourlyApplyDistribution($from, $to),
        ]);
    }

    /**
     * GET /v1/admin/analytics/geo/heatmap
     */
    public function heatMap(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->service->mapHeatIntensity($from, $to),
        ]);
    }

    /**
     * GET /v1/admin/analytics/geo/drop-off
     */
    public function dropOff(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->service->applyDropOffAnalysis($from, $to),
        ]);
    }
}
