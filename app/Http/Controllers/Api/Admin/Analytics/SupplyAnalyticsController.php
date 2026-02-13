<?php

namespace App\Http\Controllers\Api\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\FunnelAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SupplyAnalyticsController
 *
 * Investor-grade analytics endpoints for the Candidate Supply Engine.
 * Provides funnel metrics, channel quality, time-to-hire, and pool health data.
 */
class SupplyAnalyticsController extends Controller
{
    public function __construct(
        private FunnelAnalyticsService $analyticsService
    ) {}

    /**
     * GET /v1/admin/analytics/funnel
     *
     * Get candidate funnel metrics.
     */
    public function funnel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'industry' => ['nullable', 'string', 'in:general,maritime,retail,logistics,hospitality'],
            'source_channel' => ['nullable', 'string'],
        ]);

        $metrics = $this->analyticsService->getFunnelMetrics(
            $validated['start_date'],
            $validated['end_date'],
            $validated['industry'] ?? null,
            $validated['source_channel'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * GET /v1/admin/analytics/channel-quality
     *
     * Get source channel quality metrics (for CAC optimization).
     */
    public function channelQuality(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'industry' => ['nullable', 'string', 'in:general,maritime,retail,logistics,hospitality'],
        ]);

        $metrics = $this->analyticsService->getChannelQuality(
            $validated['start_date'],
            $validated['end_date'],
            $validated['industry'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * GET /v1/admin/analytics/time-to-hire
     *
     * Get time-to-hire metrics.
     */
    public function timeToHire(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'industry' => ['nullable', 'string', 'in:general,maritime,retail,logistics,hospitality'],
        ]);

        $metrics = $this->analyticsService->getTimeToHireMetrics(
            $validated['start_date'],
            $validated['end_date'],
            $validated['industry'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * GET /v1/admin/analytics/pool-health
     *
     * Get pool health metrics.
     */
    public function poolHealth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'industry' => ['nullable', 'string', 'in:general,maritime,retail,logistics,hospitality'],
        ]);

        $metrics = $this->analyticsService->getPoolHealthMetrics(
            $validated['industry'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * GET /v1/admin/analytics/company
     *
     * Get company consumption metrics.
     */
    public function companyMetrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'company_id' => ['nullable', 'uuid'],
        ]);

        $metrics = $this->analyticsService->getCompanyMetrics(
            $validated['start_date'],
            $validated['end_date'],
            $validated['company_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * GET /v1/admin/analytics/trends
     *
     * Get weekly trend data for charts.
     */
    public function trends(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'weeks' => ['nullable', 'integer', 'min:4', 'max:52'],
            'industry' => ['nullable', 'string', 'in:general,maritime,retail,logistics,hospitality'],
        ]);

        $metrics = $this->analyticsService->getWeeklyTrends(
            $validated['weeks'] ?? 12,
            $validated['industry'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * GET /v1/admin/analytics/dashboard
     *
     * Get combined dashboard metrics (single call for admin UI).
     */
    public function dashboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'industry' => ['nullable', 'string', 'in:general,maritime,retail,logistics,hospitality'],
        ]);

        $industry = $validated['industry'] ?? null;
        $today = now()->toDateString();
        $thirtyDaysAgo = now()->subDays(30)->toDateString();
        $ninetyDaysAgo = now()->subDays(90)->toDateString();

        return response()->json([
            'success' => true,
            'data' => [
                'funnel_30d' => $this->analyticsService->getFunnelMetrics(
                    $thirtyDaysAgo,
                    $today,
                    $industry
                ),
                'channel_quality_90d' => $this->analyticsService->getChannelQuality(
                    $ninetyDaysAgo,
                    $today,
                    $industry
                ),
                'pool_health' => $this->analyticsService->getPoolHealthMetrics($industry),
                'trends_12w' => $this->analyticsService->getWeeklyTrends(12, $industry),
            ],
        ]);
    }
}
