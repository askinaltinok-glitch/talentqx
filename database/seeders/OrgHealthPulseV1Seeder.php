<?php

namespace Database\Seeders;

use App\Models\OrgQuestionnaire;
use App\Models\OrgQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrgHealthPulseV1Seeder extends Seeder
{
    public function run(): void
    {
        $schema = json_decode($this->schemaJson(), true);

        $q = OrgQuestionnaire::query()->updateOrCreate(
            [
                'tenant_id' => null,
                'code' => 'pulse',
                'version' => 'v1',
            ],
            [
                'status' => 'active',
                'title' => ['tr' => 'Nabız Anketi', 'en' => 'Pulse Survey'],
                'description' => [
                    'tr' => '5 soruluk hızlı nabız değerlendirmesi',
                    'en' => '5-question quick pulse assessment',
                ],
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
                'is_reverse' => false,
                'sort_order' => $item['order'],
                'text' => ['tr' => $item['tr'], 'en' => $item['en']],
                'created_at' => now(),
            ]);
        }
    }

    private function questions(): array
    {
        return [
            [
                'order' => 1,
                'dimension' => 'engagement',
                'tr' => 'Bu hafta işime ne kadar bağlı hissettim.',
                'en' => 'I felt engaged with my work this week.',
            ],
            [
                'order' => 2,
                'dimension' => 'wellbeing',
                'tr' => 'Genel olarak kendimi iyi ve enerjik hissediyorum.',
                'en' => 'Overall I feel well and energetic.',
            ],
            [
                'order' => 3,
                'dimension' => 'alignment',
                'tr' => 'Yaptığım işin şirket hedefleriyle uyumlu olduğunu düşünüyorum.',
                'en' => 'I believe my work is aligned with company goals.',
            ],
            [
                'order' => 4,
                'dimension' => 'growth',
                'tr' => 'Mesleki gelişimim için yeterli fırsat görüyorum.',
                'en' => 'I see sufficient opportunities for my professional growth.',
            ],
            [
                'order' => 5,
                'dimension' => 'retention_intent',
                'tr' => 'Önümüzdeki 6 ay bu şirkette çalışmaya devam etmeyi planlıyorum.',
                'en' => 'I plan to continue working at this company for the next 6 months.',
            ],
        ];
    }

    private function schemaJson(): string
    {
        return <<<'JSON'
{
  "code": "pulse",
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
  "dimensions": [
    { "key": "engagement", "label": { "tr": "Bağlılık", "en": "Engagement" }, "items": 1 },
    { "key": "wellbeing", "label": { "tr": "İyi Oluş", "en": "Wellbeing" }, "items": 1 },
    { "key": "alignment", "label": { "tr": "Uyum", "en": "Alignment" }, "items": 1 },
    { "key": "growth", "label": { "tr": "Gelişim", "en": "Growth" }, "items": 1 },
    { "key": "retention_intent", "label": { "tr": "Kalma Niyeti", "en": "Retention Intent" }, "items": 1 }
  ],
  "scoring": {
    "per_dimension": "(raw_value / 5) * 100",
    "overall": "average of 5 dimension scores",
    "burnout_proxy": "((6 - retention_intent_raw) / 5) * 100"
  },
  "disclaimer": {
    "tr": "Bu anket bireysel performans değerlendirmesi değildir. Sonuçlar yalnızca genel refah analizi için kullanılır.",
    "en": "This survey is not an individual performance evaluation. Results are used only for general wellbeing analysis."
  }
}
JSON;
    }
}
