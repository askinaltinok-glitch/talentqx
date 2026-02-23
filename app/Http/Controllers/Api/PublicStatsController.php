<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicStatsController extends Controller
{
    /**
     * Public platform stats for social proof on landing pages.
     * Cached for 1 hour — no auth required.
     */
    public function __invoke(): JsonResponse
    {
        $stats = Cache::remember('public_platform_stats', 3600, function () {
            $seafarers = DB::table('pool_candidates')
                ->whereNotNull('email_verified_at')
                ->count();

            $vessels = DB::table('vessels')->count();

            $certificates = DB::table('seafarer_certificates')->count();

            // Include all pool candidates (not just verified) for total reach
            $totalCandidates = DB::table('pool_candidates')->count();

            return [
                'seafarers'    => $totalCandidates,
                'vessels'      => $vessels,
                'certificates' => $certificates,
            ];
        });

        return response()->json($stats);
    }
}
