<?php

namespace Database\Seeders;

use App\Models\InterviewQuestionSet;
use Illuminate\Database\Seeder;

/**
 * Seeds interview_question_sets with 12-question behavioral interview sets
 * for the Clean Workflow v1.
 *
 * 12 dimensions: leadership, discipline, conflict_handling, crisis_response,
 * team_compatibility, authority_perception, fatigue_tolerance, responsibility,
 * communication_clarity, decision_making, safety_mindset, cultural_fit
 *
 * 7 locales (EN master + TR/RU/AZ/FIL/ID/UK).
 */
class CleanWorkflowQuestionSeeder extends Seeder
{
    private const CODE = 'maritime_clean_v1';
    private const VERSION = '1.0';

    public function run(): void
    {
        foreach ($this->getAllLocaleQuestions() as $locale => $questions) {
            InterviewQuestionSet::updateOrCreate(
                [
                    'code' => self::CODE,
                    'version' => self::VERSION,
                    'locale' => $locale,
                    'position_code' => '__generic__',
                    'country_code' => null,
                ],
                [
                    'industry_code' => 'maritime',
                    'is_active' => true,
                    'rules_json' => [
                        'total_questions' => 12,
                        'dimensions' => [
                            'leadership', 'discipline', 'conflict_handling', 'crisis_response',
                            'team_compatibility', 'authority_perception', 'fatigue_tolerance',
                            'responsibility', 'communication_clarity', 'decision_making',
                            'safety_mindset', 'cultural_fit',
                        ],
                        'scoring_scale' => ['min' => 1, 'max' => 5],
                    ],
                    'questions_json' => $questions,
                ]
            );
        }

        $this->command->info('Seeded clean workflow v1 question sets for 7 locales (12 dimensions).');
    }

    private function getAllLocaleQuestions(): array
    {
        return [
            'en' => $this->enQuestions(),
            'tr' => $this->trQuestions(),
            'ru' => $this->ruQuestions(),
            'az' => $this->azQuestions(),
            'fil' => $this->filQuestions(),
            'id' => $this->idQuestions(),
            'uk' => $this->ukQuestions(),
        ];
    }

    private function q(string $id, string $dimension, int $difficulty, string $prompt): array
    {
        return compact('id', 'dimension', 'difficulty', 'prompt');
    }

    // ─── ENGLISH (MASTER) ──────────────────────────────────────────────

    private function enQuestions(): array
    {
        return [
            $this->q('en-lead1', 'leadership', 2, 'Describe a situation where you had to take charge during an unexpected event on board. What did you do and what was the outcome?'),
            $this->q('en-disc1', 'discipline', 2, 'Tell us about a time when following a procedure felt unnecessary but you did it anyway. Why did you follow through?'),
            $this->q('en-conf1', 'conflict_handling', 2, 'Describe a disagreement with a crew member or superior. How did you handle it and what was the resolution?'),
            $this->q('en-cris1', 'crisis_response', 3, 'Tell us about an emergency situation you faced at sea. What actions did you take and how did you prioritize?'),
            $this->q('en-team1', 'team_compatibility', 1, 'How do you adapt when joining a new crew with people from different cultural backgrounds?'),
            $this->q('en-auth1', 'authority_perception', 2, 'Describe a situation where you received an order you disagreed with. How did you respond?'),
            $this->q('en-fatg1', 'fatigue_tolerance', 2, 'Tell us about a time when you had to work extended hours under challenging conditions. How did you maintain your performance?'),
            $this->q('en-resp1', 'responsibility', 2, 'Share an example where you noticed a safety risk outside your direct duties. What did you do?'),
            $this->q('en-comm1', 'communication_clarity', 2, 'Describe a situation where miscommunication caused a problem on board. How was it resolved?'),
            $this->q('en-decm1', 'decision_making', 3, 'Tell us about a time when you had to make a difficult decision with limited information. What was your approach?'),
            $this->q('en-safe1', 'safety_mindset', 2, 'What does safety culture mean to you personally? Give an example from your experience at sea.'),
            $this->q('en-cult1', 'cultural_fit', 1, 'How do you build trust with crew members you are meeting for the first time on a new vessel?'),
        ];
    }

