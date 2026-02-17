<?php

namespace Database\Seeders;

use App\Models\CrmEmailTemplate;
use App\Models\CrmSequence;
use Illuminate\Database\Seeder;

class CrmSequenceSeeder extends Seeder
{
    public function run(): void
    {
        // Seed email templates for sequences
        $this->seedTemplates();

        // Seed default sequences
        $this->seedSequences();
    }

    private function seedTemplates(): void
    {
        $templates = [
            // General - English
            ['key' => 'intro', 'industry_code' => 'general', 'language' => 'en',
             'subject' => 'Introduction to TalentQX — Smart Hiring Solutions',
             'body_text' => "Hi {contact_name},\n\nI'm reaching out from TalentQX. We help companies like {company_name} streamline their hiring process with AI-powered assessments and candidate matching.\n\nWould you be open to a brief call to explore how we might help?\n\nBest regards,\nTalentQX Team",
             'body_html' => "<p>Hi {contact_name},</p><p>I'm reaching out from TalentQX. We help companies like {company_name} streamline their hiring process with AI-powered assessments and candidate matching.</p><p>Would you be open to a brief call to explore how we might help?</p><p>Best regards,<br>TalentQX Team</p>"],

            ['key' => 'followup_1', 'industry_code' => 'general', 'language' => 'en',
             'subject' => 'Following up — TalentQX',
             'body_text' => "Hi {contact_name},\n\nJust following up on my previous email. I'd love to show you how TalentQX can reduce your time-to-hire by 40%.\n\nAre you available for a 15-minute demo this week?\n\nBest regards,\nTalentQX Team",
             'body_html' => "<p>Hi {contact_name},</p><p>Just following up on my previous email. I'd love to show you how TalentQX can reduce your time-to-hire by 40%.</p><p>Are you available for a 15-minute demo this week?</p><p>Best regards,<br>TalentQX Team</p>"],

            ['key' => 're_engagement', 'industry_code' => 'general', 'language' => 'en',
             'subject' => 'One more thing — TalentQX',
             'body_text' => "Hi {contact_name},\n\nI understand timing may not have been right. If hiring challenges come up in the future, we're here to help.\n\nFeel free to reach out anytime.\n\nBest regards,\nTalentQX Team",
             'body_html' => "<p>Hi {contact_name},</p><p>I understand timing may not have been right. If hiring challenges come up in the future, we're here to help.</p><p>Feel free to reach out anytime.</p><p>Best regards,<br>TalentQX Team</p>"],

            // Maritime - English
            ['key' => 'maritime_intro', 'industry_code' => 'maritime', 'language' => 'en',
             'subject' => 'TalentQX — Maritime Crew Intelligence Platform',
             'body_text' => "Hi {contact_name},\n\nTalentQX is a maritime-focused crew intelligence platform. We help companies like {company_name} find qualified seafarers with verified STCW certificates, AI-powered assessments, and transparent crew pipelines.\n\nWould you be interested in learning how we support manning operations?\n\nFair winds,\nTalentQX Maritime Team",
             'body_html' => "<p>Hi {contact_name},</p><p>TalentQX is a maritime-focused crew intelligence platform. We help companies like {company_name} find qualified seafarers with verified STCW certificates, AI-powered assessments, and transparent crew pipelines.</p><p>Would you be interested in learning how we support manning operations?</p><p>Fair winds,<br>TalentQX Maritime Team</p>"],

            ['key' => 'followup_1', 'industry_code' => 'maritime', 'language' => 'en',
             'subject' => 'Following up — Maritime Crew Solutions',
             'body_text' => "Hi {contact_name},\n\nJust following up regarding TalentQX's maritime crew platform. Our clients have reduced crew turnaround time significantly.\n\nHappy to arrange a quick demo — would this week work?\n\nFair winds,\nTalentQX Maritime Team",
             'body_html' => "<p>Hi {contact_name},</p><p>Just following up regarding TalentQX's maritime crew platform. Our clients have reduced crew turnaround time significantly.</p><p>Happy to arrange a quick demo — would this week work?</p><p>Fair winds,<br>TalentQX Maritime Team</p>"],

            ['key' => 're_engagement', 'industry_code' => 'maritime', 'language' => 'en',
             'subject' => 'Staying in touch — TalentQX Maritime',
             'body_text' => "Hi {contact_name},\n\nI know crew management keeps you busy. Whenever you're looking for a smarter way to manage seafarer recruitment and compliance, TalentQX is here.\n\nFair winds,\nTalentQX Maritime Team",
             'body_html' => "<p>Hi {contact_name},</p><p>I know crew management keeps you busy. Whenever you're looking for a smarter way to manage seafarer recruitment and compliance, TalentQX is here.</p><p>Fair winds,<br>TalentQX Maritime Team</p>"],

            // General - Turkish
            ['key' => 'intro', 'industry_code' => 'general', 'language' => 'tr',
             'subject' => 'TalentQX ile Tanisma — Akilli Ise Alim Cozumleri',
             'body_text' => "Merhaba {contact_name},\n\nTalentQX olarak {company_name} gibi sirketlerin ise alim sureclerini yapay zeka destekli degerlendirmeler ve aday eslestirme ile hizlandiriyoruz.\n\nKisa bir gorusme icin musait misiniz?\n\nSaygilarimla,\nTalentQX Ekibi",
             'body_html' => "<p>Merhaba {contact_name},</p><p>TalentQX olarak {company_name} gibi sirketlerin ise alim sureclerini yapay zeka destekli degerlendirmeler ve aday eslestirme ile hizlandiriyoruz.</p><p>Kisa bir gorusme icin musait misiniz?</p><p>Saygilarimla,<br>TalentQX Ekibi</p>"],

            ['key' => 'followup_1', 'industry_code' => 'general', 'language' => 'tr',
             'subject' => 'Takip — TalentQX',
             'body_text' => "Merhaba {contact_name},\n\nOnceki mailime istinaden yaziyorum. TalentQX'in ise alim surelerinizi nasil %40 kisaltabilecegini gostermek isterim.\n\nBu hafta 15 dakikalik bir demo icin musait misiniz?\n\nSaygilarimla,\nTalentQX Ekibi",
             'body_html' => "<p>Merhaba {contact_name},</p><p>Onceki mailime istinaden yaziyorum. TalentQX'in ise alim surelerinizi nasil %40 kisaltabilecegini gostermek isterim.</p><p>Bu hafta 15 dakikalik bir demo icin musait misiniz?</p><p>Saygilarimla,<br>TalentQX Ekibi</p>"],

            // Maritime - Turkish
            ['key' => 'maritime_intro', 'industry_code' => 'maritime', 'language' => 'tr',
             'subject' => 'TalentQX — Denizcilik Personel Platformu',
             'body_text' => "Merhaba {contact_name},\n\nTalentQX, denizcilik sektorune ozel bir personel yonetim platformudur. {company_name} gibi firmalara STCW sertifikali gemici bulma, degerlendirme ve seffaf personel surecleri sunuyoruz.\n\nManning operasyonlarinizi nasil destekleyebilecegimizi konusmak ister misiniz?\n\nSaygilarimla,\nTalentQX Denizcilik Ekibi",
             'body_html' => "<p>Merhaba {contact_name},</p><p>TalentQX, denizcilik sektorune ozel bir personel yonetim platformudur. {company_name} gibi firmalara STCW sertifikali gemici bulma, degerlendirme ve seffaf personel surecleri sunuyoruz.</p><p>Manning operasyonlarinizi nasil destekleyebilecegimizi konusmak ister misiniz?</p><p>Saygilarimla,<br>TalentQX Denizcilik Ekibi</p>"],

            ['key' => 'followup_1', 'industry_code' => 'maritime', 'language' => 'tr',
             'subject' => 'Takip — Denizcilik Personel Cozumleri',
             'body_text' => "Merhaba {contact_name},\n\nTalentQX denizcilik platformu hakkinda tekrar yaziyorum. Musterilerimiz personel donusum surelerini onemli olcude azaltti.\n\nKisa bir demo icin bu hafta musait misiniz?\n\nSaygilarimla,\nTalentQX Denizcilik Ekibi",
             'body_html' => "<p>Merhaba {contact_name},</p><p>TalentQX denizcilik platformu hakkinda tekrar yaziyorum. Musterilerimiz personel donusum surelerini onemli olcude azaltti.</p><p>Kisa bir demo icin bu hafta musait misiniz?</p><p>Saygilarimla,<br>TalentQX Denizcilik Ekibi</p>"],
        ];

        foreach ($templates as $tpl) {
            CrmEmailTemplate::firstOrCreate(
                ['key' => $tpl['key'], 'industry_code' => $tpl['industry_code'], 'language' => $tpl['language']],
                array_merge($tpl, ['active' => true])
            );
        }
    }

