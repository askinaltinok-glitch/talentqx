<?php

namespace App\Services\Mail;

use App\Models\CrmEmailMessage;
use App\Models\CrmEmailTemplate;
use App\Models\CrmLead;
use App\Models\CrmOutboundQueue;
use App\Models\CrmSequence;
use App\Models\CrmSequenceEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SequenceService
{
    /**
     * Enroll a lead in a sequence.
     */
    public function enroll(CrmLead $lead, CrmSequence $sequence): CrmSequenceEnrollment
    {
        // Check if already enrolled
        $existing = CrmSequenceEnrollment::where('lead_id', $lead->id)
            ->where('sequence_id', $sequence->id)
            ->whereIn('status', [CrmSequenceEnrollment::STATUS_ACTIVE, CrmSequenceEnrollment::STATUS_PAUSED])
            ->first();

        if ($existing) {
            return $existing;
        }

        $steps = $sequence->steps ?? [];
        $firstDelay = $steps[0]['delay_days'] ?? 0;

        return CrmSequenceEnrollment::create([
            'lead_id' => $lead->id,
            'sequence_id' => $sequence->id,
            'current_step' => 0,
            'status' => CrmSequenceEnrollment::STATUS_ACTIVE,
            'next_step_at' => now()->addDays($firstDelay),
            'started_at' => now(),
        ]);
    }

    /**
     * Process all due sequence steps. Returns count of steps processed.
     */
    public function processDue(): int
    {
        $enrollments = CrmSequenceEnrollment::dueNow()
            ->with(['lead', 'sequence'])
            ->limit(100)
            ->get();

        $processed = 0;

        foreach ($enrollments as $enrollment) {
            try {
                $this->executeStep($enrollment);
                $processed++;
            } catch (\Exception $e) {
                Log::error('SequenceService: Step execution failed', [
                    'enrollment_id' => $enrollment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Execute the current step for an enrollment.
     */
    public function executeStep(CrmSequenceEnrollment $enrollment): void
    {
        $sequence = $enrollment->sequence;
        $lead = $enrollment->lead;
        $steps = $sequence->steps ?? [];
        $currentStep = $enrollment->current_step;

        if ($currentStep >= count($steps)) {
            $enrollment->update([
                'status' => CrmSequenceEnrollment::STATUS_COMPLETED,
                'completed_at' => now(),
                'next_step_at' => null,
            ]);
            return;
        }

        $step = $steps[$currentStep];
        $templateKey = $step['template_key'] ?? null;

        if (!$templateKey) {
            Log::warning('SequenceService: No template_key in step', [
                'enrollment_id' => $enrollment->id,
                'step' => $currentStep,
            ]);
            $enrollment->advanceStep();
            return;
        }

        // Respect quiet hours
        if ($this->isQuietHours()) {
            Log::info('SequenceService: Quiet hours, deferring step', ['enrollment_id' => $enrollment->id]);
            return;
        }

        // Enforce per-lead daily send limit
        $maxPerDay = config('crm_mail.auto_send_rules.max_per_lead_per_day', 2);
        $todaySent = CrmOutboundQueue::where('lead_id', $lead->id)
            ->whereIn('status', [
                CrmOutboundQueue::STATUS_APPROVED,
                CrmOutboundQueue::STATUS_SENT ?? 'sent',
                'sending',
            ])
            ->whereDate('created_at', today())
            ->count();

        if ($todaySent >= $maxPerDay) {
            Log::info('SequenceService: Daily limit reached, deferring step', [
                'enrollment_id' => $enrollment->id,
                'lead_id' => $lead->id,
                'today_sent' => $todaySent,
                'max_per_day' => $maxPerDay,
            ]);
            return;
        }

        // Resolve template
        $template = CrmEmailTemplate::findTemplate($templateKey, $sequence->industry_code, $sequence->language);
        if (!$template) {
            Log::warning('SequenceService: Template not found', [
                'key' => $templateKey,
                'industry' => $sequence->industry_code,
                'language' => $sequence->language,
            ]);
            $enrollment->advanceStep();
            return;
        }

        // Resolve from/to
        $fromEmail = $this->resolveFromEmail($lead);
        $toEmail = $lead->contact?->email ?? null;

        if (!$toEmail) {
            Log::warning('SequenceService: No recipient email for lead', ['lead_id' => $lead->id]);
            $enrollment->cancel();
            return;
        }

        // Look up last sent outbound subject for {previous_subject}
        $previousSubject = CrmOutboundQueue::where('lead_id', $lead->id)
            ->where('status', CrmOutboundQueue::STATUS_SENT ?? 'sent')
            ->orderByDesc('sent_at')
            ->value('subject');
        if (!$previousSubject) {
            // Fallback to last outbound email message
            $previousSubject = CrmEmailMessage::where('lead_id', $lead->id)
                ->where('direction', CrmEmailMessage::DIRECTION_OUTBOUND)
                ->orderByDesc('created_at')
                ->value('subject');
        }

        // Render template
        $vars = [
            'contact_name' => $lead->contact?->full_name ?? 'there',
            'company_name' => $lead->company?->name ?? $lead->lead_name,
            'lead_name' => $lead->lead_name,
            'previous_subject' => $previousSubject ?? $template->subject,
        ];
        $rendered = $template->render($vars);

        // Template cooldown: skip if same template_key + lead_id sent within 48h
        $cooldownHours = config('crm_mail.template_cooldown_hours', 48);
        $recentlySent = CrmOutboundQueue::where('lead_id', $lead->id)
            ->where('template_key', $templateKey)
            ->whereIn('status', ['sent', 'sending', 'approved'])
            ->where('created_at', '>=', now()->subHours($cooldownHours))
            ->exists();

        if ($recentlySent) {
            Log::info('SequenceService: Template cooldown active, deferring step', [
                'enrollment_id' => $enrollment->id,
                'template_key' => $templateKey,
                'cooldown_hours' => $cooldownHours,
            ]);
            return;
        }

        // Create outbound queue item
        CrmOutboundQueue::create([
            'lead_id' => $lead->id,
            'from_email' => $fromEmail,
            'to_email' => $toEmail,
            'subject' => $rendered['subject'],
            'body_text' => $rendered['body_text'],
            'body_html' => $rendered['body_html'],
            'template_key' => $templateKey,
            'source' => CrmOutboundQueue::SOURCE_SEQUENCE,
            'status' => config('crm_mail.mode') === 'auto_send'
                ? CrmOutboundQueue::STATUS_APPROVED
                : CrmOutboundQueue::STATUS_DRAFT,
        ]);

        // Advance to next step
        $enrollment->advanceStep();

        Log::info('SequenceService: Step executed', [
            'enrollment_id' => $enrollment->id,
            'step' => $currentStep,
            'template' => $templateKey,
        ]);
    }

    public function cancel(CrmSequenceEnrollment $enrollment): void
    {
        $enrollment->cancel();
    }

    public function pause(CrmSequenceEnrollment $enrollment): void
    {
        $enrollment->pause();
    }

    public function resume(CrmSequenceEnrollment $enrollment): void
    {
        $enrollment->resume();
    }

    private function isQuietHours(): bool
    {
        $config = config('crm_mail.quiet_hours');
        if (!($config['enabled'] ?? false)) {
            return false;
        }

        $tz = $config['timezone'] ?? 'UTC';
        $now = Carbon::now($tz);
        $start = Carbon::parse($config['start'], $tz);
        $end = Carbon::parse($config['end'], $tz);

        // Handle overnight quiet hours (e.g., 22:00 - 08:00)
        if ($start->gt($end)) {
            return $now->gte($start) || $now->lt($end);
        }

        return $now->gte($start) && $now->lt($end);
    }

    private function resolveFromEmail(CrmLead $lead): string
    {
        $mailboxes = config('crm_mailboxes.mailboxes', []);

        if ($lead->industry_code === 'maritime') {
            return $mailboxes['crew']['username'] ?? 'crew@octopus-ai.net';
        }

        return $mailboxes['companies']['username'] ?? 'companies@octopus-ai.net';
    }
}
