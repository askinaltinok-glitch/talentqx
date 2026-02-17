<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\PoolCandidate;
use App\Models\VesselReview;
use App\Services\VesselReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VesselReviewController extends Controller
{
    public function __construct(
        private VesselReviewService $service
    ) {}

    /**
     * POST /v1/maritime/reviews
     * Submit a vessel review (public, authenticated by candidate id + email).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'candidate_id' => ['required', 'uuid'],
            'candidate_email' => ['required', 'email'],
            'company_name' => ['required', 'string', 'max:255'],
            'vessel_name' => ['nullable', 'string', 'max:255'],
            'vessel_type' => ['nullable', 'string', 'in:' . implode(',', VesselReview::VESSEL_TYPES)],
            'rating_salary' => ['required', 'integer', 'min:1', 'max:5'],
            'rating_provisions' => ['required', 'integer', 'min:1', 'max:5'],
            'rating_cabin' => ['required', 'integer', 'min:1', 'max:5'],
            'rating_internet' => ['required', 'integer', 'min:1', 'max:5'],
            'rating_bonus' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'is_anonymous' => ['boolean'],
        ]);

        // Verify candidate identity
        $candidate = PoolCandidate::where('id', $data['candidate_id'])
            ->where('email', $data['candidate_email'])
            ->firstOrFail();

        $review = $this->service->submit($candidate->id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted. It will be visible after admin approval.',
            'data' => [
                'id' => $review->id,
                'status' => $review->status,
                'overall_rating' => $review->overall_rating,
            ],
        ], 201);
    }

    /**
     * POST /v1/maritime/reviews/{id}/report
     * Report a review for abuse (public, rate-limited).
     */
    public function report(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->service->reportReview($id, $data['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted. Thank you for your feedback.',
        ]);
    }

    /**
     * GET /v1/maritime/reviews
     * List approved reviews for a company (public).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_name' => ['required', 'string'],
        ]);

        $result = $this->service->getCompanyReviews(
            $request->input('company_name'),
            min((int) $request->input('limit', 20), 50)
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