    private function seedSequences(): void
    {
        $sequences = [
            [
                'name' => 'General Intro (EN)',
                'industry_code' => 'general',
                'language' => 'en',
                'description' => 'Standard 3-step introduction sequence for general leads in English.',
                'steps' => [
                    ['delay_days' => 0, 'template_key' => 'intro', 'channel' => 'email'],
                    ['delay_days' => 3, 'template_key' => 'followup_1', 'channel' => 'email'],
                    ['delay_days' => 7, 'template_key' => 're_engagement', 'channel' => 'email'],
                ],
            ],
            [
                'name' => 'Maritime Intro (EN)',
                'industry_code' => 'maritime',
                'language' => 'en',
                'description' => 'Maritime-specific introduction sequence for shipping companies in English.',
                'steps' => [
                    ['delay_days' => 0, 'template_key' => 'maritime_intro', 'channel' => 'email'],
                    ['delay_days' => 3, 'template_key' => 'followup_1', 'channel' => 'email'],
                    ['delay_days' => 14, 'template_key' => 're_engagement', 'channel' => 'email'],
                ],
            ],
            [
                'name' => 'General Intro (TR)',
                'industry_code' => 'general',
                'language' => 'tr',
                'description' => 'Genel leadler icin 3 adimli tanitim dizisi (Turkce).',
                'steps' => [
                    ['delay_days' => 0, 'template_key' => 'intro', 'channel' => 'email'],
                    ['delay_days' => 3, 'template_key' => 'followup_1', 'channel' => 'email'],
                    ['delay_days' => 7, 'template_key' => 're_engagement', 'channel' => 'email'],
                ],
            ],
            [
                'name' => 'Maritime Intro (TR)',
                'industry_code' => 'maritime',
                'language' => 'tr',
                'description' => 'Denizcilik firmalari icin tanitim dizisi (Turkce).',
                'steps' => [
                    ['delay_days' => 0, 'template_key' => 'maritime_intro', 'channel' => 'email'],
                    ['delay_days' => 3, 'template_key' => 'followup_1', 'channel' => 'email'],
                    ['delay_days' => 14, 'template_key' => 're_engagement', 'channel' => 'email'],
                ],
            ],
        ];

        foreach ($sequences as $seq) {
            CrmSequence::firstOrCreate(
                ['name' => $seq['name']],
                $seq
            );
        }
    }
}
