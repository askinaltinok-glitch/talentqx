<?php

namespace App\Services\Interview;

use App\Models\Competency;
use App\Models\CompanyCompetencyModel;
use App\Models\PositionQuestion;
use App\Services\AI\LLMProviderInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FormInterviewTemplateGenerator
{
    public function __construct(
        private LLMProviderInterface $llmProvider
    ) {}

    /**
     * Generate dynamic template_json from a company's competency model.
     *
     * Hybrid approach:
     * 1. For each competency → look for existing question in position_questions
     * 2. Missing competencies → batch-generate via AI
     * 3. Merge into template_json format
     */
    public function generateFromCompetencyModel(
        CompanyCompetencyModel $model,
        string $language = 'tr',
        string $positionCode = '__generic__',
        ?string $industryCode = null
    ): array {
        $selected = $this->selectCompetencies($model, 8);

        $questions = [];
        $missingForAI = [];

        foreach ($selected as $item) {
            $existing = $this->findExistingQuestion($item->competency_code, $language);
            if ($existing) {
                $questions[] = $existing;
            } else {
                $missingForAI[] = $item;
            }
        }

        if (!empty($missingForAI)) {
            $aiQuestions = $this->generateQuestionsViaAI(
                $missingForAI,
                $language,
                $positionCode,
                $industryCode
            );
            $questions = array_merge($questions, $aiQuestions);
        }

        // Assign slot numbers
        foreach ($questions as $i => &$q) {
            $q['slot'] = $i + 1;
        }
        unset($q);

        return ['questions' => $questions];
    }

    /**
     * Select competencies from the model, sorted by priority then weight.
     * critical → important → nice_to_have, then weight descending.
     */
    private function selectCompetencies(CompanyCompetencyModel $model, int $maxQuestions = 8): Collection
    {
        $priorityOrder = ['critical' => 1, 'important' => 2, 'nice_to_have' => 3];

        return $model->items
            ->sortBy([
                fn ($a, $b) => ($priorityOrder[$a->priority] ?? 9) <=> ($priorityOrder[$b->priority] ?? 9),
                fn ($a, $b) => $b->weight <=> $a->weight,
            ])
            ->take($maxQuestions)
            ->values();
    }

    /**
     * Find an existing question from the question bank for a competency code.
     * Locale fallback: requested → tr → en.
     */
    private function findExistingQuestion(string $competencyCode, string $language): ?array
    {
        $competency = Competency::where('code', $competencyCode)->first();
        if (!$competency) {
            return null;
        }

        $pq = PositionQuestion::where('competency_id', $competency->id)
            ->where('is_active', true)
            ->ordered()
            ->first();

        if (!$pq) {
            return null;
        }

        $questionText = $pq->getQuestion($language);
        if (empty(trim($questionText))) {
            return null;
        }

        return [
            'question_text' => $questionText,
            'competency_code' => $competencyCode,
            'question_type' => $pq->question_type ?? 'behavioral',
            'time_limit_seconds' => $pq->time_limit_seconds ?? 180,
            'source' => 'question_bank',
        ];
    }

    /**
     * Generate questions via AI for competencies not found in the question bank.
     * Reuses the existing LLMProvider::generateQuestions() method.
     */
    private function generateQuestionsViaAI(
        array $missingItems,
        string $language,
        string $positionCode,
        ?string $industryCode
    ): array {
        // Build competency list with metadata for AI prompt
        $competencies = [];
        foreach ($missingItems as $item) {
            $competency = Competency::where('code', $item->competency_code)->first();

            $competencies[] = [
                'code' => $item->competency_code,
                'name' => $competency ? $competency->getName($language === 'en' ? 'en' : 'tr') : $item->competency_code,
                'indicators' => $competency->indicators ?? [],
                'evaluation_criteria' => $competency->evaluation_criteria ?? [],
                'weight' => $item->weight,
            ];
        }

        $questionRules = [
            'total_questions' => count($missingItems),
            'one_per_competency' => true,
        ];

        $context = [
            'locale' => $language,
            'position_name' => $positionCode !== '__generic__' ? $positionCode : null,
            'industry_code' => $industryCode,
        ];

        try {
            $aiResult = $this->llmProvider->generateQuestions($competencies, $questionRules, $context);
            $aiQuestions = $aiResult['questions'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('AI question generation failed in template generator', [
                'error' => $e->getMessage(),
                'competency_count' => count($missingItems),
            ]);
            $aiQuestions = [];
        }

        // Index AI questions by competency_code
        $indexed = [];
        foreach ($aiQuestions as $q) {
            $code = $q['competency_code'] ?? null;
            if ($code && !isset($indexed[$code])) {
                $indexed[$code] = $q;
            }
        }

        // Map back to missing items, with generic fallback for unmatched
        $result = [];
        foreach ($missingItems as $item) {
            $code = $item->competency_code;

            if (isset($indexed[$code])) {
                $result[] = [
                    'question_text' => $indexed[$code]['question_text'] ?? '',
                    'competency_code' => $code,
                    'question_type' => $indexed[$code]['question_type'] ?? 'behavioral',
                    'time_limit_seconds' => $indexed[$code]['time_limit_seconds'] ?? 180,
                    'source' => 'ai_generated',
                ];
            } else {
                // Generic fallback question
                $competency = Competency::where('code', $code)->first();
                $name = $competency ? $competency->getName($language === 'en' ? 'en' : 'tr') : $code;

                $result[] = [
                    'question_text' => $this->genericFallbackQuestion($name, $language),
                    'competency_code' => $code,
                    'question_type' => 'behavioral',
                    'time_limit_seconds' => 180,
                    'source' => 'generic_fallback',
                ];
            }
        }

        return $result;
    }

    /**
     * Generate a simple generic fallback question when AI also fails.
     */
    private function genericFallbackQuestion(string $competencyName, string $language): string
    {
        return match ($language) {
            'en' => "Can you share a specific experience demonstrating your {$competencyName} skills?",
            'de' => "Konnen Sie eine konkrete Erfahrung teilen, die Ihre {$competencyName}-Fahigkeiten zeigt?",
            'fr' => "Pouvez-vous partager une experience specifique demontrant vos competences en {$competencyName} ?",
            'ar' => "{$competencyName} - هل يمكنك مشاركة تجربة محددة توضح مهاراتك في",
            default => "{$competencyName} yetkinliginizi gosteren spesifik bir deneyiminizi paylaşır mısınız?",
        };
    }
}
