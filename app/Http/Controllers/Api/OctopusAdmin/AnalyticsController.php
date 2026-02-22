<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Services\Reports\DailyReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * GET /v1/octopus/admin/analytics/dashboard
     *
     * Returns today's metrics + 30-day trends for the analytics dashboard.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : now();

        $service = new DailyReportService($date);
        $metrics = $service->collect();

        // Add 30-day daily breakdown for charts
        $last30 = $this->last30DaysBreakdown($date);

        return response()->json([
            'success' => true,
            'data' => [
                'today' => $metrics,
                'last_30_days' => $last30,
            ],
        ]);
    }

    /**
     * GET /v1/octopus/admin/analytics/country-map
     *
     * Returns candidate counts by country for world map.
     */
    public function countryMap(): JsonResponse
    {
        $countries = DB::table('pool_candidates')
            ->where('is_demo', false)
            ->whereNotNull('country_code')
            ->selectRaw("country_code, COUNT(*) as cnt")
            ->groupBy('country_code')
            ->orderByDesc('cnt')
            ->get()
            ->map(fn($r) => [
                'code' => $r->country_code,
                'count' => $r->cnt,
            ]);

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }

    /**
     * GET /v1/octopus/admin/analytics/trends
     *
     * Returns detailed trends for a specific metric over a date range.
     */
    public function trends(Request $request): JsonResponse
    {
        $days = min((int) $request->input('days', 30), 90);
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();

        $candidates = DB::table('pool_candidates')
            ->where('is_demo', false)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE(created_at) as d, COUNT(*) as cnt")
            ->groupBy('d')
            ->pluck('cnt', 'd')
            ->toArray();

        $interviews = DB::table('form_interviews')
            ->where('is_demo', false)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw("DATE(completed_at) as d, COUNT(*) as cnt")
            ->groupBy('d')
            ->pluck('cnt', 'd')
            ->toArray();

        $formEvents = DB::table('apply_form_events')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE(created_at) as d, COUNT(DISTINCT session_id) as cnt")
            ->groupBy('d')
            ->pluck('cnt', 'd')
            ->toArray();

        // Build day-by-day array
        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = now()->subDays($days - 1 - $i)->format('Y-m-d');
            $series[] = [
                'date' => $day,
                'candidates' => $candidates[$day] ?? 0,
                'interviews' => $interviews[$day] ?? 0,
                'sessions' => $formEvents[$day] ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $series,
        ]);
    }

    private function last30DaysBreakdown(Carbon $date): array
    {
        $end = $date->copy()->endOfDay();
        $start = $date->copy()->subDays(29)->startOfDay();

        $candidates = DB::table('pool_candidates')
            ->where('is_demo', false)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE(created_at) as d, COUNT(*) as cnt")
            ->groupBy('d')
            ->pluck('cnt', 'd')
            ->toArray();

        $interviews = DB::table('form_interviews')
            ->where('is_demo', false)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw("DATE(completed_at) as d, COUNT(*) as cnt")
            ->groupBy('d')
            ->pluck('cnt', 'd')
            ->toArray();

        $series = [];
        for ($i = 0; $i < 30; $i++) {
            $day = $date->copy()->subDays(29 - $i)->format('Y-m-d');
            $series[] = [
                'date' => $day,
                'candidates' => $candidates[$day] ?? 0,
                'interviews' => $interviews[$day] ?? 0,
            ];
        }

        return $series;
    }
}
