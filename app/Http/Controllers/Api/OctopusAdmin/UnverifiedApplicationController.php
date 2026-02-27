<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Interview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnverifiedApplicationController extends Controller
{
    /**
     * List QR-apply candidates whose interview was never email-verified.
     * GET /v1/octopus/admin/unverified-applications
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);

        $rows = Interview::query()
            ->join('candidates', 'interviews.candidate_id', '=', 'candidates.id')
            ->join('job_postings', 'interviews.job_id', '=', 'job_postings.id')
            ->join('companies', 'job_postings.company_id', '=', 'companies.id')
            ->where('candidates.source', 'qr_apply')
            ->whereNull('interviews.email_verified_at')
            ->select([
                'interviews.id as interview_id',
                'interviews.status as interview_status',
                'interviews.email_verification_attempts',
                'interviews.email_verification_expires_at',
                'interviews.created_at as applied_at',
                'candidates.id as candidate_id',
                'candidates.first_name',
                'candidates.last_name',
                'candidates.email',
                'candidates.phone',
                'candidates.consent_ip as ip_address',
                'job_postings.title as job_title',
                'job_postings.public_token',
                'companies.name as company_name',
            ])
            ->orderByDesc('interviews.created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
            ],
        ]);
    }

    /**
     * Stats for unverified applications.
     * GET /v1/octopus/admin/unverified-applications/stats
     */
    public function stats(): JsonResponse
    {
        $base = Interview::query()
            ->join('candidates', 'interviews.candidate_id', '=', 'candidates.id')
            ->where('candidates.source', 'qr_apply')
            ->whereNull('interviews.email_verified_at');

        $total = (clone $base)->count();
        $last7 = (clone $base)->where('interviews.created_at', '>=', now()->subDays(7))->count();
        $last24h = (clone $base)->where('interviews.created_at', '>=', now()->subHours(24))->count();

        // Top repeat IPs (potential abuse)
        $topIps = Interview::query()
            ->join('candidates', 'interviews.candidate_id', '=', 'candidates.id')
            ->where('candidates.source', 'qr_apply')
            ->whereNull('interviews.email_verified_at')
            ->whereNotNull('candidates.consent_ip')
            ->where('candidates.consent_ip', '!=', '')
            ->select('candidates.consent_ip as ip', DB::raw('COUNT(*) as count'))
            ->groupBy('candidates.consent_ip')
            ->having('count', '>', 1)
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Total verified (for conversion rate)
        $totalVerified = Interview::query()
            ->join('candidates', 'interviews.candidate_id', '=', 'candidates.id')
            ->where('candidates.source', 'qr_apply')
            ->whereNotNull('interviews.email_verified_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_unverified' => $total,
                'last_7_days' => $last7,
                'last_24_hours' => $last24h,
                'total_verified' => $totalVerified,
                'top_ips' => $topIps,
            ],
        ]);
    }
}
