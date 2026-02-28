<?php

namespace Database\Seeders;

use App\Models\OrgQuestionnaire;
use App\Models\OrgQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrgHealthCultureV1Seeder extends Seeder
{
    public function run(): void
    {
        $schema = json_decode($this->schemaJson(), true);

        $q = OrgQuestionnaire::query()->updateOrCreate(
            [
                'tenant_id' => null,
                'code' => 'culture',
                'version' => 'v1',
            ],
            [
                'status' => 'active',
                'title' => ['tr' => 'Kültür Profili', 'en' => 'Culture Profile'],
                'description' => [
                    'tr' => 'CVF tabanlı örgüt kültürü değerlendirmesi',
                    'en' => 'CVF-based organizational culture assessment',
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
            // CLAN (6)
            ['order' => 1, 'dimension' => 'clan', 'tr' => 'Bu şirkette insanlar birbirine destek olmaya isteklidir.', 'en' => 'In this company, people are willing to support one another.'],
            ['order' => 2, 'dimension' => 'clan', 'tr' => 'Yönetim çalışanların fikirlerini dikkate alır.', 'en' => 'Management takes employees\' opinions into account.'],
            ['order' => 3, 'dimension' => 'clan', 'tr' => 'Ekip içi güven ve samimiyet yüksektir.', 'en' => 'Trust and openness within teams are strong.'],
            ['order' => 4, 'dimension' => 'clan', 'tr' => 'Başarı, birlikte elde edilen sonuçlarla tanımlanır.', 'en' => 'Success is defined by results achieved together.'],
            ['order' => 5, 'dimension' => 'clan', 'tr' => 'Çalışan gelişimi önemli bir önceliktir.', 'en' => 'Employee development is an important priority.'],
            ['order' => 6, 'dimension' => 'clan', 'tr' => 'İnsan ilişkileri performans kadar önemlidir.', 'en' => 'Relationships are considered as important as performance.'],

            // ADHOCRACY (6)
            ['order' => 7, 'dimension' => 'adhocracy', 'tr' => 'Yeni fikirler denemek teşvik edilir.', 'en' => 'Trying new ideas is encouraged.'],
            ['order' => 8, 'dimension' => 'adhocracy', 'tr' => 'Risk almak bazen gerekli ve kabul edilebilir görülür.', 'en' => 'Taking risks is sometimes seen as necessary and acceptable.'],
            ['order' => 9, 'dimension' => 'adhocracy', 'tr' => 'Değişimlere hızlı uyum sağlanır.', 'en' => 'The organization adapts quickly to change.'],
            ['order' => 10, 'dimension' => 'adhocracy', 'tr' => 'Yenilikçi çözümler üretmek değer görür.', 'en' => 'Innovative solutions are valued.'],
            ['order' => 11, 'dimension' => 'adhocracy', 'tr' => 'Süreçler gerektiğinde esnetilebilir.', 'en' => 'Processes can be flexible when needed.'],
            ['order' => 12, 'dimension' => 'adhocracy', 'tr' => 'Geleceğe dönük fırsatlar aktif şekilde araştırılır.', 'en' => 'Future opportunities are actively explored.'],

            // MARKET (6)
            ['order' => 13, 'dimension' => 'market', 'tr' => 'Hedeflere ulaşmak en önemli önceliktir.', 'en' => 'Achieving targets is the top priority.'],
            ['order' => 14, 'dimension' => 'market', 'tr' => 'Performans ölçümleri net ve belirgindir.', 'en' => 'Performance metrics are clear and well defined.'],
            ['order' => 15, 'dimension' => 'market', 'tr' => 'Rekabetçi bir çalışma ortamı vardır.', 'en' => 'There is a competitive working environment.'],
            ['order' => 16, 'dimension' => 'market', 'tr' => 'Sonuç üretmeyen yaklaşımlar hızla değiştirilir.', 'en' => 'Approaches that do not produce results are quickly changed.'],
            ['order' => 17, 'dimension' => 'market', 'tr' => 'Başarı sayısal göstergelerle değerlendirilir.', 'en' => 'Success is evaluated through measurable indicators.'],
            ['order' => 18, 'dimension' => 'market', 'tr' => 'Yüksek performans açık şekilde ödüllendirilir.', 'en' => 'High performance is clearly rewarded.'],

            // HIERARCHY (6)
            ['order' => 19, 'dimension' => 'hierarchy', 'tr' => 'Kurallar ve prosedürler net şekilde tanımlıdır.', 'en' => 'Rules and procedures are clearly defined.'],
            ['order' => 20, 'dimension' => 'hierarchy', 'tr' => 'Yetki ve sorumluluk sınırları belirgindir.', 'en' => 'Authority and responsibility boundaries are clear.'],
            ['order' => 21, 'dimension' => 'hierarchy', 'tr' => 'Kararlar belirli bir hiyerarşi içinde alınır.', 'en' => 'Decisions are made within a defined hierarchy.'],
            ['order' => 22, 'dimension' => 'hierarchy', 'tr' => 'İş akışları standartlara göre yürütülür.', 'en' => 'Work processes follow established standards.'],
            ['order' => 23, 'dimension' => 'hierarchy', 'tr' => 'Kontrol ve denetim mekanizmaları güçlüdür.', 'en' => 'Control and monitoring mechanisms are strong.'],
            ['order' => 24, 'dimension' => 'hierarchy', 'tr' => 'İstikrar ve düzen, değişimden daha önceliklidir.', 'en' => 'Stability and order are prioritized over change.'],
        ];
    }

    private function schemaJson(): string
    {
        return <<<'JSON'
{
  "code": "culture",
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
  "culture_types": [
    { "key": "clan", "label": { "tr": "İnsan & İş Birliği", "en": "Clan" }, "items": 6 },
    { "key": "adhocracy", "label": { "tr": "Yenilik & Çeviklik", "en": "Adhocracy" }, "items": 6 },
    { "key": "market", "label": { "tr": "Sonuç & Rekabet", "en": "Market" }, "items": 6 },
    { "key": "hierarchy", "label": { "tr": "Düzen & Kontrol", "en": "Hierarchy" }, "items": 6 }
  ],
  "aggregation": {
    "formula": "avg of items per culture_type",
    "gap": "preferred - current"
  },
  "disclaimer": {
    "tr": "Bu değerlendirme bireysel performans analizi değildir. Sonuçlar yalnızca toplu kültür analizi için kullanılır.",
    "en": "This assessment is not an individual performance evaluation. Results are used only for aggregate culture analysis."
  }
}
JSON;
    }
}
