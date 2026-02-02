<?php

namespace App\Jobs;

use App\Models\MessageOutbox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOutboxMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 30;

    protected int $batchSize;

    public function __construct(int $batchSize = 50)
    {
        $this->batchSize = $batchSize;
    }

    public function handle(): void
    {
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

            // Re-throw if this is the last retry
            if (!$message->canRetry()) {
                // Could dispatch a notification to admins here
                Log::critical('Outbox: Message permanently failed', [
                    'id' => $message->id,
                    'recipient' => $message->recipient,
                ]);
            }
        }
    }

    protected function processRetryableMessages(): void
    {
        $retryable = MessageOutbox::retryable()
            ->where('failed_at', '<', now()->subMinutes(5)) // Wait 5 min between retries
            ->limit(10)
            ->get();

        foreach ($retryable as $message) {
            $message->resetForRetry();
            $this->processMessage($message);
        }
    }

    /**
     * Send SMS - placeholder implementation.
     * In production, integrate with SMS provider (Twilio, NetGSM, etc.)
     */
    protected function sendSms(MessageOutbox $message): ?string
    {
        // TODO: Implement actual SMS sending
        // For now, just log the message
        Log::info('SMS would be sent', [
            'to' => $message->recipient,
            'body' => $message->body,
        ]);

        // Return a fake external ID
        return 'SMS_' . uniqid();
    }

    /**
     * Send Email - placeholder implementation.
     * In production, use Laravel Mail or dedicated email service.
     */
    protected function sendEmail(MessageOutbox $message): ?string
    {
        // TODO: Implement actual email sending
        Log::info('Email would be sent', [
            'to' => $message->recipient,
            'subject' => $message->subject,
            'body' => $message->body,
        ]);

        return 'EMAIL_' . uniqid();
    }

    /**
     * Send WhatsApp - placeholder implementation.
     * In production, integrate with WhatsApp Business API.
     */
    protected function sendWhatsapp(MessageOutbox $message): ?string
    {
        // TODO: Implement actual WhatsApp sending
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
        // TODO: Implement push notification
        Log::info('Push notification would be sent', [
            'to' => $message->recipient,
            'body' => $message->body,
        ]);

        return 'PUSH_' . uniqid();
    }
}
