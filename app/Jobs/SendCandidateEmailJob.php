<?php

namespace App\Jobs;

use App\Mail\ApplicationReceivedMail;
use App\Mail\BehavioralInterviewInviteMail;
use App\Mail\InterviewCompletedMail;
use App\Models\CandidateEmailLog;
use App\Models\FormInterview;
use App\Models\PoolCandidate;
use App\Services\Brand\BrandResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCandidateEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout = 60;

    public function __construct(
        public string $candidateId,
        public string $mailType,
        public ?string $interviewId = null,
        public ?string $positionName = null,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $candidate = PoolCandidate::find($this->candidateId);

        if (!$candidate || !$candidate->email) {
            Log::warning('SendCandidateEmailJob: candidate not found or no email', [
                'candidate_id' => $this->candidateId,
                'mail_type' => $this->mailType,
            ]);
            return;
        }

        // Resolve brand: prefer interview context, fall back to candidate
        $brand = $this->resolveBrand($candidate);

        $mailable = $this->buildMailable($candidate, $brand);
        if (!$mailable) {
            Log::warning('SendCandidateEmailJob: unknown mail type', ['mail_type' => $this->mailType]);
            return;
        }

        // Create log entry
        $log = CandidateEmailLog::create([
            'pool_candidate_id' => $candidate->id,
            'interview_id' => $this->interviewId,
            'mail_type' => $this->mailType,
            'language' => $candidate->preferred_language ?? 'tr',
            'to_email' => $candidate->email,
            'subject' => $mailable->getSubjectText(),
            'status' => 'sending',
        ]);

        try {
            // Safety mode: only send to whitelisted emails in test mode
            if (config('mail.safety_mode') && !$this->isWhitelisted($candidate->email)) {
                Log::info('SendCandidateEmailJob: safety mode - email blocked', [
                    'email' => $candidate->email,
                    'mail_type' => $this->mailType,
                ]);
                $log->update([
                    'status' => 'blocked_safety',
                    'smtp_response' => 'Blocked by safety mode',
                    'sent_at' => now(),
                ]);
                return;
            }

            Mail::to($candidate->email)->send($mailable);

            $log->update([
                'status' => 'sent',
                'smtp_response' => 'OK',
                'sent_at' => now(),
            ]);

            Log::info('SendCandidateEmailJob: email sent', [
                'candidate_id' => $candidate->id,
                'email' => $candidate->email,
                'mail_type' => $this->mailType,
                'language' => $candidate->preferred_language,
                'brand' => $brand['name'] ?? 'unknown',
                'log_id' => $log->id,
            ]);

        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 1000),
            ]);

            Log::error('SendCandidateEmailJob: email failed', [
                'candidate_id' => $candidate->id,
                'email' => $candidate->email,
                'mail_type' => $this->mailType,
                'error' => $e->getMessage(),
                'log_id' => $log->id,
            ]);

            throw $e; // Rethrow for queue retry
        }
    }

    private function resolveBrand(PoolCandidate $candidate): array
    {
        // If we have an interview ID, resolve from the interview
        if ($this->interviewId) {
            $interview = FormInterview::find($this->interviewId);
            if ($interview) {
                return BrandResolver::fromInterview($interview);
            }
        }

        // Fall back to candidate-level resolution
        return BrandResolver::fromCandidate($candidate);
    }

    private function buildMailable(PoolCandidate $candidate, array $brand): ApplicationReceivedMail|InterviewCompletedMail|BehavioralInterviewInviteMail|null
    {
        return match ($this->mailType) {
            'application_received' => new ApplicationReceivedMail($candidate, $brand),
            'interview_completed' => new InterviewCompletedMail($candidate, $this->positionName, $brand),
            'behavioral_interview_invite' => new BehavioralInterviewInviteMail($candidate, $brand),
            default => null,
        };
    }

    private function isWhitelisted(string $email): bool
    {
        $whitelist = config('mail.test_whitelist', []);
        return in_array($email, $whitelist, true);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendCandidateEmailJob: permanently failed after retries', [
            'candidate_id' => $this->candidateId,
            'mail_type' => $this->mailType,
            'interview_id' => $this->interviewId,
            'error' => $e->getMessage(),
        ]);

        CandidateEmailLog::where('pool_candidate_id', $this->candidateId)
            ->where('mail_type', $this->mailType)
            ->where('status', 'sending')
            ->latest()
            ->first()
            ?->update([
                'status' => 'failed_permanent',
                'error_message' => 'All retries exhausted: ' . substr($e->getMessage(), 0, 500),
            ]);
    }

    /**
     * Dispatch with queue, fallback to sync if queue fails.
     */
    public static function dispatchSafe(
        string $candidateId,
        string $mailType,
        ?string $interviewId = null,
        ?string $positionName = null,
    ): void {
        try {
            self::dispatch($candidateId, $mailType, $interviewId, $positionName);
        } catch (\Throwable $e) {
            Log::warning('SendCandidateEmailJob: queue dispatch failed, sending sync', [
                'error' => $e->getMessage(),
            ]);
            try {
                self::dispatchSync($candidateId, $mailType, $interviewId, $positionName);
            } catch (\Throwable $e2) {
                Log::error('SendCandidateEmailJob: sync fallback also failed', [
                    'error' => $e2->getMessage(),
                ]);
            }
        }
    }
}
