<?php

namespace App\Console\Commands;

use App\Models\CrmOutboundQueue;
use App\Models\SmtpCircuitBreaker;
use App\Models\SeafarerCertificate;
use App\Models\SystemEvent;
use App\Services\System\SystemEventService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SystemHealthCheckCommand extends Command
{
    protected $signature = 'system:health-check';
    protected $description = 'Run system health checks and report status';

    public function handle(): int
    {
        $this->info('System Health Check');
        $this->info('===================');
        $this->newLine();

        $checks = [];
        $degraded = false;

        // 1. Queue lag — oldest pending CrmOutboundQueue with status='approved'
        $oldestApproved = CrmOutboundQueue::where('status', 'approved')
            ->orderBy('created_at')
            ->value('created_at');
        $queueLagSeconds = $oldestApproved ? now()->diffInSeconds($oldestApproved) : 0;
        $queueStatus = 'OK';
        if ($queueLagSeconds > 3600) {
            $queueStatus = 'CRITICAL';
            $degraded = true;
        } elseif ($queueLagSeconds > 600) {
            $queueStatus = 'WARN';
            $degraded = true;
        }
        $checks[] = ['Queue Lag', $queueLagSeconds . 's', $queueStatus];

        // 2. Mail failure rate (last hour)
        $lastHourSent = CrmOutboundQueue::where('status', 'sent')
            ->where('sent_at', '>=', now()->subHour())
            ->count();
        $lastHourFailed = CrmOutboundQueue::where('status', 'failed')
            ->where('updated_at', '>=', now()->subHour())
            ->count();
        $total = $lastHourSent + $lastHourFailed;
        $failureRate = $total > 0 ? round(($lastHourFailed / $total) * 100, 1) : 0;
        $mailStatus = 'OK';
        if ($failureRate > 15) {
            $mailStatus = 'CRITICAL';
            $degraded = true;
        } elseif ($failureRate > 5) {
            $mailStatus = 'WARN';
            $degraded = true;
        }
        $checks[] = ['Mail Failure Rate (1h)', $failureRate . '% (' . $lastHourFailed . '/' . $total . ')', $mailStatus];

        // 3. SMTP circuit breaker status
        $breaker = SmtpCircuitBreaker::forKey('smtp');
        $breakerState = $breaker->state;
        $breakerStatus = 'OK';
        if ($breaker->isOpen()) {
            $breakerStatus = 'CRITICAL';
            $degraded = true;
        } elseif ($breaker->isHalfOpen()) {
            $breakerStatus = 'WARN';
            $degraded = true;
        }
        $checks[] = ['SMTP Circuit Breaker', $breakerState, $breakerStatus];

        // 4. Certs expiring in 30 days (verified, expires_at between now and +30d)
        $expiringCerts = SeafarerCertificate::where('verification_status', 'verified')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->count();
        $certStatus = 'OK';
        if ($expiringCerts > 20) {
            $certStatus = 'WARN';
            $degraded = true;
        }
        $checks[] = ['Certs Expiring (30d)', (string) $expiringCerts, $certStatus];

        // 5. ML high-error events (SystemEvent where type like 'ml_%' in last 24h)
        $mlErrors = SystemEvent::where('type', 'like', 'ml_%')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        $mlStatus = 'OK';
        if ($mlErrors >= 5) {
            $mlStatus = 'CRITICAL';
            $degraded = true;
        } elseif ($mlErrors > 0) {
            $mlStatus = 'WARN';
            $degraded = true;
        }
        $checks[] = ['ML Error Events (24h)', (string) $mlErrors, $mlStatus];

        // 6. Funnel anomaly — form_interviews count last 7d vs prior 7d, >30% drop = warn
        $last7d = DB::table('form_interviews')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $prev7d = DB::table('form_interviews')
            ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
            ->count();
        $funnelDelta = $prev7d > 0 ? round((($last7d - $prev7d) / $prev7d) * 100, 1) : 0;
        $funnelStatus = 'OK';
        if ($funnelDelta <= -30) {
            $funnelStatus = 'CRITICAL';
            $degraded = true;
        } elseif ($funnelDelta <= -15) {
            $funnelStatus = 'WARN';
            $degraded = true;
        }
        $checks[] = ['Funnel Delta (7d vs prev)', $funnelDelta . '% (' . $last7d . ' vs ' . $prev7d . ')', $funnelStatus];

        // Output table
        $this->table(['Check', 'Value', 'Status'], $checks);
        $this->newLine();

        // Overall status
        if ($degraded) {
            $this->warn('Overall: DEGRADED');

            $meta = [];
            foreach ($checks as $check) {
                if ($check[2] !== 'OK') {
                    $meta[$check[0]] = ['value' => $check[1], 'status' => $check[2]];
                }
            }

            SystemEventService::warn(
                'system_degraded',
                'SystemHealthCheck',
                'System health degraded',
                $meta
            );
        } else {
            $this->info('Overall: HEALTHY');
        }

        return 0;
    }
}
