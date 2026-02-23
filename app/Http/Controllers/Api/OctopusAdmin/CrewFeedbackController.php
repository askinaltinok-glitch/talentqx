<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\CrewFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrewFeedbackController extends Controller
{
    /**
     * List feedback with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CrewFeedback::query()->with('poolCandidate:id,first_name,last_name');

        if ($vesselId = $request->input('vessel_id')) {
            $query->where('vessel_id', $vesselId);
        }
        if ($type = $request->input('type')) {
            $query->where('feedback_type', $type);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $feedback = $query->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ]);
    }

    /**
     * Get all feedback for a specific candidate.
     */
    public function forCandidate(string $id): JsonResponse
    {
        $feedback = CrewFeedback::where('pool_candidate_id', $id)
            ->with('vessel:id,name,imo')
            ->where('status', '!=', CrewFeedback::STATUS_REJECTED)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ]);
    }

    /**
     * Moderate feedback: approve, reject, or flag.
     */
    public function moderate(string $id, Request $request): JsonResponse
    {
        $feedback = CrewFeedback::findOrFail($id);

        $request->validate([
            'status' => 'required|in:approved,rejected,flagged',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $feedback->update([
            'status' => $request->input('status'),
            'admin_notes' => $request->input('admin_notes'),
            'published_at' => $request->input('status') === CrewFeedback::STATUS_APPROVED ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $feedback->fresh(),
        ]);
    }
}