    // ─── TURKISH ───────────────────────────────────────────────────────

    private function trQuestions(): array
    {
        return [
            $this->q('tr-lead1', 'leadership', 2, 'Gemide beklenmedik bir olay sırasında sorumluluk aldığınız bir durumu anlatın. Ne yaptınız ve sonuç ne oldu?'),
            $this->q('tr-disc1', 'discipline', 2, 'Bir prosedürü uygulamanın gereksiz göründüğü ama yine de uyguladığınız bir durumu anlatın. Neden uyguladınız?'),
            $this->q('tr-conf1', 'conflict_handling', 2, 'Bir mürettebat üyesi veya amirinizle yaşadığınız bir anlaşmazlığı anlatın. Nasıl çözdünüz?'),
            $this->q('tr-cris1', 'crisis_response', 3, 'Denizde karşılaştığınız bir acil durumu anlatın. Hangi aksiyonları aldınız ve öncelikleri nasıl belirlediniz?'),
            $this->q('tr-team1', 'team_compatibility', 1, 'Farklı kültürlerden gelen insanlarla yeni bir mürettebata katıldığınızda nasıl uyum sağlarsınız?'),
            $this->q('tr-auth1', 'authority_perception', 2, 'Katılmadığınız bir emir aldığınız bir durumu anlatın. Nasıl davrandınız?'),
            $this->q('tr-fatg1', 'fatigue_tolerance', 2, 'Zor koşullarda uzun saatler çalışmak zorunda kaldığınız bir durumu anlatın. Performansınızı nasıl sürdürdünüz?'),
            $this->q('tr-resp1', 'responsibility', 2, 'Doğrudan görev alanınız dışında bir güvenlik riski fark ettiğiniz bir örnek paylaşın. Ne yaptınız?'),
            $this->q('tr-comm1', 'communication_clarity', 2, 'Gemide yanlış iletişimin bir soruna neden olduğu bir durumu anlatın. Nasıl çözüldü?'),
            $this->q('tr-decm1', 'decision_making', 3, 'Sınırlı bilgiyle zor bir karar vermek zorunda kaldığınız bir durumu anlatın. Yaklaşımınız neydi?'),
            $this->q('tr-safe1', 'safety_mindset', 2, 'Güvenlik kültürü sizin için kişisel olarak ne anlama geliyor? Deniz deneyiminizden bir örnek verin.'),
            $this->q('tr-cult1', 'cultural_fit', 1, 'Yeni bir gemide ilk kez tanıştığınız mürettebat üyeleriyle nasıl güven inşa edersiniz?'),
        ];
    }

    // ─── RUSSIAN ──────────────────────────────────────────────────────

