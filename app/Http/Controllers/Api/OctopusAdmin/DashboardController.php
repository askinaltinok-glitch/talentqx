<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\FormInterview;
use App\Models\JobListing;
use App\Models\PoolCandidate;
use App\Models\SeafarerCertificate;
use App\Models\SystemEvent;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $candidates = PoolCandidate::where('primary_industry', 'maritime')->count();
        $interviews = FormInterview::where('industry_code', 'maritime')->count();
        $jobs = JobListing::where('industry_code', 'maritime')->count();
        $certificates = SeafarerCertificate::count();

        $recentEvents = SystemEvent::where('meta->brand', 'octopus')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'type', 'message', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'candidates' => $candidates,
                'interviews' => $interviews,
                'jobs' => $jobs,
                'certificates' => $certificates,
                'recent_events' => $recentEvents,
            ],
        ]);
    }
}
