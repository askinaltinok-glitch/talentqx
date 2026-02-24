<?php

namespace App\Jobs;

use App\Models\MessageOutbox;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class ProcessOutboxMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 30;

    protected int $batchSize;
    protected ?string $singleMessageId;

    /**
     * @param int|string $batchSizeOrMessageId If string (UUID), process single message. If int, batch size.
     */
    public function __construct(int|string $batchSizeOrMessageId = 50)
    {
        if (is_string($batchSizeOrMessageId) && strlen($batchSizeOrMessageId) > 10) {
            // UUID passed - process single message
            $this->singleMessageId = $batchSizeOrMessageId;
            $this->batchSize = 1;
        } else {
            $this->singleMessageId = null;
            $this->batchSize = (int) $batchSizeOrMessageId;
        }
        $this->captureBrand();
    }

    public function handle(): void
    {
        $this->setBrandDatabase();
        // Single message mode (immediate dispatch)
        if ($this->singleMessageId) {
            $message = MessageOutbox::find($this->singleMessageId);
            if ($message && $message->status === 'pending') {
                Log::info('Outbox: Processing single message', ['id' => $this->singleMessageId]);
                $this->processMessage($message);
            }
            return;
        }

        // Batch mode
        $messages = MessageOutbox::readyToSend()
            ->limit($this->batchSize)
            ->get();

        if ($messages->isEmpty()) {
            Log::debug('Outbox: No messages to process');
            return;
        }

        Log::info('Outbox: Processing batch', ['count' => $messages->count()]);

        foreach ($messages as $message) {
            $this->processMessage($message);
        }

        // Also process retryable failed messages
        $this->processRetryableMessages();
    }

    protected function processMessage(MessageOutbox $message): void
    {
        try {
            // Check safety mode BEFORE processing
            if ($this->isSafetyModeEnabled() && !$this->isRecipientWhitelisted($message->recipient)) {
                $this->blockMessage($message, 'safety_mode');
                return;
            }

            $message->markAsProcessing();

            // Route to appropriate sender based on channel
            $externalId = match ($message->channel) {
                MessageOutbox::CHANNEL_SMS => $this->sendSms($message),
                MessageOutbox::CHANNEL_EMAIL => $this->sendEmail($message),
                MessageOutbox::CHANNEL_WHATSAPP => $this->sendWhatsapp($message),
                MessageOutbox::CHANNEL_PUSH => $this->sendPush($message),
                default => throw new \InvalidArgumentException("Unknown channel: {$message->channel}"),
            };

            $message->markAsSent($externalId);

            Log::info('Outbox: Message sent', [
                'id' => $message->id,
                'channel' => $message->channel,
                'recipient' => $message->recipient,
                'external_id' => $externalId,
            ]);

        } catch (\Exception $e) {
            $message->markAsFailed($e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            Log::error('Outbox: Failed to send message', [
                'id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            if (!$message->canRetry()) {
                Log::critical('Outbox: Message permanently failed', [
                    'id' => $message->id,
                    'recipient' => $message->recipient,
                ]);
            }
        }
    }

    /**
     * Check if safety mode is enabled.
     */
    protected function isSafetyModeEnabled(): bool
    {
        return config('mail.safety_mode', false) === true;
    }

    /**
     * Check if recipient is in the whitelist.
     */
    protected function isRecipientWhitelisted(string $recipient): bool
    {
        $whitelist = config('mail.test_whitelist', []);

        return in_array(strtolower($recipient), array_map('strtolower', $whitelist));
    }

    /**
     * Block a message due to safety mode.
     */
    protected function blockMessage(MessageOutbox $message, string $reason): void
    {
        $message->update([
            'status' => 'blocked',
            'error_message' => "Blocked: {$reason}",
            'failed_at' => now(),
        ]);

        Log::warning('Outbox: Message blocked', [
            'id' => $message->id,
            'recipient' => $message->recipient,
            'reason' => $reason,
        ]);
    }

    protected function processRetryableMessages(): void
    {
        $retryable = MessageOutbox::retryable()
            ->where('failed_at', '<', now()->subMinutes(5))
            ->limit(10)
            ->get();

        foreach ($retryable as $message) {
            $message->resetForRetry();
            $this->processMessage($message);
        }
    }

    /**
     * Send SMS - placeholder implementation.
     */
    protected function sendSms(MessageOutbox $message): ?string
    {
        Log::info('SMS would be sent', [
            'to' => $message->recipient,
            'body' => $message->body,
        ]);

        return 'SMS_' . uniqid();
    }

    /**
     * Send Email via Laravel Mail with tenant branding.
     * NOTE: No reply-to header is set (noreply compatible).
     * Supports .ics and other attachments via metadata.
     */
    protected function sendEmail(MessageOutbox $message): ?string
    {
        // Load company for branding
        $company = null;
        if ($message->company_id) {
            $company = Company::find($message->company_id);
        }

        // Determine FROM name (tenant-branded)
        $fromName = $company
            ? $company->getEmailFromName()
            : config('mail.from.name', 'TalentQX');

        $fromAddress = config('mail.from.address', 'noreply@talentqx.com');

        // Extract attachments from metadata
        $attachments = $message->metadata['attachments'] ?? [];

        // NOTE: No reply-to header set per spec (noreply compatible)
        Mail::html($message->body, function (Message $mail) use ($message, $fromName, $fromAddress, $attachments) {
            $mail->to($message->recipient, $message->recipient_name)
                 ->subject($message->subject)
                 ->from($fromAddress, $fromName);

            // Add attachments (e.g., .ics calendar files)
            foreach ($attachments as $attachment) {
                $filename = $attachment['filename'] ?? 'attachment';
                $content = base64_decode($attachment['content'] ?? '');
                $mimeType = $attachment['mime_type'] ?? 'application/octet-stream';

                if ($content) {
                    $mail->attachData($content, $filename, ['mime' => $mimeType]);
                }
            }

            // No replyTo() - emails are noreply
        });

        Log::info('Email sent via SMTP', [
            'to' => $message->recipient,
            'subject' => $message->subject,
            'from_name' => $fromName,
            'from_address' => $fromAddress,
            'attachments' => count($attachments),
        ]);

        return 'SMTP_' . now()->format('Ymd_His') . '_' . substr(md5($message->id), 0, 8);
    }

    /**
     * Send WhatsApp - placeholder implementation.
     */
    protected function sendWhatsapp(MessageOutbox $message): ?string
    {
        Log::info('WhatsApp would be sent', [
            'to' => $message->recipient,
            'body' => $message->body,
        ]);

        return 'WA_' . uniqid();
    }

    /**
     * Send Push notification - placeholder implementation.
     */
    protected function sendPush(MessageOutbox $message): ?string
    {
        Log::info('Push notification would be sent', [
            'to' => $message->recipient,
            'body' => $message->body,
        ]);

        return 'PUSH_' . uniqid();
    }
}
