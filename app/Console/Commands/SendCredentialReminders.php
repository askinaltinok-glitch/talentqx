<?php

namespace App\Console\Commands;

use App\Jobs\SendCredentialReminderJob;
use App\Models\CandidateCredential;
use App\Models\CandidateProfile;
use App\Models\CandidateReminderLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendCredentialReminders extends Command
{
    protected $signature = 'credentials:send-reminders {--dry-run : Show what would be sent without sending}';
    protected $description = 'Send credential expiry reminders (60/30/7/1 days before expiry)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $today = Carbon::today();
        $sent = 0;
        $skipped = 0;

        $reminderDays = CandidateReminderLog::REMINDER_DAYS;
        // [TYPE_EXPIRY_60 => 60, TYPE_EXPIRY_30 => 30, TYPE_EXPIRY_7 => 7, TYPE_EXPIRY_1 => 1]

        foreach ($reminderDays as $reminderType => $days) {
            $targetDate = $today->copy()->addDays($days);

            // Find credentials expiring on that exact date
            $credentials = CandidateCredential::whereDate('expires_at', $targetDate)
                ->with(['poolCandidate.profile', 'poolCandidate.primaryEmail'])
                ->get();

            foreach ($credentials as $credential) {
                $candidate = $credential->poolCandidate;

                if (!$candidate) {
                    $skipped++;
                    continue;
                }

                // Gate 1: profile must have reminders_opt_in=true
                $profile = $candidate->profile;
                if (!$profile || !$profile->reminders_opt_in) {
                    $this->line("  SKIP [{$reminderType}] {$candidate->email} — reminders_opt_in=false");
                    $skipped++;
                    continue;
                }

                // Gate 2: profile must not be blocked
                if ($profile->isBlocked()) {
                    $this->line("  SKIP [{$reminderType}] {$candidate->email} — blocked");
                    $skipped++;
                    continue;
                }

                // Gate 3: primary email must be verified
                $primaryEmail = $candidate->primaryEmail;
                if (!$primaryEmail || !$primaryEmail->is_verified) {
                    $this->line("  SKIP [{$reminderType}] {$candidate->email} — email not verified");
                    $skipped++;
                    continue;
                }

                // Gate 4: de-dupe — check if same reminder_type for same credential already sent
                $alreadySent = CandidateReminderLog::where('credential_id', $credential->id)
                    ->where('reminder_type', $reminderType)
                    ->whereIn('status', [
                        CandidateReminderLog::STATUS_QUEUED,
                        CandidateReminderLog::STATUS_SENT,
                    ])
                    ->exists();

                if ($alreadySent) {
                    $this->line("  SKIP [{$reminderType}] {$candidate->email} — already sent");
                    $skipped++;
                    continue;
                }

                // Gate 5: must have email
                if (!$candidate->email) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->info("  DRY-RUN [{$reminderType}] → {$candidate->email} | {$credential->credential_type} expires {$credential->expires_at->toDateString()}");
                    $sent++;
                    continue;
                }

                // Dispatch reminder job
                try {
                    SendCredentialReminderJob::dispatch(
                        $candidate->id,
                        $credential->id,
                        $reminderType
                    );
                    $sent++;
                    $this->info("  QUEUED [{$reminderType}] → {$candidate->email} | {$credential->credential_type}");
                } catch (\Throwable $e) {
                    Log::error('credentials:send-reminders dispatch failed', [
                        'candidate_id' => $candidate->id,
                        'credential_id' => $credential->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  FAIL [{$reminderType}] {$candidate->email}: {$e->getMessage()}");
                }
            }
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Done. Sent: {$sent}, Skipped: {$skipped}");

        Log::info('credentials:send-reminders completed', [
            'dry_run' => $dryRun,
            'sent' => $sent,
            'skipped' => $skipped,
        ]);

        return self::SUCCESS;
    }
}
