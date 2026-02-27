<?php

namespace App\Services\Mail;

use App\Models\CrmActivity;
use App\Models\CrmAuditLog;
use App\Models\CrmDeal;
use App\Models\CrmEmailTemplate;
use App\Models\CrmLead;
use App\Models\CrmMailTrigger;
use App\Models\CrmOutboundQueue;
use App\Models\CrmSequence;
use App\Jobs\GenerateReplyDraftJob;
use Illuminate\Support\Facades\Log;

class MailTriggerService
{
    /**
     * Fire triggers for a given event. Context provides lead, industry, thread, etc.
     */
    public function fire(string $event, array $context): int
    {
        $triggers = CrmMailTrigger::active()
            ->forEvent($event)
            ->get();

        $fired = 0;

        foreach ($triggers as $trigger) {
            if ($this->evaluateConditions($trigger, $context)) {
                try {
                    $this->executeAction($trigger, $context);
                    $fired++;

                    CrmAuditLog::log('mail_trigger.fired', 'mail_trigger', $trigger->id, null, [
                        'event' => $event,
                        'trigger_name' => $trigger->name,
                        'lead_id' => $context['lead_id'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('MailTriggerService: Action failed', [
                        'trigger_id' => $trigger->id,
                        'event' => $event,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $fired;
    }

    /**
     * Evaluate trigger conditions against the event context.
     */
    public function evaluateConditions(CrmMailTrigger $trigger, array $context): bool
    {
        $conditions = $trigger->conditions ?? [];

        if (empty($conditions)) {
            return true; // No conditions = always match
        }

        // Check industry match
        if (!empty($conditions['industry'])) {
            $industry = $context['industry_code'] ?? $context['industry'] ?? null;
            if ($industry && $industry !== $conditions['industry']) {
                return false;
            }
        }

        // Check stage match
        if (!empty($conditions['stage'])) {
            $stage = $context['stage'] ?? null;
            if ($stage && $stage !== $conditions['stage']) {
                return false;
            }
        }

        // Check language match
        if (!empty($conditions['language'])) {
            $lang = $context['language'] ?? $context['preferred_language'] ?? null;
            if ($lang && $lang !== $conditions['language']) {
                return false;
            }
        }

        // Check intent match
        if (!empty($conditions['intent'])) {
            $intent = $context['intent'] ?? null;
            if ($intent !== $conditions['intent']) {
                return false;
            }
        }

        // Check days_stale (for no_reply triggers)
        if (!empty($conditions['days_stale'])) {
            $daysSince = $context['days_since_activity'] ?? 0;
            if ($daysSince < $conditions['days_stale']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute the trigger's action.
     */
    public function executeAction(CrmMailTrigger $trigger, array $context): void
    {
        $config = $trigger->action_config;
        $lead = isset($context['lead_id']) ? CrmLead::find($context['lead_id']) : null;

        if (!$lead && $trigger->action_type !== CrmMailTrigger::ACTION_GENERATE_AI_REPLY) {
            Log::warning('MailTriggerService: No lead for action', ['trigger_id' => $trigger->id]);
            return;
        }

        match ($trigger->action_type) {
            CrmMailTrigger::ACTION_ENROLL_SEQUENCE => $this->enrollInSequence($lead, $config),
            CrmMailTrigger::ACTION_SEND_TEMPLATE => $this->sendTemplate($lead, $config, $context),
            CrmMailTrigger::ACTION_GENERATE_AI_REPLY => $this->generateAiReply($context, $config),
            CrmMailTrigger::ACTION_ADVANCE_LEAD_STAGE => $this->advanceLeadStage($lead, $config),
            CrmMailTrigger::ACTION_CREATE_DEAL => $this->createDeal($lead, $config),
            CrmMailTrigger::ACTION_ADVANCE_DEAL_STAGE => $this->advanceDealStage($lead, $config),
            default => Log::warning('MailTriggerService: Unknown action type', ['type' => $trigger->action_type]),
        };
    }

    private function enrollInSequence(CrmLead $lead, array $config): void
    {
        $sequenceId = $config['sequence_id'] ?? null;

        // If no specific sequence, try to find one matching lead industry/language
        if (!$sequenceId) {
            $industry = $lead->industry_code ?? 'general';
            $language = $lead->preferred_language ?? config('crm_mail.default_language', 'en');

            $sequence = CrmSequence::active()
                ->where('industry_code', $industry)
                ->where('language', $language)
                ->first();

            if (!$sequence) {
                // Fallback to any active sequence for the industry
                $sequence = CrmSequence::active()
                    ->where('industry_code', $industry)
                    ->first();
            }

            $sequenceId = $sequence?->id;
        }

        if (!$sequenceId) {
            Log::info('MailTriggerService: No sequence found for enrollment', [
                'lead_id' => $lead->id,
                'industry' => $lead->industry_code,
            ]);
            return;
        }

        $service = app(SequenceService::class);
        $service->enroll($lead, CrmSequence::find($sequenceId));

        Log::info('MailTriggerService: Lead enrolled in sequence', [
            'lead_id' => $lead->id,
            'sequence_id' => $sequenceId,
        ]);
    }

    private function sendTemplate(CrmLead $lead, array $config, array $context): void
    {
        $templateKey = $config['template_key'] ?? null;
        if (!$templateKey) return;

        $industry = $lead->industry_code ?? 'general';
        $language = $lead->preferred_language ?? config('crm_mail.default_language', 'en');

        $template = CrmEmailTemplate::findTemplate($templateKey, $industry, $language);
        if (!$template) {
            Log::warning('MailTriggerService: Template not found', ['key' => $templateKey]);
            return;
        }

        $toEmail = $lead->contact?->email ?? $context['email'] ?? null;
        if (!$toEmail) return;

        // Resolve persona
        $persona = $this->resolvePersona($config['persona'] ?? null, $industry);

        $vars = [
            'contact_name' => $lead->contact?->full_name ?? $lead->lead_name,
            'company_name' => $lead->company?->name ?? '',
            'lead_name' => $lead->lead_name,
        ];

        CrmOutboundQueue::create([
            'lead_id' => $lead->id,
            'from_email' => $persona['from_email'],
            'to_email' => $toEmail,
            'subject' => $template->render($vars, 'subject'),
            'body_text' => $template->render($vars),
            'template_key' => $templateKey,
            'source' => CrmOutboundQueue::SOURCE_SEQUENCE,
            'status' => config('crm_mail.mode') === 'auto_send'
                ? CrmOutboundQueue::STATUS_APPROVED
                : CrmOutboundQueue::STATUS_DRAFT,
            'scheduled_at' => isset($config['delay_hours'])
                ? now()->addHours($config['delay_hours'])
                : null,
        ]);

        $lead->addActivity(CrmActivity::TYPE_SYSTEM, [
            'action' => 'mail_trigger_fired',
            'template_key' => $templateKey,
            'to_email' => $toEmail,
        ]);
    }

    private function generateAiReply(array $context, array $config): void
    {
        $thread = $context['thread'] ?? null;
        if (!$thread) return;

        GenerateReplyDraftJob::dispatch($thread);
    }

    private function advanceLeadStage(CrmLead $lead, array $config): void
    {
        $targetStage = $config['stage'] ?? null;
        if (!$targetStage) return;

        $fromStage = $lead->stage;
        $lead->update(['stage' => $targetStage]);

        $lead->addActivity(CrmActivity::TYPE_SYSTEM, [
            'action' => 'stage_advanced_by_trigger',
            'from_stage' => $fromStage,
            'to_stage' => $targetStage,
        ]);

        Log::info('MailTriggerService: Lead stage advanced', [
            'lead_id' => $lead->id,
            'from' => $fromStage,
            'to' => $targetStage,
        ]);
    }

    private function createDeal(CrmLead $lead, array $config): void
    {
        // Skip if lead already has an open deal
        $existingDeal = CrmDeal::where('lead_id', $lead->id)->open()->first();
        if ($existingDeal) {
            Log::info('MailTriggerService: Deal already exists, skipping', [
                'lead_id' => $lead->id,
                'deal_id' => $existingDeal->id,
            ]);
            return;
        }

        $industry = $lead->industry_code ?? 'general';
        $dealStage = $config['deal_stage'] ?? CrmDeal::initialStage($industry);

        $deal = CrmDeal::create([
            'lead_id' => $lead->id,
            'company_id' => $lead->company_id,
            'contact_id' => $lead->contact_id,
            'industry_code' => $industry,
            'deal_name' => ($lead->company?->name ?? $lead->lead_name) . ' — Inbound',
            'stage' => $dealStage,
            'probability' => CrmDeal::STAGE_PROBABILITIES[$dealStage] ?? 10,
            'currency' => 'USD',
        ]);

        $lead->addActivity(CrmActivity::TYPE_SYSTEM, [
            'action' => 'deal_created_by_trigger',
            'deal_id' => $deal->id,
            'deal_stage' => $dealStage,
        ]);

        Log::info('MailTriggerService: Deal created', [
            'lead_id' => $lead->id,
            'deal_id' => $deal->id,
            'stage' => $dealStage,
        ]);
    }

    private function advanceDealStage(CrmLead $lead, array $config): void
    {
        $targetStage = $config['deal_stage'] ?? null;
        if (!$targetStage) return;

        $industry = $lead->industry_code ?? 'general';

        // Find existing open deal or create one
        $deal = CrmDeal::where('lead_id', $lead->id)->open()->first();
        if (!$deal) {
            $deal = CrmDeal::create([
                'lead_id' => $lead->id,
                'company_id' => $lead->company_id,
                'contact_id' => $lead->contact_id,
                'industry_code' => $industry,
                'deal_name' => ($lead->company?->name ?? $lead->lead_name) . ' — Inbound',
                'stage' => CrmDeal::initialStage($industry),
                'probability' => CrmDeal::STAGE_PROBABILITIES[CrmDeal::initialStage($industry)] ?? 10,
                'currency' => 'USD',
            ]);
        }

        $deal->moveToStage($targetStage, 'mail_trigger');

        $lead->addActivity(CrmActivity::TYPE_SYSTEM, [
            'action' => 'deal_stage_advanced_by_trigger',
            'deal_id' => $deal->id,
            'deal_stage' => $targetStage,
        ]);

        Log::info('MailTriggerService: Deal stage advanced', [
            'lead_id' => $lead->id,
            'deal_id' => $deal->id,
            'stage' => $targetStage,
        ]);
    }

    /**
     * Resolve AI persona from config.
     */
    private function resolvePersona(?string $personaKey, string $industry): array
    {
        if (!$personaKey) {
            $rules = config('crm_mail.persona_rules', []);
            $personaKey = $rules[$industry] ?? $rules['default'] ?? 'ceo';
        }

        $personas = config('crm_mail.personas', []);
        return $personas[$personaKey] ?? [
            'name' => 'Octopus AI',
            'title' => 'Team',
            'from_email' => 'info@octopus-ai.net',
            'tone' => 'Professional, concise',
            'signature' => "Best regards,\nOctopus AI Team",
        ];
    }
}
