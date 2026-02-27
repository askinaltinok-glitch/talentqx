<?php

namespace App\Services\Outbox;

use App\Models\MessageOutbox;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Log;

class OutboxService
{
    /**
     * Create an outbox message for sending.
     */
    public function queue(array $params): MessageOutbox
    {
        $companyId = $params['company_id']
            ?? (app()->bound('current_tenant_id') ? app('current_tenant_id') : null);

        $message = MessageOutbox::create([
            'company_id' => $companyId,
            'channel' => $params['channel'],
            'recipient' => $params['recipient'],
            'recipient_name' => $params['recipient_name'] ?? null,
            'subject' => $params['subject'] ?? null,
            'body' => $params['body'],
            'template_data' => $params['template_data'] ?? null,
            'template_id' => $params['template_id'] ?? null,
            'related_type' => $params['related_type'] ?? null,
            'related_id' => $params['related_id'] ?? null,
            'scheduled_at' => $params['scheduled_at'] ?? null,
            'priority' => $params['priority'] ?? 0,
            'max_retries' => $params['max_retries'] ?? 3,
            'metadata' => $params['metadata'] ?? null,
        ]);

        Log::info('Outbox message queued', [
            'id' => $message->id,
            'channel' => $message->channel,
            'recipient' => $message->recipient,
        ]);

        return $message;
    }

    /**
     * Queue a message using a template.
     */
    public function queueFromTemplate(
        string $templateCode,
        string $channel,
        string $recipient,
        array $templateData,
        array $extraParams = []
    ): MessageOutbox {
        $companyId = $extraParams['company_id']
            ?? (app()->bound('current_tenant_id') ? app('current_tenant_id') : null);

        $template = MessageTemplate::findForTenant(
            $templateCode,
            $channel,
            $companyId,
            $extraParams['locale'] ?? 'tr'
        );

        if (!$template) {
            throw new \InvalidArgumentException("Template not found: {$templateCode} for channel {$channel}");
        }

        $rendered = $template->render($templateData);

        return $this->queue([
            'company_id' => $companyId,
            'channel' => $channel,
            'recipient' => $recipient,
            'recipient_name' => $extraParams['recipient_name'] ?? null,
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
            'template_data' => $templateData,
            'template_id' => $template->id,
            'related_type' => $extraParams['related_type'] ?? null,
            'related_id' => $extraParams['related_id'] ?? null,
            'scheduled_at' => $extraParams['scheduled_at'] ?? null,
            'priority' => $extraParams['priority'] ?? 0,
            'metadata' => $extraParams['metadata'] ?? null,
        ]);
    }

    /**
     * Queue interview invitation message.
     */
    public function queueInterviewInvitation(
        string $companyId,
        string $candidateId,
        string $phone,
        string $candidateName,
        string $jobTitle,
        string $interviewUrl,
        ?string $scheduledAt = null
    ): MessageOutbox {
        return $this->queueFromTemplate(
            'interview_invitation',
            MessageOutbox::CHANNEL_SMS,
            $phone,
            [
                'candidate_name' => $candidateName,
                'job_title' => $jobTitle,
                'interview_url' => $interviewUrl,
            ],
            [
                'company_id' => $companyId,
                'recipient_name' => $candidateName,
                'related_type' => 'candidate',
                'related_id' => $candidateId,
                'scheduled_at' => $scheduledAt,
                'priority' => 10, // High priority for invitations
            ]
        );
    }

    /**
     * Queue application received confirmation.
     */
    public function queueApplicationReceived(
        string $companyId,
        string $candidateId,
        string $phone,
        string $candidateName,
        string $jobTitle
    ): MessageOutbox {
        return $this->queueFromTemplate(
            'application_received',
            MessageOutbox::CHANNEL_SMS,
            $phone,
            [
                'candidate_name' => $candidateName,
                'job_title' => $jobTitle,
            ],
            [
                'company_id' => $companyId,
                'recipient_name' => $candidateName,
                'related_type' => 'candidate',
                'related_id' => $candidateId,
            ]
        );
    }
}
