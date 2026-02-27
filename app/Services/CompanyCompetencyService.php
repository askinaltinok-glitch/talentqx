<?php

namespace App\Services;

use App\Models\CompanyCompetencyModel;
use App\Models\CompanyCompetencyModelItem;
use App\Models\Company;
use App\Models\FormInterview;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyCompetencyService
{
    /**
     * Resolve the competency model to use for scoring.
     * Priority: company default model (active).
     */
    public function resolveModel(?string $companyId): ?CompanyCompetencyModel
    {
        if (!$companyId) {
            return null;
        }

        return CompanyCompetencyModel::withoutGlobalScope(TenantScope::class)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->with('items')
            ->first();
    }

    /**
     * Compute company fit score from interview competency_scores and company model.
     *
     * Algorithm:
     * 1. For each item in model: get score from competency_scores
     * 2. Unmatched codes → skip
     * 3. company_fit_score = Σ(score_i × weight_i) / Σ(weight_i)
     * 4. Critical priority + score < min_score → flag
     */
    public function computeCompanyFit(FormInterview $interview): ?array
    {
        $model = $this->resolveModel($interview->company_id);

        if (!$model || $model->items->isEmpty()) {
            return null;
        }

        $competencyScores = $interview->competency_scores ?? [];
        $weightedSum = 0;
        $totalWeight = 0;
        $breakdown = [];
        $flags = [];

        foreach ($model->items as $item) {
            $score = $competencyScores[$item->competency_code] ?? null;

            if ($score === null) {
                $breakdown[] = [
                    'code' => $item->competency_code,
                    'weight' => $item->weight,
                    'priority' => $item->priority,
                    'score' => null,
                    'matched' => false,
                ];
                continue;
            }

            $score = (float) $score;
            $weightedSum += $score * $item->weight;
            $totalWeight += $item->weight;

            $breakdown[] = [
                'code' => $item->competency_code,
                'weight' => $item->weight,
                'priority' => $item->priority,
                'min_score' => $item->min_score,
                'score' => $score,
                'matched' => true,
            ];

            if ($item->priority === 'critical' && $item->min_score !== null && $score < $item->min_score) {
                $flags[] = [
                    'code' => $item->competency_code,
                    'type' => 'below_minimum',
                    'score' => $score,
                    'min' => $item->min_score,
                ];
            }
        }

        $companyFitScore = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : null;

        return [
            'company_fit_score' => $companyFitScore,
            'company_competency_scores' => [
                'model_id' => $model->id,
                'model_name' => $model->name,
                'breakdown' => $breakdown,
                'flags' => $flags,
            ],
        ];
    }

    /**
     * Compute company fit from raw competency scores + company ID (generic, for any interview model).
     */
    public function computeFromScores(array $competencyScores, string $companyId): ?array
    {
        $model = $this->resolveModel($companyId);

        if (!$model || $model->items->isEmpty()) {
            return null;
        }

        $weightedSum = 0;
        $totalWeight = 0;
        $breakdown = [];
        $flags = [];

        foreach ($model->items as $item) {
            $rawScore = $competencyScores[$item->competency_code] ?? null;

            // Handle both plain numbers and objects like {score: 75, evidence: [...]}
            if ($rawScore === null) {
                $breakdown[] = [
                    'code' => $item->competency_code,
                    'weight' => $item->weight,
                    'priority' => $item->priority,
                    'score' => null,
                    'matched' => false,
                ];
                continue;
            }

            $score = is_array($rawScore) ? (float) ($rawScore['score'] ?? 0) : (float) $rawScore;
            $weightedSum += $score * $item->weight;
            $totalWeight += $item->weight;

            $breakdown[] = [
                'code' => $item->competency_code,
                'weight' => $item->weight,
                'priority' => $item->priority,
                'min_score' => $item->min_score,
                'score' => $score,
                'matched' => true,
            ];

            if ($item->priority === 'critical' && $item->min_score !== null && $score < $item->min_score) {
                $flags[] = [
                    'code' => $item->competency_code,
                    'type' => 'below_minimum',
                    'score' => $score,
                    'min' => $item->min_score,
                ];
            }
        }

        $companyFitScore = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : null;

        return [
            'company_fit_score' => $companyFitScore,
            'company_competency_scores' => [
                'model_id' => $model->id,
                'model_name' => $model->name,
                'breakdown' => $breakdown,
                'flags' => $flags,
            ],
        ];
    }

    /**
     * Create a competency model with items.
     */
    public function createModel(Company $company, array $data): CompanyCompetencyModel
    {
        $this->validateWeights($data['items'] ?? []);

        return DB::transaction(function () use ($company, $data) {
            // If this is set as default, unset existing defaults
            if (!empty($data['is_default'])) {
                CompanyCompetencyModel::withoutGlobalScope(TenantScope::class)
                    ->where('company_id', $company->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $model = CompanyCompetencyModel::withoutGlobalScope(TenantScope::class)->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_default' => $data['is_default'] ?? false,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($data['items'] as $item) {
                $model->items()->create([
                    'competency_code' => $item['competency_code'],
                    'weight' => $item['weight'],
                    'priority' => $item['priority'] ?? 'important',
                    'min_score' => $item['min_score'] ?? null,
                ]);
            }

            return $model->load('items');
        });
    }

    /**
     * Update a competency model and its items.
     */
    public function updateModel(CompanyCompetencyModel $model, array $data): CompanyCompetencyModel
    {
        if (isset($data['items'])) {
            $this->validateWeights($data['items']);
        }

        return DB::transaction(function () use ($model, $data) {
            // If setting as default, unset existing defaults
            if (!empty($data['is_default']) && !$model->is_default) {
                CompanyCompetencyModel::withoutGlobalScope(TenantScope::class)
                    ->where('company_id', $model->company_id)
                    ->where('is_default', true)
                    ->where('id', '!=', $model->id)
                    ->update(['is_default' => false]);
            }

            $model->update([
                'name' => $data['name'] ?? $model->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $model->description,
                'is_default' => $data['is_default'] ?? $model->is_default,
                'is_active' => $data['is_active'] ?? $model->is_active,
            ]);

            if (isset($data['items'])) {
                // Replace all items
                $model->items()->delete();
                foreach ($data['items'] as $item) {
                    $model->items()->create([
                        'competency_code' => $item['competency_code'],
                        'weight' => $item['weight'],
                        'priority' => $item['priority'] ?? 'important',
                        'min_score' => $item['min_score'] ?? null,
                    ]);
                }
            }

            return $model->load('items');
        });
    }

    /**
     * Delete a competency model.
     */
    public function deleteModel(CompanyCompetencyModel $model): void
    {
        $model->delete();
    }

    /**
     * Set a model as the company's default.
     */
    public function setDefault(CompanyCompetencyModel $model): void
    {
        DB::transaction(function () use ($model) {
            CompanyCompetencyModel::withoutGlobalScope(TenantScope::class)
                ->where('company_id', $model->company_id)
                ->where('is_default', true)
                ->where('id', '!=', $model->id)
                ->update(['is_default' => false]);

            $model->update(['is_default' => true]);
        });
    }

    /**
     * Validate that item weights sum to 100.
     */
    private function validateWeights(array $items): void
    {
        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => ['At least one competency item is required.'],
            ]);
        }

        $totalWeight = array_sum(array_column($items, 'weight'));

        if (abs($totalWeight - 100) > 0.01) {
            throw ValidationException::withMessages([
                'items' => ["Competency weights must sum to 100. Current sum: {$totalWeight}"],
            ]);
        }
    }
}
