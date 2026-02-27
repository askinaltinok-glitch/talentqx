<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DemoRequest;
use App\Models\FormInterview;
use App\Models\JobListing;
use App\Models\PoolCandidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $companiesCount = Company::count();
        $activeJobsCount = JobListing::where('is_published', true)->count();
        $candidatesCount = PoolCandidate::count();
        $interviewsThisMonth = FormInterview::where('status', 'completed')
            ->where('created_at', '>=', now()->startOfMonth())->count();
        $demoRequestsThisMonth = DemoRequest::where('created_at', '>=', now()->startOfMonth())->count();

        // Package stats from payments
        $packageStats = [
            'total_revenue' => (float) \App\Models\Payment::where('status', 'completed')->sum('amount'),
            'total_credits_sold' => (int) \App\Models\Payment::where('status', 'completed')->sum('credits_added'),
            'sales_this_month' => (int) \App\Models\Payment::where('status', 'completed')
                ->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        // Recent demo requests
        $recentDemos = DemoRequest::orderByDesc('created_at')->limit(5)->get();

        // Recent completed interviews
        $recentInterviews = FormInterview::where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'pool_candidate_id', 'position_code', 'final_score', 'status', 'created_at', 'completed_at'])
            ->map(fn($iv) => [
                'id' => $iv->id,
                'candidate_name' => $iv->poolCandidate?->full_name ?? '—',
                'position' => $iv->position_code,
                'overall_score' => $iv->final_score,
                'status' => $iv->status,
                'created_at' => $iv->created_at,
            ]);

        // Company usage summary (derived from subscription_ends_at / grace_period_ends_at)
        $now = now();
        $companyStats = [
            'total' => $companiesCount,
            'active' => Company::where(function ($q) use ($now) {
                $q->whereNull('subscription_ends_at')
                  ->orWhere('subscription_ends_at', '>', $now);
            })->count(),
            'grace' => Company::where('subscription_ends_at', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->where('grace_period_ends_at', '>', $now);
                })->count(),
            'expired' => Company::where('subscription_ends_at', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('grace_period_ends_at')
                      ->orWhere('grace_period_ends_at', '<=', $now);
                })->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'companies_count' => $companiesCount,
                'active_jobs_count' => $activeJobsCount,
                'candidates_count' => $candidatesCount,
                'interviews_this_month' => $interviewsThisMonth,
                'demo_requests_this_month' => $demoRequestsThisMonth,
                'package_stats' => $packageStats,
                'recent_demos' => $recentDemos,
                'recent_interviews' => $recentInterviews,
                'company_stats' => $companyStats,
            ],
        ]);
    }
}
