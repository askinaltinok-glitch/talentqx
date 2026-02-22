<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\MaritimeScenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScenarioBankController extends Controller
{
    /**
     * GET /v1/octopus/admin/scenario-bank
     * List scenarios with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MaritimeScenario::query()
            ->orderBy('command_class')
            ->orderBy('slot');

        if ($request->filled('command_class')) {
            $query->where('command_class', $request->input('command_class'));
        }
        if ($request->filled('domain')) {
            $query->where('domain', $request->input('domain'));
        }
        if ($request->filled('slot')) {
            $query->where('slot', (int) $request->input('slot'));
        }
        if ($request->has('active') && $request->input('active') !== '') {
            $query->where('is_active', (bool) $request->input('active'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $scenarios = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $scenarios->map(fn($s) => [
                'id'                 => $s->id,
                'scenario_code'      => $s->scenario_code,
                'command_class'      => $s->command_class,
                'slot'               => $s->slot,
                'domain'             => $s->domain,
                'difficulty_tier'    => $s->difficulty_tier,
                'primary_capability' => $s->primary_capability,
                'is_active'          => $s->is_active,
                'updated_at'         => $s->updated_at?->toIso8601String(),
            ]),
            'pagination' => [
                'current_page' => $scenarios->currentPage(),
                'per_page'     => $scenarios->perPage(),
                'total'        => $scenarios->total(),
                'last_page'    => $scenarios->lastPage(),
            ],
        ]);
    }

    /**
     * GET /v1/octopus/admin/scenario-bank/{id}
     * Show full scenario detail including all JSON fields.
     */
    public function show(string $id): JsonResponse
    {
        $scenario = MaritimeScenario::find($id);

        if (!$scenario) {
            return response()->json(['success' => false, 'message' => 'Scenario not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'                           => $scenario->id,
                'scenario_code'                => $scenario->scenario_code,
                'command_class'                => $scenario->command_class,
                'slot'                         => $scenario->slot,
                'domain'                       => $scenario->domain,
                'primary_capability'           => $scenario->primary_capability,
                'secondary_capabilities'       => $scenario->secondary_capabilities,
                'difficulty_tier'              => $scenario->difficulty_tier,
                'briefing_json'                => $scenario->briefing_json,
                'decision_prompt'              => $scenario->decision_prompt,
                'decision_prompt_i18n'         => $scenario->decision_prompt_i18n,
                'evaluation_axes_json'         => $scenario->evaluation_axes_json,
                'critical_omission_flags_json' => $scenario->critical_omission_flags_json,
                'expected_references_json'     => $scenario->expected_references_json,
                'red_flags_json'               => $scenario->red_flags_json,
                'version'                      => $scenario->version,
                'is_active'                    => $scenario->is_active,
                'created_at'                   => $scenario->created_at?->toIso8601String(),
                'updated_at'                   => $scenario->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * PUT /v1/octopus/admin/scenario-bank/{id}
     * Update scenario content fields. Does NOT change is_active.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $scenario = MaritimeScenario::find($id);

        if (!$scenario) {
            return response()->json(['success' => false, 'message' => 'Scenario not found'], 404);
        }

        $validated = $request->validate([
            'briefing_json'                => 'sometimes|array',
            'decision_prompt'              => 'sometimes|string',
            'decision_prompt_i18n'         => 'sometimes|nullable|array',
            'evaluation_axes_json'         => 'sometimes|array',
            'critical_omission_flags_json' => 'sometimes|array',
            'expected_references_json'     => 'sometimes|nullable|array',
            'red_flags_json'               => 'sometimes|nullable|array',
            'secondary_capabilities'       => 'sometimes|nullable|array',
            'domain'                       => 'sometimes|string|max:20',
            'difficulty_tier'              => 'sometimes|integer|min:1|max:3',
            'primary_capability'           => 'sometimes|string|max:20',
        ]);

        $scenario->update($validated);

        return response()->json([
            'success' => true,
            'data' => $scenario->fresh(),
        ]);
    }

    /**
     * POST /v1/octopus/admin/scenario-bank/{id}/activate
     * Validates scenario completeness before activation.
     */
    public function activate(string $id): JsonResponse
    {
        $scenario = MaritimeScenario::find($id);

        if (!$scenario) {
            return response()->json(['success' => false, 'message' => 'Scenario not found'], 404);
        }

        $errors = $this->validateForActivation($scenario);

        if (!empty($errors)) {
            return response()->json([
                'error'   => 'scenario_incomplete',
                'message' => 'Scenario content is incomplete for activation.',
                'details' => $errors,
            ], 422);
        }

        $scenario->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'data' => $scenario->fresh(),
        ]);
    }

    /**
     * POST /v1/octopus/admin/scenario-bank/{id}/deactivate
     */
    public function deactivate(string $id): JsonResponse
    {
        $scenario = MaritimeScenario::find($id);

        if (!$scenario) {
            return response()->json(['success' => false, 'message' => 'Scenario not found'], 404);
        }

        $scenario->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'data' => $scenario->fresh(),
        ]);
    }

    /**
     * Validate scenario content for activation.
     *
     * Rules:
     * 1. briefing_json: en + tr required; ru/az if present must not be empty object
     *    Each lang must have non-empty: situation, your_position, available_resources, current_conditions
     * 2. evaluation_axes_json: at least 1 axis, each with rubric_levels (5+ levels)
     * 3. critical_omission_flags_json: must not be empty
     * 4. decision_prompt: must not be empty
     */
    private function validateForActivation(MaritimeScenario $scenario): array
    {
        $errors = [];

        // 1. Briefing JSON validation
        $briefing = $scenario->briefing_json ?? [];
        $requiredLangs = ['en', 'tr'];
        $optionalLangs = ['ru', 'az'];
        $briefingFields = ['situation', 'your_position', 'available_resources', 'current_conditions'];

        foreach ($requiredLangs as $lang) {
            if (empty($briefing[$lang]) || !is_array($briefing[$lang])) {
                $errors[] = "briefing_json.{$lang} is missing or empty";
                continue;
            }
            foreach ($briefingFields as $field) {
                if (empty($briefing[$lang][$field])) {
                    $errors[] = "briefing_json.{$lang} missing {$field}";
                }
            }
        }

        foreach ($optionalLangs as $lang) {
            if (array_key_exists($lang, $briefing)) {
                if (!is_array($briefing[$lang]) || empty($briefing[$lang])) {
                    $errors[] = "briefing_json.{$lang} must not be empty object if present";
                    continue;
                }
                foreach ($briefingFields as $field) {
                    if (empty($briefing[$lang][$field])) {
                        $errors[] = "briefing_json.{$lang} missing {$field}";
                    }
                }
            }
        }

        // 2. Evaluation axes
        $axes = $scenario->evaluation_axes_json ?? [];
        if (empty($axes)) {
            $errors[] = 'evaluation_axes_json is empty';
        } else {
            foreach ($axes as $i => $axis) {
                $rubric = $axis['rubric_levels'] ?? $axis['scoring_rubric'] ?? null;
                if (empty($rubric) || !is_array($rubric)) {
                    $errors[] = "evaluation_axes_json[{$i}] missing rubric_levels";
                } elseif (count($rubric) < 5) {
                    $errors[] = "evaluation_axes_json[{$i}] rubric_levels must have at least 5 levels";
                }
            }
        }

        // 3. Critical omission flags
        if (empty($scenario->critical_omission_flags_json)) {
            $errors[] = 'critical_omission_flags_json is empty';
        }

        // 4. Decision prompt
        if (empty($scenario->decision_prompt)) {
            $errors[] = 'decision_prompt is empty';
        }

        return $errors;
    }
}
