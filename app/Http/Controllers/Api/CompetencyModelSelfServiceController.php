<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyCompetencyModel;
use App\Models\Competency;
use App\Services\CompanyCompetencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompetencyModelSelfServiceController extends Controller
{
    public function __construct(private CompanyCompetencyService $service) {}

    /**
     * GET /v1/competency-library
     * Active competencies for selection.
     */
    public function competencies(Request $request): JsonResponse
    {
        $query = Competency::active()->orderBy('category')->orderBy('name_en');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()->map(fn ($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name_tr' => $c->name_tr,
                'name_en' => $c->name_en,
                'description_tr' => $c->description_tr,
                'description_en' => $c->description_en,
                'category' => $c->category,
                'is_universal' => $c->is_universal,
            ]),
        ]);
    }

    /**
     * GET /v1/competency-models
     * List own company's models (tenant-scoped).
     */
    public function index(): JsonResponse
    {
        $models = CompanyCompetencyModel::with('items')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $models->map(fn ($m) => $this->formatModel($m)),
        ]);
    }

    /**
     * POST /v1/competency-models
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.competency_code' => ['required', 'string', 'max:50'],
            'items.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.priority' => ['nullable', Rule::in(['critical', 'important', 'nice_to_have'])],
            'items.*.min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $company = $request->user()->company;

        if (!$company) {
            return response()->json(['success' => false, 'message' => 'No company associated'], 403);
        }

        $model = $this->service->createModel($company, $data);

        return response()->json([
            'success' => true,
            'data' => $this->formatModel($model),
        ], 201);
    }

    /**
     * PUT /v1/competency-models/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $model = CompanyCompetencyModel::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.competency_code' => ['required_with:items', 'string', 'max:50'],
            'items.*.weight' => ['required_with:items', 'numeric', 'min:0', 'max:100'],
            'items.*.priority' => ['nullable', Rule::in(['critical', 'important', 'nice_to_have'])],
            'items.*.min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $model = $this->service->updateModel($model, $data);

        return response()->json([
            'success' => true,
            'data' => $this->formatModel($model),
        ]);
    }

    /**
     * DELETE /v1/competency-models/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $model = CompanyCompetencyModel::findOrFail($id);
        $this->service->deleteModel($model);

        return response()->json(['success' => true]);
    }

    /**
     * POST /v1/competency-models/{id}/set-default
     */
    public function setDefault(string $id): JsonResponse
    {
        $model = CompanyCompetencyModel::findOrFail($id);
        $this->service->setDefault($model);

        return response()->json([
            'success' => true,
            'data' => $this->formatModel($model->fresh('items')),
        ]);
    }

    private function formatModel(CompanyCompetencyModel $m): array
    {
        return [
            'id' => $m->id,
            'company_id' => $m->company_id,
            'name' => $m->name,
            'description' => $m->description,
            'is_default' => $m->is_default,
            'is_active' => $m->is_active,
            'items' => $m->items->map(fn ($i) => [
                'id' => $i->id,
                'competency_code' => $i->competency_code,
                'weight' => $i->weight,
                'priority' => $i->priority,
                'min_score' => $i->min_score,
            ]),
            'total_weight' => $m->items->sum('weight'),
            'item_count' => $m->items->count(),
            'created_at' => $m->created_at?->toIso8601String(),
            'updated_at' => $m->updated_at?->toIso8601String(),
        ];
    }
}
