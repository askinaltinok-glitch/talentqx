<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use App\Models\Job;
use App\Services\Billing\CreditService;
use App\Services\Interview\AnalysisEngine;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private AnalysisEngine $analysisEngine,
        private CreditService $creditService
    ) {}

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;
        $jobId = $request->get('job_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $jobsQuery = Job::query();
        $candidatesQuery = Candidate::query();
        $interviewsQuery = Interview::query();

        if (!$user->is_platform_admin) {
            $jobsQuery->where('company_id', $companyId);
            $candidatesQuery->whereHas('job', fn($q) => $q->where('company_id', $companyId));
            $interviewsQuery->whereHas('job', fn($q) => $q->where('company_id', $companyId));
        } elseif ($request->has('company_id')) {
            $filterCompanyId = $request->company_id;
            $jobsQuery->where('company_id', $filterCompanyId);
            $candidatesQuery->whereHas('job', fn($q) => $q->where('company_id', $filterCompanyId));
            $interviewsQuery->whereHas('job', fn($q) => $q->where('company_id', $filterCompanyId));
        }

        if ($jobId) {
            $candidatesQuery->where('job_id', $jobId);
            $interviewsQuery->where('job_id', $jobId);
        }

        if ($dateFrom) {
            $candidatesQuery->where('created_at', '>=', $dateFrom);
            $interviewsQuery->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $candidatesQuery->where('created_at', '<=', $dateTo);
            $interviewsQuery->where('created_at', '<=', $dateTo);
        }

        $totalJobs = $jobsQuery->count();
        $activeJobs = (clone $jobsQuery)->where('status', 'active')->count();
        $totalCandidates = $candidatesQuery->count();

        $interviewsCompleted = (clone $interviewsQuery)->where('status', 'completed')->count();
        $interviewsPending = (clone $interviewsQuery)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $avgScoreQuery = DB::table('interview_analyses')
            ->join('interviews', 'interview_analyses.interview_id', '=', 'interviews.id')
            ->join('job_postings', 'interviews.job_id', '=', 'job_postings.id')
            ->when($jobId, fn($q) => $q->where('interviews.job_id', $jobId));

        if (!$user->is_platform_admin) {
            $avgScoreQuery->where('job_postings.company_id', $companyId);
        } elseif ($request->has('company_id')) {
            $avgScoreQuery->where('job_postings.company_id', $request->company_id);
        }

        $avgScore = $avgScoreQuery->avg('overall_score');

        $hiredCount = (clone $candidatesQuery)->where('status', 'hired')->count();
        $hireRate = $totalCandidates > 0 ? round($hiredCount / $totalCandidates, 2) : 0;

        $redFlagQuery = DB::table('interview_analyses')
            ->join('interviews', 'interview_analyses.interview_id', '=', 'interviews.id')
            ->join('job_postings', 'interviews.job_id', '=', 'job_postings.id')
            ->when($jobId, fn($q) => $q->where('interviews.job_id', $jobId))
            ->whereJsonContains('red_flag_analysis->flags_detected', true);

        if (!$user->is_platform_admin) {
            $redFlagQuery->where('job_postings.company_id', $companyId);
        } elseif ($request->has('company_id')) {
            $redFlagQuery->where('job_postings.company_id', $request->company_id);
        }

        $redFlagCount = $redFlagQuery->count();

        $redFlagRate = $interviewsCompleted > 0 ? round($redFlagCount / $interviewsCompleted, 2) : 0;

        $byStatus = $candidatesQuery
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Get credit status for the user's company
        $credits = null;
        if (!$user->is_platform_admin && $companyId) {
            $company = Company::find($companyId);
            if ($company) {
                $credits = $this->creditService->getCreditStatus($company);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_jobs' => $totalJobs,
                'active_jobs' => $activeJobs,
                'total_candidates' => $totalCandidates,
                'interviews_completed' => $interviewsCompleted,
                'interviews_pending' => $interviewsPending,
                'average_score' => round($avgScore ?? 0, 1),
                'hire_rate' => $hireRate,
                'red_flag_rate' => $redFlagRate,
                'by_status' => [
                    'applied' => $byStatus['applied'] ?? 0,
                    'interview_pending' => $byStatus['interview_pending'] ?? 0,
                    'interview_completed' => $byStatus['interview_completed'] ?? 0,
                    'under_review' => $byStatus['under_review'] ?? 0,
                    'shortlisted' => $byStatus['shortlisted'] ?? 0,
                    'hired' => $byStatus['hired'] ?? 0,
                    'rejected' => $byStatus['rejected'] ?? 0,
                ],
                'credits' => $credits,
            ],
        ]);
    }

    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_ids' => 'required|array|min:2|max:5',
            'candidate_ids.*' => 'uuid|exists:candidates,id',
        ]);

        $user = $request->user();
        $query = Candidate::with(['job', 'latestInterview.analysis'])
            ->whereIn('id', $validated['candidate_ids']);

        if (!$user->is_platform_admin) {
            $query->whereHas('job', fn($q) => $q->where('company_id', $user->company_id));
        }

        $candidates = $query->get();

        if ($candidates->count() < 2) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INSUFFICIENT_CANDIDATES',
                    'message' => 'Karsilastirma icin en az 2 aday gerekli.',
                ],
            ], 422);
        }

        $competencies = $candidates->first()->job->getEffectiveCompetencies();
        $competencyCodes = collect($competencies)->pluck('code')->toArray();

        $comparisonData = $candidates->map(function ($candidate) use ($competencyCodes) {
            $analysis = $candidate->latestInterview?->analysis;

            $competencyScores = [];
            foreach ($competencyCodes as $code) {
                $competencyScores[$code] = $analysis?->getCompetencyScore($code) ?? 0;
            }

            return [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'overall_score' => $analysis?->overall_score ?? 0,
                'recommendation' => $analysis?->getRecommendation() ?? 'N/A',
                'confidence_percent' => $analysis?->getConfidencePercent() ?? 0,
                'competency_scores' => $competencyScores,
                'red_flags_count' => $analysis?->getRedFlagsCount() ?? 0,
                'culture_fit' => $analysis?->getCultureFitScore() ?? 0,
            ];
        });

        $bestOverall = $comparisonData->sortByDesc('overall_score')->first()['id'];

        $bestByCompetency = [];
        foreach ($competencyCodes as $code) {
            $best = $comparisonData->sortByDesc(fn($c) => $c['competency_scores'][$code] ?? 0)->first();
            $bestByCompetency[$code] = $best['id'];
        }

        $topCandidate = $comparisonData->sortByDesc('overall_score')->first();
        $summary = "{$topCandidate['name']} en yuksek puana ({$topCandidate['overall_score']}) sahip";
        if ($topCandidate['red_flags_count'] === 0) {
            $summary .= ' ve kirmizi bayrak tespit edilmedi.';
        } else {
            $summary .= " ancak {$topCandidate['red_flags_count']} kirmizi bayrak tespit edildi.";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'candidates' => $comparisonData->values(),
                'comparison' => [
                    'best_overall' => $bestOverall,
                    'best_by_competency' => $bestByCompetency,
                    'recommendation_summary' => $summary,
                ],
            ],
        ]);
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|uuid|exists:job_postings,id',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $user = $request->user();
        $jobQuery = Job::query();

        if (!$user->is_platform_admin) {
            $jobQuery->where('company_id', $user->company_id);
        }

        $job = $jobQuery->findOrFail($validated['job_id']);

        $limit = $validated['limit'] ?? 10;

        $candidates = Candidate::with(['latestInterview.analysis'])
            ->where('job_id', $job->id)
            ->whereHas('latestInterview.analysis')
            ->get()
            ->sortByDesc(fn($c) => $c->latestInterview?->analysis?->overall_score ?? 0)
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $candidates->map(fn($candidate, $index) => [
                'rank' => $index + 1,
                'candidate' => [
                    'id' => $candidate->id,
                    'name' => $candidate->full_name,
                ],
                'overall_score' => $candidate->latestInterview?->analysis?->overall_score ?? 0,
                'recommendation' => $candidate->latestInterview?->analysis?->getRecommendation(),
                'has_red_flags' => $candidate->latestInterview?->analysis?->hasRedFlags() ?? false,
            ]),
        ]);
    }

    /**
     * Interview punctuality/time discipline analytics.
     *
     * GET /dashboard/punctuality?from=YYYY-MM-DD&to=YYYY-MM-DD&group_by=role|branch|hour
     */
    public function punctuality(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        // Default: last 7 days
        $from = $request->get('from')
            ? Carbon::parse($request->get('from'))->startOfDay()
            : Carbon::now()->subDays(7)->startOfDay();
        $to = $request->get('to')
            ? Carbon::parse($request->get('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        $groupBy = $request->get('group_by', 'role'); // role, branch, hour

        // Base query: only scheduled interviews within date range
        $baseQuery = Interview::query()
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$from, $to]);

        if (!$user->is_platform_admin) {
            $baseQuery->whereHas('job', fn($q) => $q->where('company_id', $companyId));
        } elseif ($request->has('company_id')) {
            $baseQuery->whereHas('job', fn($q) => $q->where('company_id', $request->company_id));
            $companyId = $request->company_id;
        } else {
            $companyId = null; // Platform admin without filter - all data
        }

        // KPIs
        $scheduled = (clone $baseQuery)->count();
        $joined = (clone $baseQuery)->whereNotNull('joined_at')->count();
        $noShow = (clone $baseQuery)->whereNotNull('no_show_marked_at')->count();
        $onTime = (clone $baseQuery)->whereNotNull('joined_at')->where('late_minutes', 0)->count();

        // Late minutes stats (only for joined interviews)
        $lateStats = (clone $baseQuery)
            ->whereNotNull('joined_at')
            ->selectRaw('
                AVG(late_minutes) as avg_late,
                MAX(late_minutes) as max_late
            ')
            ->first();

        // Percentile calculations (MySQL compatible)
        $latePercentiles = $this->calculateLatePercentiles($companyId, $from, $to);

        $kpis = [
            'scheduled' => $scheduled,
            'joined' => $joined,
            'no_show' => $noShow,
            'no_show_rate' => $scheduled > 0 ? round($noShow / $scheduled, 3) : 0,
            'join_rate' => $scheduled > 0 ? round($joined / $scheduled, 3) : 0,
            'on_time' => $onTime,
            'on_time_rate' => $joined > 0 ? round($onTime / $joined, 3) : 0,
            'median_late_minutes' => $latePercentiles['p50'],
            'p75_late_minutes' => $latePercentiles['p75'],
            'avg_late_minutes' => round($lateStats->avg_late ?? 0, 1),
            'max_late_minutes' => (int) ($lateStats->max_late ?? 0),
        ];

        // Breakdown by group
        $breakdown = $this->getPunctualityBreakdown($companyId, $from, $to, $groupBy);

        return response()->json([
            'success' => true,
            'data' => [
                'range' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'kpis' => $kpis,
                'group_by' => $groupBy,
                'breakdown' => $breakdown,
            ],
        ]);
    }

    /**
     * Export punctuality breakdown as CSV.
     *
     * GET /dashboard/punctuality/export?from=YYYY-MM-DD&to=YYYY-MM-DD&group_by=role|branch|hour
     */
    public function punctualityExport(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;

        if ($user->is_platform_admin && $request->has('company_id')) {
            $companyId = $request->company_id;
        } elseif ($user->is_platform_admin) {
            $companyId = null;
        }

        $from = $request->get('from')
            ? Carbon::parse($request->get('from'))->startOfDay()
            : Carbon::now()->subDays(7)->startOfDay();
        $to = $request->get('to')
            ? Carbon::parse($request->get('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        $groupBy = $request->get('group_by', 'role');

        $breakdown = $this->getPunctualityBreakdown($companyId, $from, $to, $groupBy);

        // Build CSV content
        $groupLabel = match ($groupBy) {
            'branch' => 'Şube',
            'hour' => 'Saat',
            default => 'Pozisyon',
        };

        $headers = [$groupLabel, 'Planlanan', 'Katılan', 'Gelmedi', 'Zamanında', 'No-show %', 'Katılım %', 'Ort. Gecikme (dk)'];
        $csvRows = [];
        $csvRows[] = implode(',', $headers);

        foreach ($breakdown as $row) {
            $key = $groupBy === 'hour' ? "{$row['key']}:00" : $row['key'];
            $csvRows[] = implode(',', [
                '"' . str_replace('"', '""', $key) . '"',
                $row['scheduled'],
                $row['joined'],
                $row['no_show'],
                $row['on_time'],
                round($row['no_show_rate'] * 100) . '%',
                round($row['join_rate'] * 100) . '%',
                number_format($row['avg_late_minutes'], 1),
            ]);
        }

        $csvContent = implode("\n", $csvRows);
        $filename = "zaman-disiplini-{$groupBy}-{$from->format('Y-m-d')}-{$to->format('Y-m-d')}.csv";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Calculate late minutes percentiles (p50/median and p75) using MySQL-compatible approach.
     */
    private function calculateLatePercentiles(?string $companyId, Carbon $from, Carbon $to): array
    {
        $query = Interview::query()
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$from, $to])
            ->whereNotNull('joined_at')
            ->orderBy('late_minutes');

        if ($companyId) {
            $query->whereHas('job', fn($q) => $q->where('company_id', $companyId));
        }

        $values = $query->pluck('late_minutes')->toArray();

        if (empty($values)) {
            return ['p50' => null, 'p75' => null];
        }

        $count = count($values);

        // Calculate p50 (median)
        $p50Index = (int) floor($count / 2);
        if ($count % 2 === 0) {
            $p50 = (int) round(($values[$p50Index - 1] + $values[$p50Index]) / 2);
        } else {
            $p50 = (int) $values[$p50Index];
        }

        // Calculate p75 (75th percentile)
        $p75Index = (int) floor($count * 0.75);
        $p75 = (int) $values[min($p75Index, $count - 1)];

        return ['p50' => $p50, 'p75' => $p75];
    }

    /**
     * Get punctuality breakdown by role, branch, or hour.
     */
    private function getPunctualityBreakdown(?string $companyId, Carbon $from, Carbon $to, string $groupBy): array
    {
        $query = Interview::query()
            ->join('job_postings', 'interviews.job_id', '=', 'job_postings.id')
            ->whereNotNull('interviews.scheduled_at')
            ->whereBetween('interviews.scheduled_at', [$from, $to]);

        if ($companyId) {
            $query->where('job_postings.company_id', $companyId);
        }

        switch ($groupBy) {
            case 'branch':
                $query->leftJoin('branches', 'job_postings.branch_id', '=', 'branches.id')
                    ->selectRaw("
                        COALESCE(branches.name, job_postings.location, 'Belirtilmemiş') as group_key,
                        COUNT(*) as scheduled,
                        SUM(CASE WHEN interviews.joined_at IS NOT NULL THEN 1 ELSE 0 END) as joined,
                        SUM(CASE WHEN interviews.no_show_marked_at IS NOT NULL THEN 1 ELSE 0 END) as no_show,
                        SUM(CASE WHEN interviews.joined_at IS NOT NULL AND interviews.late_minutes = 0 THEN 1 ELSE 0 END) as on_time,
                        AVG(CASE WHEN interviews.joined_at IS NOT NULL THEN interviews.late_minutes END) as avg_late
                    ")
                    ->groupByRaw("COALESCE(branches.name, job_postings.location, 'Belirtilmemiş')");
                break;

            case 'hour':
                $query->selectRaw("
                        HOUR(interviews.scheduled_at) as group_key,
                        COUNT(*) as scheduled,
                        SUM(CASE WHEN interviews.joined_at IS NOT NULL THEN 1 ELSE 0 END) as joined,
                        SUM(CASE WHEN interviews.no_show_marked_at IS NOT NULL THEN 1 ELSE 0 END) as no_show,
                        SUM(CASE WHEN interviews.joined_at IS NOT NULL AND interviews.late_minutes = 0 THEN 1 ELSE 0 END) as on_time,
                        AVG(CASE WHEN interviews.joined_at IS NOT NULL THEN interviews.late_minutes END) as avg_late
                    ")
                    ->groupByRaw('HOUR(interviews.scheduled_at)');
                break;

            case 'role':
            default:
                $query->selectRaw("
                        job_postings.title as group_key,
                        COUNT(*) as scheduled,
                        SUM(CASE WHEN interviews.joined_at IS NOT NULL THEN 1 ELSE 0 END) as joined,
                        SUM(CASE WHEN interviews.no_show_marked_at IS NOT NULL THEN 1 ELSE 0 END) as no_show,
                        SUM(CASE WHEN interviews.joined_at IS NOT NULL AND interviews.late_minutes = 0 THEN 1 ELSE 0 END) as on_time,
                        AVG(CASE WHEN interviews.joined_at IS NOT NULL THEN interviews.late_minutes END) as avg_late
                    ")
                    ->groupBy('job_postings.title');
                break;
        }

        $results = $query->orderByDesc('scheduled')->limit(20)->get();

        return $results->map(function ($row) {
            $scheduled = (int) $row->scheduled;
            $joined = (int) $row->joined;
            $noShow = (int) $row->no_show;

            return [
                'key' => $row->group_key,
                'scheduled' => $scheduled,
                'joined' => $joined,
                'no_show' => $noShow,
                'on_time' => (int) $row->on_time,
                'no_show_rate' => $scheduled > 0 ? round($noShow / $scheduled, 3) : 0,
                'join_rate' => $scheduled > 0 ? round($joined / $scheduled, 3) : 0,
                'avg_late_minutes' => round($row->avg_late ?? 0, 1),
            ];
        })->toArray();
    }
}
