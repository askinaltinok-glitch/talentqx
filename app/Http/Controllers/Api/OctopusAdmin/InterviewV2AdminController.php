<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\InterviewQuestionSet;
use Illuminate\Http\JsonResponse;

class InterviewV2AdminController extends Controller
{
    /**
     * GET /api/octo-admin/interview-v2/question-sets
     *
     * List all question sets with usage stats.
     */
    public function index(): JsonResponse
    {
        $sets = InterviewQuestionSet::withCount('attempts')
            ->orderBy('locale')
            ->orderBy('position_code')
            ->get()
            ->map(fn($set) => [
                'id' => $set->id,
                'code' => $set->code,
                'version' => $set->version,
                'locale' => $set->locale,
                'position_code' => $set->position_code,
                'country_code' => $set->country_code,
                'is_active' => $set->is_active,
                'question_count' => count($set->questions_json ?? []),
                'attempts_count' => $set->attempts_count,
                'created_at' => $set->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $sets,
            'engine_enabled' => config('maritime.interview_engine_v2', false),
        ]);
    }

    /**
     * POST /api/octo-admin/interview-v2/question-sets/{id}/activate
     */
    public function activate(string $id): JsonResponse
    {
        $set = InterviewQuestionSet::findOrFail($id);
        $set->update(['is_active' => true]);

        return response()->json(['success' => true, 'message' => "Question set {$set->locale}/{$set->position_code} activated."]);
    }

    /**
     * POST /api/octo-admin/interview-v2/question-sets/{id}/deactivate
     */
    public function deactivate(string $id): JsonResponse
    {
        $set = InterviewQuestionSet::findOrFail($id);
        $set->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => "Question set {$set->locale}/{$set->position_code} deactivated."]);
    }
}
