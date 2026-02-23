<?php

namespace Database\Seeders;

use App\Models\InterviewQuestionSet;
use Illuminate\Database\Seeder;

/**
 * Seeds interview_question_sets with 12-question behavioral interview sets
 * for 7 locales (TR master + EN/RU/AZ/FIL/ID/UK).
 *
 * Each question targets one of 7 behavioral dimensions:
 * responsibility, teamwork, decision_under_pressure, communication,
 * discipline, leadership, adaptability
 */
class BehavioralInterviewV2Seeder extends Seeder
{
    private const CODE = 'maritime_behavioral_v2';
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
                        'dimensions' => ['responsibility', 'teamwork', 'decision_under_pressure', 'communication', 'discipline', 'leadership', 'adaptability'],
                        'scoring_scale' => ['min' => 1, 'max' => 5],
                    ],
                    'questions_json' => $questions,
                ]
            );
        }

        $this->command->info('Seeded behavioral interview v2 question sets for 7 locales.');
    }

    private function getAllLocaleQuestions(): array
    {
        return [
            'tr' => $this->trQuestions(),
            'en' => $this->enQuestions(),
            'ru' => $this->ruQuestions(),
            'az' => $this->azQuestions(),
            'fil' => $this->filQuestions(),
            'id' => $this->idQuestions(),
            'uk' => $this->ukQuestions(),
        ];
    }

    // ─── TURKISH (MASTER) ───────────────────────────────────────────────

    private function trQuestions(): array
    {
        return [
            $this->q('tr-r1', 'responsibility', 1, 'Göreviniz dışında bir risk fark ettiğiniz bir durumu anlatır mısınız? Nasıl müdahale ettiniz?'),
            $this->q('tr-r2', 'responsibility', 2, 'Bir hata yaptığınızda bunu nasıl yönettiniz?'),
            $this->q('tr-r3', 'responsibility', 2, 'Güvenliği sağlamak için inisiyatif aldığınız bir örnek paylaşır mısınız?'),
            $this->q('tr-t1', 'teamwork', 1, 'Farklı milletlerden oluşan bir mürettebatla çalışırken yaşadığınız bir deneyimi anlatın.'),
            $this->q('tr-t2', 'teamwork', 2, 'Bir ekip üyesi görevini yerine getirmediğinde nasıl davrandınız?'),
            $this->q('tr-t3', 'teamwork', 3, 'Üstünüzle görüş ayrılığı yaşadığınız bir durumda nasıl hareket ettiniz?'),
            $this->q('tr-d1', 'decision_under_pressure', 2, 'Acil bir durumda hızlı karar vermeniz gereken bir anı anlatın.'),
            $this->q('tr-d2', 'decision_under_pressure', 3, 'Operasyon ile güvenlik arasında tercih yapmak zorunda kaldığınız oldu mu?'),
            $this->q('tr-c1', 'communication', 1, 'Karmaşık bir talimatı ekibe nasıl aktardınız?'),
            $this->q('tr-c2', 'communication', 2, 'Yanlış anlaşılmayı nasıl düzelttiniz?'),
            $this->q('tr-p1', 'discipline', 2, 'Prosedürlere uymanın zor olduğu bir durumda ne yaptınız?'),
            $this->q('tr-l1', 'leadership', 3, 'Bir kriz anında sorumluluk aldığınız bir durumu anlatın.'),
        ];
    }

    // ─── ENGLISH ────────────────────────────────────────────────────────

    private function enQuestions(): array
    {
        return [
            $this->q('en-r1', 'responsibility', 1, 'Describe a situation where you noticed a risk outside your direct responsibilities. How did you respond?'),
            $this->q('en-r2', 'responsibility', 2, 'How did you handle a mistake you made on board?'),
            $this->q('en-r3', 'responsibility', 2, 'Can you share an example where you took initiative to ensure safety?'),
            $this->q('en-t1', 'teamwork', 1, 'Tell us about an experience working with a multinational crew.'),
            $this->q('en-t2', 'teamwork', 2, 'What did you do when a crew member failed to carry out their duties?'),
            $this->q('en-t3', 'teamwork', 3, 'How did you handle a disagreement with a superior officer?'),
            $this->q('en-d1', 'decision_under_pressure', 2, 'Describe a moment when you had to make a quick decision in an emergency.'),
            $this->q('en-d2', 'decision_under_pressure', 3, 'Have you ever had to choose between operational efficiency and safety?'),
            $this->q('en-c1', 'communication', 1, 'How did you convey a complex instruction to your crew?'),
            $this->q('en-c2', 'communication', 2, 'How did you resolve a miscommunication on board?'),
            $this->q('en-p1', 'discipline', 2, 'What did you do in a situation where following procedures was difficult?'),
            $this->q('en-l1', 'leadership', 3, 'Describe a crisis situation where you took charge.'),
        ];
    }

    // ─── RUSSIAN (Formal, Hierarchy-aware) ──────────────────────────────

    private function ruQuestions(): array
    {
        return [
            $this->q('ru-r1', 'responsibility', 1, 'Опишите ситуацию, когда вы взяли на себя ответственность вне своих обязанностей.'),
            $this->q('ru-r2', 'responsibility', 2, 'Как вы действовали при допущенной ошибке?'),
            $this->q('ru-r3', 'responsibility', 2, 'Приходилось ли вам принимать срочные решения в условиях риска?'),
            $this->q('ru-t1', 'teamwork', 1, 'Как вы взаимодействуете с многонациональным экипажем?'),
            $this->q('ru-t2', 'teamwork', 2, 'Что вы делаете при несогласии с капитаном?'),
            $this->q('ru-t3', 'teamwork', 3, 'Как вы предотвращаете конфликт в экипаже?'),
            $this->q('ru-d1', 'decision_under_pressure', 2, 'Как вы реагируете на давление времени?'),
            $this->q('ru-d2', 'decision_under_pressure', 3, 'Опишите кризисную ситуацию, в которой вы участвовали.'),
            $this->q('ru-c1', 'communication', 1, 'Как вы обеспечиваете соблюдение процедур безопасности?'),
            $this->q('ru-c2', 'communication', 2, 'Как вы мотивируете коллег?'),
            $this->q('ru-p1', 'discipline', 2, 'Как вы поддерживаете дисциплину на борту?'),
            $this->q('ru-l1', 'leadership', 3, 'Как вы адаптируетесь к новому судну?'),
        ];
    }

    // ─── AZERBAIJANI (Practical, operational) ───────────────────────────

    private function azQuestions(): array
    {
        return [
            $this->q('az-r1', 'responsibility', 1, 'Öz vəzifənizdən kənar məsuliyyət götürdüyünüz vəziyyəti izah edin.'),
            $this->q('az-r2', 'responsibility', 2, 'Səhv etdiyiniz zaman necə davranırsınız?'),
            $this->q('az-r3', 'responsibility', 2, 'Risk gördükdə nə edirsiniz?'),
            $this->q('az-t1', 'teamwork', 1, 'Fərqli millətlərdən ibarət heyətlə iş təcrübəniz.'),
            $this->q('az-t2', 'teamwork', 2, 'Kapitanla fikir ayrılığı olduqda nə edirsiniz?'),
            $this->q('az-t3', 'teamwork', 3, 'Konflikti necə idarə edirsiniz?'),
            $this->q('az-d1', 'decision_under_pressure', 2, 'Təzyiq altında qərar verdiyiniz hadisə.'),
            $this->q('az-d2', 'decision_under_pressure', 3, 'Böhran vəziyyətində rolunuz nə olub?'),
            $this->q('az-c1', 'communication', 1, 'Təhlükəsizlik qaydalarına riayət etməyin vacibliyini necə təmin edirsiniz?'),
            $this->q('az-c2', 'communication', 2, 'Məsuliyyəti bölüşdürmə təcrübəniz.'),
            $this->q('az-p1', 'discipline', 2, 'Yeni gəmi mühitinə necə uyğunlaşırsınız?'),
            $this->q('az-l1', 'leadership', 3, 'Uzun kontraktda motivasiyanı necə qoruyursunuz?'),
        ];
    }

    // ─── FILIPINO (Team-centric, solidarity) ────────────────────────────

    private function filQuestions(): array
    {
        return [
            $this->q('fil-r1', 'responsibility', 1, 'Ikuwento ang pagkakataong kumuha ka ng responsibilidad para sa kaligtasan ng crew.'),
            $this->q('fil-r2', 'responsibility', 2, 'Paano mo hinaharap ang pagkakamali sa trabaho?'),
            $this->q('fil-r3', 'responsibility', 2, 'Ikuwento ang isang sitwasyong may panganib.'),
            $this->q('fil-t1', 'teamwork', 1, 'Paano ka nakikipagtulungan sa crew na may iba\'t ibang kultura?'),
            $this->q('fil-t2', 'teamwork', 2, 'Ano ang ginagawa mo kapag may hindi sumusunod sa alituntunin?'),
            $this->q('fil-t3', 'teamwork', 3, 'Paano mo sinusuportahan ang kapitan?'),
            $this->q('fil-d1', 'decision_under_pressure', 2, 'Ikuwento ang isang emergency situation na hinarap mo.'),
            $this->q('fil-d2', 'decision_under_pressure', 3, 'Ano ang ginagawa mo kapag may pressure sa oras?'),
            $this->q('fil-c1', 'communication', 1, 'Paano mo inaayos ang hindi pagkakaunawaan?'),
            $this->q('fil-c2', 'communication', 2, 'Paano mo pinananatili ang teamwork?'),
            $this->q('fil-p1', 'discipline', 2, 'Paano mo pinananatili ang disiplina sa barko?'),
            $this->q('fil-l1', 'leadership', 3, 'Paano ka nag-a-adjust sa bagong kontrata?'),
        ];
    }

    // ─── INDONESIAN (Procedure-focused) ─────────────────────────────────

    private function idQuestions(): array
    {
        return [
            $this->q('id-r1', 'responsibility', 1, 'Jelaskan situasi ketika Anda mengambil tanggung jawab tambahan.'),
            $this->q('id-r2', 'responsibility', 2, 'Bagaimana Anda menangani kesalahan kerja?'),
            $this->q('id-r3', 'responsibility', 2, 'Bagaimana Anda menjaga keselamatan operasional?'),
            $this->q('id-t1', 'teamwork', 1, 'Bagaimana Anda bekerja dengan kru multinasional?'),
            $this->q('id-t2', 'teamwork', 2, 'Apa yang Anda lakukan jika terjadi konflik di kapal?'),
            $this->q('id-t3', 'teamwork', 3, 'Ceritakan peran kepemimpinan Anda.'),
            $this->q('id-d1', 'decision_under_pressure', 2, 'Ceritakan keputusan penting dalam kondisi darurat.'),
            $this->q('id-d2', 'decision_under_pressure', 3, 'Bagaimana Anda menghadapi tekanan waktu?'),
            $this->q('id-c1', 'communication', 1, 'Bagaimana Anda berkomunikasi dalam situasi kritis?'),
            $this->q('id-c2', 'communication', 2, 'Bagaimana Anda memastikan kepatuhan terhadap prosedur?'),
            $this->q('id-p1', 'discipline', 2, 'Bagaimana Anda beradaptasi dengan kapal baru?'),
            $this->q('id-l1', 'leadership', 3, 'Bagaimana Anda menjaga motivasi selama kontrak panjang?'),
        ];
    }

    // ─── UKRAINIAN (Safety and responsibility) ──────────────────────────

    private function ukQuestions(): array
    {
        return [
            $this->q('uk-r1', 'responsibility', 1, 'Опишіть ситуацію, коли ви взяли на себе додаткову відповідальність.'),
            $this->q('uk-r2', 'responsibility', 2, 'Як ви діяли у випадку помилки?'),
            $this->q('uk-r3', 'responsibility', 2, 'Як ви підтримуєте безпеку?'),
            $this->q('uk-t1', 'teamwork', 1, 'Як ви співпрацюєте з міжнародним екіпажем?'),
            $this->q('uk-t2', 'teamwork', 2, 'Як ви вирішуєте конфлікти на борту?'),
            $this->q('uk-t3', 'teamwork', 3, 'Який у вас досвід лідерства?'),
            $this->q('uk-d1', 'decision_under_pressure', 2, 'Розкажіть про прийняття рішення в аварійній ситуації.'),
            $this->q('uk-d2', 'decision_under_pressure', 3, 'Опишіть кризову ситуацію, в якій ви брали участь.'),
            $this->q('uk-c1', 'communication', 1, 'Як ви забезпечуєте дотримання процедур?'),
            $this->q('uk-c2', 'communication', 2, 'Як ви реагуєте на тиск часу?'),
            $this->q('uk-p1', 'discipline', 2, 'Як ви підтримуєте дисципліну?'),
            $this->q('uk-l1', 'leadership', 3, 'Як ви адаптуєтесь до нового судна?'),
        ];
    }

    // ─── Helper: build question object ──────────────────────────────────

    private function q(string $id, string $dimension, int $difficulty, string $prompt): array
    {
        return [
            'id' => $id,
            'dimension' => $dimension,
            'difficulty' => $difficulty,
            'prompt' => $prompt,
            'rubric' => [
                'high' => 'Specific maritime example with clear STAR structure, ownership, and outcome.',
                'mid' => 'Relevant answer with some detail but lacking specificity or outcome.',
                'low' => 'Vague, generic, or no relevant example provided.',
            ],
            'red_flags' => [],
        ];
    }
}
