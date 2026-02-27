<?php

namespace App\Jobs;

use App\Mail\CredentialExpiryReminderMail;
use App\Models\CandidateCredential;
use App\Models\CandidateEmailLog;
use App\Models\CandidateReminderLog;
use App\Models\CandidateTimelineEvent;
use App\Models\PoolCandidate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Mail;

class SendCredentialReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout = 60;

    public function __construct(
        public string $candidateId,
        public string $credentialId,
        public string $reminderType,
    ) {
        $this->onQueue('emails');
        $this->captureBrand();
    }

    public function handle(): void
    {
        $this->setBrandDatabase();
        $candidate = PoolCandidate::find($this->candidateId);
        $credential = CandidateCredential::find($this->credentialId);

        if (!$candidate || !$credential || !$candidate->email) {
            Log::warning('SendCredentialReminderJob: candidate/credential not found', [
                'candidate_id' => $this->candidateId,
                'credential_id' => $this->credentialId,
            ]);
            return;
        }

        $mailable = new CredentialExpiryReminderMail($candidate, $credential, $this->reminderType);

        // Create reminder log
        $reminderLog = CandidateReminderLog::create([
            'pool_candidate_id' => $candidate->id,
            'credential_id' => $credential->id,
            'reminder_type' => $this->reminderType,
            'channel' => 'email',
            'to' => $candidate->email,
            'language' => $candidate->preferred_language ?? 'en',
            'status' => CandidateReminderLog::STATUS_QUEUED,
        ]);

        // Also log to candidate_email_logs for unified tracking
        $emailLog = CandidateEmailLog::create([
            'pool_candidate_id' => $candidate->id,
            'mail_type' => 'credential_expiry_reminder',
            'language' => $candidate->preferred_language ?? 'en',
            'to_email' => $candidate->email,
            'subject' => $mailable->getSubjectText(),
            'status' => 'sending',
        ]);

        try {
            // Safety mode check
            if (config('mail.safety_mode') && !$this->isWhitelisted($candidate->email)) {
                Log::info('SendCredentialReminderJob: safety mode - blocked', [
                    'email' => $candidate->email,
                    'credential_id' => $this->credentialId,
                ]);
                $reminderLog->update(['status' => CandidateReminderLog::STATUS_BLOCKED_SAFETY]);
                $emailLog->update(['status' => 'blocked_safety', 'sent_at' => now()]);
                return;
            }

            Mail::to($candidate->email)->send($mailable);

            $reminderLog->markSent();
            $emailLog->update(['status' => 'sent', 'smtp_response' => 'OK', 'sent_at' => now()]);

            try {
                app(\App\Services\AdminNotificationService::class)->notifyEmailSent(
                    'credential_reminder',
                    $candidate->email,
                    "Credential reminder: {$candidate->first_name} {$candidate->last_name}",
                    ['candidate_id' => $candidate->id, 'credential_id' => $this->credentialId]
                );
            } catch (\Throwable) {}

            // Update credential last reminded timestamp
            $credential->update(['last_reminded_at' => now()]);

            // Timeline event
            CandidateTimelineEvent::record(
                $candidate->id,
                CandidateTimelineEvent::TYPE_REMINDER_SENT,
                CandidateTimelineEvent::SOURCE_SYSTEM,
                [
                    'credential_id' => $credential->id,
                    'credential_type' => $credential->credential_type,
                    'reminder_type' => $this->reminderType,
                ]
            );

            Log::info('SendCredentialReminderJob: sent', [
                'candidate_id' => $candidate->id,
                'credential_id' => $credential->id,
                'reminder_type' => $this->reminderType,
            ]);

        } catch (\Throwable $e) {
            $reminderLog->markFailed($e->getMessage());
            $emailLog->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 1000),
            ]);

            Log::error('SendCredentialReminderJob: failed', [
                'candidate_id' => $candidate->id,
                'credential_id' => $credential->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Rethrow for retry
        }
    }

    private function isWhitelisted(string $email): bool
    {
        $whitelist = config('mail.test_whitelist', []);
        return in_array($email, $whitelist, true);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendCredentialReminderJob: permanently failed', [
            'candidate_id' => $this->candidateId,
            'credential_id' => $this->credentialId,
            'reminder_type' => $this->reminderType,
            'error' => $e->getMessage(),
        ]);
    }
}
