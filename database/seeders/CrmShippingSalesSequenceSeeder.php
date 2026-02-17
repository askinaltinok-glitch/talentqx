<?php

namespace Database\Seeders;

use App\Models\CrmSequence;
use Illuminate\Database\Seeder;

class CrmShippingSalesSequenceSeeder extends Seeder
{
    public function run(): void
    {
        CrmSequence::firstOrCreate(
            ['name' => 'Shipping Company Sales Outreach'],
            [
                'industry_code' => 'maritime',
                'language' => 'en',
                'description' => 'Targeted 4-step outreach sequence for shipping companies (armators, fleet operators). Day 0 intro + 3 follow-ups.',
                'steps' => [
                    ['delay_days' => 0, 'template_key' => 'shipping_intro', 'channel' => 'email'],
                    ['delay_days' => 3, 'template_key' => 'shipping_followup_3d', 'channel' => 'email'],
                    ['delay_days' => 7, 'template_key' => 'shipping_followup_7d', 'channel' => 'email'],
                    ['delay_days' => 14, 'template_key' => 'shipping_followup_14d', 'channel' => 'email'],
                ],
                'active' => true,
            ]
        );

        $this->command->info('Seeded Shipping Company Sales Outreach sequence.');
    }
}
