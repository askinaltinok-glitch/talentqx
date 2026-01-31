<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Job;
use App\Services\Interview\AnalysisEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private AnalysisEngine $analysisEngine
    ) {}

    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $jobId = $request->get('job_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $jobsQuery = Job::where('company_id', $companyId);
        $candidatesQuery = Candidate::whereHas('job', fn($q) => $q->where('company_id', $companyId));
        $interviewsQuery = Interview::whereHas('job', fn($q) => $q->where('company_id', $companyId));

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

        $avgScore = DB::table('interview_analyses')
            ->join('interviews', 'interview_analyses.interview_id', '=', 'interviews.id')
            ->join('job_postings', 'interviews.job_id', '=', 'job_postings.id')
            ->where('job_postings.company_id', $companyId)
            ->when($jobId, fn($q) => $q->where('interviews.job_id', $jobId))
            ->avg('overall_score');

        $hiredCount = (clone $candidatesQuery)->where('status', 'hired')->count();
        $hireRate = $totalCandidates > 0 ? round($hiredCount / $totalCandidates, 2) : 0;

        $redFlagCount = DB::table('interview_analyses')
            ->join('interviews', 'interview_analyses.interview_id', '=', 'interviews.id')
            ->join('job_postings', 'interviews.job_id', '=', 'job_postings.id')
            ->where('job_postings.company_id', $companyId)
            ->when($jobId, fn($q) => $q->where('interviews.job_id', $jobId))
            ->whereJsonContains('red_flag_analysis->flags_detected', true)
            ->count();

        $redFlagRate = $interviewsCompleted > 0 ? round($redFlagCount / $interviewsCompleted, 2) : 0;

        $byStatus = $candidatesQuery
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

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
            ],
        ]);
    }

    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_ids' => 'required|array|min:2|max:5',
            'candidate_ids.*' => 'uuid|exists:candidates,id',
        ]);

        $candidates = Candidate::with(['job', 'latestInterview.analysis'])
            ->whereHas('job', fn($q) => $q->where('company_id', $request->user()->company_id))
            ->whereIn('id', $validated['candidate_ids'])
            ->get();

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

        $job = Job::where('company_id', $request->user()->company_id)
            ->findOrFail($validated['job_id']);

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
}
