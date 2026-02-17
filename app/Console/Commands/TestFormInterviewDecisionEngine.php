<?php

namespace App\Console\Commands;

use App\Models\FormInterview;
use App\Models\FormInterviewAnswer;
use App\Services\DecisionEngine\FormInterviewDecisionEngineAdapter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestFormInterviewDecisionEngine extends Command
{
    protected $signature = 'form-interview:test-decision-engine';
    protected $description = 'Test DecisionEngine integration with FormInterview';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════════════════════════════════╗');
        $this->info('║           FORM INTERVIEW DECISION ENGINE INTEGRATION TEST                     ║');
        $this->info('╚═══════════════════════════════════════════════════════════════════════════════╝');
        $this->info('');

        $adapter = app(FormInterviewDecisionEngineAdapter::class);

        // Test 1: Strong candidate (detailed answers)
        $this->testCandidate($adapter, 'Strong Candidate', 'retail_cashier', [
            ['slot' => 1, 'competency' => 'communication', 'answer_text' => 'Musteri ile iletisim kurma konusunda kendimi cok gelistirdim. Zor musterilerle bile sabir ve empati ile yaklasarak cozum odakli konusmalar yapiyorum. Ornegin gecen ay bir musteri cok sinirli gelmisti ama onu dinleyerek ve anlayis gostererek sorunu cozdum ve tesekkur ederek ayrildi.'],
            ['slot' => 2, 'competency' => 'accountability', 'answer_text' => 'Hatalarimdan her zaman ders cikaririm. Gecen ay kasada bir hata yaptim ve hemen mudurume bildirdim. Birlikte cozum urettik ve bu tecrubeden ogrendiklerimi ekibimle paylastim. Sorumluluk almak benim icin cok onemli.'],
            ['slot' => 3, 'competency' => 'teamwork', 'answer_text' => 'Ekip calismasi benim icin cok degerli. Yogun saatlerde arkadaslarima yardim ederim, onlar da bana. Birlikte calismak isin kalitesini artiriyor. Aylik toplantilarda fikirlerimi paylasir ve baskalarin fikirlerini de dinlerim.'],
            ['slot' => 4, 'competency' => 'stress_resilience', 'answer_text' => 'Stresli ortamlarda sakin kalmaya calisiyorum. Yogun gunlerde once islerimi onceliklendiririm, sonra tek tek hallederim. Derin nefes alarak ve pozitif dusunurek stresi yonetiyorum. Yilbasinda 500 musteri ile ilgilendik ve basariyla atlattik.'],
            ['slot' => 5, 'competency' => 'adaptability', 'answer_text' => 'Degisime acigim. Yeni kasa sistemi geldiginde hemen ogrenmek istedim ve bir haftada ustesinden geldim. Farkli vardiyalarda calisabiliyorum ve ani degisikliklere hizla uyum sagliyorum.'],
            ['slot' => 6, 'competency' => 'learning_agility', 'answer_text' => 'Surekli ogrenmeyi seviyorum. Online egitimler aliyor, yeni urunler hakkinda bilgi ediniyorum. Her firsatta kendimi gelistirmeye calisiyorum. Gecen ay Excel kursu aldim ve raporlarimi daha iyi hazirliyorum artik.'],
            ['slot' => 7, 'competency' => 'integrity', 'answer_text' => 'Durustluk benim icin en onemli deger. Musteri fazla para verirse hemen uyaririm. Kasa sayimlarinda tutarsizlik olursa hemen bildiririm. Guven kazanmak zor, kaybetmek cok kolay.'],
            ['slot' => 8, 'competency' => 'role_competence', 'answer_text' => 'Kasiyerlik konusunda 3 yillik deneyimim var. Kasa acma/kapama, sayim, iade islemleri, kredi karti islemleri ve stok kontrolu konularinda tecrubem var. POS sistemlerini iyi kullaniyorum.'],
        ]);

        // Test 2: Weak candidate (short answers)
        $this->testCandidate($adapter, 'Weak Candidate', 'retail_cashier', [
            ['slot' => 1, 'competency' => 'communication', 'answer_text' => 'Iyi konusurum.'],
            ['slot' => 2, 'competency' => 'accountability', 'answer_text' => 'Evet.'],
            ['slot' => 3, 'competency' => 'teamwork', 'answer_text' => 'Ekiple calisirim.'],
            ['slot' => 4, 'competency' => 'stress_resilience', 'answer_text' => 'Strese dayanikli.'],
            ['slot' => 5, 'competency' => 'adaptability', 'answer_text' => 'Uyum saglarim.'],
            ['slot' => 6, 'competency' => 'learning_agility', 'answer_text' => 'Ogrenirim.'],
            ['slot' => 7, 'competency' => 'integrity', 'answer_text' => 'Durustum.'],
            ['slot' => 8, 'competency' => 'role_competence', 'answer_text' => 'Kasada calistim.'],
        ]);

        // Test 3: Red flag candidate (toxic language)
        $this->testCandidate($adapter, 'Red Flag Candidate', 'retail_cashier', [
            ['slot' => 1, 'competency' => 'communication', 'answer_text' => 'Musteri ile iletisim kuruyorum ama bazen onlarin hatasi yuzunden sorun cikiyor.'],
            ['slot' => 2, 'competency' => 'accountability', 'answer_text' => 'Hatalar genelde benim hatam degil, ekip arkadaslarim dinlemedi veya yoneticiler yuzunden oluyor.'],
            ['slot' => 3, 'competency' => 'teamwork', 'answer_text' => 'En iyi ben calisiyorum, digerleri yetersiz. Ben cozerim genelde.'],
            ['slot' => 4, 'competency' => 'stress_resilience', 'answer_text' => 'Stres mi? Aptal insanlar stres yapiyor sadece.'],
            ['slot' => 5, 'competency' => 'adaptability', 'answer_text' => 'Degisiklik istemiyorum, benim isim degil.'],
            ['slot' => 6, 'competency' => 'learning_agility', 'answer_text' => 'Yeni seyler yapmam, ugrasimam.'],
            ['slot' => 7, 'competency' => 'integrity', 'answer_text' => 'Ben karismam baska seylere.'],
            ['slot' => 8, 'competency' => 'role_competence', 'answer_text' => 'Kasada calistim ama kisa sureli, cok is degistirdim.'],
        ]);

        // Test 4: Incomplete answers
        $this->testCandidate($adapter, 'Incomplete Candidate', 'retail_cashier', [
            ['slot' => 1, 'competency' => 'communication', 'answer_text' => 'Iletisim kuruyorum, musterilerle iyi anlasiyorum.'],
            ['slot' => 2, 'competency' => 'accountability', 'answer_text' => 'Sorumluluk alirim, hatalarimi kabul ederim.'],
            ['slot' => 3, 'competency' => 'teamwork', 'answer_text' => ''],
            ['slot' => 4, 'competency' => 'stress_resilience', 'answer_text' => ''],
            ['slot' => 5, 'competency' => 'adaptability', 'answer_text' => ''],
            ['slot' => 6, 'competency' => 'learning_agility', 'answer_text' => ''],
            ['slot' => 7, 'competency' => 'integrity', 'answer_text' => 'Durustluk onemli.'],
            ['slot' => 8, 'competency' => 'role_competence', 'answer_text' => ''],
        ]);

        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════════════════════');
        $this->info(' All tests completed successfully!');
        $this->info('═══════════════════════════════════════════════════════════════════════════════');
        $this->info('');

        return self::SUCCESS;
    }

    private function testCandidate(FormInterviewDecisionEngineAdapter $adapter, string $name, string $positionCode, array $answers): void
    {
        $this->info("┌─────────────────────────────────────────────────────────────────────────────┐");
        $this->info("│ TEST: {$name}");
        $this->info("├─────────────────────────────────────────────────────────────────────────────┤");

        // Create temporary interview
        $interview = new FormInterview();
        $interview->id = Str::uuid();
        $interview->position_code = $positionCode;
        $interview->version = 'v1';
        $interview->language = 'tr';
        $interview->status = 'in_progress';

        // Mock answers relationship
        $answerModels = collect($answers)->map(function ($a) use ($interview) {
            $answer = new FormInterviewAnswer();
            $answer->id = Str::uuid();
            $answer->form_interview_id = $interview->id;
            $answer->slot = $a['slot'];
            $answer->competency = $a['competency'];
            $answer->answer_text = $a['answer_text'];
            return $answer;
        });

        // Override answers method for testing
        $interview->setRelation('answers', $answerModels);

        // Evaluate
        $result = $adapter->evaluate($interview);

        // Display results
        $this->line("│ Position: {$positionCode}");
        $this->line("│ ");
        $this->line("│ Competency Scores:");

        foreach ($result['competency_scores'] as $comp => $score) {
            $bar = str_repeat('█', (int) ($score / 5)) . str_repeat('░', 20 - (int) ($score / 5));
            $this->line("│   {$comp}: {$score}% {$bar}");
        }

        $this->line("│ ");
        $this->line("│ Base Score: " . number_format($result['base_score'], 2) . "%");
        $this->line("│ Risk Penalty: -{$result['risk_penalty']} pts");
        $this->line("│ Red Flag Penalty: -{$result['red_flag_penalty']} pts");
        $this->line("│ Final Score: {$result['final_score']}%");
        $this->line("│ ");

        // Skill gate
        $gateStatus = $result['skill_gate']['passed'] ? '✓ PASS' : '✗ FAIL';
        $this->line("│ Skill Gate: {$gateStatus} (role_competence {$result['skill_gate']['role_competence']}% vs gate {$result['skill_gate']['gate']}%)");

        // Red flags
        if (!empty($result['risk_flags'])) {
            $this->line("│ ");
            $this->line("│ Red Flags Detected:");
            foreach ($result['risk_flags'] as $flag) {
                $this->line("│   ⚠ {$flag['code']}: {$flag['name']} (severity: {$flag['severity']}, penalty: -{$flag['penalty']})");
                $this->line("│     Evidence: " . implode(', ', $flag['evidence']));
            }
        }

        // Decision
        $this->line("│ ");
        $decisionColor = match ($result['decision']) {
            'HIRE' => 'green',
            'HOLD' => 'yellow',
            'REJECT' => 'red',
            default => 'white',
        };
        $this->line("│ <fg={$decisionColor};options=bold>DECISION: {$result['decision']}</>");
        $this->line("│ Reason: {$result['decision_reason']}");
        $this->info("└─────────────────────────────────────────────────────────────────────────────┘");
        $this->info('');
    }
}
