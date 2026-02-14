<?php

namespace App\Console\Commands;

use App\Models\CrmEmailMessage;
use App\Models\CrmLead;
use App\Models\CrmActivity;
use App\Models\CrmAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CrmMailboxPollCommand extends Command
{
    protected $signature = 'crm:mailbox-poll {--mailbox=all : Which mailbox to poll (crew, companies, info, all)}';
    protected $description = 'Poll IMAP mailboxes for inbound emails and match to CRM leads';

    public function handle(): int
    {
        $mailboxName = $this->option('mailbox');
        $config = config('crm_mailboxes');

        if (!$config) {
            $this->error('CRM mailbox config not found.');
            return 1;
        }

        $mailboxes = $config['mailboxes'] ?? [];

        if ($mailboxName !== 'all') {
            if (!isset($mailboxes[$mailboxName])) {
                $this->error("Mailbox '{$mailboxName}' not configured.");
                return 1;
            }
            $mailboxes = [$mailboxName => $mailboxes[$mailboxName]];
        }

        $totalProcessed = 0;
        $totalMatched = 0;

        foreach ($mailboxes as $name => $mbConfig) {
            if (empty($mbConfig['host']) || empty($mbConfig['password'])) {
                $this->warn("Mailbox '{$name}' not configured (missing host or password), skipping.");
                continue;
            }

            $this->info("Polling mailbox: {$name} ({$mbConfig['username']})");

            try {
                $result = $this->pollMailbox($name, $mbConfig, $config);
                $totalProcessed += $result['processed'];
                $totalMatched += $result['matched'];
                $this->info("  Processed: {$result['processed']}, Matched: {$result['matched']}");
            } catch (\Exception $e) {
                $this->error("  Error polling {$name}: {$e->getMessage()}");
                Log::error("CRM mailbox poll error", ['mailbox' => $name, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Total: processed={$totalProcessed}, matched={$totalMatched}");

        return 0;
    }

    private function pollMailbox(string $name, array $mbConfig, array $globalConfig): array
    {
        $host = $mbConfig['host'];
        $port = $mbConfig['port'];
        $encryption = $mbConfig['encryption'];
        $username = $mbConfig['username'];
        $password = $mbConfig['password'];
        $folder = $mbConfig['folder'] ?? 'INBOX';

        // Build IMAP connection string
        $flags = $encryption === 'ssl' ? '/imap/ssl/novalidate-cert' : '/imap';
        $connectionString = "{{$host}:{$port}{$flags}}{$folder}";

        $imap = @imap_open($connectionString, $username, $password);

        if (!$imap) {
            throw new \RuntimeException('IMAP connection failed: ' . imap_last_error());
        }

        $maxPerRun = $globalConfig['max_per_run'] ?? 50;
        $lookbackDays = $globalConfig['lookback_days'] ?? 7;

        // Search for recent unseen emails
        $since = date('d-M-Y', strtotime("-{$lookbackDays} days"));
        $emailIds = imap_search($imap, "SINCE {$since} UNSEEN");

        $processed = 0;
        $matched = 0;

        if ($emailIds) {
            $emailIds = array_slice($emailIds, 0, $maxPerRun);

            foreach ($emailIds as $emailId) {
                try {
                    $result = $this->processEmail($imap, $emailId, $name);
                    $processed++;
                    if ($result) {
                        $matched++;
                    }
                } catch (\Exception $e) {
                    Log::warning("CRM mailbox: error processing email", [
                        'mailbox' => $name,
                        'email_id' => $emailId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        imap_close($imap);

        return ['processed' => $processed, 'matched' => $matched];
    }

    private function processEmail($imap, int $emailId, string $mailboxName): bool
    {
        $header = imap_headerinfo($imap, $emailId);
        $structure = imap_fetchstructure($imap, $emailId);

        $fromEmail = $header->from[0]->mailbox . '@' . $header->from[0]->host;
        $toEmail = $header->to[0]->mailbox . '@' . $header->to[0]->host;
        $subject = $this->decodeHeader($header->subject ?? '');
        $messageId = trim($header->message_id ?? '', '<>');
        $inReplyTo = trim($header->in_reply_to ?? '', '<>');
        $references = $header->references ?? '';
        $date = date('Y-m-d H:i:s', strtotime($header->date ?? 'now'));

        // Extract body
        $bodyText = $this->getBody($imap, $emailId, $structure);

        // Try to match by In-Reply-To or References
        $matchedLead = null;

        if ($inReplyTo) {
            $outbound = CrmEmailMessage::where('message_id', '<' . $inReplyTo . '@talentqx.com>')
                ->orWhere('message_id', $inReplyTo)
                ->orWhere('message_id', '<' . $inReplyTo . '>')
                ->first();

            if ($outbound) {
                $matchedLead = $outbound->lead;
            }
        }

        // Fallback: match by from_email in existing lead contacts
        if (!$matchedLead) {
            $existingMsg = CrmEmailMessage::where('to_email', $fromEmail)
                ->whereNotNull('lead_id')
                ->orderByDesc('created_at')
                ->first();

            if ($existingMsg) {
                $matchedLead = $existingMsg->lead;
            }
        }

        if (!$matchedLead) {
            return false; // No matching lead found
        }

        // Store inbound message
        $emailRecord = CrmEmailMessage::create([
            'lead_id' => $matchedLead->id,
            'direction' => CrmEmailMessage::DIRECTION_INBOUND,
            'provider' => 'imap',
            'message_id' => $messageId ? "<{$messageId}>" : null,
            'thread_id' => $inReplyTo ? "<{$inReplyTo}>" : null,
            'in_reply_to' => $inReplyTo ? "<{$inReplyTo}>" : null,
            'from_email' => $fromEmail,
            'to_email' => $toEmail,
            'subject' => $subject,
            'body_text' => $bodyText,
            'raw_headers' => [
                'message_id' => $messageId,
                'in_reply_to' => $inReplyTo,
                'references' => $references,
                'date' => $date,
                'mailbox' => $mailboxName,
            ],
            'status' => CrmEmailMessage::STATUS_DELIVERED,
            'received_at' => $date,
        ]);

        // Log activity
        $matchedLead->addActivity(CrmActivity::TYPE_EMAIL_REPLY, [
            'subject' => $subject,
            'from' => $fromEmail,
            'snippet' => mb_substr($bodyText, 0, 200),
            'message_id' => $messageId,
            'email_record_id' => $emailRecord->id,
        ]);

        // Mark previous outbound as replied
        if ($inReplyTo) {
            CrmEmailMessage::where('message_id', 'like', "%{$inReplyTo}%")
                ->where('direction', CrmEmailMessage::DIRECTION_OUTBOUND)
                ->update(['status' => CrmEmailMessage::STATUS_REPLIED]);
        }

        CrmAuditLog::log('email.inbound_matched', 'email', $emailRecord->id, null, [
            'lead_id' => $matchedLead->id,
            'from' => $fromEmail,
            'subject' => $subject,
        ]);

        Log::info("CRM: inbound email matched", [
            'lead_id' => $matchedLead->id,
            'from' => $fromEmail,
            'mailbox' => $mailboxName,
        ]);

        // Mark email as seen
        imap_setflag_full($imap, (string) $emailId, '\\Seen');

        return true;
    }

    private function decodeHeader(string $header): string
    {
        $decoded = imap_mime_header_decode($header);
        $result = '';
        foreach ($decoded as $part) {
            $result .= $part->text;
        }
        return $result;
    }

    private function getBody($imap, int $emailId, $structure): string
    {
        // Simple text/plain extraction
        if ($structure->type === 0) { // text
            $body = imap_fetchbody($imap, $emailId, '1');
            if ($structure->encoding === 3) { // base64
                $body = base64_decode($body);
            } elseif ($structure->encoding === 4) { // quoted-printable
                $body = quoted_printable_decode($body);
            }
            return mb_substr($body, 0, 10000);
        }

        // Multipart — find text/plain part
        if ($structure->type === 1 && isset($structure->parts)) {
            foreach ($structure->parts as $i => $part) {
                if ($part->subtype === 'PLAIN') {
                    $body = imap_fetchbody($imap, $emailId, (string) ($i + 1));
                    if ($part->encoding === 3) {
                        $body = base64_decode($body);
                    } elseif ($part->encoding === 4) {
                        $body = quoted_printable_decode($body);
                    }
                    return mb_substr($body, 0, 10000);
                }
            }
        }

        return '';
    }
}
