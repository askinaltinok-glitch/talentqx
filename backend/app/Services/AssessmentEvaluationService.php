<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class AssessmentEvaluationService
{
    private array $template;
    private string $roleCode;

    /**
     * Load assessment template for a role
     */
    public function loadTemplate(string $roleCode): self
    {
        $this->roleCode = $roleCode;
        $path = config_path("assessments/{$roleCode}.json");

        if (!File::exists($path)) {
            throw new \Exception("Assessment template not found: {$roleCode}");
        }

        $this->template = json_decode(File::get($path), true);
        return $this;
    }

    /**
     * Get questions for the assessment
     */
    public function getQuestions(): array
    {
        return $this->template['questions'] ?? [];
    }

    /**
     * Get competencies
     */
    public function getCompetencies(): array
    {
        return $this->template['competencies'] ?? [];
    }

    /**
     * Evaluate a single response
     *
     * @param int $questionOrder
     * @param string $response
     * @return array
     */
    public function evaluateResponse(int $questionOrder, string $response): array
    {
        $question = collect($this->template['questions'])
            ->firstWhere('order', $questionOrder);

        if (!$question) {
            throw new \Exception("Question not found: {$questionOrder}");
        }

        // This will be replaced with AI evaluation
        // For now, return structure for manual/AI evaluation
        return [
            'question_order' => $questionOrder,
            'competency_code' => $question['competency_code'],
            'response' => $response,
            'evaluation_criteria' => $question['evaluation_criteria'],
            'red_flag_triggers' => $question['red_flag_triggers'],
            'score' => null, // To be filled by AI
            'feedback' => null, // To be filled by AI
            'detected_red_flags' => [], // To be filled by AI
        ];
    }

    /**
     * Build AI prompt for evaluation
     */
    public function buildEvaluationPrompt(array $responses): string
    {
        $role = $this->template['role'];
        $competencies = $this->template['competencies'];
        $redFlags = $this->template['red_flags'];
        $guidelines = $this->template['evaluation_guidelines'];

        $prompt = <<<PROMPT
# DEĞERLENDİRME GÖREVİ

Sen bir İK değerlendirme uzmanısın. Aşağıdaki pozisyon için aday yanıtlarını değerlendireceksin.

## POZİSYON
- Rol: {$role['name']}
- Kategori: {$role['category']}
- Açıklama: {$role['description']}

## YETKİNLİKLER
PROMPT;

        foreach ($competencies as $comp) {
            $prompt .= "\n- **{$comp['name']}** ({$comp['code']}): {$comp['description']} [Ağırlık: %{$comp['weight']}]";
        }

        $prompt .= "\n\n## KIRMIZI BAYRAKLAR (DİKKAT!)";
        foreach ($redFlags as $flag) {
            $indicators = implode(', ', $flag['indicators']);
            $prompt .= "\n- **{$flag['label']}** [{$flag['severity']}]: {$indicators}";
        }

        $prompt .= "\n\n## DEĞERLENDİRME KURALLARI";
        foreach ($guidelines['general_principles'] as $principle) {
            $prompt .= "\n- {$principle}";
        }

        $prompt .= "\n\n## ADAY YANITLARI VE DEĞERLENDİRME KRİTERLERİ\n";

        foreach ($responses as $resp) {
            $question = collect($this->template['questions'])
                ->firstWhere('order', $resp['question_order']);

            $prompt .= "\n### SORU {$resp['question_order']} ({$question['competency_code']})\n";
            $prompt .= "**Soru:** {$question['text']}\n\n";
            $prompt .= "**Aday Yanıtı:** {$resp['response']}\n\n";
            $prompt .= "**Puanlama Kriterleri:**\n";
            foreach ($question['evaluation_criteria'] as $score => $criteria) {
                $prompt .= "- {$score} puan: {$criteria}\n";
            }
            $prompt .= "\n**Kırmızı Bayrak Tetikleyicileri:** " . implode(', ', $question['red_flag_triggers']) . "\n";
        }

        $prompt .= <<<OUTPUT

## ÇIKTI FORMATI

Yanıtını aşağıdaki JSON formatında ver:

```json
{
  "question_scores": [
    {
      "question_order": 1,
      "score": 0-5,
      "competency_code": "CUSTOMER_SERVICE",
      "feedback": "Kısa değerlendirme açıklaması",
      "red_flags_detected": []
    }
  ],
  "competency_scores": {
    "CUSTOMER_SERVICE": {
      "score": 0-100,
      "weight": 20,
      "weighted_score": 0-20,
      "feedback": "Yetkinlik özeti",
      "evidence": ["Kanıt 1", "Kanıt 2"]
    }
  },
  "overall_score": 0-100,
  "level_label": "Yetersiz|Geliştirilmeli|Yeterli|İyi|Mükemmel",
  "level_numeric": 1-5,
  "risk_flags": [
    {
      "code": "THEFT_TENDENCY",
      "label": "Hırsızlık Eğilimi",
      "severity": "critical",
      "detected_in_question": 2,
      "evidence": "Tespit edilen ifade veya davranış"
    }
  ],
  "risk_level": "low|medium|high|critical",
  "manager_summary": "Yönetici için 2-3 cümlelik özet",
  "hiring_recommendation": "hire|hire_with_training|conditional|reject",
  "strengths": ["Güçlü yön 1", "Güçlü yön 2"],
  "development_areas": [
    {
      "competency": "Yetkinlik adı",
      "suggestion": "Gelişim önerisi"
    }
  ]
}
```

Sadece JSON çıktısı ver, başka açıklama ekleme.
OUTPUT;

        return $prompt;
    }

    /**
     * Calculate final scores from AI response
     */
    public function calculateFinalScores(array $aiResponse): array
    {
        $competencyScores = $aiResponse['competency_scores'] ?? [];
        $questionScores = $aiResponse['question_scores'] ?? [];
        $riskFlags = $aiResponse['risk_flags'] ?? [];

        // Determine risk level from flags
        $riskLevel = 'low';
        foreach ($riskFlags as $flag) {
            if ($flag['severity'] === 'critical') {
                $riskLevel = 'critical';
                break;
            } elseif ($flag['severity'] === 'high' && $riskLevel !== 'critical') {
                $riskLevel = 'high';
            } elseif ($flag['severity'] === 'medium' && !in_array($riskLevel, ['critical', 'high'])) {
                $riskLevel = 'medium';
            }
        }

        // Apply red flag penalty to competency scores
        foreach ($riskFlags as $flag) {
            $questionOrder = $flag['detected_in_question'] ?? null;
            if ($questionOrder) {
                $question = collect($this->template['questions'])
                    ->firstWhere('order', $questionOrder);
                if ($question) {
                    $compCode = $question['competency_code'];
                    if (isset($competencyScores[$compCode])) {
                        // Cap at 50% for critical flags
                        if ($flag['severity'] === 'critical') {
                            $competencyScores[$compCode]['score'] = min(
                                $competencyScores[$compCode]['score'],
                                50
                            );
                        }
                    }
                }
            }
        }

        // Recalculate overall score with weighted average
        $totalWeight = 0;
        $weightedSum = 0;
        foreach ($this->template['competencies'] as $comp) {
            $code = $comp['code'];
            $weight = $comp['weight'];
            $score = $competencyScores[$code]['score'] ?? 0;
            $weightedSum += $score * ($weight / 100);
            $totalWeight += $weight;
        }

        $overallScore = $totalWeight > 0 ? round($weightedSum * 100 / $totalWeight) : 0;

        // Determine level
        $level = $this->determineLevel($overallScore);

        // Determine hiring recommendation based on score and flags
        $recommendation = $this->determineRecommendation($overallScore, $riskLevel);

        return [
            'competency_scores' => $competencyScores,
            'question_scores' => $questionScores,
            'overall_score' => $overallScore,
            'level_label' => $level['label'],
            'level_numeric' => $level['numeric'],
            'risk_flags' => $riskFlags,
            'risk_level' => $riskLevel,
            'manager_summary' => $aiResponse['manager_summary'] ?? '',
            'hiring_recommendation' => $recommendation,
            'strengths' => $aiResponse['strengths'] ?? [],
            'development_areas' => $aiResponse['development_areas'] ?? [],
        ];
    }

    /**
     * Determine performance level
     */
    private function determineLevel(int $score): array
    {
        foreach ($this->template['scoring']['levels'] as $level) {
            if ($score >= $level['min'] && $score <= $level['max']) {
                return $level;
            }
        }
        return $this->template['scoring']['levels'][0];
    }

    /**
     * Determine hiring recommendation
     */
    private function determineRecommendation(int $score, string $riskLevel): string
    {
        // Critical risk = reject regardless of score
        if ($riskLevel === 'critical') {
            return 'reject';
        }

        // High risk = conditional at best
        if ($riskLevel === 'high') {
            return $score >= 70 ? 'conditional' : 'reject';
        }

        // Score-based recommendation
        if ($score >= 85) {
            return 'hire';
        } elseif ($score >= 70) {
            return $riskLevel === 'medium' ? 'hire_with_training' : 'hire';
        } elseif ($score >= 55) {
            return 'hire_with_training';
        } elseif ($score >= 40) {
            return 'conditional';
        }

        return 'reject';
    }

    /**
     * Generate manager summary
     */
    public function generateManagerSummary(string $name, array $result): string
    {
        $level = $result['level_label'];
        $recommendation = match($result['hiring_recommendation']) {
            'hire' => 'İşe alınması önerilir',
            'hire_with_training' => 'Eğitimle birlikte işe alınabilir',
            'conditional' => 'Koşullu değerlendirme önerilir',
            'reject' => 'İşe alınması önerilmez',
        };

        $strengths = !empty($result['strengths'])
            ? implode(', ', array_slice($result['strengths'], 0, 2))
            : 'Belirgin güçlü yön tespit edilemedi';

        $concerns = !empty($result['risk_flags'])
            ? collect($result['risk_flags'])->pluck('label')->implode(', ')
            : ($result['risk_level'] !== 'low' ? 'Bazı gelişim alanları mevcut' : 'Önemli bir risk tespit edilmedi');

        return "{$name}, {$level} seviyesinde performans gösterdi (Skor: {$result['overall_score']}/100). " .
               "Güçlü yönleri: {$strengths}. " .
               "Dikkat edilmesi gerekenler: {$concerns}. " .
               "Öneri: {$recommendation}.";
    }
}
