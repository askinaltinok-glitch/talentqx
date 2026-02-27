<?php

namespace Database\Seeders;

use App\Models\OrgQuestionnaire;
use App\Models\OrgQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrgHealthWorkstyleV1Seeder extends Seeder
{
    public function run(): void
    {
        $schema = json_decode($this->schemaJson(), true);

        $q = OrgQuestionnaire::query()->updateOrCreate(
            [
                'tenant_id' => null,
                'code' => 'workstyle',
                'version' => 'v1',
            ],
            [
                'status' => 'active',
                'title' => ['tr' => 'WorkStyle', 'en' => 'WorkStyle'],
                'description' => ['tr' => 'Çalışma tarzı boyutları profili', 'en' => 'Work style dimensions profile'],
                'scoring_schema' => $schema,
                'created_at' => now(),
            ]
        );

        // Clear existing questions for idempotency
        OrgQuestion::query()->where('questionnaire_id', $q->id)->delete();

        foreach ($this->questions() as $item) {
            OrgQuestion::create([
                'id' => (string) Str::uuid(),
                'questionnaire_id' => $q->id,
                'dimension' => $item['dimension'],
                'is_reverse' => $item['reverse'],
                'sort_order' => $item['order'],
                'text' => ['tr' => $item['tr'], 'en' => $item['en']],
                'created_at' => now(),
            ]);
        }
    }

    private function schemaJson(): string
    {
        return <<<'JSON'
{
  "code": "workstyle",
  "version": "v1",
  "likert": {
    "min": 1,
    "max": 5,
    "anchors": {
      "tr": {
        "1": "Kesinlikle katılmıyorum",
        "2": "Katılmıyorum",
        "3": "Kararsızım",
        "4": "Katılıyorum",
        "5": "Kesinlikle katılıyorum"
      },
      "en": {
        "1": "Strongly disagree",
        "2": "Disagree",
        "3": "Neutral",
        "4": "Agree",
        "5": "Strongly agree"
      }
    }
  },
  "reverse_coding": {
    "formula": "reversed = 6 - value"
  },
  "dimensions": [
    { "key": "planning", "label": { "tr": "Planlama & Disiplin", "en": "Planning & Discipline" }, "items": 8 },
    { "key": "social", "label": { "tr": "Sosyal Enerji", "en": "Social Energy" }, "items": 8 },
    { "key": "cooperation", "label": { "tr": "İş Birliği", "en": "Cooperation" }, "items": 8 },
    { "key": "stability", "label": { "tr": "Stres Dayanıklılığı", "en": "Stress Stability" }, "items": 8 },
    { "key": "adaptability", "label": { "tr": "Uyum & Esneklik", "en": "Adaptability" }, "items": 8 }
  ],
  "normalization": {
    "raw_min": 8,
    "raw_max": 40,
    "formula": "score = ((raw - 8) / 32) * 100",
    "decimals": 2
  },
  "bands": [
    { "min": 0, "max": 39, "label": { "tr": "Düşük eğilim", "en": "Low tendency" } },
    { "min": 40, "max": 69, "label": { "tr": "Dengeli", "en": "Balanced" } },
    { "min": 70, "max": 100, "label": { "tr": "Yüksek eğilim", "en": "High tendency" } }
  ],
  "disclaimer": {
    "tr": "Bu çıktı tanı koymaz; yönetsel içgörü üretir.",
    "en": "This output is not diagnostic; it provides managerial insights."
  }
}
JSON;
    }

    private function questions(): array
    {
        return [
            // planning 1-8
            ['order'=>1,'dimension'=>'planning','reverse'=>false,'tr'=>'İşe başlamadan önce adımları planlamak bana doğal gelir.','en'=>'Planning the steps before starting work comes naturally to me.'],
            ['order'=>2,'dimension'=>'planning','reverse'=>false,'tr'=>'Son teslim tarihleri yaklaşmadan önce işleri parça parça ilerletmeyi tercih ederim.','en'=>'I prefer to advance work in parts before deadlines get close.'],
            ['order'=>3,'dimension'=>'planning','reverse'=>false,'tr'=>'Detayların gözden kaçmaması için kontrol listeleri veya notlar kullanırım.','en'=>'I use checklists or notes to avoid missing details.'],
            ['order'=>4,'dimension'=>'planning','reverse'=>false,'tr'=>'Öncelikleri netleştirip ona göre ilerlediğimde daha verimli olurum.','en'=>'I am more efficient when I clarify priorities and proceed accordingly.'],
            ['order'=>5,'dimension'=>'planning','reverse'=>false,'tr'=>'Düzenli bir çalışma düzeni kurduğumda performansım artar.','en'=>'My performance improves when I establish a consistent work routine.'],
            ['order'=>6,'dimension'=>'planning','reverse'=>true,'tr'=>'Genelde plan yapmadan, o an ne çıkarsa onunla ilerlerim.','en'=>'I usually proceed without planning, dealing with whatever comes up.'],
            ['order'=>7,'dimension'=>'planning','reverse'=>true,'tr'=>'İşleri son ana bırakmak benim için sorun değildir.','en'=>'Leaving tasks to the last minute is not a problem for me.'],
            ['order'=>8,'dimension'=>'planning','reverse'=>true,'tr'=>'Aynı anda birçok işe atlayıp sonra toparlamayı tercih ederim.','en'=>'I prefer jumping into many tasks at once and organizing later.'],

            // social 9-16
            ['order'=>9,'dimension'=>'social','reverse'=>false,'tr'=>'Ekip içinde fikir alışverişi yapmak beni motive eder.','en'=>'Exchanging ideas within a team motivates me.'],
            ['order'=>10,'dimension'=>'social','reverse'=>false,'tr'=>'İş arkadaşlarımla iletişim kurmak, işi ilerletmemi kolaylaştırır.','en'=>'Communicating with colleagues makes it easier for me to move work forward.'],
            ['order'=>11,'dimension'=>'social','reverse'=>false,'tr'=>'Toplantılarda düşüncelerimi net şekilde paylaşabilirim.','en'=>'I can share my thoughts clearly in meetings.'],
            ['order'=>12,'dimension'=>'social','reverse'=>false,'tr'=>'Yeni insanlarla tanışıp hızlıca iş ilişkisi kurabilirim.','en'=>'I can meet new people and quickly build a working relationship.'],
            ['order'=>13,'dimension'=>'social','reverse'=>false,'tr'=>'Yoğun bir günde bile kısa bir sohbet enerjimi yükseltebilir.','en'=>'Even on a busy day, a brief chat can boost my energy.'],
            ['order'=>14,'dimension'=>'social','reverse'=>true,'tr'=>'İş ortamında mümkün olduğunca az iletişim kurmayı tercih ederim.','en'=>'In a work environment, I prefer to communicate as little as possible.'],
            ['order'=>15,'dimension'=>'social','reverse'=>true,'tr'=>'Kalabalık ekip ortamları beni genelde yorar.','en'=>'Crowded team environments usually drain me.'],
            ['order'=>16,'dimension'=>'social','reverse'=>true,'tr'=>'İşle ilgili konularda bile konuşmak yerine yazışmayı her zaman tercih ederim.','en'=>'Even for work topics, I always prefer messaging over talking.'],

            // cooperation 17-24
            ['order'=>17,'dimension'=>'cooperation','reverse'=>false,'tr'=>'Ekip hedefi için gerekirse kendi işimi uyarlayıp destek olabilirim.','en'=>'For the team goal, I can adapt my work and support when needed.'],
            ['order'=>18,'dimension'=>'cooperation','reverse'=>false,'tr'=>'Farklı görüşler olduğunda ortak bir çözüm bulmaya çalışırım.','en'=>'When there are differing views, I try to find a common solution.'],
            ['order'=>19,'dimension'=>'cooperation','reverse'=>false,'tr'=>'Geri bildirim verirken yapıcı olmaya özen gösteririm.','en'=>'I try to be constructive when giving feedback.'],
            ['order'=>20,'dimension'=>'cooperation','reverse'=>false,'tr'=>'Bir işi paylaşırken sorumlulukların net olmasına önem veririm.','en'=>'When sharing work, I value having responsibilities clearly defined.'],
            ['order'=>21,'dimension'=>'cooperation','reverse'=>false,'tr'=>"Başkalarının yükünü azaltacak küçük katkıların önemli olduğuna inanırım.",'en'=>"I believe small contributions that reduce others' load are important."],
            ['order'=>22,'dimension'=>'cooperation','reverse'=>true,'tr'=>'Ekip çalışmasında genelde kendi yöntemimin doğru olduğunda ısrar ederim.','en'=>'In teamwork, I usually insist that my way is the right way.'],
            ['order'=>23,'dimension'=>'cooperation','reverse'=>true,'tr'=>"Bir sorun çıktığında başkalarının hatasını bulmak benim için daha önceliklidir.",'en'=>"When a problem occurs, finding others' mistakes is my priority."],
            ['order'=>24,'dimension'=>'cooperation','reverse'=>true,'tr'=>'Yardım istemek yerine tek başıma halletmeyi tercih ederim, paylaşmak zaman kaybı gibi gelir.','en'=>'I prefer handling things alone rather than asking for help; sharing feels like a waste of time.'],

            // stability 25-32
            ['order'=>25,'dimension'=>'stability','reverse'=>false,'tr'=>'Baskı altında sakin kalıp işe odaklanabilirim.','en'=>'Under pressure, I can stay calm and focus on work.'],
            ['order'=>26,'dimension'=>'stability','reverse'=>false,'tr'=>'Beklenmedik sorunlarda çözüm aramaya hızlıca geçebilirim.','en'=>'When unexpected issues arise, I can quickly shift into problem-solving.'],
            ['order'=>27,'dimension'=>'stability','reverse'=>false,'tr'=>'Yoğun dönemlerde bile önceliklerimi koruyabilirim.','en'=>'Even in busy periods, I can maintain my priorities.'],
            ['order'=>28,'dimension'=>'stability','reverse'=>false,'tr'=>'Zor geri bildirim alsam bile bunu geliştirmek için kullanabilirim.','en'=>'Even if I receive tough feedback, I can use it to improve.'],
            ['order'=>29,'dimension'=>'stability','reverse'=>false,'tr'=>'Hata yaptığımda paniğe kapılmadan düzeltme planı yapabilirim.','en'=>'When I make a mistake, I can plan a fix without panicking.'],
            ['order'=>30,'dimension'=>'stability','reverse'=>true,'tr'=>"İş baskısı arttığında kontrolü kaybetmiş gibi hissederim.",'en'=>"When work pressure increases, I feel like I'm losing control."],
            ['order'=>31,'dimension'=>'stability','reverse'=>true,'tr'=>'Stresli durumlarda karar vermekte zorlanırım.','en'=>'In stressful situations, I struggle to make decisions.'],
            ['order'=>32,'dimension'=>'stability','reverse'=>true,'tr'=>'Bir problem çıktığında zihnim kolayca dağılır ve odaklanmak zorlaşır.','en'=>'When a problem occurs, my mind easily gets scattered and focusing becomes difficult.'],

            // adaptability 33-40
            ['order'=>33,'dimension'=>'adaptability','reverse'=>false,'tr'=>'Yeni bir süreç veya araç öğrenmem gerektiğinde hızlı uyum sağlayabilirim.','en'=>'When I need to learn a new process or tool, I can adapt quickly.'],
            ['order'=>34,'dimension'=>'adaptability','reverse'=>false,'tr'=>'Belirsizlik olduğunda bile ilerlemek için seçenek üretmeye çalışırım.','en'=>'Even with uncertainty, I try to generate options to move forward.'],
            ['order'=>35,'dimension'=>'adaptability','reverse'=>false,'tr'=>'İşin gerektirdiği değişiklikleri kabullenip uygulamaya dökebilirim.','en'=>'I can accept required changes and put them into practice.'],
            ['order'=>36,'dimension'=>'adaptability','reverse'=>false,'tr'=>'Farklı görevler arasında geçiş yapmak benim için genelde kolaydır.','en'=>'Switching between different tasks is generally easy for me.'],
            ['order'=>37,'dimension'=>'adaptability','reverse'=>false,'tr'=>'Yeni fikirleri denemek, işi geliştirebilecekse değerlidir.','en'=>'Trying new ideas is worthwhile if it can improve the work.'],
            ['order'=>38,'dimension'=>'adaptability','reverse'=>true,'tr'=>'Alıştığım yöntemler değiştiğinde verimim ciddi düşer.','en'=>'When my usual methods change, my efficiency drops significantly.'],
            ['order'=>39,'dimension'=>'adaptability','reverse'=>true,'tr'=>'Plan dışı bir değişiklik olduğunda bunu kabullenmem zaman alır.','en'=>'When there is an unplanned change, it takes me time to accept it.'],
            ['order'=>40,'dimension'=>'adaptability','reverse'=>true,'tr'=>'Yeni bir yaklaşım denemek yerine mevcut düzeni sürdürmek daha doğrudur.','en'=>'Rather than trying a new approach, it is better to maintain the current routine.'],
        ];
    }
}
