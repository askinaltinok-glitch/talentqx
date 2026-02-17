<?php

namespace Database\Seeders;

use App\Models\CrmMailTrigger;
use Illuminate\Database\Seeder;

class CrmShippingTriggersSeeder extends Seeder
{
    public function run(): void
    {
        $triggers = [
            [
                'name' => 'Maritime Reply → Lead Contacted',
                'trigger_event' => CrmMailTrigger::EVENT_REPLY_RECEIVED,
                'conditions' => ['industry' => 'maritime'],
                'action_type' => CrmMailTrigger::ACTION_ADVANCE_LEAD_STAGE,
                'action_config' => ['stage' => 'contacted'],
            ],
            [
                'name' => 'Maritime Reply → Create Deal',
                'trigger_event' => CrmMailTrigger::EVENT_REPLY_RECEIVED,
                'conditions' => ['industry' => 'maritime'],
                'action_type' => CrmMailTrigger::ACTION_CREATE_DEAL,
                'action_config' => ['deal_stage' => 'contacted'],
            ],
            [
                'name' => 'Demo Request → Advance Deal',
                'trigger_event' => CrmMailTrigger::EVENT_REPLY_RECEIVED,
                'conditions' => ['industry' => 'maritime', 'intent' => 'demo_request'],
                'action_type' => CrmMailTrigger::ACTION_ADVANCE_DEAL_STAGE,
                'action_config' => ['deal_stage' => 'demo_scheduled'],
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

        $this->command->info('Seeded ' . count($triggers) . ' shipping automation triggers.');
    }
}
