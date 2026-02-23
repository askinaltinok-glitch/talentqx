<?php

namespace App\Console\Commands;

use App\Models\CandidateReminderLog;
use App\Models\MaritimeJob;
use App\Models\MessageOutbox;
use App\Models\PoolCandidate;
use App\Services\Outbox\OutboxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendWeeklyJobPulse extends Command
{
    protected $signature = 'maritime:weekly-job-pulse
        {--limit=200 : Maximum candidates per run}
        {--dry-run : Preview without sending}';

    protected $description = 'Send weekly job pulse digest emails to active maritime candidates';

    public function handle(OutboxService $outboxService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        // Get active jobs (posted in last 14 days)
        $recentJobs = MaritimeJob::query()
            ->where('status', 'published')
            ->where('created_at', '>=', now()->subDays(14))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Find eligible candidates:
        // - Maritime, verified email, not archived, not hired
        // - Haven't received a pulse email in last 6 days
        $candidates = PoolCandidate::query()
            ->where('primary_industry', 'maritime')
            ->whereNotNull('email_verified_at')
            ->whereNotIn('status', [PoolCandidate::STATUS_ARCHIVED, PoolCandidate::STATUS_HIRED])
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('candidate_reminder_logs')
                    ->whereColumn('candidate_reminder_logs.pool_candidate_id', 'pool_candidates.id')
                    ->where('reminder_type', 'weekly_job_pulse')
                    ->where('sent_at', '>=', now()->subDays(6));
            })
            ->limit($limit)
            ->get();

        $count = $candidates->count();
        $sent = 0;
        $errors = 0;

        $this->info("Eligible candidates: {$count}");
        $this->info("Active jobs to feature: {$recentJobs->count()}");

        if ($count === 0) {
            $this->info('No candidates eligible for weekly pulse.');
            return Command::SUCCESS;
        }

        foreach ($candidates as $candidate) {
            if ($dryRun) {
                $this->line("  Would send to: {$candidate->email} ({$candidate->full_name})");
                $sent++;
                continue;
            }

            try {
                $body = $this->buildPulseEmail($candidate, $recentJobs);

                $outboxService->queue([
                    'channel' => MessageOutbox::CHANNEL_EMAIL,
                    'recipient' => $candidate->email,
                    'recipient_name' => $candidate->full_name,
                    'subject' => 'Your Weekly Maritime Job Pulse — Octopus AI',
                    'body' => $body,
                    'related_type' => 'pool_candidate',
                    'related_id' => $candidate->id,
                    'priority' => 40, // Low priority
                    'metadata' => [
                        'type' => 'weekly_job_pulse',
                        'jobs_featured' => $recentJobs->count(),
                    ],
                ]);

                // Log to prevent re-sends
                CandidateReminderLog::create([
                    'pool_candidate_id' => $candidate->id,
                    'reminder_type' => 'weekly_job_pulse',
                    'sent_at' => now(),
                    'channel' => 'email',
                ]);

                $sent++;
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('WeeklyJobPulse: failed', [
                    'candidate_id' => $candidate->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent: {$sent}, Errors: {$errors}" . ($dryRun ? ' (DRY RUN)' : ''));

        Log::info('maritime:weekly-job-pulse', [
            'eligible' => $count,
            'sent' => $sent,
            'errors' => $errors,
            'dry_run' => $dryRun,
        ]);

        return Command::SUCCESS;
    }

    private function buildPulseEmail(PoolCandidate $candidate, $jobs): string
    {
        $name = $candidate->first_name ?: 'Seafarer';
        $rank = $candidate->rank
            ? str_replace('_', ' ', ucwords($candidate->rank, '_'))
            : null;

        $greeting = "Hello {$name},";
        if ($rank) {
            $greeting .= "\n\nAs a {$rank}, here's your weekly update:";
        }

        $body = "{$greeting}\n\n";

        // Certificate reminders
        $expiringCerts = $candidate->credentials()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(90))
            ->where('expires_at', '>', now())
            ->count();

        if ($expiringCerts > 0) {
            $body .= "⚠ You have {$expiringCerts} certificate(s) expiring within 90 days. Log in to review.\n\n";
        }

        // Job listings
        if ($jobs->isNotEmpty()) {
            $body .= "--- New Opportunities ---\n\n";
            foreach ($jobs->take(3) as $job) {
                $body .= "• {$job->title}";
                if ($job->vessel_type) $body .= " ({$job->vessel_type})";
                if ($job->company_name) $body .= " — {$job->company_name}";
                $body .= "\n";
            }
            $body .= "\nView all jobs: https://octopus-ai.net/en/maritime/jobs\n\n";
        }

        // Profile completion prompt
        $body .= "--- Keep Your Profile Active ---\n";
        $body .= "Companies are searching for crew. Make sure your certificates are up to date.\n\n";
        $body .= "Best regards,\nOctopus AI Maritime Team\n\n";
        $body .= "---\n";
        $body .= "You're receiving this because you registered on Octopus AI Maritime.\n";
        $body .= "To unsubscribe, reply with STOP.";

        return $body;
    }
}
