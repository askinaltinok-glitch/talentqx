<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\MessageOutbox;
use App\Services\Outbox\OutboxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:send-reminders {--dry-run}';
    protected $description = 'Send subscription expiry reminders to companies (7d, 3d, 1d before expiration)';

    private const REMINDER_DAYS = [7, 3, 1];

    public function handle(OutboxService $outboxService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $sent = 0;

        foreach (self::REMINDER_DAYS as $days) {
            $targetDate = now()->addDays($days)->startOfDay();
            $nextDay = $targetDate->copy()->addDay();

            $companies = Company::query()
                ->whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '>=', $targetDate)
                ->where('subscription_ends_at', '<', $nextDay)
                ->where('subscription_plan', '!=', 'free')
                ->get();

            foreach ($companies as $company) {
                // Skip if we already sent this reminder level
                $cacheKey = "sub_reminder:{$company->id}:{$days}d";
                if (cache()->has($cacheKey)) {
                    continue;
                }

                $billingEmail = $company->billing_email ?? $company->users()->where('role', 'admin')->value('email');
                if (!$billingEmail) {
                    continue;
                }

                $this->info("[{$days}d] {$company->name} ({$billingEmail}) — expires {$company->subscription_ends_at->toDateString()}");

                if (!$dryRun) {
                    try {
                        $outboxService->queue([
                            'company_id' => $company->id,
                            'channel' => MessageOutbox::CHANNEL_EMAIL,
                            'recipient' => $billingEmail,
                            'recipient_name' => $company->name,
                            'subject' => "Subscription expires in {$days} day(s) — Octopus AI",
                            'body' => $this->buildBody($company, $days),
                            'related_type' => 'company',
                            'related_id' => $company->id,
                            'priority' => 15,
                            'metadata' => [
                                'type' => 'subscription_expiry_reminder',
                                'days_before' => $days,
                            ],
                        ]);

                        cache()->put($cacheKey, true, now()->addDays($days + 1));
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::warning('SendSubscriptionReminders: failed to queue', [
                            'company_id' => $company->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        $this->info("Sent {$sent} subscription reminders." . ($dryRun ? ' (DRY RUN)' : ''));
        return Command::SUCCESS;
    }

    private function buildBody(Company $company, int $days): string
    {
        $plan = strtoupper($company->subscription_plan);
        $expiresAt = $company->subscription_ends_at->format('d M Y');

        return <<<TEXT
Dear {$company->name},

Your Octopus AI {$plan} subscription will expire on {$expiresAt} ({$days} day(s) remaining).

After expiration, you will enter a 60-day grace period with limited access. To ensure uninterrupted service, please contact your account manager to renew.

Current plan: {$plan}
Credits remaining: {$company->getRemainingCredits()}

Contact us at support@octopus-ai.net for renewal.

Best regards,
Octopus AI Team
TEXT;
    }
}
