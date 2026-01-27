<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PositionTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = PositionTemplate::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'category' => $template->category,
                'description' => $template->description,
                'competencies_count' => count($template->competencies ?? []),
                'created_at' => $template->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $template = PositionTemplate::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'category' => $template->category,
                'description' => $template->description,
                'competencies' => $template->competencies,
                'red_flags' => $template->red_flags,
                'question_rules' => $template->question_rules,
                'scoring_rubric' => $template->scoring_rubric,
                'critical_behaviors' => $template->critical_behaviors,
                'sample_questions' => $template->question_rules['sample_questions'] ?? [],
            ],
        ]);
    }
}
