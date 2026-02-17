<?php

namespace Database\Seeders;

use App\Models\CrmEmailTemplate;
use Illuminate\Database\Seeder;

class CrmShippingSalesTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'shipping_intro',
                'industry_code' => 'maritime',
                'language' => 'en',
                'subject' => 'Reduce crew hiring risk (STCW + English verified)',
                'body_text' => "Dear {contact_name},\n\nI'm reaching out from TalentQX Maritime. We work with ship managers and fleet operators to reduce crew hiring risk.\n\nThe problem we solve: bad joins — when a seafarer boards and doesn't meet the actual requirements. STCW papers look fine, but English is weak, or the candidate isn't fit for the vessel type.\n\nOur platform gives you:\n- English proficiency assessed via structured video interview (not self-reported)\n- STCW certificate verification built in\n- AI-scored candidate ranking by role and vessel type\n- Real-time pool of pre-screened candidates from TR, RU, UA, IN\n\nWould a 15-minute walkthrough be useful? I can show you live candidates matching your fleet profile.\n\nBest regards,\nTalentQX Maritime\nCommercial Operations",
                'body_html' => '',
            ],
            [
                'key' => 'shipping_followup_3d',
                'industry_code' => 'maritime',
                'language' => 'en',
                'subject' => 'Re: {previous_subject}',
                'body_text' => "Dear {contact_name},\n\nFollowing up on my previous message. I know crew operations keep you busy.\n\nQuick context on why this matters now: the cost of a bad join (repatriation + replacement + lost time) runs $15-30K. Our verification catches mismatches before the candidate boards.\n\nTwo things I can show you in 15 minutes:\n1. How we verify English + STCW before you see a candidate\n2. Live candidates matching your fleet type and rank requirements\n\nWorth a quick look?\n\nBest regards,\nTalentQX Maritime\nCommercial Operations",
                'body_html' => '',
            ],
            [
                'key' => 'shipping_followup_7d',
                'industry_code' => 'maritime',
                'language' => 'en',
                'subject' => 'Re: {previous_subject}',
                'body_text' => "Dear {contact_name},\n\nOne more note — we're actively building our verified crew pool from key supply markets: Turkey, Russia, Ukraine, and India.\n\nEvery candidate in our system has:\n- STCW certificates verified against our database\n- English level assessed through structured AI interview\n- Video profile available for your review\n\nIf {company_name} is looking to diversify crew sourcing channels or add a verification layer, this could be a good fit.\n\nHappy to arrange a brief walkthrough whenever convenient.\n\nBest regards,\nTalentQX Maritime\nCommercial Operations",
                'body_html' => '',
            ],
            [
                'key' => 'shipping_followup_14d',
                'industry_code' => 'maritime',
                'language' => 'en',
                'subject' => 'Re: {previous_subject}',
                'body_text' => "Dear {contact_name},\n\nI'll keep this short — I've reached out a few times and I respect your time.\n\nIf crew quality or compliance verification isn't a priority right now, no problem at all. I'll close the loop on my end.\n\nIf it becomes relevant down the line, feel free to reach out directly. We'll be here.\n\nWishing you fair winds.\n\nBest regards,\nTalentQX Maritime\nCommercial Operations",
                'body_html' => '',
            ],
        ];

        foreach ($templates as $t) {
            CrmEmailTemplate::firstOrCreate(
                ['key' => $t['key'], 'industry_code' => $t['industry_code'], 'language' => $t['language']],
                ['subject' => $t['subject'], 'body_text' => $t['body_text'], 'body_html' => $t['body_html'], 'active' => true]
            );
        }

        $this->command->info('Seeded ' . count($templates) . ' shipping sales email templates.');
    }
}
