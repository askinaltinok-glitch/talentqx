<?php

namespace App\Services\Maritime;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoleFitAlertService
{
    private RoleFitMetricsService $metricsService;

    public function __construct(RoleFitMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Check alert conditions and fire webhook if threshold breached.
     * Returns true if alert was sent, false otherwise.
     */
    public function check(): bool
    {
        if (!config('maritime.role_fit_alerts.enabled', false)) {
            return false;
        }

        $windowHours = (int) config('maritime.role_fit_alerts.window_hours', 24);
        $threshold = (float) config('maritime.role_fit_alerts.mismatch_pct_threshold', 30.0);
        $minTotal = (int) config('maritime.role_fit_alerts.min_total_evaluations', 10);
        $cooldownMinutes = (int) config('maritime.role_fit_alerts.cooldown_minutes', 120);
        $webhookUrl = config('maritime.role_fit_alerts.webhook_url', '');
        $channel = config('maritime.role_fit_alerts.channel', 'slack');

        if (empty($webhookUrl)) {
            return false;
        }

        // Namespaced cooldown: channel + window so slack/telegram don't block each other
        $cooldownKey = "role_fit_alert:last_sent:{$channel}:{$windowHours}h";
        if (Cache::has($cooldownKey)) {
            return false;
        }

        $metrics = $this->metricsService->compute($windowHours);

        // Hard min_total gate — never alert on tiny sample sizes
        $total = $metrics['counts']['total'] ?? 0;
        if ($total < $minTotal || $minTotal < 1) {
            return false;
        }

        $mismatchPct = $metrics['rates']['role_mismatch_pct'] ?? 0.0;
        if ($mismatchPct < $threshold) {
            return false;
        }

        // Threshold breached — send alert
        $sent = $this->sendAlert($channel, $webhookUrl, $metrics, $mismatchPct, $threshold, $windowHours);

        if ($sent) {
            Cache::put($cooldownKey, true, now()->addMinutes($cooldownMinutes));
            Log::info("Role-fit alert sent: mismatch {$mismatchPct}% >= {$threshold}% (window {$windowHours}h, total {$total}, channel {$channel})");
        }

        return $sent;
    }

    private function sendAlert(string $channel, string $webhookUrl, array $metrics, float $pct, float $threshold, int $windowHours): bool
    {
        $topRoles = implode(', ', array_map(
            fn($r) => "{$r['role_key']} ({$r['count']})",
            $metrics['top_mismatch_roles'] ?? [],
        ));

        $text = ":warning: *Role-Fit Alert* — Mismatch rate {$pct}% exceeds {$threshold}% threshold\n"
            . "Window: {$windowHours}h | Total: {$metrics['counts']['total']} | "
            . "Mismatch: {$metrics['counts']['role_mismatch']} | "
            . "Avg score: {$metrics['score']['avg']}\n"
            . "Top roles: {$topRoles}";

        try {
            if ($channel === 'slack') {
                $response = Http::timeout(10)->post($webhookUrl, ['text' => $text]);
                return $response->successful();
            }

            if ($channel === 'telegram') {
                $response = Http::timeout(10)->post($webhookUrl, [
                    'text' => strip_tags(str_replace(['*', ':warning:'], ['', '⚠️'], $text)),
                    'parse_mode' => 'HTML',
                ]);
                return $response->successful();
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning("Role-fit alert delivery failed: {$e->getMessage()}");
            return false;
        }
    }
}
