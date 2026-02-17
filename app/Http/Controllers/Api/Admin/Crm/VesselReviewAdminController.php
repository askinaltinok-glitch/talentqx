<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\VesselReview;
use App\Services\VesselReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VesselReviewAdminController extends Controller
{
    public function __construct(
        private VesselReviewService $service
    ) {}

    /**
     * GET /v1/admin/crm/reviews
     * List all reviews (admin sees author).
     */
    public function index(Request $request): JsonResponse
    {
        $query = VesselReview::query()->with('author:id,first_name,last_name,email');

        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }
        if ($request->filled('company_name')) {
            $query->where('company_name', 'like', '%' . $request->input('company_name') . '%');
        }

        $reviews = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'meta' => [
                'total' => $reviews->total(),
                'page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * GET /v1/admin/crm/reviews/stats
     * Review stats.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total' => VesselReview::count(),
                'pending' => VesselReview::pending()->count(),
                'approved' => VesselReview::approved()->count(),
                'rejected' => VesselReview::status(VesselReview::STATUS_REJECTED)->count(),
            ],
        ]);
    }

    /**
     * POST /v1/admin/crm/reviews/{id}/approve
     */
    public function approve(string $id): JsonResponse
    {
        $review = VesselReview::findOrFail($id);
        $this->service->approve($review);

        return response()->json([
            'success' => true,
            'message' => 'Review approved',
            'data' => $review->fresh()->load('author:id,first_name,last_name,email'),
        ]);
    }

    /**
     * POST /v1/admin/crm/reviews/{id}/reject
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $review = VesselReview::findOrFail($id);
        $notes = $request->input('admin_notes');

        $this->service->reject($review, $notes);

        return response()->json([
            'success' => true,
            'message' => 'Review rejected',
            'data' => $review->fresh()->load('author:id,first_name,last_name,email'),
        ]);
    }

    /**
     * DELETE /v1/admin/crm/reviews/{id}
     * Soft-delete a review.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notes = $request->input('admin_notes');
        $this->service->softDeleteReview($id, $notes);

        return response()->json([
            'success' => true,
            'message' => 'Review deleted',
        ]);
    }
}
