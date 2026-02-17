<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmtpMailProvider implements MailProviderInterface
{
    public function send(
        string $from,
        string $to,
        string $subject,
        string $bodyText,
        ?string $bodyHtml = null,
        array $options = []
    ): array {
        $messageId = '<' . Str::uuid() . '@talentqx.com>';
        $fromName = $options['from_name'] ?? 'TalentQX';
        $inReplyTo = $options['in_reply_to'] ?? null;

        Mail::raw($bodyText, function ($message) use ($from, $to, $subject, $messageId, $fromName, $inReplyTo) {
            $message->from($from, $fromName)
                    ->to($to)
                    ->subject($subject);

            $headers = $message->getHeaders();
            if ($headers->has('Message-ID')) {
                $headers->remove('Message-ID');
            }
            $headers->addIdHeader('Message-ID', trim($messageId, '<>'));

            if ($inReplyTo) {
                $headers->addIdHeader('In-Reply-To', trim($inReplyTo, '<>'));
            }
        });

        Log::info('SmtpMailProvider: email sent', [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'message_id' => $messageId,
        ]);

        return [
            'message_id' => $messageId,
            'status' => 'sent',
        ];
    }
}
