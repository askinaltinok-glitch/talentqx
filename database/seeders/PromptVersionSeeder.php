<?php

namespace Database\Seeders;

use App\Models\PromptVersion;
use Illuminate\Database\Seeder;

class PromptVersionSeeder extends Seeder
{
    public function run(): void
    {
        $roleCategories = [
            'kasiyer',
            'depo_sorumlusu',
            'satis_danismani',
            'musteri_hizmetleri',
            'sube_muduru',
            'bolge_muduru',
            'teknik_servis',
        ];

        // Create base assessment analysis prompt for each role
        foreach ($roleCategories as $roleCategory) {
            PromptVersion::create([
                'name' => 'assessment_analysis',
                'role_type' => $roleCategory,
                'version' => 1,
                'prompt_text' => $this->getBasePrompt($roleCategory),
                'variables' => ['template', 'employee', 'responses'],
                'is_active' => true,
                'change_notes' => 'Initial version',
            ]);
        }

        // Create a generic prompt for any role
        PromptVersion::create([
            'name' => 'assessment_analysis',
            'role_type' => null,
            'version' => 1,
            'prompt_text' => $this->getGenericPrompt(),
            'variables' => ['template', 'employee', 'responses'],
            'is_active' => true,
            'change_notes' => 'Generic assessment analysis prompt',
        ]);
    }

    private function getBasePrompt(string $roleCategory): string
    {
        $roleSpecificInstructions = match ($roleCategory) {
            'kasiyer' => 'Ozellikle nakit yonetimi, musteriye hizmet ve hiz/dogruluk dengesine odaklan.',
            'depo_sorumlusu' => 'Stok yonetimi, organizasyon ve guvenlik protokollerine uyum konularini onemle degerlendir.',
            'satis_danismani' => 'Satis teknikleri, musteri iliskileri ve urun bilgisini detayli incele.',
            'musteri_hizmetleri' => 'Iletisim becerileri, problem cozme ve sabir/empati yeteneklerini oncelikli degerlendir.',
            'sube_muduru' => 'Liderlik, ekip yonetimi ve operasyonel kararlar uzerinde yogunlas.',
            'bolge_muduru' => 'Stratejik dusunce, coklu lokasyon yonetimi ve performans optimizasyonunu degerlendir.',
            'teknik_servis' => 'Teknik bilgi, problem teshisi ve musteri iletisimi becerilerini analiz et.',
            default => '',
        };

        return <<<PROMPT
Sen deneyimli bir HR analisti ve davranis bilimleri uzmanisin. Mevcut calisanlarin yetkinlik degerlendirmesini yapiyorsun.

ROL KATEGORISI: {$roleCategory}
{$roleSpecificInstructions}

TEMPLATE BILGISI:
{template}

ANALIZ KURALLARI:
1. Her yetkinligi 0-100 arasi puanla (agirliga gore)
2. Senaryo sorularinda verimsiz/yavas/hatali cevaplari tespit et
3. Davranissal sorularda tutarsizliklari ve kirmizi bayraklari tespit et
4. Bilgi sorularinda hatali cevaplari degerlendir
5. Risk seviyesini belirle: low (dusuk), medium (orta), high (yuksek), critical (kritik)
6. Seviye etiketi belirle: basarisiz (0-39), gelisime_acik (40-54), yeterli (55-69), iyi (70-84), mukemmel (85-100)
7. Terfi uygunlugunu ve hazirligini degerlendir
8. Gelisim plani olustur

CIKTI FORMATI (JSON):
{
    "overall_score": 72.5,
    "competency_scores": {
        "yetkinlik_kodu": {
            "score": 75,
            "weight": 0.20,
            "weighted_score": 15,
            "feedback": "Detayli geri bildirim",
            "evidence": ["Kanit 1", "Kanit 2"]
        }
    },
    "risk_flags": [],
    "risk_level": "low|medium|high|critical",
    "level_numeric": 4,
    "level_label": "iyi",
    "strengths": [],
    "improvement_areas": [],
    "development_plan": [],
    "promotion_suitable": true,
    "promotion_readiness": "not_ready|developing|ready|highly_ready",
    "promotion_notes": "Terfi degerlendirmesi notlari",
    "question_analyses": []
}

Sadece JSON formatinda cevap ver, baska aciklama ekleme.
PROMPT;
    }

    private function getGenericPrompt(): string
    {
        return <<<PROMPT
Sen deneyimli bir HR analisti ve davranis bilimleri uzmanisin. Mevcut calisanlarin yetkinlik degerlendirmesini yapiyorsun.

TEMPLATE BILGISI:
{template}

ANALIZ KURALLARI:
1. Her yetkinligi 0-100 arasi puanla (agirliga gore)
2. Senaryo sorularinda verimsiz/yavas/hatali cevaplari tespit et
3. Davranissal sorularda tutarsizliklari ve kirmizi bayraklari tespit et
4. Bilgi sorularinda hatali cevaplari degerlendir
5. Risk seviyesini belirle: low (dusuk), medium (orta), high (yuksek), critical (kritik)
6. Seviye etiketi belirle: basarisiz (0-39), gelisime_acik (40-54), yeterli (55-69), iyi (70-84), mukemmel (85-100)
7. Terfi uygunlugunu ve hazirligini degerlendir
8. Gelisim plani olustur

CIKTI FORMATI (JSON):
{
    "overall_score": 72.5,
    "competency_scores": {},
    "risk_flags": [],
    "risk_level": "low|medium|high|critical",
    "level_numeric": 4,
    "level_label": "iyi",
    "strengths": [],
    "improvement_areas": [],
    "development_plan": [],
    "promotion_suitable": true,
    "promotion_readiness": "not_ready|developing|ready|highly_ready",
    "promotion_notes": "",
    "question_analyses": []
}

Sadece JSON formatinda cevap ver, baska aciklama ekleme.
PROMPT;
    }
}
