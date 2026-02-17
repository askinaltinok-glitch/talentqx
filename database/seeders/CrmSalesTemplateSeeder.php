<?php

namespace Database\Seeders;

use App\Models\CrmEmailTemplate;
use Illuminate\Database\Seeder;

class CrmSalesTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Maritime Welcome Series
            [
                'key' => 'welcome_maritime',
                'industry_code' => 'maritime',
                'language' => 'en',
                'subject' => 'Crew Supply Solutions for {company_name}',
                'body_text' => "Dear {contact_name},\n\nWelcome to TalentQX Maritime. We specialize in providing verified, pre-screened crew for the maritime industry.\n\nOur platform offers:\n- AI-powered candidate matching\n- STCW certification verification\n- English proficiency assessment\n- Video interview evaluation\n\nWe'd love to understand your crewing needs. Would you be available for a 15-minute call this week?\n\nBest regards,\nTalentQX Maritime Crew Team",
            ],
            [
                'key' => 'welcome_maritime',
                'industry_code' => 'maritime',
                'language' => 'tr',
                'subject' => '{company_name} icin Murettebat Temini',
                'body_text' => "Sayın {contact_name},\n\nTalentQX Maritime'e hoş geldiniz. Denizcilik sektörü için doğrulanmış, ön elemeden geçmiş mürettebat sağlamada uzmanız.\n\nPlatformumuz:\n- Yapay zeka destekli aday eşleştirmesi\n- STCW sertifika doğrulaması\n- İngilizce yeterlilik değerlendirmesi\n- Video mülakat değerlendirmesi\n\nMürettebat ihtiyaçlarınızı anlamak isteriz. Bu hafta 15 dakikalık bir görüşme için müsait misiniz?\n\nSaygılarımızla,\nTalentQX Maritime Mürettebat Ekibi",
            ],
            [
                'key' => 'welcome_maritime',
                'industry_code' => 'maritime',
                'language' => 'ru',
                'subject' => 'Решения по подбору экипажа для {company_name}',
                'body_text' => "Уважаемый {contact_name},\n\nДобро пожаловать в TalentQX Maritime. Мы специализируемся на предоставлении проверенного экипажа для морской отрасли.\n\nНаша платформа предлагает:\n- Подбор кандидатов с помощью ИИ\n- Верификация сертификатов STCW\n- Оценка уровня английского языка\n- Видео-интервью\n\nМы хотели бы понять ваши потребности в экипаже. Можем ли мы договориться о 15-минутном звонке на этой неделе?\n\nС уважением,\nКоманда TalentQX Maritime",
            ],

            // General Welcome
            [
                'key' => 'welcome_general',
                'industry_code' => 'general',
                'language' => 'en',
                'subject' => 'AI-Powered Hiring for {company_name}',
                'body_text' => "Dear {contact_name},\n\nThank you for your interest in TalentQX. We help companies make smarter hiring decisions using AI-powered interviews and structured assessments.\n\nOur platform provides:\n- Structured competency-based interviews\n- AI-powered candidate scoring\n- English proficiency testing\n- Predictive hiring analytics\n\nWould you like to see a quick demo? I'd be happy to schedule a 15-minute walkthrough.\n\nBest regards,\nTalentQX Team",
            ],
            [
                'key' => 'welcome_general',
                'industry_code' => 'general',
                'language' => 'tr',
                'subject' => '{company_name} icin AI Destekli İşe Alım',
                'body_text' => "Sayın {contact_name},\n\nTalentQX'e ilginiz için teşekkürler. Yapay zeka destekli mülakatlar ve yapılandırılmış değerlendirmelerle daha akıllı işe alım kararları vermenize yardımcı oluyoruz.\n\nPlatformumuz:\n- Yetkinlik bazlı yapılandırılmış mülakatlar\n- Yapay zeka destekli aday puanlama\n- İngilizce yeterlilik testi\n- Öngörücü işe alım analitiği\n\nKısa bir demo görmek ister misiniz? 15 dakikalık bir tanıtım için randevu ayarlayabilirim.\n\nSaygılarımızla,\nTalentQX Ekibi",
            ],

            // Product Intro (Maritime)
            [
                'key' => 'product_intro_maritime',
                'industry_code' => 'maritime',
                'language' => 'en',
                'subject' => 'How {company_name} Can Reduce Crew Sourcing Time by 60%',
                'body_text' => "Dear {contact_name},\n\nI wanted to share how TalentQX Maritime is helping ship managers reduce crew sourcing time significantly.\n\nOne of our clients reduced their average crew deployment time from 45 to 18 days using our AI-matching engine.\n\nKey benefits:\n- Pre-screened, verified seafarer pool\n- Automated STCW compliance checking\n- Multi-language support (EN/TR/RU)\n- Real-time availability tracking\n\nWould a quick demo be of interest?\n\nBest regards,\nTalentQX Maritime Crew Team",
            ],
            [
                'key' => 'product_intro_maritime',
                'industry_code' => 'maritime',
                'language' => 'tr',
                'subject' => '{company_name} Mürettebat Temin Süresini %60 Azaltabilir',
                'body_text' => "Sayın {contact_name},\n\nTalentQX Maritime'in gemi yöneticilerinin mürettebat temin süresini nasıl önemli ölçüde azalttığını paylaşmak istedim.\n\nMüşterilerimizden biri, AI eşleştirme motorumuzu kullanarak ortalama mürettebat görevlendirme süresini 45 günden 18 güne düşürdü.\n\nTemel avantajlar:\n- Ön elemeden geçmiş, doğrulanmış denizci havuzu\n- Otomatik STCW uyumluluk kontrolü\n- Çoklu dil desteği (EN/TR/RU)\n- Gerçek zamanlı müsaitlik takibi\n\nKısa bir demo ilginizi çeker mi?\n\nSaygılarımızla,\nTalentQX Maritime Mürettebat Ekibi",
            ],

            // Demo Invite
            [
                'key' => 'demo_invite',
                'industry_code' => 'general',
                'language' => 'en',
                'subject' => 'Your TalentQX Demo - Let\'s Schedule',
                'body_text' => "Dear {contact_name},\n\nGreat to connect! I'd like to schedule a personalized demo of TalentQX for {company_name}.\n\nIn the demo, I'll show you:\n- How our AI interview engine works\n- Real candidate scoring in action\n- How companies like yours are saving time on hiring\n\nThe demo takes about 20 minutes. What time works best for you this week?\n\nBest regards,\nTalentQX Team",
            ],
            [
                'key' => 'demo_invite',
                'industry_code' => 'general',
                'language' => 'tr',
                'subject' => 'TalentQX Demo - Randevu Ayarlayalım',
                'body_text' => "Sayın {contact_name},\n\nİletişime geçtiğiniz için mutluyuz! {company_name} için kişiselleştirilmiş bir TalentQX demosu planlamak istiyorum.\n\nDemoda göstereceklerim:\n- AI mülakat motorumuzun nasıl çalıştığı\n- Gerçek aday puanlama örnekleri\n- Sizin gibi şirketlerin işe alım sürecinde nasıl zaman kazandığı\n\nDemo yaklaşık 20 dakika sürer. Bu hafta hangi zaman sizin için uygun?\n\nSaygılarımızla,\nTalentQX Ekibi",
            ],

            // Follow-up (3 days no reply)
            [
                'key' => 'followup_3d',
                'industry_code' => 'general',
                'language' => 'en',
                'subject' => 'Quick follow-up - {company_name}',
                'body_text' => "Dear {contact_name},\n\nI wanted to follow up on my previous message. I understand things get busy.\n\nWould it be helpful if I sent over a brief overview of how TalentQX works? Or if you prefer, we can jump on a quick 10-minute call.\n\nEither way, I'm here to help whenever you're ready.\n\nBest regards,\nTalentQX Team",
            ],
            [
                'key' => 'followup_3d',
                'industry_code' => 'general',
                'language' => 'tr',
                'subject' => 'Kısa takip - {company_name}',
                'body_text' => "Sayın {contact_name},\n\nÖnceki mesajımla ilgili takip etmek istedim. İşlerin yoğun olduğunu anlıyorum.\n\nTalentQX'in nasıl çalıştığına dair kısa bir genel bakış göndermem yardımcı olur mu? Ya da tercih ederseniz, kısa bir 10 dakikalık görüşme yapabiliriz.\n\nHer durumda, hazır olduğunuzda buradayım.\n\nSaygılarımızla,\nTalentQX Ekibi",
            ],

            // Follow-up (10 days - case study)
            [
                'key' => 'followup_10d',
                'industry_code' => 'general',
                'language' => 'en',
                'subject' => 'How companies are hiring smarter with TalentQX',
                'body_text' => "Dear {contact_name},\n\nI wanted to share a quick insight: companies using structured AI interviews are seeing up to 40% better hire quality.\n\nTalentQX provides:\n- Consistent, bias-reduced screening\n- Predictive scoring for cultural fit and competency\n- 60% faster shortlisting\n\nIf this resonates, I'd love to chat about how it could work for {company_name}.\n\nBest regards,\nTalentQX Team",
            ],
            [
                'key' => 'followup_10d',
                'industry_code' => 'general',
                'language' => 'tr',
                'subject' => 'Şirketler TalentQX ile nasıl daha akıllı işe alıyor',
                'body_text' => "Sayın {contact_name},\n\nKısa bir bilgi paylaşmak istedim: yapılandırılmış AI mülakatları kullanan şirketler, işe alım kalitesinde %40'a varan iyileşme görüyor.\n\nTalentQX şunları sağlıyor:\n- Tutarlı, önyargısız eleme\n- Kültürel uyum ve yetkinlik için öngörücü puanlama\n- %60 daha hızlı aday kısa listesi\n\nBu size hitap ediyorsa, {company_name} için nasıl işe yarayabileceğini konuşmak isterim.\n\nSaygılarımızla,\nTalentQX Ekibi",
            ],
        ];

        foreach ($templates as $t) {
            CrmEmailTemplate::firstOrCreate(
                ['key' => $t['key'], 'industry_code' => $t['industry_code'], 'language' => $t['language']],
                ['subject' => $t['subject'], 'body_text' => $t['body_text'], 'body_html' => '', 'active' => true]
            );
        }

        $this->command->info('Seeded ' . count($templates) . ' sales email templates.');
    }
}
