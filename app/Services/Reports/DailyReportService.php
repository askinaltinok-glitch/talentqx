<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    private Carbon $date;
    private Carbon $startOfDay;
    private Carbon $endOfDay;

    public function __construct(?Carbon $date = null)
    {
        $this->date = $date ?? now();
        $this->startOfDay = $this->date->copy()->startOfDay();
        $this->endOfDay = $this->date->copy()->endOfDay();
    }

    /**
     * Collect all daily metrics.
     */
    public function collect(): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'date_label' => $this->date->format('d.m.Y'),
            'candidates' => $this->candidateMetrics(),
            'interviews' => $this->interviewMetrics(),
            'company' => $this->companyMetrics(),
            'engagement' => $this->engagementMetrics(),
            'funnel' => $this->funnelMetrics(),
            'emails' => $this->emailMetrics(),
            'monthly_trend' => $this->monthlyTrend(),
            'summary' => $this->summaryMetrics(),
        ];
    }

    private function candidateMetrics(): array
    {
        $base = DB::table('pool_candidates')
            ->whereBetween('created_at', [$this->startOfDay, $this->endOfDay])
            ->where('is_demo', false);

        $total = (clone $base)->count();

        $byLanguage = (clone $base)
            ->selectRaw("COALESCE(preferred_language, 'tr') as lang, COUNT(*) as cnt")
            ->groupBy('lang')
            ->pluck('cnt', 'lang')
            ->toArray();

        $bySource = (clone $base)
            ->selectRaw("COALESCE(source_channel, 'unknown') as src, COUNT(*) as cnt")
            ->groupBy('src')
            ->pluck('cnt', 'src')
            ->toArray();

        $byPosition = (clone $base)
            ->selectRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(source_meta, '$.rank')), 'unknown') as pos, COUNT(*) as cnt")
            ->groupBy('pos')
            ->pluck('cnt', 'pos')
            ->toArray();

        $byCountry = (clone $base)
            ->selectRaw("COALESCE(country_code, '??') as cc, COUNT(*) as cnt")
            ->groupBy('cc')
            ->orderByDesc('cnt')
            ->limit(10)
            ->pluck('cnt', 'cc')
            ->toArray();

        $byStatus = DB::table('pool_candidates')
            ->where('is_demo', false)
            ->selectRaw("status, COUNT(*) as cnt")
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        return [
            'today_new' => $total,
            'by_language' => $byLanguage,
            'by_source' => $bySource,
            'by_position' => $byPosition,
            'by_country' => $byCountry,
            'total_by_status' => $byStatus,
            'total_all' => DB::table('pool_candidates')->where('is_demo', false)->count(),
        ];
    }

    private function interviewMetrics(): array
    {
        $base = DB::table('form_interviews')->where('is_demo', false);

        $todayStarted = (clone $base)
            ->whereBetween('created_at', [$this->startOfDay, $this->endOfDay])
            ->count();

        $todayCompleted = (clone $base)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$this->startOfDay, $this->endOfDay])
            ->count();

        $todayInProgress = (clone $base)
            ->where('status', 'in_progress')
            ->whereBetween('created_at', [$this->startOfDay, $this->endOfDay])
            ->count();

        $decisions = (clone $base)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$this->startOfDay, $this->endOfDay])
            ->selectRaw("COALESCE(decision, 'UNKNOWN') as d, COUNT(*) as cnt")
            ->groupBy('d')
            ->pluck('cnt', 'd')
            ->toArray();

        $scores = (clone $base)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$this->startOfDay, $this->endOfDay])
            ->whereNotNull('final_score');

        $avgScore = (clone $scores)->avg('final_score');
        $medianScore = $this->median((clone $scores)->pluck('final_score')->toArray());

        $topRoles = (clone $base)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$this->startOfDay, $this->endOfDay])
            ->selectRaw("COALESCE(position_code, 'generic') as role, COUNT(*) as cnt")
            ->groupBy('role')
            ->orderByDesc('cnt')
            ->limit(5)
            ->pluck('cnt', 'role')
            ->toArray();

        return [
            'today_started' => $todayStarted,
            'today_completed' => $todayCompleted,
            'today_in_progress' => $todayInProgress,
            'decisions' => $decisions,
            'avg_score' => $avgScore ? round($avgScore, 1) : null,
            'median_score' => $medianScore,
            'top_roles' => $topRoles,
            'total_completed' => (clone $base)->where('status', 'completed')->count(),
        ];
    }

    private function companyMetrics(): array
    {
        $creditUsageToday = DB::table('credit_usage_logs')
            ->whereBetween('created_at', [$this->startOfDay, $this->endOfDay])
            ->where('action', 'deduct')
            ->sum('amount');

        $activeCompanies = DB::table('companies')
            ->where('subscription_ends_at', '>', now())
            ->count();

        $presentationsToday = DB::table('candidate_presentations')
            ->whereBetween('presented_at', [$this->startOfDay, $this->endOfDay])
            ->count();

        $talentRequestsOpen = DB::table('talent_requests')
            ->where('status', 'open')
            ->count();

        return [
            'credits_used_today' => abs($creditUsageToday),
            'active_companies' => $activeCompanies,
            'presentations_today' => $presentationsToday,
            'talent_requests_open' => $talentRequestsOpen,
        ];
    }

    private function engagementMetrics(): array
    {
        $profileViewsToday = DB::table('candidate_profile_views')
            ->whereBetween('viewed_at', [$this->startOfDay, $this->endOfDay])
            ->where('is_demo', false)
            ->count();

        $viewerTypes = DB::table('candidate_profile_views')
            ->whereBetween('viewed_at', [$this->startOfDay, $this->endOfDay])
            ->where('is_demo', false)
            ->selectRaw("viewer_type, COUNT(*) as cnt")
            ->groupBy('viewer_type')
            ->pluck('cnt', 'viewer_type')
            ->toArray();

        return [
            'profile_views_today' => $profileViewsToday,
            'viewer_types' => $viewerTypes,
        ];
    }

    private function funnelMetrics(): array
    {
        $base = DB::table('apply_form_events')
            ->whereBetween('created_at', [$this->startOfDay, $this->endOfDay]);

        $uniqueSessions = (clone $base)->distinct('session_id')->count('session_id');

        $byStep = (clone $base)
            ->where('event_type', 'step_view')
            ->selectRaw("step_number, COUNT(DISTINCT session_id) as cnt")
            ->groupBy('step_number')
            ->pluck('cnt', 'step_number')
            ->toArray();

        $submits = (clone $base)
            ->where('event_type', 'submit')
            ->distinct('session_id')
            ->count('session_id');

        $abandons = (clone $base)
            ->where('event_type', 'abandon')
            ->distinct('session_id')
            ->count('session_id');

        $byCountry = (clone $base)
            ->selectRaw("COALESCE(country_code, '??') as cc, COUNT(DISTINCT session_id) as cnt")
            ->groupBy('cc')
            ->orderByDesc('cnt')
            ->limit(10)
            ->pluck('cnt', 'cc')
            ->toArray();

        return [
            'unique_sessions' => $uniqueSessions,
            'step_views' => $byStep,
            'submits' => $submits,
            'abandons' => $abandons,
            'conversion_rate' => $uniqueSessions > 0 ? round(($submits / $uniqueSessions) * 100, 1) : 0,
            'by_country' => $byCountry,
        ];
    }

    private function emailMetrics(): array
    {
        $sent = DB::table('candidate_email_logs')
            ->whereBetween('created_at', [$this->startOfDay, $this->endOfDay])
            ->where('status', 'sent')
            ->count();

        $failed = DB::table('candidate_email_logs')
            ->whereBetween('created_at', [$this->startOfDay, $this->endOfDay])
            ->whereIn('status', ['failed', 'failed_permanent'])
            ->count();

        $byType = DB::table('candidate_email_logs')
            ->whereBetween('created_at', [$this->startOfDay, $this->endOfDay])
            ->selectRaw("mail_type, COUNT(*) as cnt")
            ->groupBy('mail_type')
            ->pluck('cnt', 'mail_type')
            ->toArray();

        return [
            'sent' => $sent,
            'failed' => $failed,
            'by_type' => $byType,
        ];
    }

    /**
     * Daily candidate counts for current month + previous month.
     */
    public function monthlyTrend(): array
    {
        $currentMonth = $this->date->copy()->startOfMonth();
        $previousMonth = $this->date->copy()->subMonth()->startOfMonth();
        $daysInMonth = $this->date->daysInMonth;
        $prevDaysInMonth = $this->date->copy()->subMonth()->daysInMonth;

        $currentData = $this->dailyCounts('pool_candidates', $currentMonth, $daysInMonth);
        $previousData = $this->dailyCounts('pool_candidates', $previousMonth, $prevDaysInMonth);

        // Calculate trends
        $currentTotal = array_sum($currentData);
        $previousTotal = array_sum($previousData);
        $mom = $previousTotal > 0 ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1) : 0;

        // Last 7 days trend
        $last7 = 0;
        $prev7 = 0;
        $today = $this->date->day;
        for ($d = max(1, $today - 6); $d <= $today; $d++) {
            $last7 += $currentData[$d] ?? 0;
        }
        for ($d = max(1, $today - 13); $d <= max(1, $today - 7); $d++) {
            $prev7 += $currentData[$d] ?? 0;
        }
        $weekTrend = $prev7 > 0 ? round((($last7 - $prev7) / $prev7) * 100, 1) : 0;

        return [
            'current_month' => $currentData,
            'previous_month' => $previousData,
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'mom_pct' => $mom,
            'week_trend_pct' => $weekTrend,
            'days_in_month' => $daysInMonth,
        ];
    }

    private function dailyCounts(string $table, Carbon $monthStart, int $days): array
    {
        $rows = DB::table($table)
            ->whereBetween('created_at', [$monthStart, $monthStart->copy()->endOfMonth()])
            ->where('is_demo', false)
            ->selectRaw("DAY(created_at) as d, COUNT(*) as cnt")
            ->groupBy('d')
            ->pluck('cnt', 'd')
            ->toArray();

        $result = [];
        for ($d = 1; $d <= $days; $d++) {
            $result[$d] = $rows[$d] ?? 0;
        }
        return $result;
    }

    private function summaryMetrics(): array
    {
        // Yesterday comparison
        $yesterdayCandidates = DB::table('pool_candidates')
            ->where('is_demo', false)
            ->whereBetween('created_at', [
                $this->startOfDay->copy()->subDay(),
                $this->startOfDay,
            ])->count();

        $todayCandidates = DB::table('pool_candidates')
            ->where('is_demo', false)
            ->whereBetween('created_at', [$this->startOfDay, $this->endOfDay])
            ->count();

        $changeVsYesterday = $yesterdayCandidates > 0
            ? round((($todayCandidates - $yesterdayCandidates) / $yesterdayCandidates) * 100, 1)
            : 0;

        return [
            'today_candidates' => $todayCandidates,
            'yesterday_candidates' => $yesterdayCandidates,
            'change_vs_yesterday' => $changeVsYesterday,
        ];
    }

    private function median(array $values): ?float
    {
        if (empty($values)) return null;
        sort($values);
        $count = count($values);
        $mid = (int) floor($count / 2);
        if ($count % 2 === 0) {
            return round(($values[$mid - 1] + $values[$mid]) / 2, 1);
        }
        return (float) $values[$mid];
    }
}
