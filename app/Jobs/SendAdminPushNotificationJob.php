<?php

namespace App\Jobs;

use App\Models\AdminNotification;
use App\Models\AdminPushSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use App\Jobs\Traits\BrandAware;

class SendAdminPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout = 30;

    public function __construct(
        private AdminNotification $notification,
    ) {
        $this->onQueue('default');
        $this->captureBrand();
    }

    public function handle(): void
    {
        $this->setBrandDatabase();
        $subscriptions = AdminPushSubscription::all();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $auth = [
            'VAPID' => [
                'subject'    => config('services.web_push.subject'),
                'publicKey'  => config('services.web_push.public_key'),
                'privateKey' => config('services.web_push.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);

        $payload = json_encode([
            'title' => $this->notification->title,
            'body'  => $this->notification->body ?? '',
            'icon'  => '/icons/octopus-192.png',
            'badge' => '/icons/octopus-badge-72.png',
            'data'  => [
                'url'  => $this->notification->data['url'] ?? '/octo-admin/notifications',
                'id'   => $this->notification->id,
                'type' => $this->notification->type,
            ],
        ]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification($sub->toWebPushSubscription(), $payload);
        }

        $expiredIds = [];

        /** @var MessageSentReport $report */
        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                // Collect expired subscriptions for cleanup
                $endpoint = $report->getEndpoint();
                $expired = $subscriptions->first(fn ($s) => $s->endpoint === $endpoint);
                if ($expired) {
                    $expiredIds[] = $expired->id;
                }
            }

            if (!$report->isSuccess()) {
                Log::channel('daily')->warning('[AdminPush] Failed to send', [
                    'endpoint' => substr($report->getEndpoint(), 0, 80),
                    'reason'   => $report->getReason(),
                ]);
            }
        }

        // Cleanup expired subscriptions
        if (!empty($expiredIds)) {
            AdminPushSubscription::whereIn('id', $expiredIds)->delete();
            Log::channel('daily')->info('[AdminPush] Cleaned expired subscriptions', [
                'count' => count($expiredIds),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('daily')->error('[AdminPush] Job failed', [
            'notification_id' => $this->notification->id,
            'error' => $e->getMessage(),
        ]);
    }
}
