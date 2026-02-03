<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOutboxMessagesJob;
use App\Models\Interview;
use App\Models\MessageOutbox;
use App\Services\Calendar\IcsService;
use App\Services\Email\EmailTemplateService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Send interview reminder emails 24 hours before token expiry.
 *
 * Conditions:
 * - interview.status = pending
 * - interview.token_expires_at within 24 hours
 * - interview.reminder_sent_at IS NULL
 *
 * Runs hourly via scheduler.
 */
class SendInterviewReminders extends Command
{
    protected $signature = 'interviews:send-reminders
                            {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send reminder emails for interviews expiring within 24 hours';

    private EmailTemplateService $emailService;
    private IcsService $icsService;

    public function __construct(EmailTemplateService $emailService, IcsService $icsService)
    {
        parent::__construct();
        $this->emailService = $emailService;
        $this->icsService = $icsService;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Checking for interviews requiring reminders...');

        // Find interviews that:
        // 1. Status is pending (not started, not completed)
        // 2. Token expires within 24 hours
        // 3. Reminder not yet sent
        // 4. Token hasn't expired yet
        $now = Carbon::now();
        $cutoff = $now->copy()->addHours(24);

        $interviews = Interview::query()
            ->where('status', Interview::STATUS_PENDING)
            ->whereNull('reminder_sent_at')
            ->where('token_expires_at', '>', $now)
            ->where('token_expires_at', '<=', $cutoff)
            ->with(['candidate', 'job.company'])
            ->get();

        if ($interviews->isEmpty()) {
            $this->info('No interviews require reminders at this time.');
            return Command::SUCCESS;
        }

        $this->info("Found {$interviews->count()} interviews to remind.");

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($interviews as $interview) {
            $candidate = $interview->candidate;
            $job = $interview->job;
            $company = $job?->company;

            if (!$candidate || !$candidate->email) {
                $this->warn("Skipping interview {$interview->id}: No candidate email");
                $skipped++;
                continue;
            }

            if (!$company) {
                $this->warn("Skipping interview {$interview->id}: No company");
                $skipped++;
                continue;
            }

            $this->line("Processing: {$candidate->email} (Interview: {$interview->id})");

            if ($dryRun) {
                $this->info("  [DRY RUN] Would send reminder to {$candidate->email}");
                $sent++;
                continue;
            }

            try {
                $this->sendReminder($interview, $candidate, $job, $company);
                $sent++;
                $this->info("  ✓ Reminder queued for {$candidate->email}");
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ Failed: {$e->getMessage()}");
                Log::error('Interview reminder failed', [
                    'interview_id' => $interview->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary: Sent={$sent}, Skipped={$skipped}, Failed={$failed}");

        return Command::SUCCESS;
    }

    private function sendReminder($interview, $candidate, $job, $company): void
    {
        $locale = 'tr'; // Default locale
        $interviewUrl = $interview->getInterviewUrl();

        // Render reminder email
        $email = $this->emailService->renderInterviewReminder([
            'company' => $company,
            'candidate' => $candidate,
            'interview_url' => $interviewUrl,
            'expires_at' => $interview->token_expires_at,
            'locale' => $locale,
        ]);

        // Generate ICS attachment
        $icsContent = $this->icsService->generateInterviewIcs([
            'interview_id' => $interview->id,
            'company_name' => $company->name,
            'job_title' => $job->title ?? 'Position',
            'interview_url' => $interviewUrl,
            'start_time' => $interview->scheduled_at ?? $interview->token_expires_at->copy()->subHours(24),
            'duration_minutes' => config('interview.default_duration', 30),
            'timezone' => $company->timezone ?? 'Europe/Istanbul',
            'locale' => $locale,
        ]);

        $icsFilename = $this->icsService->getFilename($company->name, $locale);

        // Create outbox message with ICS attachment
        $outbox = MessageOutbox::create([
            'company_id' => $company->id,
            'channel' => 'email',
            'message_type' => 'interview_reminder',
            'recipient' => $candidate->email,
            'recipient_name' => trim($candidate->first_name . ' ' . $candidate->last_name),
            'subject' => $email['subject'],
            'body' => $email['body'],
            'metadata' => [
                'interview_id' => $interview->id,
                'candidate_id' => $candidate->id,
                'locale' => $locale,
                'attachments' => [
                    [
                        'filename' => $icsFilename,
                        'content' => base64_encode($icsContent),
                        'mime_type' => 'text/calendar',
                    ],
                ],
            ],
            'status' => 'pending',
            'priority' => 'high',
        ]);

        // Mark reminder as sent
        $interview->update(['reminder_sent_at' => now()]);

        // Dispatch job
        ProcessOutboxMessagesJob::dispatch($outbox->id);

        Log::info('Interview reminder queued', [
            'interview_id' => $interview->id,
            'outbox_id' => $outbox->id,
            'candidate_email' => $candidate->email,
        ]);
    }
}
