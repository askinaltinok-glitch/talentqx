<?php

namespace App\Services;

use App\Jobs\SendCandidateNotificationJob;
use App\Models\CandidateNotification;
use App\Models\CandidatePresentation;
use App\Models\PoolCandidate;
use App\Models\VesselReview;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class VesselReviewService
{
    private const MAX_REVIEWS_PER_DAY = 5;

    private const BLOCKED_PATTERNS = [
        '/\b(fuck|shit|damn|bitch|bastard|asshole|dick|cunt|piss)\w*\b/iu',
        '/\b(idiot|moron|stupid|retard|loser)\w*\b/iu',
        '/(.)\1{5,}/',                   // repeated chars: aaaaaa
        '/\b[A-Z\s]{30,}\b/',            // ALL-CAPS spam blocks
        '/(https?:\/\/[^\s]+){3,}/',     // 3+ URLs = spam
    ];

    /**
     * Submit a new vessel review (status = pending).
     */
    public function submit(string $candidateId, array $data): VesselReview
    {
        // Block demo candidates from submitting reviews
        $candidate = PoolCandidate::withoutGlobalScope('exclude_demo')->find($candidateId);
        if ($candidate && $candidate->is_demo) {
            throw ValidationException::withMessages([
                'candidate' => ['Demo candidates cannot submit reviews.'],
            ]);
        }

        // Daily review limit per candidate
        $todayCount = VesselReview::where('pool_candidate_id', $candidateId)
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= self::MAX_REVIEWS_PER_DAY) {
            throw ValidationException::withMessages([
                'limit' => ['You can submit a maximum of ' . self::MAX_REVIEWS_PER_DAY . ' reviews per day.'],
            ]);
        }

        // Per-vessel per-day limit
        if (!empty($data['vessel_name'])) {
            $vesselToday = VesselReview::where('pool_candidate_id', $candidateId)
                ->where('vessel_name', $data['vessel_name'])
                ->whereDate('created_at', today())
                ->exists();

            if ($vesselToday) {
                throw ValidationException::withMessages([
                    'vessel_name' => ['You can only submit one review per vessel per day.'],
                ]);
            }
        }

        // Content moderation on comment
        if (!empty($data['comment'])) {
            $this->validateCommentContent($data['comment']);
        }

        // Minimum comment length
        if (!empty($data['comment']) && mb_strlen($data['comment']) < 50) {
            throw ValidationException::withMessages([
                'comment' => ['Review comments must be at least 50 characters long.'],
            ]);
        }

        // Similarity check against recent reviews for the same vessel
        if (!empty($data['comment']) && !empty($data['vessel_name'])) {
            $recentComments = VesselReview::where('vessel_name', $data['vessel_name'])
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('comment')
                ->pluck('comment');

            foreach ($recentComments as $existing) {
                similar_text($data['comment'], $existing, $pct);
                if ($pct > 80) {
                    throw ValidationException::withMessages([
                        'comment' => ['Your review is too similar to an existing review. Please provide original feedback.'],
                    ]);
                }
            }
        }

        // Verify candidate was deployed to this company (hired status)
        $wasDeployed = CandidatePresentation::where('pool_candidate_id', $candidateId)
            ->where('presentation_status', 'hired')
            ->whereHas('talentRequest.company', function ($q) use ($data) {
                $q->where('company_name', $data['company_name']);
            })
            ->exists();

        if (!$wasDeployed) {
            throw ValidationException::withMessages([
                'company_name' => ['You can only review companies you have been deployed to.'],
            ]);
        }

        try {
            $review = VesselReview::create([
                'pool_candidate_id' => $candidateId,
                'company_name' => $data['company_name'],
                'vessel_name' => $data['vessel_name'] ?? null,
                'vessel_type' => $data['vessel_type'] ?? null,
                'rating_salary' => $data['rating_salary'],
                'rating_provisions' => $data['rating_provisions'],
                'rating_cabin' => $data['rating_cabin'],
                'rating_internet' => $data['rating_internet'],
                'rating_bonus' => $data['rating_bonus'],
                'overall_rating' => 0,
                'comment' => $data['comment'] ?? null,
                'is_anonymous' => $data['is_anonymous'] ?? true,
            ]);

            $review->update(['overall_rating' => $review->computeOverallRating()]);

            return $review;
        } catch (QueryException $e) {
            // Unique constraint violation
            if ($e->errorInfo[1] === 1062) {
                throw ValidationException::withMessages([
                    'duplicate' => ['You have already submitted a review for this company and vessel.'],
                ]);
            }
            throw $e;
        }
    }

    /**
     * Approve a review and notify the candidate.
     */
    public function approve(VesselReview $review): void
    {
        $review->approve();

        $notification = CandidateNotification::create([
            'pool_candidate_id' => $review->pool_candidate_id,
            'type' => CandidateNotification::TYPE_REVIEW_APPROVED,
            'title' => 'Your review was approved',
            'body' => "Your review for {$review->company_name} has been approved and is now visible.",
            'data' => ['review_id' => $review->id, 'company_name' => $review->company_name],
            'tier_required' => CandidateNotification::TIER_FREE,
            'created_at' => now(),
        ]);

        SendCandidateNotificationJob::dispatch($notification);
    }

    /**
     * Reject a review with admin notes.
     */
    public function reject(VesselReview $review, ?string $notes = null): void
    {
        $review->reject($notes);
    }

    /**
     * Get aggregate ratings for a company.
     */
    public function getCompanyRatings(string $companyName): array
    {
        return VesselReview::companyRatings($companyName);
    }

    /**
     * List approved reviews for a company.
     */
    public function getCompanyReviews(string $companyName, int $limit = 20): array
    {
        $reviews = VesselReview::approved()
            ->forCompany($companyName)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'vessel_name' => $r->vessel_name,
                    'vessel_type' => $r->vessel_type,
                    'rating_salary' => $r->rating_salary,
                    'rating_provisions' => $r->rating_provisions,
                    'rating_cabin' => $r->rating_cabin,
                    'rating_internet' => $r->rating_internet,
                    'rating_bonus' => $r->rating_bonus,
                    'overall_rating' => $r->overall_rating,
                    'comment' => $r->comment,
                    'is_anonymous' => $r->is_anonymous,
                    'author_name' => $r->is_anonymous ? null : optional($r->author)->full_name,
                    'created_at' => $r->created_at->toIso8601String(),
                ];
            })
            ->toArray();

        $ratings = $this->getCompanyRatings($companyName);

        return [
            'company_name' => $companyName,
            'ratings' => $ratings,
            'reviews' => $reviews,
        ];
    }

    /**
     * Validate review comment for profanity/spam patterns.
     */
    private function validateCommentContent(string $comment): void
    {
        foreach (self::BLOCKED_PATTERNS as $pattern) {
            if (preg_match($pattern, $comment)) {
                throw ValidationException::withMessages([
                    'comment' => ['Your review contains inappropriate content. Please revise and resubmit.'],
                ]);
            }
        }
    }

    /**
     * Report a review for abuse.
     */
    public function reportReview(string $reviewId, string $reason): void
    {
        $review = VesselReview::findOrFail($reviewId);
        $review->increment('report_count');

        \App\Services\System\SystemEventService::warn('review_reported', 'VesselReviewService', "Review {$reviewId} reported: {$reason}", [
            'review_id' => $reviewId,
            'report_count' => $review->fresh()->report_count,
            'reason' => $reason,
        ]);

        // Auto-unpublish at 3+ reports
        if ($review->fresh()->report_count >= 3) {
            $review->update(['published_at' => null]);
            \App\Services\System\SystemEventService::alert('review_auto_unpublished', 'VesselReviewService', "Review {$reviewId} auto-unpublished after 3+ reports", [
                'review_id' => $reviewId,
                'report_count' => $review->fresh()->report_count,
            ]);
        }
    }

    /**
     * Soft-delete a review (admin action).
     */
    public function softDeleteReview(string $reviewId, ?string $notes = null): void
    {
        $review = VesselReview::findOrFail($reviewId);
        if ($notes) {
            $review->update(['admin_notes' => $notes]);
        }
        $review->delete();

        \App\Services\System\SystemEventService::log('review_deleted', 'info', 'VesselReviewService', "Review {$reviewId} soft-deleted", [
            'review_id' => $reviewId,
            'notes' => $notes,
        ]);
    }
}