    private function ruQuestions(): array
    {
        return [
            $this->q('ru-lead1', 'leadership', 2, 'Опишите ситуацию, когда вам пришлось взять на себя командование во время непредвиденного события на борту. Что вы сделали и каков был результат?'),
            $this->q('ru-disc1', 'discipline', 2, 'Расскажите о случае, когда выполнение процедуры казалось ненужным, но вы всё равно её выполнили. Почему?'),
            $this->q('ru-conf1', 'conflict_handling', 2, 'Опишите разногласие с членом экипажа или начальником. Как вы его разрешили?'),
            $this->q('ru-cris1', 'crisis_response', 3, 'Расскажите об аварийной ситуации, с которой вы столкнулись в море. Какие действия вы предприняли и как расставили приоритеты?'),
            $this->q('ru-team1', 'team_compatibility', 1, 'Как вы адаптируетесь, присоединяясь к новому экипажу с людьми из разных культур?'),
            $this->q('ru-auth1', 'authority_perception', 2, 'Опишите ситуацию, когда вы получили приказ, с которым не согласились. Как вы отреагировали?'),
            $this->q('ru-fatg1', 'fatigue_tolerance', 2, 'Расскажите о случае, когда вам пришлось работать сверхурочно в тяжёлых условиях. Как вы поддерживали свою работоспособность?'),
            $this->q('ru-resp1', 'responsibility', 2, 'Приведите пример, когда вы заметили угрозу безопасности за пределами ваших прямых обязанностей. Что вы сделали?'),
            $this->q('ru-comm1', 'communication_clarity', 2, 'Опишите ситуацию, когда недопонимание привело к проблеме на борту. Как она была решена?'),
            $this->q('ru-decm1', 'decision_making', 3, 'Расскажите о случае, когда вам пришлось принять сложное решение с ограниченной информацией. Каков был ваш подход?'),
            $this->q('ru-safe1', 'safety_mindset', 2, 'Что для вас лично означает культура безопасности? Приведите пример из вашего морского опыта.'),
            $this->q('ru-cult1', 'cultural_fit', 1, 'Как вы выстраиваете доверие с членами экипажа, с которыми впервые встречаетесь на новом судне?'),
        ];
    }

    // ─── AZERBAIJANI ──────────────────────────────────────────────────

    private function azQuestions(): array
    {
        return [
            $this->q('az-lead1', 'leadership', 2, 'Gəmidə gözlənilməz bir hadisə zamanı rəhbərliyi öz üzərinizə götürdüyünüz bir vəziyyəti təsvir edin. Nə etdiniz və nəticə nə oldu?'),
            $this->q('az-disc1', 'discipline', 2, 'Bir prosedurun lazımsız göründüyü, amma yenə də tətbiq etdiyiniz bir hadisəni danışın. Niyə tətbiq etdiniz?'),
            $this->q('az-conf1', 'conflict_handling', 2, 'Heyət üzvü və ya rəisinizlə yaşadığınız fikir ayrılığını təsvir edin. Necə həll etdiniz?'),
            $this->q('az-cris1', 'crisis_response', 3, 'Dənizdə qarşılaşdığınız fövqəladə vəziyyəti danışın. Hansı addımları atdınız və prioritetləri necə müəyyənləşdirdiniz?'),
            $this->q('az-team1', 'team_compatibility', 1, 'Müxtəlif mədəniyyətlərdən olan insanlarla yeni heyətə qoşulduqda necə uyğunlaşırsınız?'),
            $this->q('az-auth1', 'authority_perception', 2, 'Razı olmadığınız bir əmr aldığınız vəziyyəti təsvir edin. Necə reaksiya verdiniz?'),
            $this->q('az-fatg1', 'fatigue_tolerance', 2, 'Çətin şəraitdə uzun saatlar işləmək məcburiyyətində qaldığınız bir vəziyyəti danışın. Performansınızı necə qorudunuz?'),
            $this->q('az-resp1', 'responsibility', 2, 'Birbaşa vəzifəniz xaricində təhlükəsizlik riski müşahidə etdiyiniz bir nümunə paylaşın. Nə etdiniz?'),
            $this->q('az-comm1', 'communication_clarity', 2, 'Gəmidə yanlış ünsiyyətin problemə səbəb olduğu bir vəziyyəti təsvir edin. Necə həll edildi?'),
            $this->q('az-decm1', 'decision_making', 3, 'Məhdud məlumatla çətin qərar verməli olduğunuz bir vəziyyəti danışın. Yanaşmanız nə idi?'),
            $this->q('az-safe1', 'safety_mindset', 2, 'Təhlükəsizlik mədəniyyəti sizin üçün şəxsən nə deməkdir? Dəniz təcrübənizdən bir nümunə verin.'),
            $this->q('az-cult1', 'cultural_fit', 1, 'Yeni gəmidə ilk dəfə tanış olduğunuz heyət üzvləri ilə necə etibar qurursunuz?'),
        ];
    }

