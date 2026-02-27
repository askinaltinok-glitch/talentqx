<?php

namespace App\Services\Mail;

use App\Models\CrmActivity;
use App\Models\CrmAuditLog;
use App\Models\CrmCompany;
use App\Models\CrmContact;
use App\Models\CrmEmailMessage;
use App\Models\CrmEmailThread;
use App\Models\CrmLead;
use App\Jobs\GenerateReplyDraftJob;
use App\Services\Research\ResearchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InboundEmailService
{
    /**
     * Process an inbound email (from IMAP poll or webhook).
     * Handles threading, lead matching, storage, and dispatches classification + reply draft.
     */
    public function process(array $emailData): CrmEmailMessage
    {
        $fromEmail = $emailData['from_email'];
        $fromName = $emailData['from_name'] ?? null;
        $toEmail = $emailData['to_email'];
        $subject = $emailData['subject'] ?? '(no subject)';
        $bodyText = $emailData['body_text'] ?? '';
        $messageId = $emailData['message_id'] ?? null;
        $inReplyTo = $emailData['in_reply_to'] ?? null;
        $references = $emailData['references'] ?? null;
        $date = $emailData['date'] ?? now()->toDateTimeString();
        $mailbox = $emailData['mailbox'] ?? $this->detectMailbox($toEmail);

        // 1. Find or create thread
        $thread = CrmEmailThread::findOrCreateFromMessage($emailData, $mailbox);

        // 2. Match lead (by In-Reply-To, then by from_email in existing messages)
        $lead = $this->matchLead($fromEmail, $inReplyTo);

        // 3. If no lead: create company + lead from sender domain
        if (!$lead) {
            $lead = $this->createLeadFromSender($fromEmail, $fromName, $subject, $mailbox);
        }

        // Link thread to lead if not already linked
        if ($lead && !$thread->lead_id) {
            $thread->update(['lead_id' => $lead->id]);
        }

        // 4. Store CrmEmailMessage
        $emailRecord = CrmEmailMessage::create([
            'lead_id' => $lead?->id,
            'email_thread_id' => $thread->id,
            'direction' => CrmEmailMessage::DIRECTION_INBOUND,
            'provider' => $emailData['provider'] ?? 'imap',
            'message_id' => $messageId ? "<{$messageId}>" : null,
            'thread_id' => $inReplyTo ? "<{$inReplyTo}>" : null,
            'in_reply_to' => $inReplyTo ? "<{$inReplyTo}>" : null,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'to_email' => $toEmail,
            'mailbox' => $mailbox,
            'subject' => $subject,
            'body_text' => mb_substr($bodyText, 0, 10000),
            'raw_headers' => [
                'message_id' => $messageId,
                'in_reply_to' => $inReplyTo,
                'references' => $references,
                'date' => $date,
                'mailbox' => $mailbox,
            ],
            'status' => CrmEmailMessage::STATUS_DELIVERED,
            'received_at' => $date,
        ]);

        // Update thread stats
        $thread->updateStats();

        // 5. Classify email (language, intent, industry) — synchronous for now
        $this->classifyAndUpdateThread($emailRecord, $thread);

        // 6. Log activity on lead
        if ($lead) {
            $lead->addActivity(CrmActivity::TYPE_EMAIL_REPLY, [
                'subject' => $subject,
                'from' => $fromEmail,
                'snippet' => mb_substr($bodyText, 0, 200),
                'message_id' => $messageId,
                'email_record_id' => $emailRecord->id,
                'thread_id' => $thread->id,
            ]);

            // Mark outbound as replied
            if ($inReplyTo) {
                CrmEmailMessage::where('message_id', 'like', "%{$inReplyTo}%")
                    ->where('direction', CrmEmailMessage::DIRECTION_OUTBOUND)
                    ->update(['status' => CrmEmailMessage::STATUS_REPLIED]);
            }

            // 7. Fire reply_received triggers for automation
            try {
                app(MailTriggerService::class)->fire('reply_received', [
                    'lead_id' => $lead->id,
                    'industry_code' => $lead->industry_code,
                    'stage' => $lead->stage,
                    'intent' => $thread->intent,
                    'language' => $thread->lang_detected,
                    'thread' => $thread,
                    'email' => $lead->contact?->email ?? $fromEmail,
                ]);
            } catch (\Exception $e) {
                Log::warning('InboundEmailService: Trigger fire failed', ['error' => $e->getMessage()]);
            }

            // 8. Dispatch reply draft generation
            GenerateReplyDraftJob::dispatch($thread);
        }

        // 8. If unmatched domain: enrich via ResearchService
        if (!$lead) {
            $this->enrichUnmatchedDomain($fromEmail, $subject, $mailbox);
        }

        CrmAuditLog::log('email.inbound_processed', 'email', $emailRecord->id, null, [
            'lead_id' => $lead?->id,
            'thread_id' => $thread->id,
            'from' => $fromEmail,
            'subject' => $subject,
        ]);

        return $emailRecord;
    }

    /**
     * Classify an email using GPT-4o-mini for language, intent, and industry.
     */
    public function classifyEmail(CrmEmailMessage $message): array
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            Log::warning('InboundEmailService: No OpenAI API key, using heuristic fallback');
            return $this->fallbackClassification($message);
        }

        $input = "Subject: {$message->subject}\n\nBody:\n" . mb_substr($message->body_text ?? '', 0, 2000);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('crm_mail.ai.model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $this->classificationSystemPrompt()],
                        ['role' => 'user', 'content' => $input],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 300,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                Log::warning('InboundEmailService: Classification API failed', ['status' => $response->status()]);
                return $this->fallbackClassification($message);
            }

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode($content, true);

            if (!$parsed || !isset($parsed['language'])) {
                return $this->fallbackClassification($message);
            }

            return [
                'language' => in_array($parsed['language'], ['en', 'tr', 'ru']) ? $parsed['language'] : 'en',
                'intent' => $parsed['intent'] ?? 'other',
                'industry' => in_array($parsed['industry'] ?? '', ['general', 'maritime']) ? $parsed['industry'] : 'general',
                'confidence' => min(100, max(0, (int) ($parsed['confidence'] ?? 50))),
                'reasoning' => $parsed['reasoning'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('InboundEmailService: Classification exception', ['error' => $e->getMessage()]);
            return $this->fallbackClassification($message);
        }
    }

    /**
     * Strip Re:/Fwd:/FW: prefixes from subject.
     */
    public static function normalizeSubject(string $subject): string
    {
        return CrmEmailThread::normalizeSubject($subject);
    }

    // --- Private helpers ---

    private function matchLead(string $fromEmail, ?string $inReplyTo): ?CrmLead
    {
        // Try by In-Reply-To header
        if ($inReplyTo) {
            $outbound = CrmEmailMessage::where('message_id', '<' . $inReplyTo . '@octopus-ai.net>')
                ->orWhere('message_id', '<' . $inReplyTo . '@talentqx.com>')
                ->orWhere('message_id', $inReplyTo)
                ->orWhere('message_id', '<' . $inReplyTo . '>')
                ->first();

            if ($outbound && $outbound->lead) {
                return $outbound->lead;
            }
        }

        // Fallback: by from_email in existing messages
        $existingMsg = CrmEmailMessage::where('to_email', $fromEmail)
            ->whereNotNull('lead_id')
            ->orderByDesc('created_at')
            ->first();

        if ($existingMsg) {
            return $existingMsg->lead;
        }

        // Try matching via CrmContact email
        $contact = CrmContact::where('email', $fromEmail)->first();
        if ($contact) {
            $lead = CrmLead::where('contact_id', $contact->id)->first();
            if ($lead) return $lead;
        }

        return null;
    }

    private function createLeadFromSender(string $fromEmail, ?string $fromName, string $subject, string $mailbox): ?CrmLead
    {
        $domain = substr($fromEmail, strpos($fromEmail, '@') + 1);
        $freeProviders = config('crm_mail.free_email_providers', []);

        if (in_array(strtolower($domain), $freeProviders)) {
            // For free-provider emails, create lead without company
            $leadName = $fromName ?: $fromEmail;
            return CrmLead::create([
                'industry_code' => $mailbox === 'crew' ? 'maritime' : 'general',
                'source_channel' => CrmLead::SOURCE_INBOUND_EMAIL,
                'source_meta' => ['from_email' => $fromEmail, 'subject' => $subject, 'mailbox' => $mailbox],
                'lead_name' => $leadName,
                'stage' => CrmLead::STAGE_NEW,
                'priority' => CrmLead::PRIORITY_MED,
            ]);
        }

        // Find or create company from domain
        $company = CrmCompany::findByDomain($domain);
        if (!$company) {
            $company = CrmCompany::create([
                'industry_code' => $mailbox === 'crew' ? 'maritime' : 'general',
                'name' => ucfirst(explode('.', $domain)[0]),
                'domain' => $domain,
                'website' => "https://{$domain}",
                'country_code' => '--',
                'status' => CrmCompany::STATUS_NEW,
            ]);
        }

        // Create contact if not exists
        $contact = CrmContact::where('email', $fromEmail)->first();
        if (!$contact) {
            $contact = CrmContact::create([
                'company_id' => $company->id,
                'full_name' => $fromName ?: explode('@', $fromEmail)[0],
                'email' => $fromEmail,
            ]);
        }

        $leadName = $fromName ?: $company->name;
        return CrmLead::create([
            'industry_code' => $company->industry_code,
            'source_channel' => CrmLead::SOURCE_INBOUND_EMAIL,
            'source_meta' => ['from_email' => $fromEmail, 'subject' => $subject, 'mailbox' => $mailbox, 'domain' => $domain],
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'lead_name' => $leadName,
            'stage' => CrmLead::STAGE_NEW,
            'priority' => CrmLead::PRIORITY_MED,
        ]);
    }

    private function detectMailbox(string $toEmail): string
    {
        $local = strtolower(explode('@', $toEmail)[0]);
        if (in_array($local, ['crew', 'companies', 'info'])) {
            return $local;
        }
        return 'info';
    }

    private function classifyAndUpdateThread(CrmEmailMessage $message, CrmEmailThread $thread): void
    {
        try {
            $classification = $this->classifyEmail($message);

            $message->update([
                'lang_detected' => $classification['language'],
                'intent' => $classification['intent'],
            ]);

            $thread->update([
                'lang_detected' => $classification['language'],
                'intent' => $classification['intent'],
                'industry_code' => $classification['industry'],
                'classification' => $classification,
            ]);

            // Update lead preferred language if not set
            if ($thread->lead && !$thread->lead->preferred_language) {
                $thread->lead->update(['preferred_language' => $classification['language']]);
            }
        } catch (\Exception $e) {
            Log::warning('InboundEmailService: Classification failed', ['error' => $e->getMessage()]);
        }
    }

    private function enrichUnmatchedDomain(string $fromEmail, string $subject, string $mailbox): void
    {
        try {
            $domain = substr($fromEmail, strpos($fromEmail, '@') + 1);
            $freeProviders = config('crm_mail.free_email_providers', []);

            if (in_array(strtolower($domain), $freeProviders)) {
                return;
            }

            $service = app(ResearchService::class);
            $service->enrichFromDomain($domain, [
                'source' => 'inbound_email',
                'from_email' => $fromEmail,
                'subject' => $subject,
                'mailbox' => $mailbox,
                'date' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::warning('InboundEmailService: Domain enrichment failed', [
                'from' => $fromEmail,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function classificationSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an email classification engine for Octopus AI, a maritime recruitment and HR tech company. Given an email subject and body, output STRICT JSON with these fields:
{
  "language": "en" | "tr" | "ru",
  "intent": "inquiry" | "application" | "complaint" | "info_request" | "follow_up" | "spam" | "other",
  "industry": "general" | "maritime",
  "confidence": integer 0-100,
  "reasoning": string (1 sentence)
}

Language detection rules:
- Turkish: Look for characters like ş, ç, ğ, ı, ö, ü and Turkish words
- Russian: Look for Cyrillic characters (а-я, А-Я)
- English: Default if neither Turkish nor Russian detected

Industry detection rules:
- Maritime: mentions of ships, vessels, fleet, crew, seafarer, IMO, STCW, manning, shipping, port, marine
- General: everything else

Intent detection rules:
- inquiry: asking about services, pricing, partnerships
- application: job application, CV submission, crew availability
- complaint: expressing dissatisfaction, issues
- info_request: requesting specific information or documents
- follow_up: responding to previous communication
- spam: promotional, unsolicited, automated
- other: doesn't fit above categories
PROMPT;
    }

    private function fallbackClassification(CrmEmailMessage $message): array
    {
        $text = strtolower(($message->subject ?? '') . ' ' . ($message->body_text ?? ''));

        // Language detection by character analysis
        $language = 'en';
        if (preg_match('/[şçğıöü]/u', $text)) {
            $language = 'tr';
        } elseif (preg_match('/[\p{Cyrillic}]/u', $text)) {
            $language = 'ru';
        }

        // Industry detection
        $maritimeKeywords = ['ship', 'maritime', 'marine', 'vessel', 'crew', 'manning', 'offshore', 'tanker', 'bulk', 'cargo', 'seafar', 'fleet', 'stcw', 'imo'];
        $industry = 'general';
        foreach ($maritimeKeywords as $kw) {
            if (str_contains($text, $kw)) {
                $industry = 'maritime';
                break;
            }
        }

        // Intent detection
        $intent = 'other';
        if (preg_match('/\b(cv|resume|apply|application|position|job|vacancy)\b/i', $text)) {
            $intent = 'application';
        } elseif (preg_match('/\b(price|pricing|cost|quote|proposal|partnership|collaborate)\b/i', $text)) {
            $intent = 'inquiry';
        } elseif (preg_match('/\b(complaint|unhappy|dissatisfied|issue|problem)\b/i', $text)) {
            $intent = 'complaint';
        } elseif (preg_match('/\b(information|details|brochure|document|certificate)\b/i', $text)) {
            $intent = 'info_request';
        }

        return [
            'language' => $language,
            'intent' => $intent,
            'industry' => $industry,
            'confidence' => 30,
            'reasoning' => 'Keyword-based fallback classification (no AI available)',
        ];
    }
}
