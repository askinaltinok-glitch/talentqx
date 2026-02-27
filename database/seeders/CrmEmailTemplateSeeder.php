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
                'body_text' => "Dear {contact_name},\n\nI wanted to follow up on my previous message about TalentQX.\n\nWould 15 minutes work for a quick demo this week?\n\nBest regards,\n{sender_name}\n{sender_title}\ninfo@talentqx.com",
                'body_html' => "<p>Dear {contact_name},</p><p>I wanted to follow up on my previous message about TalentQX.</p><p>Would 15 minutes work for a quick demo this week?</p><p>Best regards,<br>{sender_name}<br>{sender_title}<br>info@talentqx.com</p>",
            ],

            // =============================================
            // General - Turkish (TR)
            // =============================================
            [
                'key' => 'intro',
                'industry_code' => 'general',
                'language' => 'tr',
                'subject' => 'TalentQX — {company_name} için Yapay Zeka Destekli Yetenek Değerlendirme',
                'body_text' => "Sayın {contact_name},\n\nTalentQX olarak, yapay zeka destekli yetenek değerlendirme platformumuz hakkında sizinle iletişime geçmek istedik.\n\nPlatformumuz şunları sunar:\n- Yapılandırılmış yetkinlik bazlı mülakat\n- Veri odaklı karar paketleri\n- AI destekli aday analizi\n- {trial_days} gün ücretsiz deneme ({package_name} paketi)\n\n{company_name} için kısa bir tanıtım görüşmesi yapmak ister misiniz?\n\nSaygılarımla,\n{sender_name}\n{sender_title}\ninfo@talentqx.com",
                'body_html' => "<p>Sayın {contact_name},</p><p>TalentQX olarak, yapay zeka destekli yetenek değerlendirme platformumuz hakkında sizinle iletişime geçmek istedik.</p><p>Platformumuz şunları sunar:</p><ul><li>Yapılandırılmış yetkinlik bazlı mülakat</li><li>Veri odaklı karar paketleri</li><li>AI destekli aday analizi</li><li>{trial_days} gün ücretsiz deneme ({package_name} paketi)</li></ul><p>{company_name} için kısa bir tanıtım görüşmesi yapmak ister misiniz?</p><p>Saygılarımla,<br>{sender_name}<br>{sender_title}<br>info@talentqx.com</p>",
            ],
            [
                'key' => 'followup',
                'industry_code' => 'general',
                'language' => 'tr',
                'subject' => 'Hatırlatma — {company_name} için TalentQX',
                'body_text' => "Sayın {contact_name},\n\nGeçen hafta TalentQX platformu hakkında gönderdiğim mesajımı hatırlatmak istedim.\n\nBu hafta 15 dakikalık kısa bir demo için müsait olur musunuz?\n\nSaygılarımla,\n{sender_name}\n{sender_title}\ninfo@talentqx.com",
                'body_html' => "<p>Sayın {contact_name},</p><p>Geçen hafta TalentQX platformu hakkında gönderdiğim mesajımı hatırlatmak istedim.</p><p>Bu hafta 15 dakikalık kısa bir demo için müsait olur musunuz?</p><p>Saygılarımla,<br>{sender_name}<br>{sender_title}<br>info@talentqx.com</p>",
            ],

            // =============================================
            // General - German (DE)
            // =============================================
            [
                'key' => 'intro',
                'industry_code' => 'general',
                'language' => 'de',
                'subject' => 'TalentQX — KI-gestützte Talentbewertung für {company_name}',
                'body_text' => "Sehr geehrte(r) {contact_name},\n\nich kontaktiere Sie im Namen von TalentQX, einer KI-gestützten Plattform zur Talentbewertung.\n\nUnsere Plattform bietet:\n- Strukturierte kompetenzbasierte Interviews\n- Datengesteuerte Entscheidungspakete\n- KI-unterstützte Kandidatenanalyse\n- {trial_days} Tage kostenlose Testphase ({package_name}-Paket)\n\nHätten Sie Interesse an einem kurzen Gespräch, um zu besprechen, wie wir {company_name} unterstützen können?\n\nMit freundlichen Grüßen,\n{sender_name}\n{sender_title}\ninfo@talentqx.com",
                'body_html' => "<p>Sehr geehrte(r) {contact_name},</p><p>ich kontaktiere Sie im Namen von TalentQX, einer KI-gestützten Plattform zur Talentbewertung.</p><p>Unsere Plattform bietet:</p><ul><li>Strukturierte kompetenzbasierte Interviews</li><li>Datengesteuerte Entscheidungspakete</li><li>KI-unterstützte Kandidatenanalyse</li><li>{trial_days} Tage kostenlose Testphase ({package_name}-Paket)</li></ul><p>Hätten Sie Interesse an einem kurzen Gespräch, um zu besprechen, wie wir {company_name} unterstützen können?</p><p>Mit freundlichen Grüßen,<br>{sender_name}<br>{sender_title}<br>info@talentqx.com</p>",
            ],
            [
                'key' => 'followup',
                'industry_code' => 'general',
                'language' => 'de',
                'subject' => 'Nachfassen — TalentQX für {company_name}',
                'body_text' => "Sehr geehrte(r) {contact_name},\n\nich möchte an meine vorherige Nachricht bezüglich TalentQX erinnern.\n\nHätten Sie diese Woche 15 Minuten Zeit für eine kurze Demo?\n\nMit freundlichen Grüßen,\n{sender_name}\n{sender_title}\ninfo@talentqx.com",
                'body_html' => "<p>Sehr geehrte(r) {contact_name},</p><p>ich möchte an meine vorherige Nachricht bezüglich TalentQX erinnern.</p><p>Hätten Sie diese Woche 15 Minuten Zeit für eine kurze Demo?</p><p>Mit freundlichen Grüßen,<br>{sender_name}<br>{sender_title}<br>info@talentqx.com</p>",
            ],

            // =============================================
            // General - French (FR)
            // =============================================
            [
                'key' => 'intro',
                'industry_code' => 'general',
                'language' => 'fr',
                'subject' => 'TalentQX — Évaluation des talents par IA pour {company_name}',
                'body_text' => "Cher/Chère {contact_name},\n\nJe me permets de vous contacter de la part de TalentQX, une plateforme d'évaluation des talents propulsée par l'intelligence artificielle.\n\nNotre plateforme propose :\n- Des entretiens structurés basés sur les compétences\n- Des dossiers de décision fondés sur les données\n- Une analyse des candidats assistée par IA\n- {trial_days} jours d'essai gratuit (formule {package_name})\n\nSeriez-vous disponible pour un bref appel afin de discuter de la manière dont nous pourrions accompagner {company_name} ?\n\nCordialement,\n{sender_name}\n{sender_title}\ninfo@talentqx.com",
                'body_html' => "<p>Cher/Chère {contact_name},</p><p>Je me permets de vous contacter de la part de TalentQX, une plateforme d'évaluation des talents propulsée par l'intelligence artificielle.</p><p>Notre plateforme propose :</p><ul><li>Des entretiens structurés basés sur les compétences</li><li>Des dossiers de décision fondés sur les données</li><li>Une analyse des candidats assistée par IA</li><li>{trial_days} jours d'essai gratuit (formule {package_name})</li></ul><p>Seriez-vous disponible pour un bref appel afin de discuter de la manière dont nous pourrions accompagner {company_name} ?</p><p>Cordialement,<br>{sender_name}<br>{sender_title}<br>info@talentqx.com</p>",
            ],
            [
                'key' => 'followup',
                'industry_code' => 'general',
                'language' => 'fr',
                'subject' => 'Relance — TalentQX pour {company_name}',
                'body_text' => "Cher/Chère {contact_name},\n\nJe souhaitais revenir vers vous suite à mon précédent message concernant TalentQX.\n\nAuriez-vous 15 minutes cette semaine pour une courte démonstration ?\n\nCordialement,\n{sender_name}\n{sender_title}\ninfo@talentqx.com",
                'body_html' => "<p>Cher/Chère {contact_name},</p><p>Je souhaitais revenir vers vous suite à mon précédent message concernant TalentQX.</p><p>Auriez-vous 15 minutes cette semaine pour une courte démonstration ?</p><p>Cordialement,<br>{sender_name}<br>{sender_title}<br>info@talentqx.com</p>",
            ],

            // =============================================
            // General - Arabic (AR)
            // =============================================
            [
                'key' => 'intro',
                'industry_code' => 'general',
                'language' => 'ar',
                'subject' => 'TalentQX — تقييم المواهب بالذكاء الاصطناعي لشركة {company_name}',
                'body_text' => "السيد/السيدة {contact_name} المحترم(ة)،\n\nأتواصل معكم من منصة TalentQX، منصة تقييم المواهب المدعومة بالذكاء الاصطناعي.\n\nتوفر منصتنا:\n- مقابلات منظمة قائمة على الكفاءات\n- حزم قرارات مبنية على البيانات\n- تحليل المرشحين بدعم الذكاء الاصطناعي\n- {trial_days} يوم تجربة مجانية (باقة {package_name})\n\nهل تودون إجراء مكالمة قصيرة لمناقشة كيف يمكننا دعم {company_name}؟\n\nمع أطيب التحيات،\n{sender_name}\n{sender_title}\ninfo@talentqx.com",
                'body_html' => "<p>السيد/السيدة {contact_name} المحترم(ة)،</p><p>أتواصل معكم من منصة TalentQX، منصة تقييم المواهب المدعومة بالذكاء الاصطناعي.</p><p>توفر منصتنا:</p><ul><li>مقابلات منظمة قائمة على الكفاءات</li><li>حزم قرارات مبنية على البيانات</li><li>تحليل المرشحين بدعم الذكاء الاصطناعي</li><li>{trial_days} يوم تجربة مجانية (باقة {package_name})</li></ul><p>هل تودون إجراء مكالمة قصيرة لمناقشة كيف يمكننا دعم {company_name}؟</p><p>مع أطيب التحيات،<br>{sender_name}<br>{sender_title}<br>info@talentqx.com</p>",
            ],
            [
                'key' => 'followup',
                'industry_code' => 'general',
                'language' => 'ar',
                'subject' => 'متابعة — TalentQX لشركة {company_name}',
                'body_text' => "السيد/السيدة {contact_name} المحترم(ة)،\n\nأود المتابعة بخصوص رسالتي السابقة حول منصة TalentQX.\n\nهل لديكم 15 دقيقة هذا الأسبوع لعرض توضيحي سريع؟\n\nمع أطيب التحيات،\n{sender_name}\n{sender_title}\ninfo@talentqx.com",
                'body_html' => "<p>السيد/السيدة {contact_name} المحترم(ة)،</p><p>أود المتابعة بخصوص رسالتي السابقة حول منصة TalentQX.</p><p>هل لديكم 15 دقيقة هذا الأسبوع لعرض توضيحي سريع؟</p><p>مع أطيب التحيات،<br>{sender_name}<br>{sender_title}<br>info@talentqx.com</p>",
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
