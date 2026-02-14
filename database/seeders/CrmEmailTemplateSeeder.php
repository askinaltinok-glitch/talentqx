<?php

namespace Database\Seeders;

use App\Models\CrmEmailTemplate;
use Illuminate\Database\Seeder;

class CrmEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Maritime - English
            [
                'key' => 'intro',
                'industry_code' => 'maritime',
                'language' => 'en',
                'subject' => 'TalentQX — AI-Powered Crew Assessment for {company_name}',
                'body_text' => "Dear {contact_name},\n\nI'm reaching out from TalentQX, a maritime talent assessment platform that helps shipping companies and crewing agencies streamline crew hiring.\n\nOur platform provides:\n- AI-powered competency assessment for seafarers\n- STCW compliance verification per rank and vessel type\n- Structured English evaluation\n- Audit-ready decision packets\n\nWould you be open to a brief call to explore how we can support {company_name}'s crew supply needs?\n\nBest regards,\nTalentQX Team\ncrew@talentqx.com",
                'body_html' => "<p>Dear {contact_name},</p><p>I'm reaching out from TalentQX, a maritime talent assessment platform that helps shipping companies and crewing agencies streamline crew hiring.</p><p>Our platform provides:</p><ul><li>AI-powered competency assessment for seafarers</li><li>STCW compliance verification per rank and vessel type</li><li>Structured English evaluation</li><li>Audit-ready decision packets</li></ul><p>Would you be open to a brief call to explore how we can support {company_name}'s crew supply needs?</p><p>Best regards,<br>TalentQX Team<br>crew@talentqx.com</p>",
            ],
            // Maritime - Russian
            [
                'key' => 'intro',
                'industry_code' => 'maritime',
                'language' => 'ru',
                'subject' => 'TalentQX — AI-оценка экипажа для {company_name}',
                'body_text' => "Уважаемый(ая) {contact_name},\n\nОбращаюсь к вам от имени TalentQX — платформы оценки морских кадров.\n\nМы предлагаем:\n- AI-оценку компетенций моряков\n- Проверку соответствия STCW по должности и типу судна\n- Структурированную оценку английского языка\n- Аудит-готовые пакеты решений\n\nГотовы ли вы к короткому звонку для обсуждения?\n\nС уважением,\nКоманда TalentQX\ncrew@talentqx.com",
                'body_html' => "<p>Уважаемый(ая) {contact_name},</p><p>Обращаюсь к вам от имени TalentQX — платформы оценки морских кадров.</p><p>Мы предлагаем:</p><ul><li>AI-оценку компетенций моряков</li><li>Проверку соответствия STCW по должности и типу судна</li><li>Структурированную оценку английского языка</li><li>Аудит-готовые пакеты решений</li></ul><p>Готовы ли вы к короткому звонку для обсуждения?</p><p>С уважением,<br>Команда TalentQX<br>crew@talentqx.com</p>",
            ],
            // Maritime - Follow-up English
            [
                'key' => 'followup',
                'industry_code' => 'maritime',
                'language' => 'en',
                'subject' => 'Following up — TalentQX crew assessment for {company_name}',
                'body_text' => "Dear {contact_name},\n\nI wanted to follow up on my previous message about TalentQX's crew assessment platform.\n\nWe recently helped a crewing agency reduce their screening time by 60% while improving STCW compliance rates.\n\nWould 15 minutes work for a quick demo this week?\n\nBest regards,\nTalentQX Team\ncrew@talentqx.com",
                'body_html' => "<p>Dear {contact_name},</p><p>I wanted to follow up on my previous message about TalentQX's crew assessment platform.</p><p>We recently helped a crewing agency reduce their screening time by 60% while improving STCW compliance rates.</p><p>Would 15 minutes work for a quick demo this week?</p><p>Best regards,<br>TalentQX Team<br>crew@talentqx.com</p>",
            ],
            // Maritime - Follow-up Russian
            [
                'key' => 'followup',
                'industry_code' => 'maritime',
                'language' => 'ru',
                'subject' => 'Напоминание — оценка экипажа TalentQX для {company_name}',
                'body_text' => "Уважаемый(ая) {contact_name},\n\nХотел(а) бы вернуться к моему предыдущему сообщению о платформе оценки экипажа TalentQX.\n\nНедавно мы помогли крюинговому агентству сократить время скрининга на 60% при повышении показателей соответствия STCW.\n\nНайдётся ли у вас 15 минут на демонстрацию на этой неделе?\n\nС уважением,\nКоманда TalentQX\ncrew@talentqx.com",
                'body_html' => "<p>Уважаемый(ая) {contact_name},</p><p>Хотел(а) бы вернуться к моему предыдущему сообщению о платформе оценки экипажа TalentQX.</p><p>Недавно мы помогли крюинговому агентству сократить время скрининга на 60% при повышении показателей соответствия STCW.</p><p>Найдётся ли у вас 15 минут на демонстрацию на этой неделе?</p><p>С уважением,<br>Команда TalentQX<br>crew@talentqx.com</p>",
            ],
            // General - English Intro
            [
                'key' => 'intro',
                'industry_code' => 'general',
                'language' => 'en',
                'subject' => 'TalentQX — AI-Powered Talent Assessment for {company_name}',
                'body_text' => "Dear {contact_name},\n\nI'm reaching out from TalentQX, an AI-powered talent assessment platform.\n\nWe help companies streamline their hiring with structured competency assessment and data-driven decision packets.\n\nWould you be open to a brief call?\n\nBest regards,\nTalentQX Team\ninfo@talentqx.com",
                'body_html' => "<p>Dear {contact_name},</p><p>I'm reaching out from TalentQX, an AI-powered talent assessment platform.</p><p>We help companies streamline their hiring with structured competency assessment and data-driven decision packets.</p><p>Would you be open to a brief call?</p><p>Best regards,<br>TalentQX Team<br>info@talentqx.com</p>",
            ],
            // General - English Follow-up
            [
                'key' => 'followup',
                'industry_code' => 'general',
                'language' => 'en',
                'subject' => 'Following up — TalentQX for {company_name}',
                'body_text' => "Dear {contact_name},\n\nI wanted to follow up on my previous message about TalentQX.\n\nWould 15 minutes work for a quick demo this week?\n\nBest regards,\nTalentQX Team\ninfo@talentqx.com",
                'body_html' => "<p>Dear {contact_name},</p><p>I wanted to follow up on my previous message about TalentQX.</p><p>Would 15 minutes work for a quick demo this week?</p><p>Best regards,<br>TalentQX Team<br>info@talentqx.com</p>",
            ],
        ];

        foreach ($templates as $t) {
            CrmEmailTemplate::updateOrCreate(
                ['key' => $t['key'], 'industry_code' => $t['industry_code'], 'language' => $t['language']],
                $t
            );
        }

        $this->command->info('Seeded ' . count($templates) . ' CRM email templates.');
    }
}
