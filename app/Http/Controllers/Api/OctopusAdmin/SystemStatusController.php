<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\CrmOutboundQueue;
use App\Models\SmtpCircuitBreaker;
use App\Models\SystemEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SystemStatusController extends Controller
{
    /**
     * GET /v1/octopus/admin/system/status
     *
     * Lightweight health summary for admin status pill.
     * Cached 60s to avoid DB pressure from polling.
     */
    public function __invoke(): JsonResponse
    {
        $data = Cache::remember('octo_admin_system_status', 60, function () {
            $overallStatus = 'healthy';

            // Mail failure rate (last hour)
            $lastHourSent = CrmOutboundQueue::where('status', 'sent')
                ->where('sent_at', '>=', now()->subHour())
                ->count();
            $lastHourFailed = CrmOutboundQueue::where('status', 'failed')
                ->where('updated_at', '>=', now()->subHour())
                ->count();
            $failureRate = ($lastHourSent + $lastHourFailed) > 0
                ? round(($lastHourFailed / ($lastHourSent + $lastHourFailed)) * 100, 1)
                : 0;

            // Circuit breakers
            $openBreakers = SmtpCircuitBreaker::where('state', 'open')
                ->where(function ($q) {
                    $q->whereNull('opened_until')->orWhere('opened_until', '>', now());
                })
                ->count();

            // Fraud signals
            $fraudSignals = SystemEvent::whereIn('type', ['cert_duplicate_serial', 'cert_duplicate_hash'])
                ->where('created_at', '>=', now()->subDay())
                ->count();

            // ML volatility
            $volatilityBlocks = SystemEvent::where('type', 'ml_volatility_block')
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            // Determine status
            if ($failureRate > 15 || $openBreakers > 1 || $fraudSignals >= 3 || $volatilityBlocks >= 3) {
                $overallStatus = 'critical';
            } elseif ($failureRate > 5 || $openBreakers > 0 || $fraudSignals > 0 || $volatilityBlocks > 0) {
                $overallStatus = 'degraded';
            }

            return [
                'overall_status' => $overallStatus,
                'last_checked_at' => now()->toIso8601String(),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }
}
