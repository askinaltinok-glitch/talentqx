<?php

namespace App\Services;

use App\Jobs\SendCandidateNotificationJob;
use App\Models\CandidateNotification;
use App\Models\CandidateProfileView;
use App\Models\MaritimeJob;
use App\Models\PoolCandidate;
use App\Models\SeafarerCertificate;

class CandidateNotificationService
{
    /**
     * Check if a similar notification was recently sent (dedup/throttle).
     * Returns true if we should SKIP creating a new notification.
     */
    private function isDuplicate(string $candidateId, string $type, int $cooldownMinutes = 360, ?array $matchData = null): bool
    {
        $query = CandidateNotification::where('pool_candidate_id', $candidateId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subMinutes($cooldownMinutes));

        // For profile views, also match by company name to avoid spam from same company
        if ($matchData) {
            foreach ($matchData as $key => $value) {
                if ($value !== null) {
                    $query->whereJsonContains("data->{$key}", $value);
                }
            }
        }

        return $query->exists();
    }

    /**
     * Record a profile view and create a notification.
     */
    public function notifyProfileView(
        PoolCandidate $candidate,
        string $viewerType,
        ?string $viewerId,
        ?string $viewerName,
        string $context,
        ?array $contextMeta = null,
        ?string $companyName = null
    ): CandidateProfileView {
        // Skip notifications for demo candidates
        if ($candidate->is_demo) {
            return CandidateProfileView::create([
                'pool_candidate_id' => $candidate->id,
                'viewer_type' => $viewerType,
                'viewer_id' => $viewerId,
                'viewer_name' => $viewerName,
                'company_name' => $companyName ?? $viewerName,
                'context' => $context,
                'context_meta' => $contextMeta,
                'viewed_at' => now(),
                'is_demo' => true,
            ]);
        }
        $view = CandidateProfileView::create([
            'pool_candidate_id' => $candidate->id,
            'viewer_type' => $viewerType,
            'viewer_id' => $viewerId,
            'viewer_name' => $viewerName,
            'company_name' => $companyName ?? $viewerName,
            'context' => $context,
            'context_meta' => $contextMeta,
            'viewed_at' => now(),
        ]);

        // Dedup: skip notification if same company viewed within 6 hours
        if ($this->isDuplicate($candidate->id, CandidateNotification::TYPE_PROFILE_VIEWED, 360, ['company_name' => $companyName ?? $viewerName])) {
            return $view;
        }

        // Determine notification tier based on context
        $tier = match ($context) {
            CandidateProfileView::CONTEXT_PRESENTATION => CandidateNotification::TIER_FREE,
            CandidateProfileView::CONTEXT_SEARCH => CandidateNotification::TIER_PLUS,
            CandidateProfileView::CONTEXT_BROWSE => CandidateNotification::TIER_PRO,
            default => CandidateNotification::TIER_FREE,
        };

        // Build notification body based on tier
        $title = 'Your profile was viewed';
        $body = null;
        $data = [];

        $displayName = $companyName ?? $viewerName;

        if ($tier === CandidateNotification::TIER_FREE) {
            $body = 'A company viewed your profile.';
        } elseif ($tier === CandidateNotification::TIER_PLUS) {
            $body = $displayName
                ? "Your profile was viewed by {$displayName}."
                : 'A company viewed your profile.';
            $data['viewer_name'] = $viewerName;
            $data['company_name'] = $companyName;
        } else {
            $body = $displayName
                ? "Your profile was viewed by {$displayName}."
                : 'A company viewed your profile.';
            $data['viewer_name'] = $viewerName;
            $data['company_name'] = $companyName;
            $data['context'] = $context;
            $data['context_meta'] = $contextMeta;
        }

        $notification = CandidateNotification::create([
            'pool_candidate_id' => $candidate->id,
            'type' => CandidateNotification::TYPE_PROFILE_VIEWED,
            'title' => $title,
            'body' => $body,
            'data' => $data ?: null,
            'tier_required' => $tier,
            'created_at' => now(),
        ]);

        SendCandidateNotificationJob::dispatch($notification);

        return $view;
    }

