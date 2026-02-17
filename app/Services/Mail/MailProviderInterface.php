<?php

namespace App\Services\Mail;

interface MailProviderInterface
{
    /**
     * Send an email via the configured provider.
     *
     * @return array{message_id: string, status: string}
     */
    public function send(
        string $from,
        string $to,
        string $subject,
        string $bodyText,
        ?string $bodyHtml = null,
        array $options = []
    ): array;
}
