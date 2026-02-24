<?php

namespace App\Jobs;

use App\Models\CandidateNotification;
use App\Models\CandidatePushToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Mail;

class SendCandidateNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout = 60;

    public function __construct(
        public CandidateNotification $notification
    ) {
        $this->captureBrand();
    }

    public function handle(): void
    {
        $this->setBrandDatabase();
        // Skip delivery for demo notifications (mark as delivered but don't actually send)
        if ($this->notification->is_demo) {
            $this->notification->update(['delivered_at' => now()]);
            Log::info('SendCandidateNotificationJob: demo notification auto-delivered', [
                'notification_id' => $this->notification->id,
            ]);
            return;
        }

        $candidateId = $this->notification->pool_candidate_id;

        // Channel 1: In-app — already created before dispatch
        Log::info('SendCandidateNotificationJob: in-app notification exists', [
            'notification_id' => $this->notification->id,
            'type' => $this->notification->type,
        ]);

        // Channel 2: Push — iterate tokens, log for now (FCM/APNs placeholder)
        $tokens = CandidatePushToken::forCandidate($candidateId)->get();

        foreach ($tokens as $token) {
            // TODO: Integrate FCM/APNs SDK here
            Log::info('SendCandidateNotificationJob: push placeholder', [
                'notification_id' => $this->notification->id,
                'device_type' => $token->device_type,
                'token_prefix' => substr($token->token, 0, 10) . '...',
            ]);
        }

        // Channel 3: Email for high-priority types
        $highPriorityTypes = [
            CandidateNotification::TYPE_HIRED,
            CandidateNotification::TYPE_CERTIFICATE_EXPIRING,
        ];

        if (in_array($this->notification->type, $highPriorityTypes)) {
            $candidate = $this->notification->candidate;

            if ($candidate && $candidate->email) {
                // TODO: Replace with proper Mailable class
                Log::info('SendCandidateNotificationJob: email placeholder', [
                    'notification_id' => $this->notification->id,
                    'type' => $this->notification->type,
                    'email' => $candidate->email,
                ]);
            }
        }

        // Mark as delivered
        $this->notification->update(['delivered_at' => now()]);
    }

    /**
     * Handle permanent failure after all retries exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('SendCandidateNotificationJob: permanently failed', [
            'notification_id' => $this->notification->id,
            'type' => $this->notification->type,
            'candidate_id' => $this->notification->pool_candidate_id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
