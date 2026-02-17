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
 * Send interview reminder emails.
 *
 * T-24: 24 hours before (reminder_sent_at IS NULL)
 * T-1:  1 hour before (last_hour_reminder_sent_at IS NULL)
 *
 * Conditions:
 * - interview.status = pending
 * - interview.token_expires_at/scheduled_at within window
 *
 * Runs hourly via scheduler.
 */
class SendInterviewReminders extends Command
{
    protected $signature = 'interviews:send-reminders
                            {--dry-run : Show what would be sent without actually sending}
                            {--type=all : Type of reminder: all, T-24, T-1}';

    protected $description = 'Send T-24 and T-1 reminder emails for pending interviews';

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
        $type = $this->option('type');
        $now = Carbon::now();

        $this->info("🕐 Şu an: {$now->format('Y-m-d H:i')}");
        $this->newLine();

        $totalSent = 0;
        $totalSkipped = 0;
        $totalFailed = 0;

        // T-24 reminders (23-25 hours window)
        if ($type === 'all' || $type === 'T-24') {
            $this->info('📬 T-24 Hatırlatmaları (24 saat önce)');
            $result = $this->processReminders(
                $now->copy()->addHours(23),
                $now->copy()->addHours(25),
                'reminder_sent_at',
                'T-24',
                $dryRun
            );
            $totalSent += $result['sent'];
            $totalSkipped += $result['skipped'];
            $totalFailed += $result['failed'];
        }

        // T-1 reminders (55-65 minutes window)
        if ($type === 'all' || $type === 'T-1') {
            $this->newLine();
            $this->info('⏰ T-1 Hatırlatmaları (1 saat önce)');
            $result = $this->processReminders(
                $now->copy()->addMinutes(55),
                $now->copy()->addMinutes(65),
                'last_hour_reminder_sent_at',
                'T-1',
                $dryRun
            );
            $totalSent += $result['sent'];
            $totalSkipped += $result['skipped'];
            $totalFailed += $result['failed'];
        }

        $this->newLine();
        $this->info("📧 Toplam: Sent={$totalSent}, Skipped={$totalSkipped}, Failed={$totalFailed}");

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN: Mail gönderilmedi.');
        }

        return Command::SUCCESS;
    }

    private function processReminders(Carbon $start, Carbon $end, string $sentAtField, string $reminderType, bool $dryRun): array
    {
        $now = Carbon::now();

        // Find interviews that need reminders
        $query = Interview::query()
            ->where('status', Interview::STATUS_PENDING)
            ->with(['candidate', 'job.company']);

        // Check scheduled_at first, fallback to token_expires_at
        $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('scheduled_at', [$start, $end])
              ->orWhere(function ($q2) use ($start, $end) {
                  $q2->whereNull('scheduled_at')
                     ->whereBetween('token_expires_at', [$start, $end]);
              });
        });

        // Filter: only send if not already sent (prevents duplicate emails)
        $query->whereNull($sentAtField);

        $interviews = $query->get();

        if ($interviews->isEmpty()) {
            $this->line('  (yok)');
            return ['sent' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($interviews as $interview) {
            $candidate = $interview->candidate;
            $job = $interview->job;
            $company = $job?->company;
            $targetTime = $interview->scheduled_at ?? $interview->token_expires_at;

            if (!$candidate || !$candidate->email) {
                $this->warn("  ⊘ {$interview->id}: Email yok");
                $skipped++;
                continue;
            }

            if (!$company) {
                $this->warn("  ⊘ {$interview->id}: Company yok");
                $skipped++;
                continue;
            }

            $this->line("  • {$candidate->first_name} {$candidate->last_name} <{$candidate->email}> → {$targetTime->format('d.m H:i')}");

            if ($dryRun) {
                $sent++;
                continue;
            }

            try {
                $this->sendReminder($interview, $candidate, $job, $company, $reminderType);
                $sent++;
                $this->info("    ✓ Queued");
            } catch (\Exception $e) {
                $failed++;
                $this->error("    ✗ {$e->getMessage()}");
                Log::error('Interview reminder failed', [
                    'interview_id' => $interview->id,
                    'type' => $reminderType,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
    }

    private function sendReminder($interview, $candidate, $job, $company, string $reminderType = 'T-24'): void
    {
        $locale = 'tr'; // Default locale
        $interviewUrl = $interview->getInterviewUrl();
        $targetTime = $interview->scheduled_at ?? $interview->token_expires_at;

        // Render reminder email based on type
        if ($reminderType === 'T-1') {
            $email = $this->emailService->renderLastHourReminder([
                'company' => $company,
                'candidate' => $candidate,
                'job' => $job,
                'interview_url' => $interviewUrl,
                'scheduled_at' => $targetTime,
                'locale' => $locale,
            ]);
            $messageType = 'interview_reminder_t1';
        } else {
            $email = $this->emailService->renderInterviewReminder([
                'company' => $company,
                'candidate' => $candidate,
                'interview_url' => $interviewUrl,
                'expires_at' => $interview->token_expires_at,
                'locale' => $locale,
            ]);
            $messageType = 'interview_reminder';
        }

        // Generate ICS attachment (only for T-24)
        $attachments = [];
        if ($reminderType === 'T-24') {
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
            $attachments[] = [
                'filename' => $icsFilename,
                'content' => base64_encode($icsContent),
                'mime_type' => 'text/calendar',
            ];
        }

        // Create outbox message
        $outbox = MessageOutbox::create([
            'company_id' => $company->id,
            'channel' => 'email',
            'message_type' => $messageType,
            'recipient' => $candidate->email,
            'recipient_name' => trim($candidate->first_name . ' ' . $candidate->last_name),
            'subject' => $email['subject'],
            'body' => $email['body'],
            'metadata' => [
                'interview_id' => $interview->id,
                'candidate_id' => $candidate->id,
                'reminder_type' => $reminderType,
                'locale' => $locale,
                'attachments' => $attachments,
            ],
            'status' => 'pending',
            'priority' => $reminderType === 'T-1' ? 'urgent' : 'high',
        ]);

        // Mark reminder as sent (prevents duplicate emails)
        if ($reminderType === 'T-24') {
            $interview->update(['reminder_sent_at' => now()]);
        } else {
            $interview->update(['last_hour_reminder_sent_at' => now()]);
        }

        // Dispatch job
        ProcessOutboxMessagesJob::dispatch($outbox->id);

        Log::info('Interview reminder queued', [
            'interview_id' => $interview->id,
            'outbox_id' => $outbox->id,
            'candidate_email' => $candidate->email,
            'type' => $reminderType,
        ]);
    }
}