    // ─── FILIPINO ─────────────────────────────────────────────────────

    private function filQuestions(): array
    {
        return [
            $this->q('fil-lead1', 'leadership', 2, 'Ilarawan ang isang sitwasyon kung saan kinailangan mong manguna sa isang hindi inaasahang pangyayari sa barko. Ano ang ginawa mo at ano ang naging resulta?'),
            $this->q('fil-disc1', 'discipline', 2, 'Kwentuhan kami tungkol sa isang pagkakataon na parang hindi kailangan ang isang pamamaraan pero sinunod mo pa rin. Bakit mo sinunod?'),
            $this->q('fil-conf1', 'conflict_handling', 2, 'Ilarawan ang isang hindi pagkakaunawaan sa isang kasamahan sa barko o sa iyong superior. Paano mo ito hinarap?'),
            $this->q('fil-cris1', 'crisis_response', 3, 'Ikwento sa amin ang isang emergency situation na naranasan mo sa dagat. Anong mga aksyon ang ginawa mo at paano mo pinrioridad ang mga ito?'),
            $this->q('fil-team1', 'team_compatibility', 1, 'Paano ka nag-aadjust kapag sumasali sa bagong crew na may iba-ibang cultural background?'),
            $this->q('fil-auth1', 'authority_perception', 2, 'Ilarawan ang isang sitwasyon kung saan nakatanggap ka ng utos na hindi mo sinang-ayunan. Paano ka tumugon?'),
            $this->q('fil-fatg1', 'fatigue_tolerance', 2, 'Ikwento sa amin ang isang pagkakataon na kinailangan mong magtrabaho ng mahabang oras sa mahirap na kondisyon. Paano mo napanatili ang iyong performance?'),
            $this->q('fil-resp1', 'responsibility', 2, 'Magbahagi ng halimbawa kung saan napansin mo ang isang safety risk na wala sa iyong direktang responsibilidad. Ano ang ginawa mo?'),
            $this->q('fil-comm1', 'communication_clarity', 2, 'Ilarawan ang isang sitwasyon kung saan ang miscommunication ay nagdulot ng problema sa barko. Paano ito nalutas?'),
            $this->q('fil-decm1', 'decision_making', 3, 'Ikwento sa amin ang isang pagkakataon na kailangan mong gumawa ng mahirap na desisyon na may limitadong impormasyon. Ano ang iyong approach?'),
            $this->q('fil-safe1', 'safety_mindset', 2, 'Ano ang ibig sabihin ng safety culture para sa iyo? Magbigay ng halimbawa mula sa iyong karanasan sa dagat.'),
            $this->q('fil-cult1', 'cultural_fit', 1, 'Paano ka nagtatayo ng tiwala sa mga crew member na unang beses mong nakakasama sa bagong barko?'),
        ];
    }

    // ─── INDONESIAN ───────────────────────────────────────────────────

