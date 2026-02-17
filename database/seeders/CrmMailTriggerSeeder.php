<?php

namespace Database\Seeders;

use App\Models\CrmMailTrigger;
use Illuminate\Database\Seeder;

class CrmMailTriggerSeeder extends Seeder
{
    public function run(): void
    {
        $triggers = [
            // New maritime company → enroll in welcome sequence
            [
                'name' => 'Maritime Welcome Sequence',
                'trigger_event' => CrmMailTrigger::EVENT_NEW_COMPANY,
                'conditions' => ['industry' => 'maritime'],
                'action_type' => CrmMailTrigger::ACTION_ENROLL_SEQUENCE,
                'action_config' => ['persona' => 'crew_director'],
            ],
            // New general company → enroll in welcome sequence
            [
                'name' => 'General Welcome Sequence',
                'trigger_event' => CrmMailTrigger::EVENT_NEW_COMPANY,
                'conditions' => ['industry' => 'general'],
                'action_type' => CrmMailTrigger::ACTION_ENROLL_SEQUENCE,
                'action_config' => ['persona' => 'ceo'],
            ],
            // No reply after 3 days → follow-up template
            [
                'name' => '3-Day Follow-up',
                'trigger_event' => CrmMailTrigger::EVENT_NO_REPLY,
                'conditions' => ['days_stale' => 3],
                'action_type' => CrmMailTrigger::ACTION_SEND_TEMPLATE,
                'action_config' => ['template_key' => 'followup_3d'],
            ],
            // No reply after 10 days → case study template
            [
                'name' => '10-Day Case Study',
                'trigger_event' => CrmMailTrigger::EVENT_NO_REPLY,
                'conditions' => ['days_stale' => 10],
                'action_type' => CrmMailTrigger::ACTION_SEND_TEMPLATE,
                'action_config' => ['template_key' => 'followup_10d'],
            ],
            // Reply received → generate AI reply
            [
                'name' => 'Auto AI Reply',
                'trigger_event' => CrmMailTrigger::EVENT_REPLY_RECEIVED,
                'conditions' => [],
                'action_type' => CrmMailTrigger::ACTION_GENERATE_AI_REPLY,
                'action_config' => [],
            ],
            // Demo scheduled → send demo invite
            [
                'name' => 'Demo Stage Invite',
                'trigger_event' => CrmMailTrigger::EVENT_DEAL_STAGE_CHANGED,
                'conditions' => ['stage' => 'demo_scheduled'],
                'action_type' => CrmMailTrigger::ACTION_SEND_TEMPLATE,
                'action_config' => ['template_key' => 'demo_invite'],
            ],
        ];

        foreach ($triggers as $t) {
            CrmMailTrigger::firstOrCreate(
                ['name' => $t['name'], 'trigger_event' => $t['trigger_event']],
                [
                    'conditions' => $t['conditions'],
                    'action_type' => $t['action_type'],
                    'action_config' => $t['action_config'],
                    'active' => true,
                ]
            );
        }

        $this->command->info('Seeded ' . count($triggers) . ' mail triggers.');
    }
}
