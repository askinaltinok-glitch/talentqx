<?php

namespace App\Domains\OrgHealth\Pulse;

use App\Models\OrgPulseRiskSnapshot;
use App\Services\AI\LLMProviderFactory;
use Illuminate\Support\Facades\Log;

class PulseSuggestionService
{
    public const ACTION_LIBRARY = [
        'one_on_one_meeting' => ['tr' => '1:1 görüşme planla', 'en' => 'Schedule a 1:1 meeting'],
        'career_development_plan' => ['tr' => 'Kariyer gelişim planı oluştur', 'en' => 'Create a career development plan'],
        'workload_review' => ['tr' => 'İş yükünü gözden geçir', 'en' => 'Review workload distribution'],
        'recognition_program' => ['tr' => 'Tanıma ve takdir programı uygula', 'en' => 'Implement recognition program'],
        'mentorship_assignment' => ['tr' => 'Mentorluk ataması yap', 'en' => 'Assign a mentor'],
        'training_opportunity' => ['tr' => 'Eğitim fırsatı sun', 'en' => 'Offer training opportunity'],
        'team_building_activity' => ['tr' => 'Ekip aktivitesi düzenle', 'en' => 'Organize team building activity'],
        'compensation_review' => ['tr' => 'Ücret gözden geçirmesi yap', 'en' => 'Conduct compensation review'],
        'role_adjustment' => ['tr' => 'Rol/sorumluluk ayarlaması yap', 'en' => 'Adjust role/responsibilities'],
        'wellbeing_support' => ['tr' => 'Refah desteği sağla', 'en' => 'Provide wellbeing support'],
        'skip_level_meeting' => ['tr' => 'Üst yöneticiyle görüşme ayarla', 'en' => 'Arrange skip-level meeting'],
        'flexible_work_arrangement' => ['tr' => 'Esnek çalışma düzenlemesi öner', 'en' => 'Suggest flexible work arrangement'],
    ];

    /**
     * Rule-based fallback: maps risk drivers to suggested actions.
     */
    private const DRIVER_ACTION_MAP = [
        'stay_intent_low' => ['one_on_one_meeting', 'compensation_review', 'career_development_plan'],
        'stay_intent_declining' => ['skip_level_meeting', 'one_on_one_meeting', 'flexible_work_arrangement'],
        'motivation_low' => ['recognition_program', 'role_adjustment', 'training_opportunity'],
        'motivation_declining' => ['one_on_one_meeting', 'recognition_program'],
        'burnout_high' => ['workload_review', 'wellbeing_support', 'flexible_work_arrangement'],
        'wellbeing_low' => ['wellbeing_support', 'flexible_work_arrangement', 'workload_review'],
        'growth_stagnant' => ['career_development_plan', 'training_opportunity', 'mentorship_assignment'],
        'alignment_weak' => ['one_on_one_meeting', 'team_building_activity', 'skip_level_meeting'],
        'overall_low' => ['one_on_one_meeting', 'wellbeing_support', 'career_development_plan'],
        'consecutive_decline' => ['skip_level_meeting', 'workload_review', 'one_on_one_meeting'],
    ];

    public function generateSuggestions(OrgPulseRiskSnapshot $snapshot, string $lang = 'tr'): array
    {
        $drivers = $snapshot->drivers ?? [];

        if (empty($drivers)) {
            return [];
        }

        try {
            return $this->generateViaLLM($snapshot, $lang);
        } catch (\Throwable $e) {
            Log::warning('PulseSuggestionService: LLM failed, using rule-based fallback', [
                'error' => $e->getMessage(),
                'snapshot_id' => $snapshot->id,
            ]);
            return $this->generateRuleBased($drivers, $lang);
        }
    }

    private function generateViaLLM(OrgPulseRiskSnapshot $snapshot, string $lang): array
    {
        $provider = LLMProviderFactory::create(null, $snapshot->tenant_id);

        $actionKeys = array_keys(self::ACTION_LIBRARY);
        $drivers = $snapshot->drivers ?? [];

        $systemPrompt = 'You are an HR advisor. Given risk drivers, select 2-4 actions from the provided action library. Return JSON: {"actions": [{"key": "action_key", "reason_tr": "Turkish reason", "reason_en": "English reason"}]}. Do NOT invent new actions. Only use keys from the provided library.';

        $employeeHash = substr(md5($snapshot->employee_id), 0, 8);
        $userPrompt = sprintf(
            'Employee #%s. Risk score: %d. Drivers: [%s]. Action library keys: [%s].',
            $employeeHash,
            $snapshot->risk_score,
            implode(', ', $drivers),
            implode(', ', $actionKeys)
        );

        $responseJson = $provider->chatJson($systemPrompt, $userPrompt);
        $parsed = json_decode($responseJson, true);

        if (!is_array($parsed) || !isset($parsed['actions']) || !is_array($parsed['actions'])) {
            throw new \RuntimeException('Invalid LLM response structure');
        }

        // Validate: only allow keys from ACTION_LIBRARY
        $validated = [];
        foreach ($parsed['actions'] as $action) {
            if (!isset($action['key']) || !array_key_exists($action['key'], self::ACTION_LIBRARY)) {
                continue;
            }
            $validated[] = [
                'key' => $action['key'],
                'label' => self::ACTION_LIBRARY[$action['key']][$lang] ?? self::ACTION_LIBRARY[$action['key']]['en'],
                'reason' => $action["reason_{$lang}"] ?? $action['reason_en'] ?? '',
            ];
        }

        if (empty($validated)) {
            throw new \RuntimeException('LLM returned no valid actions');
        }

        return array_slice($validated, 0, 4);
    }

    private function generateRuleBased(array $drivers, string $lang): array
    {
        $actionScores = [];

        foreach ($drivers as $driver) {
            $actions = self::DRIVER_ACTION_MAP[$driver] ?? [];
            foreach ($actions as $i => $actionKey) {
                // Higher priority for first-listed actions
                $weight = 3 - $i;
                $actionScores[$actionKey] = ($actionScores[$actionKey] ?? 0) + $weight;
            }
        }

        // Sort by score descending, take top 3
        arsort($actionScores);
        $topKeys = array_slice(array_keys($actionScores), 0, 3);

        $suggestions = [];
        foreach ($topKeys as $key) {
            $suggestions[] = [
                'key' => $key,
                'label' => self::ACTION_LIBRARY[$key][$lang] ?? self::ACTION_LIBRARY[$key]['en'],
                'reason' => '', // Rule-based has no custom reason
            ];
        }

        return $suggestions;
    }
}
