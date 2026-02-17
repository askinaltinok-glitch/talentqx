<?php

namespace App\Console\Commands;

use App\Jobs\SendOutboundEmailJob;
use App\Models\CrmOutboundQueue;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CrmSendQueuedMailsCommand extends Command
{
    protected $signature = 'crm:send-queued-mails {--batch=20 : Max emails to process per run}';
    protected $description = 'Send approved outbound queue emails via SMTP';

    public function handle(): int
    {
        // Respect quiet hours
        if ($this->isQuietHours()) {
            $this->info('Quiet hours active — skipping.');
            return 0;
        }

        // Global daily send cap
        $maxTotalPerDay = config('crm_mail.max_total_per_day', 300);
        $todayTotalSent = CrmOutboundQueue::whereIn('status', ['sent', 'sending'])
            ->whereDate('sent_at', today())
            ->count();

        if ($todayTotalSent >= $maxTotalPerDay) {
            \App\Services\System\SystemEventService::warn('mail_daily_cap', 'CrmSendQueuedMails', "Global daily cap reached ({$todayTotalSent}/{$maxTotalPerDay})");
            $this->warn("Global daily cap reached ({$todayTotalSent}/{$maxTotalPerDay}). Skipping.");
            return 0;
        }

        // SMTP Circuit Breaker check
        $breaker = \App\Models\SmtpCircuitBreaker::forKey('smtp');
        if ($breaker->isOpen()) {
            \App\Services\System\SystemEventService::warn('smtp_breaker_open', 'CrmSendQueuedMails', 'SMTP circuit breaker is open, skipping send cycle');
            $this->warn('SMTP circuit breaker is open. Skipping.');
            return 0;
        }

        $batch = (int) $this->option('batch');

        $items = CrmOutboundQueue::readyToSend()
            ->orderBy('created_at')
            ->limit($batch)
            ->get();

        if ($items->isEmpty()) {
            $this->info('No approved emails to send.');
            return 0;
        }

        $this->info("Dispatching {$items->count()} email(s)...");

        $maxPerDay = config('crm_mail.auto_send_rules.max_per_lead_per_day', 2);
        $dispatched = 0;
        $skipped = 0;

        foreach ($items as $item) {
            // Safety net: enforce per-lead daily limit before dispatching
            $todaySent = CrmOutboundQueue::where('lead_id', $item->lead_id)
                ->where('id', '!=', $item->id)
                ->whereIn('status', ['sent', 'sending'])
                ->whereDate('sent_at', today())
                ->count();

            if ($todaySent >= $maxPerDay) {
                $skipped++;
                Log::info('CrmSendQueuedMails: Daily limit reached, skipping', [
                    'queue_id' => $item->id,
                    'lead_id' => $item->lead_id,
                    'today_sent' => $todaySent,
                ]);
                continue;
            }

            // Template cooldown: skip if same template + lead sent within 48h
            if ($item->template_key) {
                $cooldownHours = config('crm_mail.template_cooldown_hours', 48);
                $recentlySent = CrmOutboundQueue::where('lead_id', $item->lead_id)
                    ->where('template_key', $item->template_key)
                    ->where('id', '!=', $item->id)
                    ->where('status', 'sent')
                    ->where('sent_at', '>=', now()->subHours($cooldownHours))
                    ->exists();

                if ($recentlySent) {
                    $skipped++;
                    Log::info('CrmSendQueuedMails: Template cooldown active, skipping', [
                        'queue_id' => $item->id,
                        'template_key' => $item->template_key,
                    ]);
                    continue;
                }
            }

            SendOutboundEmailJob::dispatch($item);
            $dispatched++;
        }

        $this->info("Done. Dispatched {$dispatched}, skipped {$skipped} (daily limit).");

        return 0;
    }

    private function isQuietHours(): bool
    {
        $config = config('crm_mail.quiet_hours');
        if (!($config['enabled'] ?? false)) {
            return false;
        }

        $tz = $config['timezone'] ?? 'UTC';
        $now = Carbon::now($tz);
        $start = Carbon::parse($config['start'], $tz);
        $end = Carbon::parse($config['end'], $tz);

        if ($start->gt($end)) {
            return $now->gte($start) || $now->lt($end);
        }

        return $now->gte($start) && $now->lt($end);
    }
}