    /**
     * Get notifications for a candidate, filtered by tier.
     */
    public function getNotifications(string $candidateId, string $tier = 'free', int $limit = 50): array
    {
        $notifications = CandidateNotification::forCandidate($candidateId)
            ->forTier($tier)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $unreadCount = CandidateNotification::forCandidate($candidateId)
            ->forTier($tier)
            ->unread()
            ->count();

        return [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * Mark notifications as read.
     */
    public function markRead(string $candidateId, ?array $notificationIds = null): int
    {
        $query = CandidateNotification::forCandidate($candidateId)->unread();

        if ($notificationIds) {
            $query->whereIn('id', $notificationIds);
        }

        return $query->update(['read_at' => now()]);
    }

    /**
     * Notify candidate of a status change (shortlisted, rejected, hired).
     */
    public function notifyStatusChange(PoolCandidate $candidate, string $status, array $contextData = []): ?CandidateNotification
    {
        // Skip notifications for demo candidates
        if ($candidate->is_demo) {
            return null;
        }

        // Dedup: skip if same status+company notified within 24 hours
        $type = match ($status) {
            'hired' => CandidateNotification::TYPE_HIRED,
            'shortlisted' => CandidateNotification::TYPE_SHORTLISTED,
            'rejected' => CandidateNotification::TYPE_REJECTED,
            default => CandidateNotification::TYPE_STATUS_CHANGED,
        };
        if ($this->isDuplicate($candidate->id, $type, 1440, ['company_name' => $contextData['company_name'] ?? null])) {
            return null;
        }

        $tier = match ($status) {
            'hired' => CandidateNotification::TIER_FREE,
            'shortlisted' => CandidateNotification::TIER_PLUS,
            'rejected' => CandidateNotification::TIER_PLUS,
            default => CandidateNotification::TIER_FREE,
        };

        $title = match ($status) {
            'hired' => 'Congratulations! You\'ve been hired',
            'shortlisted' => 'You\'ve been shortlisted',
            'rejected' => 'Application update',
            default => 'Status update',
        };

        $companyName = $contextData['company_name'] ?? 'A company';
        $body = match ($status) {
            'hired' => "{$companyName} has hired you. Congratulations!",
            'shortlisted' => "{$companyName} has shortlisted you for further consideration.",
            'rejected' => "Your application with {$companyName} was not selected at this time.",
            default => "Your status has been updated.",
        };

        $notification = CandidateNotification::create([
            'pool_candidate_id' => $candidate->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $contextData ?: null,
            'tier_required' => $tier,
            'created_at' => now(),
        ]);

        SendCandidateNotificationJob::dispatch($notification);

        return $notification;
    }

    /**
     * Notify candidate of a job match.
     */
    public function notifyJobMatch(PoolCandidate $candidate, MaritimeJob $job): ?CandidateNotification
    {
        // Skip notifications for demo candidates
        if ($candidate->is_demo) {
            return null;
        }

        // Dedup: skip if already notified about this job within 24 hours
        if ($this->isDuplicate($candidate->id, CandidateNotification::TYPE_JOB_MATCH, 1440, ['job_id' => $job->id])) {
            return null;
        }

        $companyName = $job->company?->company_name ?? 'A company';

        $notification = CandidateNotification::create([
            'pool_candidate_id' => $candidate->id,
            'type' => CandidateNotification::TYPE_JOB_MATCH,
            'title' => 'New job match',
            'body' => "A new {$job->rank} position is available at {$companyName}.",
            'data' => [
                'job_id' => $job->id,
                'rank' => $job->rank,
                'company_name' => $companyName,
                'vessel_type' => $job->vessel_type,
            ],
            'tier_required' => CandidateNotification::TIER_FREE,
            'created_at' => now(),
        ]);

        SendCandidateNotificationJob::dispatch($notification);

        return $notification;
    }

    /**
     * Notify candidate of an expiring certificate.
     */
    public function notifyCertificateExpiring(PoolCandidate $candidate, SeafarerCertificate $cert, int $daysLeft): ?CandidateNotification
    {
        // Skip notifications for demo candidates
        if ($candidate->is_demo) {
            return null;
        }

        // Dedup: skip if already notified about this certificate within 7 days
        if ($this->isDuplicate($candidate->id, CandidateNotification::TYPE_CERTIFICATE_EXPIRING, 10080, ['certificate_id' => $cert->id])) {
            return null;
        }

        $notification = CandidateNotification::create([
            'pool_candidate_id' => $candidate->id,
            'type' => CandidateNotification::TYPE_CERTIFICATE_EXPIRING,
            'title' => 'Certificate expiring soon',
            'body' => "Your {$cert->certificate_type} certificate expires in {$daysLeft} days.",
            'data' => [
                'certificate_id' => $cert->id,
                'certificate_type' => $cert->certificate_type,
                'days_left' => $daysLeft,
                'expiry_date' => $cert->expiry_date?->toIso8601String(),
            ],
            'tier_required' => CandidateNotification::TIER_FREE,
            'created_at' => now(),
        ]);

        SendCandidateNotificationJob::dispatch($notification);

        return $notification;
    }

    /**
     * Get profile view stats for a candidate.
     */
    public function getViewStats(string $candidateId, int $days = 30): array
    {
        $views = CandidateProfileView::forCandidate($candidateId)
            ->recent($days)
            ->get();

        return [
            'total_views' => $views->count(),
            'unique_viewers' => $views->unique('viewer_id')->count(),
            'by_context' => $views->groupBy('context')->map->count()->toArray(),
            'recent_viewers' => $views->take(10)->map(fn ($v) => [
                'viewer_name' => $v->viewer_name,
                'context' => $v->context,
                'viewed_at' => $v->viewed_at->toIso8601String(),
            ])->values()->toArray(),
        ];
    }
}
