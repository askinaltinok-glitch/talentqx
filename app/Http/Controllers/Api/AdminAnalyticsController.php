<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidatePresentation;
use App\Models\FormInterview;
use App\Models\PoolCandidate;
use App\Models\TalentRequest;
use App\Services\Analytics\PositionBaselineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminAnalyticsController extends Controller
{
    public function __construct(
        private readonly PositionBaselineService $baselineService
    ) {}
    /**
     * Get filter options for interview analytics dropdowns.
     *
     * GET /v1/admin/analytics/interviews/filters
     */
    public function interviewFilters(): JsonResponse
    {
        // Positions from form_interviews
        $fiPositions = FormInterview::where('status', FormInterview::STATUS_COMPLETED)
            ->whereNotNull('position_code')
            ->distinct()
            ->pluck('position_code')
            ->toArray();

        // Positions from QR interviews (job titles)
        $qrPositions = DB::table('interviews')
            ->join('job_postings', 'job_postings.id', '=', 'interviews.job_id')
            ->where('interviews.status', 'completed')
            ->whereNotNull('job_postings.title')
            ->distinct()
            ->pluck('job_postings.title')
            ->toArray();

        $positions = collect(array_merge($fiPositions, $qrPositions))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // All companies
        $allCompanies = DB::table('companies')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'positions' => $positions,
                'companies' => $allCompanies,
            ],
        ]);
    }

    /**
     * Get interview analytics summary.
     * Includes both form_interviews (AI form) and interviews (QR flow).
     *
     * GET /v1/admin/analytics/interviews/summary
     * Query params: from (date), to (date), company_id, position_code
     */
    public function interviewsSummary(Request $request): JsonResponse
    {
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        // ── Form Interviews (AI form) ──
        $fiQuery = FormInterview::where('status', FormInterview::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$from, $to]);

        if ($request->filled('company_id')) {
            $fiQuery->where('company_id', $request->input('company_id'));
        }
        if ($request->filled('position_code')) {
            $fiQuery->where('position_code', $request->input('position_code'));
        }

        $fiTotal = (clone $fiQuery)->count();
        $fiAvg = (clone $fiQuery)->whereNotNull('final_score')->avg('final_score');

        $fiByDecision = (clone $fiQuery)
            ->select('decision', DB::raw('COUNT(*) as count'))
            ->whereNotNull('decision')
            ->groupBy('decision')
            ->pluck('count', 'decision')
            ->toArray();

        $fiByPosition = (clone $fiQuery)
            ->select('position_code', DB::raw('COUNT(*) as count'))
            ->groupBy('position_code')
            ->pluck('count', 'position_code')
            ->toArray();

        $fiTimeseries = (clone $fiQuery)
            ->select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN decision = "HIRE" THEN 1 ELSE 0 END) as hire'),
                DB::raw('SUM(CASE WHEN decision = "HOLD" THEN 1 ELSE 0 END) as hold'),
                DB::raw('SUM(CASE WHEN decision = "REJECT" THEN 1 ELSE 0 END) as reject')
            )
            ->groupBy(DB::raw('DATE(completed_at)'))
            ->get()
            ->keyBy('date')
            ->toArray();

        // Risk flags from form_interviews
        $riskFlagCounts = [];
        $fiFlags = (clone $fiQuery)->whereNotNull('risk_flags')->pluck('risk_flags');
        foreach ($fiFlags as $flags) {
            $flagsArray = is_array($flags) ? $flags : json_decode($flags, true);
            if (!is_array($flagsArray)) continue;
            foreach ($flagsArray as $flag) {
                $code = $flag['code'] ?? $flag['flag_code'] ?? null;
                if ($code) {
                    $riskFlagCounts[$code] = ($riskFlagCounts[$code] ?? 0) + 1;
                }
            }
        }

        // ── QR Interviews ──
        $qrQuery = DB::table('interviews')
            ->where('interviews.status', 'completed')
            ->whereBetween('interviews.completed_at', [$from, $to]);

        if ($request->filled('company_id')) {
            $qrQuery->join('job_postings', 'job_postings.id', '=', 'interviews.job_id')
                ->where('job_postings.company_id', $request->input('company_id'));
        }

        $qrTotal = (clone $qrQuery)->count();

        // QR scores from interview_analyses
        $qrScores = DB::table('interviews')
            ->join('interview_analyses', 'interview_analyses.interview_id', '=', 'interviews.id')
            ->where('interviews.status', 'completed')
            ->whereBetween('interviews.completed_at', [$from, $to])
            ->whereNotNull('interview_analyses.overall_score');

        if ($request->filled('company_id')) {
            $qrScores->join('job_postings', 'job_postings.id', '=', 'interviews.job_id')
                ->where('job_postings.company_id', $request->input('company_id'));
        }

        $qrAvg = (clone $qrScores)->avg('interview_analyses.overall_score');
        $qrScoreCount = (clone $qrScores)->count();

        // QR by position (from job_postings.title)
        $qrByPosition = DB::table('interviews')
            ->join('job_postings', 'job_postings.id', '=', 'interviews.job_id')
            ->where('interviews.status', 'completed')
            ->whereBetween('interviews.completed_at', [$from, $to])
            ->select('job_postings.title as position_code', DB::raw('COUNT(*) as count'))
            ->groupBy('job_postings.title')
            ->pluck('count', 'position_code')
            ->toArray();

        // QR timeseries
        $qrTimeseries = DB::table('interviews')
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('DATE(completed_at)'))
            ->get()
            ->keyBy('date')
            ->toArray();

        // ── Merge results ──
        $total = $fiTotal + $qrTotal;

        // Weighted average score
        $fiScoreCount = (clone $fiQuery)->whereNotNull('final_score')->count();
        $avgFinalScore = null;
        if (($fiScoreCount + $qrScoreCount) > 0) {
            $avgFinalScore = round(
                (($fiAvg ?: 0) * $fiScoreCount + ($qrAvg ?: 0) * $qrScoreCount)
                / ($fiScoreCount + $qrScoreCount),
                1
            );
        }

        // Merge decisions
        $byDecision = array_merge(['HIRE' => 0, 'HOLD' => 0, 'REJECT' => 0], $fiByDecision);

        // Merge positions
        $mergedPositions = $fiByPosition;
        foreach ($qrByPosition as $pos => $cnt) {
            $mergedPositions[$pos] = ($mergedPositions[$pos] ?? 0) + $cnt;
        }
        arsort($mergedPositions);
        $byPosition = collect($mergedPositions)->take(10)->map(fn($count, $pos) => [
            'position_code' => $pos,
            'count' => $count,
        ])->values()->toArray();

        // Merge timeseries
        $allDates = array_unique(array_merge(array_keys($fiTimeseries), array_keys($qrTimeseries)));
        sort($allDates);
        $timeseriesDaily = [];
        foreach ($allDates as $date) {
            $fi = $fiTimeseries[$date] ?? null;
            $qr = $qrTimeseries[$date] ?? null;
            $timeseriesDaily[] = [
                'date' => $date,
                'total' => (int)($fi->total ?? 0) + (int)($qr->total ?? 0),
                'hire' => (int)($fi->hire ?? 0),
                'hold' => (int)($fi->hold ?? 0),
                'reject' => (int)($fi->reject ?? 0),
            ];
        }

        arsort($riskFlagCounts);
        $topRiskFlags = array_slice(
            array_map(fn($code, $count) => ['code' => $code, 'count' => $count],
                array_keys($riskFlagCounts),
                array_values($riskFlagCounts)
            ), 0, 10
        );

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'by_decision' => $byDecision,
                'avg_final_score' => $avgFinalScore,
                'by_position' => $byPosition,
                'top_risk_flags' => $topRiskFlags,
                'timeseries_daily' => $timeseriesDaily,
                'breakdown' => [
                    'form_interviews' => $fiTotal,
                    'qr_interviews' => $qrTotal,
                ],
            ],
            'meta' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    /**
     * Get interview list for drill-down.
     * Includes both form_interviews and QR interviews.
     *
     * GET /v1/admin/analytics/interviews
     * Query params: from, to, decision, position_code, company_id, page, per_page
     */
    public function interviewsList(Request $request): JsonResponse
    {
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        $perPage = min((int)($request->input('per_page', 20)), 100);

        // ── Form Interviews ──
        $fiQuery = FormInterview::where('status', FormInterview::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$from, $to]);

        if ($request->filled('decision')) {
            $fiQuery->where('decision', strtoupper($request->input('decision')));
        }
        if ($request->filled('position_code')) {
            $fiQuery->where('position_code', $request->input('position_code'));
        }
        if ($request->filled('company_id')) {
            $fiQuery->where('company_id', $request->input('company_id'));
        }
        if ($request->filled('language')) {
            $fiQuery->where('language', strtolower($request->input('language')));
        }

        $fiItems = $fiQuery->orderByDesc('completed_at')->get()->map(function ($fi) {
            $riskFlags = $fi->risk_flags;
            $riskFlagsCount = 0;
            if (is_array($riskFlags)) {
                $riskFlagsCount = count($riskFlags);
            } elseif (is_string($riskFlags)) {
                $decoded = json_decode($riskFlags, true);
                $riskFlagsCount = is_array($decoded) ? count($decoded) : 0;
            }

            return [
                'id' => $fi->id,
                'source' => 'form',
                'candidate_name' => null,
                'created_at' => $fi->created_at->toIso8601String(),
                'completed_at' => $fi->completed_at?->toIso8601String(),
                'position_code' => $fi->position_code,
                'language' => $fi->language,
                'final_score' => $fi->final_score,
                'decision' => $fi->decision,
                'risk_flags_count' => $riskFlagsCount,
            ];
        });

        // ── QR Interviews ──
        $qrQuery = DB::table('interviews')
            ->leftJoin('interview_analyses', 'interview_analyses.interview_id', '=', 'interviews.id')
            ->leftJoin('candidates', 'candidates.id', '=', 'interviews.candidate_id')
            ->leftJoin('job_postings', 'job_postings.id', '=', 'interviews.job_id')
            ->where('interviews.status', 'completed')
            ->whereBetween('interviews.completed_at', [$from, $to])
            ->select(
                'interviews.id',
                'interviews.completed_at',
                'interviews.created_at',
                'interviews.started_at',
                DB::raw("CONCAT(candidates.first_name, ' ', candidates.last_name) as candidate_name"),
                'job_postings.title as position_title',
                'job_postings.locale as language',
                'job_postings.company_id',
                'interview_analyses.overall_score as final_score',
                'interview_analyses.cheating_level'
            );

        if ($request->filled('company_id')) {
            $qrQuery->where('job_postings.company_id', $request->input('company_id'));
        }

        $qrItems = $qrQuery->orderByDesc('interviews.completed_at')->get()->map(fn($row) => [
            'id' => $row->id,
            'source' => 'qr',
            'candidate_name' => $row->candidate_name,
            'created_at' => $row->created_at,
            'completed_at' => $row->completed_at,
            'position_code' => $row->position_title,
            'language' => $row->language ?? 'tr',
            'final_score' => $row->final_score ? round($row->final_score, 1) : null,
            'decision' => null,
            'risk_flags_count' => ($row->cheating_level && $row->cheating_level !== 'low') ? 1 : 0,
        ]);

        // Merge and sort by completed_at desc
        $merged = $fiItems->concat($qrItems)
            ->sortByDesc('completed_at')
            ->values();

        $total = $merged->count();
        $page = max(1, (int)$request->input('page', 1));
        $paged = $merged->forPage($page, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paged,
            'meta' => [
                'current_page' => $page,
                'last_page' => (int)ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    /**
     * Get single interview detail for drill-down.
     *
     * GET /v1/admin/analytics/interviews/{id}
     */
    public function interviewDetail(string $id): JsonResponse
    {
        $interview = FormInterview::with('answers')->find($id);

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Interview not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $interview->id,
                'version' => $interview->version,
                'language' => $interview->language,
                'position_code' => $interview->position_code,
                'template_position_code' => $interview->template_position_code,
                'status' => $interview->status,
                'meta' => $interview->meta,
                'competency_scores' => $interview->competency_scores,
                'risk_flags' => $interview->risk_flags,
                'final_score' => $interview->final_score,
                'decision' => $interview->decision,
                'decision_reason' => $interview->decision_reason,
                'created_at' => $interview->created_at->toIso8601String(),
                'completed_at' => $interview->completed_at?->toIso8601String(),
                'answers' => $interview->answers->map(fn($a) => [
                    'id' => $a->id,
                    'slot' => $a->slot,
                    'competency' => $a->competency,
                    'question' => $a->question,
                    'answer' => $a->answer,
                    'score' => $a->score,
                    'score_reason' => $a->score_reason,
                    'red_flags' => $a->red_flags,
                    'positive_signals' => $a->positive_signals,
                ]),
            ],
        ]);
    }

    /**
     * Get position baseline statistics for calibration (v2).
     *
     * GET /v1/admin/analytics/positions/baseline
     * Query params: version, language, position (required), industry
     */
    public function positionBaseline(Request $request): JsonResponse
    {
        $version = $request->query('version', 'v1');
        $language = $request->query('language', 'tr');
        $position = $request->query('position');
        $industry = $request->query('industry', 'general');

        if (!$position) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'message' => 'position is required',
            ], 422);
        }

        // Get detailed baseline with v2 fallback chain
        $baseline = $this->baselineService->baselineDetailed($version, $language, $position, $industry);

        // Get industry breakdown for this position
        $industryBreakdown = $this->baselineService->industryBreakdown($version, $language, $position);

        return response()->json([
            'success' => true,
            'data' => $baseline,
            'industry_breakdown' => $industryBreakdown,
        ]);
    }

    /**
     * Get drift detection summary for last N days (v2).
     *
     * GET /v1/admin/analytics/drift
     * Query params: days (default 7), version, language, position, industry
     */
    public function driftSummary(Request $request): JsonResponse
    {
        $days = min((int) ($request->query('days', 7)), 30);
        $version = $request->query('version', 'v1');
        $language = $request->query('language');
        $position = $request->query('position');
        $industry = $request->query('industry');

        $from = Carbon::now()->subDays($days)->startOfDay();
        $to = Carbon::now()->endOfDay();

        // Base query for completed interviews in date range
        $query = FormInterview::where('status', FormInterview::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$from, $to])
            ->where('version', $version);

        if ($language) {
            $query->where('language', $language);
        }
        if ($position) {
            $query->where('template_position_code', $position);
        }
        if ($industry) {
            $query->where('industry_code', $industry);
        }

        $interviews = $query->get();
        $total = $interviews->count();

        if ($total === 0) {
            return response()->json([
                'success' => true,
                'data' => [
                    'period_days' => $days,
                    'total' => 0,
                    'alerts' => ['No completed interviews in period'],
                ],
                'meta' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
            ]);
        }

        // Decision distribution
        $byDecision = $interviews->groupBy('decision')->map->count();
        $byDecisionPct = [
            'HIRE' => round(($byDecision->get('HIRE', 0) / $total) * 100, 1),
            'HOLD' => round(($byDecision->get('HOLD', 0) / $total) * 100, 1),
            'REJECT' => round(($byDecision->get('REJECT', 0) / $total) * 100, 1),
        ];

        // Policy code distribution
        $byPolicyCode = $interviews->groupBy('policy_code')->map->count()->toArray();

        // Score statistics
        $rawScores = $interviews->pluck('raw_final_score')->filter()->values();
        $calScores = $interviews->pluck('calibrated_score')->filter()->values();
        $zScores = $interviews->pluck('z_score')->filter()->values();

        $avgRawScore = $rawScores->count() > 0 ? round($rawScores->avg(), 1) : null;
        $avgCalScore = $calScores->count() > 0 ? round($calScores->avg(), 1) : null;
        $avgZScore = $zScores->count() > 0 ? round($zScores->avg(), 2) : null;

        // Top risk flags
        $riskFlagCounts = [];
        foreach ($interviews as $interview) {
            $flags = $interview->risk_flags ?? [];
            if (is_string($flags)) {
                $flags = json_decode($flags, true) ?: [];
            }
            foreach ($flags as $flag) {
                $code = is_array($flag) ? ($flag['code'] ?? null) : $flag;
                if ($code) {
                    $riskFlagCounts[$code] = ($riskFlagCounts[$code] ?? 0) + 1;
                }
            }
        }
        arsort($riskFlagCounts);
        $topRiskFlags = array_slice($riskFlagCounts, 0, 5, true);

        // Generate alerts
        $alerts = [];

        // Alert: High reject rate
        if ($byDecisionPct['REJECT'] > 40) {
            $alerts[] = "High REJECT rate: {$byDecisionPct['REJECT']}% (threshold: 40%)";
        }

        // Alert: Very low hire rate
        if ($byDecisionPct['HIRE'] < 10 && $total >= 10) {
            $alerts[] = "Very low HIRE rate: {$byDecisionPct['HIRE']}% (threshold: 10%)";
        }

        // Alert: Z-score drift
        if ($avgZScore !== null && abs($avgZScore) > 0.5) {
            $direction = $avgZScore > 0 ? 'above' : 'below';
            $alerts[] = "Z-score drift detected: avg z={$avgZScore} ({$direction} baseline mean)";
        }

        // Alert: Critical flags present
        $criticalFlags = ['RF_AGGRESSION'];
        foreach ($criticalFlags as $cf) {
            if (isset($riskFlagCounts[$cf]) && $riskFlagCounts[$cf] > 0) {
                $alerts[] = "Critical flag {$cf} detected {$riskFlagCounts[$cf]} times";
            }
        }

        // Alert: High incomplete rate
        if (isset($riskFlagCounts['RF_INCOMPLETE'])) {
            $incompleteRate = round($riskFlagCounts['RF_INCOMPLETE'] / $total * 100, 1);
            if ($incompleteRate > 20) {
                $alerts[] = "High RF_INCOMPLETE rate: {$incompleteRate}% (threshold: 20%)";
            }
        }

        // Industry breakdown
        $byIndustry = $interviews->groupBy('industry_code')->map(function ($industryInterviews, $industryCode) {
            $count = $industryInterviews->count();
            return [
                'industry_code' => $industryCode,
                'count' => $count,
                'hire_count' => $industryInterviews->where('decision', 'HIRE')->count(),
                'hold_count' => $industryInterviews->where('decision', 'HOLD')->count(),
                'reject_count' => $industryInterviews->where('decision', 'REJECT')->count(),
                'avg_raw_score' => round($industryInterviews->pluck('raw_final_score')->filter()->avg() ?? 0, 1),
                'avg_z_score' => round($industryInterviews->pluck('z_score')->filter()->avg() ?? 0, 2),
            ];
        })->sortByDesc('count')->values()->toArray();

        // Daily breakdown
        $dailyBreakdown = $interviews->groupBy(fn($i) => $i->completed_at->format('Y-m-d'))
            ->map(function ($dayInterviews, $date) {
                $count = $dayInterviews->count();
                return [
                    'date' => $date,
                    'total' => $count,
                    'hire' => $dayInterviews->where('decision', 'HIRE')->count(),
                    'hold' => $dayInterviews->where('decision', 'HOLD')->count(),
                    'reject' => $dayInterviews->where('decision', 'REJECT')->count(),
                    'avg_raw' => round($dayInterviews->pluck('raw_final_score')->filter()->avg() ?? 0, 1),
                    'avg_cal' => round($dayInterviews->pluck('calibrated_score')->filter()->avg() ?? 0, 1),
                ];
            })
            ->sortKeys()
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'total' => $total,
                'by_decision' => $byDecision->toArray(),
                'by_decision_pct' => $byDecisionPct,
                'by_policy_code' => $byPolicyCode,
                'by_industry' => $byIndustry,
                'avg_raw_score' => $avgRawScore,
                'avg_calibrated_score' => $avgCalScore,
                'avg_z_score' => $avgZScore,
                'top_risk_flags' => $topRiskFlags,
                'daily_breakdown' => $dailyBreakdown,
                'alerts' => $alerts,
            ],
            'meta' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'filters' => [
                    'version' => $version,
                    'language' => $language,
                    'position' => $position,
                    'industry' => $industry,
                ],
            ],
        ]);
    }

    /**
     * Get position baseline v2 (industry + rolling window + fallback).
     *
     * GET /v1/admin/analytics/positions/baseline-v2
     */
    public function positionBaselineV2(Request $request): JsonResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:16'],
            'language' => ['required', 'string', 'max:8'],
            'position' => ['required', 'string', 'max:64'],
            'industry' => ['nullable', 'string', 'max:64'],
            'min_n' => ['nullable', 'integer', 'min:5', 'max:500'],
            'max_n' => ['nullable', 'integer', 'min:20', 'max:2000'],
            'days' => ['nullable', 'integer', 'min:7', 'max:365'],
        ]);

        $dims = [
            'version' => $data['version'],
            'language' => $data['language'],
            'position_code' => $data['position'],
            'industry_code' => $data['industry'] ?? null,
        ];

        $stats = $this->baselineService->baseline(
            $dims,
            $data['min_n'] ?? 30,
            $data['max_n'] ?? 200,
            $data['days'] ?? 90
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get model health metrics (confusion matrix + precision + FN signal).
     *
     * GET /v1/admin/analytics/model-health
     */
    public function modelHealth(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'version' => ['nullable', 'string', 'max:16'],
            'language' => ['nullable', 'string', 'max:8'],
            'position' => ['nullable', 'string', 'max:64'],
            'industry' => ['nullable', 'string', 'max:64'],
        ]);

        $from = $data['from'] ?? now()->subDays(90)->toDateString();
        $to = $data['to'] ?? now()->toDateString();

        $q = DB::table('form_interviews as fi')
            ->leftJoin('interview_outcomes as io', 'io.form_interview_id', '=', 'fi.id')
            ->whereBetween('fi.completed_at', [$from, $to])
            ->where('fi.status', FormInterview::STATUS_COMPLETED);

        if (!empty($data['version'])) {
            $q->where('fi.version', $data['version']);
        }
        if (!empty($data['language'])) {
            $q->where('fi.language', $data['language']);
        }
        if (!empty($data['position'])) {
            $q->where('fi.position_code', $data['position']);
        }
        if (array_key_exists('industry', $data)) {
            if ($data['industry'] === null || $data['industry'] === '') {
                $q->whereNull('fi.industry_code');
            } else {
                $q->where('fi.industry_code', $data['industry']);
            }
        }

        $rows = $q->select([
            'fi.decision',
            'fi.final_score',
            'fi.calibrated_score',
            'fi.raw_final_score',
            'io.hired',
            'io.started',
            'io.still_employed_30d',
            'io.still_employed_90d',
            'io.performance_rating',
            'io.incident_flag',
        ])
            ->limit(5000)
            ->get();

        $total = $rows->count();

        // Outcome score compute (MVP)
        $outcomeScore = function ($r): int {
            if (!$r->hired) return 0;
            if ($r->hired && !$r->started) return 10;
            if ($r->started && !$r->still_employed_30d) return 30;
            if ($r->still_employed_30d && !$r->still_employed_90d) return 50;
            if ($r->still_employed_90d && $r->incident_flag) return 70;
            if ($r->still_employed_90d && !$r->incident_flag && (int) $r->performance_rating >= 4) return 100;
            if ($r->still_employed_90d && !$r->incident_flag) return 85;
            return 50;
        };

        // Buckets
        $matrix = [
            'HIRE' => ['good' => 0, 'bad' => 0, 'unknown' => 0],
            'HOLD' => ['good' => 0, 'bad' => 0, 'unknown' => 0],
            'REJECT' => ['good' => 0, 'bad' => 0, 'unknown' => 0],
        ];

        $hireTotal = 0;
        $hireGood = 0;
        $rejectTotal = 0;
        $rejectGoodSignal = 0;
        $withOutcome = 0;

        foreach ($rows as $r) {
            $decision = $r->decision ?? 'HOLD';
            if (!isset($matrix[$decision])) {
                $decision = 'HOLD';
            }

            $hasOutcome = $r->hired !== null;

            if (!$hasOutcome) {
                $matrix[$decision]['unknown']++;
                continue;
            }

            $withOutcome++;
            $os = $outcomeScore($r);
            $good = $os >= 50;

            if ($good) {
                $matrix[$decision]['good']++;
            } else {
                $matrix[$decision]['bad']++;
            }

            if ($decision === 'HIRE') {
                $hireTotal++;
                if ($os >= 50) {
                    $hireGood++;
                }
            }
            if ($decision === 'REJECT') {
                $rejectTotal++;
                if ($os >= 70) {
                    $rejectGoodSignal++;
                }
            }
        }

        $hirePrecision = $hireTotal > 0 ? round(($hireGood / $hireTotal) * 100, 2) : null;
        $rejectFalseNegativeSignalRate = $rejectTotal > 0 ? round(($rejectGoodSignal / $rejectTotal) * 100, 2) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'range' => ['from' => $from, 'to' => $to],
                'total_completed' => $total,
                'with_outcome' => $withOutcome,
                'hire_precision_pct' => $hirePrecision,
                'reject_fn_signal_pct' => $rejectFalseNegativeSignalRate,
                'decision_outcome_matrix' => $matrix,
            ],
        ]);
    }

    /**
     * Get Candidate Supply Engine metrics.
     *
     * GET /v1/admin/analytics/candidate-supply
     */
    public function candidateSupplyMetrics(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'industry' => ['nullable', 'string', 'max:64'],
        ]);

        $from = $data['from'] ?? now()->subDays(90)->toDateString();
        $to = $data['to'] ?? now()->toDateString();

        // Base queries
        $candidateQuery = PoolCandidate::whereBetween('created_at', [$from, $to]);
        if (!empty($data['industry'])) {
            $candidateQuery->where('primary_industry', $data['industry']);
        }

        // Total candidates
        $totalCandidates = (clone $candidateQuery)->count();

        // Pool size (currently in pool)
        $poolSize = PoolCandidate::where('status', PoolCandidate::STATUS_IN_POOL)
            ->when(!empty($data['industry']), fn($q) => $q->where('primary_industry', $data['industry']))
            ->count();

        // Presented count
        $presentedCount = PoolCandidate::where('status', PoolCandidate::STATUS_PRESENTED)
            ->whereBetween('updated_at', [$from, $to])
            ->when(!empty($data['industry']), fn($q) => $q->where('primary_industry', $data['industry']))
            ->count();

        // Hired count
        $hiredCount = PoolCandidate::where('status', PoolCandidate::STATUS_HIRED)
            ->whereBetween('updated_at', [$from, $to])
            ->when(!empty($data['industry']), fn($q) => $q->where('primary_industry', $data['industry']))
            ->count();

        // Pool to hire rate
        $poolToHireRate = $poolSize > 0 ? round(($hiredCount / ($poolSize + $hiredCount)) * 100, 2) : null;

        // Source channel quality (hire rate per source)
        $sourceChannelQuality = DB::table('pool_candidates')
            ->select('source_channel')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as hired', [PoolCandidate::STATUS_HIRED])
            ->whereBetween('created_at', [$from, $to])
            ->when(!empty($data['industry']), fn($q) => $q->where('primary_industry', $data['industry']))
            ->groupBy('source_channel')
            ->having('total', '>=', 5) // minimum sample size
            ->get()
            ->map(function ($row) {
                return [
                    'source_channel' => $row->source_channel,
                    'total' => $row->total,
                    'hired' => $row->hired,
                    'hire_rate_pct' => $row->total > 0 ? round(($row->hired / $row->total) * 100, 2) : 0,
                ];
            })
            ->sortByDesc('hire_rate_pct')
            ->values()
            ->toArray();

        // By industry breakdown
        $byIndustry = DB::table('pool_candidates')
            ->select('primary_industry')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_pool', [PoolCandidate::STATUS_IN_POOL])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as presented', [PoolCandidate::STATUS_PRESENTED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as hired', [PoolCandidate::STATUS_HIRED])
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('primary_industry')
            ->get()
            ->map(fn($row) => [
                'industry' => $row->primary_industry,
                'total' => $row->total,
                'in_pool' => $row->in_pool,
                'presented' => $row->presented,
                'hired' => $row->hired,
            ])
            ->toArray();

        // Timeseries weekly
        $timeseriesWeekly = DB::table('pool_candidates')
            ->select(DB::raw('YEARWEEK(created_at, 1) as week'))
            ->selectRaw('MIN(DATE(created_at)) as week_start')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as qualified', [
                PoolCandidate::STATUS_IN_POOL,
                PoolCandidate::STATUS_PRESENTED,
                PoolCandidate::STATUS_HIRED,
            ])
            ->whereBetween('created_at', [$from, $to])
            ->when(!empty($data['industry']), fn($q) => $q->where('primary_industry', $data['industry']))
            ->groupBy(DB::raw('YEARWEEK(created_at, 1)'))
            ->orderBy('week')
            ->get()
            ->map(fn($row) => [
                'week_start' => $row->week_start,
                'total' => $row->total,
                'qualified' => $row->qualified,
                'qualification_rate' => $row->total > 0 ? round(($row->qualified / $row->total) * 100, 1) : 0,
            ])
            ->toArray();

        // Maritime-specific metrics
        $maritimeMetrics = null;
        if (empty($data['industry']) || $data['industry'] === 'maritime') {
            $maritimeMetrics = [
                'seafarers_total' => PoolCandidate::where('seafarer', true)
                    ->whereBetween('created_at', [$from, $to])
                    ->count(),
                'seafarers_in_pool' => PoolCandidate::where('seafarer', true)
                    ->where('status', PoolCandidate::STATUS_IN_POOL)
                    ->count(),
                'english_assessment_pending' => PoolCandidate::where('english_assessment_required', true)
                    ->whereNull('english_level_self')
                    ->where('status', '!=', PoolCandidate::STATUS_ARCHIVED)
                    ->count(),
                'video_assessment_pending' => PoolCandidate::where('video_assessment_required', true)
                    ->where('status', '!=', PoolCandidate::STATUS_ARCHIVED)
                    ->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'range' => ['from' => $from, 'to' => $to],
                'candidates_total' => $totalCandidates,
                'pool_size' => $poolSize,
                'presented_count' => $presentedCount,
                'hired_count' => $hiredCount,
                'pool_to_hire_rate_pct' => $poolToHireRate,
                'source_channel_quality' => $sourceChannelQuality,
                'by_industry' => $byIndustry,
                'timeseries_weekly' => $timeseriesWeekly,
                'maritime' => $maritimeMetrics,
            ],
        ]);
    }

    /**
     * Get Company Consumption Layer metrics.
     *
     * GET /v1/admin/analytics/consumption
     */
    public function consumptionMetrics(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'industry' => ['nullable', 'string', 'max:64'],
        ]);

        $from = $data['from'] ?? now()->subDays(90)->toDateString();
        $to = $data['to'] ?? now()->toDateString();

        // Talent request metrics
        $requestsQuery = TalentRequest::whereBetween('created_at', [$from, $to]);
        if (!empty($data['industry'])) {
            $requestsQuery->where('industry_code', $data['industry']);
        }

        $totalRequests = (clone $requestsQuery)->count();
        $requestsByStatus = (clone $requestsQuery)
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Average fill rate for closed/fulfilled requests
        $avgFillRate = TalentRequest::whereIn('status', ['fulfilled', 'closed'])
            ->whereBetween('created_at', [$from, $to])
            ->when(!empty($data['industry']), fn($q) => $q->where('industry_code', $data['industry']))
            ->selectRaw('AVG(hired_count * 100.0 / NULLIF(required_count, 0)) as avg_fill')
            ->value('avg_fill');

        // Presentation metrics
        $presentationsQuery = CandidatePresentation::whereBetween('presented_at', [$from, $to]);
        if (!empty($data['industry'])) {
            $presentationsQuery->whereHas('talentRequest', function ($q) use ($data) {
                $q->where('industry_code', $data['industry']);
            });
        }

        $totalPresentations = (clone $presentationsQuery)->count();
        $totalHired = (clone $presentationsQuery)->hired()->count();
        $totalRejected = (clone $presentationsQuery)->where('presentation_status', CandidatePresentation::STATUS_REJECTED)->count();
        $presentationToHireRate = $totalPresentations > 0
            ? round(($totalHired / $totalPresentations) * 100, 2)
            : null;

        // Average client score
        $avgClientScore = (clone $presentationsQuery)
            ->whereNotNull('client_score')
            ->avg('client_score');

        // Source channel → hire conversion (key metric)
        $sourceChannelConversion = DB::table('candidate_presentations as cp')
            ->join('pool_candidates as pc', 'cp.pool_candidate_id', '=', 'pc.id')
            ->whereBetween('cp.presented_at', [$from, $to])
            ->when(!empty($data['industry']), function ($q) use ($data) {
                $q->whereExists(function ($subQ) use ($data) {
                    $subQ->select(DB::raw(1))
                        ->from('talent_requests as tr')
                        ->whereColumn('tr.id', 'cp.talent_request_id')
                        ->where('tr.industry_code', $data['industry']);
                });
            })
            ->select('pc.source_channel')
            ->selectRaw('COUNT(*) as total_presented')
            ->selectRaw('SUM(CASE WHEN cp.presentation_status = ? THEN 1 ELSE 0 END) as hired', [CandidatePresentation::STATUS_HIRED])
            ->selectRaw('SUM(CASE WHEN cp.presentation_status = ? THEN 1 ELSE 0 END) as rejected', [CandidatePresentation::STATUS_REJECTED])
            ->selectRaw('AVG(cp.client_score) as avg_score')
            ->groupBy('pc.source_channel')
            ->having('total_presented', '>=', 3)
            ->get()
            ->map(fn($row) => [
                'source_channel' => $row->source_channel,
                'total_presented' => $row->total_presented,
                'hired' => $row->hired,
                'rejected' => $row->rejected,
                'hire_rate_pct' => $row->total_presented > 0 ? round(($row->hired / $row->total_presented) * 100, 1) : 0,
                'avg_client_score' => $row->avg_score ? round($row->avg_score, 1) : null,
            ])
            ->sortByDesc('hire_rate_pct')
            ->values()
            ->toArray();

        // By industry breakdown
        $byIndustry = DB::table('candidate_presentations as cp')
            ->join('talent_requests as tr', 'cp.talent_request_id', '=', 'tr.id')
            ->whereBetween('cp.presented_at', [$from, $to])
            ->select('tr.industry_code')
            ->selectRaw('COUNT(*) as total_presented')
            ->selectRaw('SUM(CASE WHEN cp.presentation_status = ? THEN 1 ELSE 0 END) as hired', [CandidatePresentation::STATUS_HIRED])
            ->selectRaw('SUM(CASE WHEN cp.presentation_status = ? THEN 1 ELSE 0 END) as rejected', [CandidatePresentation::STATUS_REJECTED])
            ->groupBy('tr.industry_code')
            ->get()
            ->map(fn($row) => [
                'industry_code' => $row->industry_code,
                'total_presented' => $row->total_presented,
                'hired' => $row->hired,
                'rejected' => $row->rejected,
                'hire_rate_pct' => $row->total_presented > 0 ? round(($row->hired / $row->total_presented) * 100, 1) : 0,
            ])
            ->toArray();

        // Time to hire (avg days from presentation to hire)
        $avgTimeToHire = DB::table('candidate_presentations')
            ->whereBetween('presented_at', [$from, $to])
            ->where('presentation_status', CandidatePresentation::STATUS_HIRED)
            ->whereNotNull('hired_at')
            ->selectRaw('AVG(DATEDIFF(hired_at, presented_at)) as avg_days')
            ->value('avg_days');

        // Weekly timeseries
        $timeseriesWeekly = DB::table('candidate_presentations')
            ->select(DB::raw('YEARWEEK(presented_at, 1) as week'))
            ->selectRaw('MIN(DATE(presented_at)) as week_start')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN presentation_status = ? THEN 1 ELSE 0 END) as hired', [CandidatePresentation::STATUS_HIRED])
            ->selectRaw('SUM(CASE WHEN presentation_status = ? THEN 1 ELSE 0 END) as rejected', [CandidatePresentation::STATUS_REJECTED])
            ->whereBetween('presented_at', [$from, $to])
            ->groupBy(DB::raw('YEARWEEK(presented_at, 1)'))
            ->orderBy('week')
            ->get()
            ->map(fn($row) => [
                'week_start' => $row->week_start,
                'total' => $row->total,
                'hired' => $row->hired,
                'rejected' => $row->rejected,
                'hire_rate_pct' => $row->total > 0 ? round(($row->hired / $row->total) * 100, 1) : 0,
            ])
            ->toArray();

        // Top companies by hires
        $topCompanies = DB::table('candidate_presentations as cp')
            ->join('talent_requests as tr', 'cp.talent_request_id', '=', 'tr.id')
            ->join('pool_companies as pc', 'tr.pool_company_id', '=', 'pc.id')
            ->whereBetween('cp.presented_at', [$from, $to])
            ->select('pc.id', 'pc.company_name')
            ->selectRaw('COUNT(*) as total_presented')
            ->selectRaw('SUM(CASE WHEN cp.presentation_status = ? THEN 1 ELSE 0 END) as hired', [CandidatePresentation::STATUS_HIRED])
            ->selectRaw('AVG(cp.client_score) as avg_score')
            ->groupBy('pc.id', 'pc.company_name')
            ->having('hired', '>=', 1)
            ->orderByDesc('hired')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'company_id' => $row->id,
                'company_name' => $row->company_name,
                'total_presented' => $row->total_presented,
                'hired' => $row->hired,
                'avg_score' => $row->avg_score ? round($row->avg_score, 1) : null,
            ])
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'range' => ['from' => $from, 'to' => $to],
                'requests' => [
                    'total' => $totalRequests,
                    'by_status' => $requestsByStatus,
                    'avg_fill_rate_pct' => $avgFillRate ? round($avgFillRate, 1) : null,
                ],
                'presentations' => [
                    'total' => $totalPresentations,
                    'hired' => $totalHired,
                    'rejected' => $totalRejected,
                    'hire_rate_pct' => $presentationToHireRate,
                    'avg_client_score' => $avgClientScore ? round($avgClientScore, 1) : null,
                    'avg_time_to_hire_days' => $avgTimeToHire ? round($avgTimeToHire, 1) : null,
                ],
                'source_channel_conversion' => $sourceChannelConversion,
                'by_industry' => $byIndustry,
                'timeseries_weekly' => $timeseriesWeekly,
                'top_companies' => $topCompanies,
            ],
        ]);
    }
}
