<?php

namespace App\Services;

use App\Jobs\SendAdminPushNotificationJob;
use App\Models\AdminNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class AdminNotificationService
{
    private const UNREAD_CACHE_KEY = 'admin_notifications:unread_count';
    private const UNREAD_CACHE_TTL = 60; // seconds

    /**
     * Create a notification and dispatch push to all admin subscribers.
     */
    public function create(string $type, string $title, ?string $body = null, ?array $data = null): AdminNotification
    {
        $notification = AdminNotification::create([
            'type'  => $type,
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
        ]);

        // Bust unread cache
        Cache::forget(self::UNREAD_CACHE_KEY);

        // Dispatch push notification to all subscribed admins
        SendAdminPushNotificationJob::dispatch($notification);

        return $notification;
    }

    /**
     * Paginated list with optional type filter.
     */
    public function list(int $page = 1, int $perPage = 20, ?string $type = null): LengthAwarePaginator
    {
        $query = AdminNotification::query()->orderByDesc('created_at');

        if ($type) {
            $query->ofType($type);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Unread count (Redis cached for 60s).
     */
    public function unreadCount(): int
    {
        return (int) Cache::remember(self::UNREAD_CACHE_KEY, self::UNREAD_CACHE_TTL, function () {
            return AdminNotification::unread()->count();
        });
    }

    /**
     * Mark specific notifications as read.
     */
    public function markRead(array $ids): int
    {
        $count = AdminNotification::whereIn('id', $ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Cache::forget(self::UNREAD_CACHE_KEY);

        return $count;
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(): int
    {
        $count = AdminNotification::unread()->update(['read_at' => now()]);
        Cache::forget(self::UNREAD_CACHE_KEY);

        return $count;
    }

    /* ── Convenience methods for event hooks ── */

    public function notifyNewCandidate(string $candidateName, string $rank, string $candidateId): AdminNotification
    {
        return $this->create(
            AdminNotification::TYPE_NEW_CANDIDATE,
            "New candidate: {$candidateName}",
            "Applied for {$rank}",
            ['candidate_id' => $candidateId, 'url' => "/octo-admin/candidates/{$candidateId}"]
        );
    }

    public function notifyDemoRequest(string $fullName, string $company): AdminNotification
    {
        return $this->create(
            AdminNotification::TYPE_DEMO_REQUEST,
            "Demo request: {$company}",
            "From {$fullName}",
            ['url' => '/octo-admin/demo-requests']
        );
    }

    public function notifyInterviewCompleted(string $candidateName, string $interviewId, string $candidateId): AdminNotification
    {
        return $this->create(
            AdminNotification::TYPE_INTERVIEW_COMPLETED,
            "Interview completed: {$candidateName}",
            null,
            ['interview_id' => $interviewId, 'candidate_id' => $candidateId, 'url' => "/octo-admin/candidates/{$candidateId}"]
        );
    }

    public function notifyCompanyOnboard(string $companyName, string $companyId): AdminNotification
    {
        return $this->create(
            AdminNotification::TYPE_COMPANY_ONBOARD,
            "New company: {$companyName}",
            'Onboarded via wizard',
            ['company_id' => $companyId, 'url' => '/octo-admin/onboard']
        );
    }
}
