<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmOutboundQueue;
use App\Models\ModelWeight;
use App\Models\SeafarerCertificate;
use App\Models\SmtpCircuitBreaker;
use App\Models\SystemEvent;
use App\Models\VesselReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SystemHealthController extends Controller
{
    /**
     * GET /v1/admin/system/health
     */
    public function health(): JsonResponse
    {
        $checks = [];
        $overallStatus = 'healthy';

        // 1. Queue check
        $oldestPending = CrmOutboundQueue::where('status', 'approved')
            ->orderBy('created_at')
            ->value('created_at');
        $oldestAge = $oldestPending ? now()->diffInSeconds($oldestPending) : 0;
        $pendingJobs = CrmOutboundQueue::where('status', 'approved')->count();

        // Check actual queue worker status via /proc filesystem
        $workerActive = false;
        try {
            foreach (glob('/proc/[0-9]*/cmdline') as $cmdFile) {
                $cmd = @file_get_contents($cmdFile);
                if ($cmd && str_contains($cmd, 'queue:work')) {
                    $workerActive = true;
                    break;
                }
            }
        } catch (\Throwable) {
            $workerActive = false;
        }

        // Also check Laravel failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        $failedJobsRecent = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDay())
            ->count();

        $checks['queue'] = [
            'oldest_job_age_seconds' => $oldestAge,
            'pending_jobs' => $pendingJobs,
            'worker_active' => $workerActive,
            'failed_jobs_total' => $failedJobs,
            'failed_jobs_24h' => $failedJobsRecent,
        ];

        // 2. Mail check
        $lastHourSent = CrmOutboundQueue::where('status', 'sent')
            ->where('sent_at', '>=', now()->subHour())
            ->count();
        $lastHourFailed = CrmOutboundQueue::where('status', 'failed')
            ->where('updated_at', '>=', now()->subHour())
            ->count();
        $failureRate = ($lastHourSent + $lastHourFailed) > 0
            ? round(($lastHourFailed / ($lastHourSent + $lastHourFailed)) * 100, 1)
            : 0;
        $dailySent = CrmOutboundQueue::where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();
        $openBreakers = SmtpCircuitBreaker::where('state', 'open')
            ->where(function ($q) {
                $q->whereNull('opened_until')->orWhere('opened_until', '>', now());
            })
            ->count();

        $checks['mail'] = [
            'last_hour_failure_rate_pct' => $failureRate,
            'daily_sent_count' => $dailySent,
            'daily_cap' => config('crm_mail.max_total_per_day', 300),
            'circuit_breakers_open' => $openBreakers,
        ];

        // 3. ML check
        $activeWeights = ModelWeight::where('is_active', true)->first();
        $lastCycleStatus = DB::table('learning_events')
            ->orderByDesc('created_at')
            ->value('status') ?? 'none';
        $volatilityBlocks = SystemEvent::where('type', 'ml_volatility_block')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $checks['ml'] = [
            'active_version' => $activeWeights?->model_version ?? 'none',
            'is_frozen' => $activeWeights?->is_frozen ?? false,
            'last_cycle_status' => $lastCycleStatus,
            'volatility_blocks_7d' => $volatilityBlocks,
        ];

        // 4. Certification check
        $expiring30d = SeafarerCertificate::where('verification_status', 'verified')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->count();
        $fraudSignals = SystemEvent::whereIn('type', ['cert_duplicate_serial', 'cert_duplicate_hash'])
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $uploadBlocks = SystemEvent::where('type', 'cert_upload_flood')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $checks['certification'] = [
            'expiring_30d' => $expiring30d,
            'fraud_signals_24h' => $fraudSignals,
            'upload_blocks_24h' => $uploadBlocks,
        ];

        // 5. Funnel check
        $last7d = DB::table('form_interviews')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $prev7d = DB::table('form_interviews')
            ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
            ->count();

        $applyCount = DB::table('pool_candidates')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $submitCount = DB::table('form_interviews')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $interviewCount = $last7d;

        $applyToSubmit = $applyCount > 0 ? round(($submitCount / $applyCount) * 100, 1) : 0;
        $submitToInterview = $submitCount > 0 ? round(($interviewCount / $submitCount) * 100, 1) : 0;
        $delta = $prev7d > 0 ? round((($last7d - $prev7d) / $prev7d) * 100, 1) : 0;

        $checks['funnel'] = [
            'apply_to_submit_rate_pct' => $applyToSubmit,
            'submit_to_interview_rate_pct' => $submitToInterview,
            'delta_7d_vs_prev_7d_pct' => $delta,
        ];

        // 6. Reviews check
        $reports24h = VesselReview::withTrashed()
            ->where('report_count', '>', 0)
            ->where('updated_at', '>=', now()->subDay())
            ->count();
        $autoUnpublished = SystemEvent::where('type', 'review_auto_unpublished')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $deleted24h = SystemEvent::where('type', 'review_deleted')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $checks['reviews'] = [
            'reports_24h' => $reports24h,
            'auto_unpublished_24h' => $autoUnpublished,
            'deleted_24h' => $deleted24h,
        ];

        // Recent alerts
        $recentAlerts = SystemEvent::whereIn('severity', ['critical', 'warn'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['severity', 'type', 'message', 'created_at'])
            ->toArray();

        // Determine overall status
        if ($failureRate > 15 || $openBreakers > 1 || $fraudSignals >= 3 || $delta <= -15 || $volatilityBlocks >= 3) {
            $overallStatus = 'critical';
        } elseif ($failureRate > 5 || $openBreakers > 0 || $fraudSignals > 0 || $delta <= -5 || $volatilityBlocks > 0) {
            $overallStatus = 'degraded';
        }

        // 7. Infrastructure check
        $redisOk = false;
        $redisLatency = 0;
        try {
            $start = microtime(true);
            Redis::ping();
            $redisLatency = round((microtime(true) - $start) * 1000, 1);
            $redisOk = true;
        } catch (\Throwable) {
            $redisOk = false;
        }

        $dbOk = false;
        $dbLatency = 0;
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbLatency = round((microtime(true) - $start) * 1000, 1);
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        $diskUsagePct = 0;
        try {
            $total = disk_total_space('/');
            $free = disk_free_space('/');
            $diskUsagePct = $total > 0 ? round((1 - $free / $total) * 100, 1) : 0;
        } catch (\Throwable) {
            // ignore
        }

        $checks['infrastructure'] = [
            'redis_connected' => $redisOk,
            'redis_latency_ms' => $redisLatency,
            'database_connected' => $dbOk,
            'database_latency_ms' => $dbLatency,
            'disk_usage_pct' => $diskUsagePct,
        ];

        // 8. Activity check — real data from recent operations
        $lastInterviewAt = DB::table('form_interviews')
            ->orderByDesc('created_at')
            ->value('created_at');
        $lastTranscriptionAt = DB::table('voice_transcriptions')
            ->orderByDesc('created_at')
            ->value('created_at');
        $lastCandidateAt = DB::table('pool_candidates')
            ->orderByDesc('created_at')
            ->value('created_at');

        $checks['activity'] = [
            'total_interviews' => DB::table('form_interviews')->count(),
            'completed_interviews' => DB::table('form_interviews')->where('status', 'completed')->count(),
            'total_candidates' => DB::table('pool_candidates')->count(),
            'total_transcriptions' => DB::table('voice_transcriptions')->count(),
            'last_interview_at' => $lastInterviewAt,
            'last_transcription_at' => $lastTranscriptionAt,
            'last_candidate_at' => $lastCandidateAt,
        ];

        // Uptime estimate
        $totalEvents = SystemEvent::where('created_at', '>=', now()->subDays(30))->count();
        $criticalEvents = SystemEvent::where('severity', 'critical')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $uptimePct = $totalEvents > 0
            ? round(100 - (($criticalEvents / max($totalEvents, 1)) * 100), 2)
            : 99.99;

        // Infrastructure affects overall status
        if (!$redisOk || !$dbOk || !$workerActive) {
            $overallStatus = 'critical';
        } elseif ($diskUsagePct > 90) {
            $overallStatus = $overallStatus === 'critical' ? 'critical' : 'degraded';
        }

        return response()->json([
            'overall_status' => $overallStatus,
            'last_checked_at' => now()->toIso8601String(),
            'uptime_pct' => $uptimePct,
            ...$checks,
            'recent_alerts' => $recentAlerts,
        ]);
    }

    /**
     * GET /v1/admin/system/events
     */
    public function events(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'severity' => ['nullable', 'string', 'in:info,warn,critical'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = SystemEvent::query()
            ->when($data['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($data['severity'] ?? null, fn($q, $v) => $q->where('severity', $v))
            ->when($data['from'] ?? null, fn($q, $v) => $q->where('created_at', '>=', $v))
            ->when($data['to'] ?? null, fn($q, $v) => $q->where('created_at', '<=', $v . ' 23:59:59'))
            ->orderByDesc('created_at');

        $perPage = $data['per_page'] ?? 25;
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
        ]);
    }
}