    private function idQuestions(): array
    {
        return [
            $this->q('id-lead1', 'leadership', 2, 'Ceritakan situasi di mana Anda harus mengambil kendali selama kejadian tak terduga di kapal. Apa yang Anda lakukan dan bagaimana hasilnya?'),
            $this->q('id-disc1', 'discipline', 2, 'Ceritakan tentang saat mengikuti prosedur terasa tidak perlu tetapi Anda tetap melakukannya. Mengapa?'),
            $this->q('id-conf1', 'conflict_handling', 2, 'Jelaskan perselisihan dengan anggota kru atau atasan. Bagaimana Anda menanganinya?'),
            $this->q('id-cris1', 'crisis_response', 3, 'Ceritakan tentang situasi darurat yang Anda hadapi di laut. Tindakan apa yang Anda ambil dan bagaimana Anda memprioritaskan?'),
            $this->q('id-team1', 'team_compatibility', 1, 'Bagaimana Anda beradaptasi ketika bergabung dengan kru baru dengan latar belakang budaya yang berbeda?'),
            $this->q('id-auth1', 'authority_perception', 2, 'Jelaskan situasi di mana Anda menerima perintah yang tidak Anda setujui. Bagaimana respons Anda?'),
            $this->q('id-fatg1', 'fatigue_tolerance', 2, 'Ceritakan tentang saat Anda harus bekerja berjam-jam dalam kondisi menantang. Bagaimana Anda menjaga performa?'),
            $this->q('id-resp1', 'responsibility', 2, 'Bagikan contoh di mana Anda menyadari risiko keselamatan di luar tugas langsung Anda. Apa yang Anda lakukan?'),
            $this->q('id-comm1', 'communication_clarity', 2, 'Jelaskan situasi di mana miskomunikasi menyebabkan masalah di kapal. Bagaimana hal itu diselesaikan?'),
            $this->q('id-decm1', 'decision_making', 3, 'Ceritakan tentang saat Anda harus membuat keputusan sulit dengan informasi terbatas. Apa pendekatan Anda?'),
            $this->q('id-safe1', 'safety_mindset', 2, 'Apa arti budaya keselamatan bagi Anda secara pribadi? Berikan contoh dari pengalaman Anda di laut.'),
            $this->q('id-cult1', 'cultural_fit', 1, 'Bagaimana Anda membangun kepercayaan dengan anggota kru yang baru pertama kali Anda temui di kapal baru?'),
        ];
    }

    // ─── UKRAINIAN ────────────────────────────────────────────────────

    private function ukQuestions(): array
    {
        return [
            $this->q('uk-lead1', 'leadership', 2, 'Опишіть ситуацію, коли вам довелося взяти командування під час непередбаченої події на борту. Що ви зробили і який був результат?'),
            $this->q('uk-disc1', 'discipline', 2, 'Розкажіть про випадок, коли виконання процедури здавалось непотрібним, але ви все одно її виконали. Чому?'),
            $this->q('uk-conf1', 'conflict_handling', 2, 'Опишіть розбіжності з членом екіпажу або керівником. Як ви їх вирішили?'),
            $this->q('uk-cris1', 'crisis_response', 3, 'Розкажіть про аварійну ситуацію, з якою ви зіткнулися в морі. Які дії ви вжили та як розставили пріоритети?'),
            $this->q('uk-team1', 'team_compatibility', 1, 'Як ви адаптуєтесь, приєднуючись до нового екіпажу з людьми з різних культур?'),
            $this->q('uk-auth1', 'authority_perception', 2, 'Опишіть ситуацію, коли ви отримали наказ, з яким не погоджувались. Як ви відреагували?'),
            $this->q('uk-fatg1', 'fatigue_tolerance', 2, 'Розкажіть про випадок, коли вам довелося працювати понаднормово в складних умовах. Як ви підтримували свою працездатність?'),
            $this->q('uk-resp1', 'responsibility', 2, 'Наведіть приклад, коли ви помітили загрозу безпеці поза межами ваших прямих обов\'язків. Що ви зробили?'),
            $this->q('uk-comm1', 'communication_clarity', 2, 'Опишіть ситуацію, коли непорозуміння призвело до проблеми на борту. Як вона була вирішена?'),
            $this->q('uk-decm1', 'decision_making', 3, 'Розкажіть про випадок, коли вам довелося прийняти складне рішення з обмеженою інформацією. Який був ваш підхід?'),
            $this->q('uk-safe1', 'safety_mindset', 2, 'Що для вас особисто означає культура безпеки? Наведіть приклад з вашого морського досвіду.'),
            $this->q('uk-cult1', 'cultural_fit', 1, 'Як ви будуєте довіру з членами екіпажу, з якими вперше зустрічаєтесь на новому судні?'),
        ];
    }
}
