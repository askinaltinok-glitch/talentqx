<?php

namespace App\Jobs;

use App\Models\CrmActivity;
use App\Models\CrmEmailMessage;
use App\Models\CrmOutboundQueue;
use App\Services\Mail\MailProviderInterface;
use App\Services\Mail\SmtpMailProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class SendOutboundEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout = 60;

    public function __construct(
        public CrmOutboundQueue $queueItem
    ) {
        $this->captureBrand();
    }

    public function handle(): void
    {
        $this->setBrandDatabase();
        $item = $this->queueItem;

        if ($item->status !== CrmOutboundQueue::STATUS_APPROVED) {
            Log::info('SendOutboundEmailJob: Skipping non-approved item', ['id' => $item->id, 'status' => $item->status]);
            return;
        }

        $item->update(['status' => CrmOutboundQueue::STATUS_SENDING]);

        try {
            $provider = app(MailProviderInterface::class);

            $options = [];
            // If part of a thread, set In-Reply-To for threading
            if ($item->email_thread_id) {
                $lastMsg = CrmEmailMessage::where('email_thread_id', $item->email_thread_id)
                    ->where('direction', CrmEmailMessage::DIRECTION_INBOUND)
                    ->orderByDesc('created_at')
                    ->first();

                if ($lastMsg?->message_id) {
                    $options['in_reply_to'] = $lastMsg->message_id;
                }
            }

            $result = $provider->send(
                $item->from_email,
                $item->to_email,
                $item->subject,
                $item->body_text,
                $item->body_html,
                $options
            );

            // Create CrmEmailMessage record for the sent email
            $emailMsg = CrmEmailMessage::create([
                'lead_id' => $item->lead_id,
                'email_thread_id' => $item->email_thread_id,
                'direction' => CrmEmailMessage::DIRECTION_OUTBOUND,
                'provider' => 'smtp',
                'message_id' => $result['message_id'],
                'from_email' => $item->from_email,
                'to_email' => $item->to_email,
                'mailbox' => $item->thread?->mailbox,
                'subject' => $item->subject,
                'body_text' => $item->body_text,
                'body_html' => $item->body_html,
                'status' => CrmEmailMessage::STATUS_SENT,
                'sent_at' => now(),
            ]);

            // Update thread stats
            if ($item->email_thread_id && $item->thread) {
                $item->thread->updateStats();
            }

            // Log activity
            if ($item->lead) {
                $item->lead->addActivity(CrmActivity::TYPE_EMAIL_SENT, [
                    'subject' => $item->subject,
                    'to' => $item->to_email,
                    'source' => $item->source,
                    'email_record_id' => $emailMsg->id,
                ]);

                $item->lead->update(['last_contacted_at' => now()]);
            }

            $item->markSent();

            // Record SMTP success for circuit breaker
            \App\Models\SmtpCircuitBreaker::forKey('smtp')->recordSuccess();

            Log::info('SendOutboundEmailJob: Sent', ['id' => $item->id, 'to' => $item->to_email]);
        } catch (\Exception $e) {
            $item->markFailed($e->getMessage());

            // Record SMTP failure for circuit breaker
            $tripped = \App\Models\SmtpCircuitBreaker::forKey('smtp')->recordFailure();
            if ($tripped) {
                \App\Services\System\SystemEventService::alert('smtp_breaker_tripped', 'SendOutboundEmailJob', 'SMTP circuit breaker tripped after repeated failures', [
                    'queue_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::error('SendOutboundEmailJob: Failed', ['id' => $item->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
