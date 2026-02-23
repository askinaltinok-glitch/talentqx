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

    /**
     * GET /v1/octopus/admin/analytics/kpi
     *
     * Per-country KPI dashboard: signups/day, verified %, logbook activation %,
     * weekly active %, company onboarding %, crew planning runs/day.
     */
    public function kpi(Request $request): JsonResponse
    {
        $days = min((int) $request->input('days', 7), 30);
        $countryFilter = $request->input('country'); // optional
        $start = now()->subDays($days)->startOfDay();

        // Base query scoping
        $candidateBase = DB::table('pool_candidates')
            ->where('is_demo', false)
            ->where('primary_industry', 'maritime');

        if ($countryFilter) {
            $candidateBase = $candidateBase->where('country_code', $countryFilter);
        }

        // 1. Per-country signups
        $signups = (clone $candidateBase)
            ->where('created_at', '>=', $start)
            ->selectRaw("country_code, DATE(created_at) as d, COUNT(*) as cnt")
            ->groupBy('country_code', 'd')
            ->get();

        // 2. Total by country
        $totalByCountry = (clone $candidateBase)
            ->selectRaw("country_code, COUNT(*) as total")
            ->groupBy('country_code')
            ->pluck('total', 'country_code');

        // 3. Verified by country
        $verifiedByCountry = (clone $candidateBase)
            ->whereNotNull('email_verified_at')
            ->selectRaw("country_code, COUNT(*) as total")
            ->groupBy('country_code')
            ->pluck('total', 'country_code');

        // 4. Weekly active (logged in or had timeline event in last 7 days)
        $weeklyActive = DB::table('candidate_timeline_events')
            ->join('pool_candidates', 'candidate_timeline_events.pool_candidate_id', '=', 'pool_candidates.id')
            ->where('pool_candidates.is_demo', false)
            ->where('pool_candidates.primary_industry', 'maritime')
            ->where('candidate_timeline_events.created_at', '>=', now()->subDays(7))
            ->when($countryFilter, fn($q) => $q->where('pool_candidates.country_code', $countryFilter))
            ->selectRaw("pool_candidates.country_code, COUNT(DISTINCT pool_candidates.id) as cnt")
            ->groupBy('pool_candidates.country_code')
            ->pluck('cnt', 'country_code');

        // 5. Interview completion (logbook activation proxy)
        $interviewsCompleted = DB::table('form_interviews')
            ->join('pool_candidates', 'form_interviews.pool_candidate_id', '=', 'pool_candidates.id')
            ->where('form_interviews.is_demo', false)
            ->where('form_interviews.status', 'completed')
            ->where('pool_candidates.primary_industry', 'maritime')
            ->where('form_interviews.completed_at', '>=', $start)
            ->when($countryFilter, fn($q) => $q->where('pool_candidates.country_code', $countryFilter))
            ->selectRaw("pool_candidates.country_code, COUNT(*) as cnt")
            ->groupBy('pool_candidates.country_code')
            ->pluck('cnt', 'country_code');

        // 6. Referral stats
        $referrals = (clone $candidateBase)
            ->whereNotNull('referred_by_id')
            ->where('created_at', '>=', $start)
            ->selectRaw("country_code, COUNT(*) as cnt")
            ->groupBy('country_code')
            ->pluck('cnt', 'country_code');

        // Build per-country result
        $countries = $totalByCountry->keys()
            ->merge($verifiedByCountry->keys())
            ->unique()
            ->sort()
            ->values();

        $perCountry = [];
        foreach ($countries as $cc) {
            $total = $totalByCountry[$cc] ?? 0;
            $verified = $verifiedByCountry[$cc] ?? 0;
            $active = $weeklyActive[$cc] ?? 0;

            // Daily signups for this country
            $dailySignups = $signups->where('country_code', $cc);
            $avgSignupsPerDay = $days > 0 ? round($dailySignups->sum('cnt') / $days, 1) : 0;

            $perCountry[] = [
                'country_code' => $cc,
                'total' => $total,
                'new_last_period' => $dailySignups->sum('cnt'),
                'avg_signups_per_day' => $avgSignupsPerDay,
                'verified' => $verified,
                'verified_pct' => $total > 0 ? round(($verified / $total) * 100, 1) : 0,
                'weekly_active' => $active,
                'weekly_active_pct' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
                'interviews_completed' => $interviewsCompleted[$cc] ?? 0,
                'logbook_activation_pct' => $total > 0 ? round((($interviewsCompleted[$cc] ?? 0) / $total) * 100, 1) : 0,
                'referrals' => $referrals[$cc] ?? 0,
            ];
        }

        // Sort by total desc
        usort($perCountry, fn($a, $b) => $b['total'] - $a['total']);

        // Global summary
        $globalTotal = $totalByCountry->sum();
        $globalVerified = $verifiedByCountry->sum();
        $globalActive = $weeklyActive->sum();
        $globalNewSignups = $signups->sum('cnt');

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'global' => [
                    'total' => $globalTotal,
                    'new_signups' => $globalNewSignups,
                    'avg_signups_per_day' => $days > 0 ? round($globalNewSignups / $days, 1) : 0,
                    'verified_pct' => $globalTotal > 0 ? round(($globalVerified / $globalTotal) * 100, 1) : 0,
                    'weekly_active_pct' => $globalTotal > 0 ? round(($globalActive / $globalTotal) * 100, 1) : 0,
                ],
                'countries' => $perCountry,
            ],
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
