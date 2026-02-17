<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\PoolCandidate;
use App\Services\CandidateNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateNotificationController extends Controller
{
    public function __construct(
        private CandidateNotificationService $service
    ) {}

    /**
     * GET /v1/maritime/candidates/{id}/notifications
     * List notifications for a candidate (filtered by tier).
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $candidate = PoolCandidate::findOrFail($id);
        $tier = $request->input('tier', 'free');
        $limit = min((int) $request->input('limit', 50), 100);

        $result = $this->service->getNotifications($candidate->id, $tier, $limit);

        return response()->json([
            'success' => true,
            'data' => $result['notifications'],
            'unread_count' => $result['unread_count'],
        ]);
    }

    /**
     * POST /v1/maritime/candidates/{id}/notifications/read
     * Mark notifications as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $candidate = PoolCandidate::findOrFail($id);
        $ids = $request->input('notification_ids'); // null = mark all

        $count = $this->service->markRead($candidate->id, $ids);

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) marked as read",
            'marked_count' => $count,
        ]);
    }

    /**
     * GET /v1/maritime/candidates/{id}/views
     * Profile view stats for a candidate.
     */
    public function viewStats(Request $request, string $id): JsonResponse
    {
        $candidate = PoolCandidate::findOrFail($id);
        $days = min((int) $request->input('days', 30), 90);

        $stats = $this->service->getViewStats($candidate->id, $days);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
