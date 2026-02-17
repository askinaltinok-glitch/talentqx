<?php

namespace App\Services\Mail;

use App\Models\CrmEmailThread;
use App\Models\CrmOutboundQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReplyDraftService
{
    /**
     * Generate an AI reply draft for a thread and store it in the outbound queue.
     */
    public function generateDraft(CrmEmailThread $thread): ?CrmOutboundQueue
    {
        $thread->load(['messages' => function ($q) {
            $q->orderByDesc('created_at')->limit(5);
        }, 'lead.company', 'lead.contact']);

        $lead = $thread->lead;
        if (!$lead) {
            Log::warning('ReplyDraftService: No lead for thread', ['thread_id' => $thread->id]);
            return null;
        }

        // Resolve persona based on industry/mailbox
        $persona = $this->resolvePersona($thread, $lead);

        // Determine from/to
        $fromEmail = $persona['from_email'] ?? $this->resolveFromEmail($thread->mailbox);
        $lastInbound = $thread->messages->where('direction', 'inbound')->first();
        $toEmail = $lastInbound?->from_email ?? $lead->contact?->email;

        if (!$toEmail) {
            Log::warning('ReplyDraftService: No recipient email', ['thread_id' => $thread->id]);
            return null;
        }

        // Generate AI draft with persona
        $draftBody = $this->generateAiReply($thread, $lead, $persona);

        if (!$draftBody) {
            return null;
        }

        $subject = 'Re: ' . $thread->subject;

        return CrmOutboundQueue::create([
            'lead_id' => $lead->id,
            'email_thread_id' => $thread->id,
            'from_email' => $fromEmail,
            'to_email' => $toEmail,
            'subject' => $subject,
            'body_text' => $draftBody,
            'source' => CrmOutboundQueue::SOURCE_AI_REPLY,
            'status' => CrmOutboundQueue::STATUS_DRAFT,
        ]);
    }

    private function generateAiReply(CrmEmailThread $thread, $lead, array $persona = []): ?string
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            Log::warning('ReplyDraftService: No OpenAI API key');
            return null;
        }

        $systemPrompt = $this->buildSystemPrompt($thread, $persona);
        $context = $this->buildContext($thread, $lead);

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('crm_mail.ai.model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $context],
                    ],
                    'temperature' => config('crm_mail.ai.temperature', 0.7),
                    'max_tokens' => config('crm_mail.ai.max_tokens', 1000),
                ]);

            if (!$response->successful()) {
                Log::warning('ReplyDraftService: API call failed', ['status' => $response->status()]);
                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Exception $e) {
            Log::error('ReplyDraftService: Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function buildSystemPrompt(CrmEmailThread $thread, array $persona = []): string
    {
        $lang = $thread->lang_detected ?? config('crm_mail.default_language', 'en');
        $mailbox = $thread->mailbox;

        $langInstructions = match ($lang) {
            'tr' => 'Reply in Turkish (Türkçe). Use formal, professional Turkish.',
            'ru' => 'Reply in Russian (Русский). Use formal, professional Russian.',
            default => 'Reply in English. Use professional, warm business English.',
        };

        // Use persona if available, otherwise fall back to mailbox-based role
        if (!empty($persona['tone'])) {
            $personaName = $persona['name'] ?? 'TalentQX';
            $personaTitle = $persona['title'] ?? 'Team';
            $personaTone = $persona['tone'];
            $personaSignature = $persona['signature'] ?? "Best regards,\n{$personaName}";

            $roleInstructions = "You are writing as {$personaName} ({$personaTitle}). Your communication style: {$personaTone}.";
            $signOff = "Sign off with:\n{$personaSignature}";
        } else {
            $roleInstructions = match ($mailbox) {
                'crew' => 'You are a maritime recruitment specialist at TalentQX. You handle crew inquiries, seafarer applications, and certificate questions. Be knowledgeable about STCW, maritime ranks, and vessel types.',
                'companies' => 'You are a B2B business development representative at TalentQX. You handle company partnerships, pricing inquiries, and service proposals for maritime and HR companies.',
                default => 'You are a professional customer service representative at TalentQX, a maritime recruitment and HR technology company.',
            };
            $signOff = 'Sign off as "TalentQX Team" or appropriate for the mailbox';
        }

        // Build objection handling block if handlers exist for this industry
        $objectionBlock = '';
        $industry = $thread->industry_code ?? ($thread->lead?->industry_code ?? null);
        if ($industry) {
            $handlers = config("crm_mail.objection_handlers.{$industry}", []);
            if (!empty($handlers)) {
                $lines = ["Objection Handling Guidelines:"];
                foreach ($handlers as $objection => $response) {
                    $lines[] = "- If the prospect says \"{$objection}\": {$response}";
                }
                $objectionBlock = "\n\n" . implode("\n", $lines);
            }
        }

        return <<<PROMPT
{$roleInstructions}

{$langInstructions}

Guidelines:
- Be professional, helpful, and concise
- Keep emails short and sales-focused — no filler
- If the email is an application: acknowledge receipt, mention the profile will be reviewed
- If the email is an inquiry: provide helpful information and offer next steps
- If the email is a complaint: be empathetic, acknowledge the concern, propose resolution
- Do NOT make promises about specific timelines unless certain
- Do NOT include any [brackets] or placeholder text
- Write only the email body, no subject line or headers
- {$signOff}{$objectionBlock}
PROMPT;
    }

    private function buildContext(CrmEmailThread $thread, $lead): string
    {
        $parts = [];

        // Company context
        if ($lead->company) {
            $parts[] = "Company: {$lead->company->name}";
            if ($lead->company->industry_code) {
                $parts[] = "Industry: {$lead->company->industry_code}";
            }
        }

        // Lead context
        $parts[] = "Lead: {$lead->lead_name} (Stage: {$lead->stage})";

        if ($lead->contact) {
            $parts[] = "Contact: {$lead->contact->full_name}";
        }

        // Thread context
        $parts[] = "\n--- Conversation History ---";
        $messages = $thread->messages->sortBy('created_at');
        foreach ($messages as $msg) {
            $direction = $msg->direction === 'inbound' ? 'FROM THEM' : 'FROM US';
            $parts[] = "[{$direction}] Subject: {$msg->subject}";
            $parts[] = mb_substr($msg->body_text ?? '', 0, 1000);
            $parts[] = "---";
        }

        $parts[] = "\nPlease write a professional reply to the most recent inbound message.";

        return implode("\n", $parts);
    }

    private function resolveFromEmail(string $mailbox): string
    {
        $mailboxes = config('crm_mailboxes.mailboxes', []);
        return $mailboxes[$mailbox]['username'] ?? "info@talentqx.com";
    }

    /**
     * Resolve AI persona based on thread mailbox and lead industry.
     */
    private function resolvePersona(CrmEmailThread $thread, $lead): array
    {
        $industry = $lead->industry_code ?? 'general';
        $mailbox = $thread->mailbox;

        // Mailbox overrides: crew@ always uses crew_director
        if ($mailbox === 'crew') {
            $personaKey = 'crew_director';
        } else {
            $rules = config('crm_mail.persona_rules', []);
            $personaKey = $rules[$industry] ?? $rules['default'] ?? 'ceo';
        }

        $personas = config('crm_mail.personas', []);
        return $personas[$personaKey] ?? [
            'name' => 'TalentQX',
            'title' => 'Team',
            'from_email' => 'info@talentqx.com',
            'tone' => 'Professional, concise',
            'signature' => "Best regards,\nTalentQX Team",
        ];
    }
}
