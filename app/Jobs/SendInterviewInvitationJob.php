<?php

namespace App\Jobs;

use App\Models\CandidateTimelineEvent;
use App\Models\InterviewInvitation;
use App\Models\PoolCandidate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class SendInterviewInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout = 60;

    public function __construct(
        public string $candidateId,
    ) {
        $this->onQueue('emails');
        $this->captureBrand();
    }

    public function handle(): void
    {
        $this->setBrandDatabase();
        // Guard: feature flag — either clean_workflow_v1 or question_bank_v1 must be enabled
        if (!config('maritime.clean_workflow_v1') && !config('maritime.question_bank_v1')) {
            Log::info('SendInterviewInvitationJob: no invitation workflow enabled, skipping', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        $candidate = PoolCandidate::find($this->candidateId);

        if (!$candidate) {
            Log::warning('SendInterviewInvitationJob: candidate not found', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        // Guard: candidate must have email
        if (!$candidate->email) {
            Log::warning('SendInterviewInvitationJob: candidate has no email', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        // Guard: application must be completed
        if (!$candidate->application_completed_at) {
            Log::warning('SendInterviewInvitationJob: application not completed', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        // Idempotency: skip if active invitation already exists
        $existing = InterviewInvitation::where('pool_candidate_id', $candidate->id)
            ->whereIn('status', [InterviewInvitation::STATUS_INVITED, InterviewInvitation::STATUS_STARTED])
            ->first();

        if ($existing) {
            Log::info('SendInterviewInvitationJob: active invitation already exists', [
                'candidate_id' => $candidate->id,
                'invitation_id' => $existing->id,
                'status' => $existing->status,
            ]);
            return;
        }

        // Idempotency: skip if interview already completed
        $completedInterview = $candidate->formInterviews()
            ->where('type', 'behavioral')
            ->where('status', 'completed')
            ->exists();

        if ($completedInterview) {
            Log::info('SendInterviewInvitationJob: candidate already has completed interview', [
                'candidate_id' => $candidate->id,
            ]);
            return;
        }

        // Create invitation
        $invitation = InterviewInvitation::create([
            'pool_candidate_id' => $candidate->id,
            'locale' => $candidate->preferred_language ?? 'en',
            'meta' => [
                'rank' => $candidate->rank ?? $candidate->source_meta['position'] ?? null,
                'department' => $candidate->source_meta['department'] ?? null,
                'question_bank_v1' => config('maritime.question_bank_v1', false),
            ],
        ]);

        Log::info('SendInterviewInvitationJob: invitation created', [
            'candidate_id' => $candidate->id,
            'invitation_id' => $invitation->id,
            'expires_at' => $invitation->expires_at->toIso8601String(),
            'question_bank' => config('maritime.question_bank_v1', false),
        ]);

        // Dispatch email
        SendCandidateEmailJob::dispatchSafe(
            $candidate->id,
            'interview_invitation',
        );

        // Track invite_sent_at for idempotency audit
        $invitation->update(['meta' => array_merge($invitation->meta ?? [], [
            'invite_sent_at' => now()->toIso8601String(),
        ])]);

        // Record timeline event
        CandidateTimelineEvent::record(
            $candidate->id,
            'interview_invited',
            CandidateTimelineEvent::SOURCE_SYSTEM,
            [
                'invitation_id' => $invitation->id,
                'expires_at' => $invitation->expires_at->toIso8601String(),
                'question_bank_v1' => config('maritime.question_bank_v1', false),
            ]
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendInterviewInvitationJob: permanently failed', [
            'candidate_id' => $this->candidateId,
            'error' => $e->getMessage(),
        ]);
    }
}
