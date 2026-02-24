<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccessRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireMarketplaceRequestsCommand extends Command
{
    protected $signature = 'marketplace:expire-requests';

    protected $description = 'Mark pending marketplace access requests with expired tokens as expired';

    public function handle(): int
    {
        $count = MarketplaceAccessRequest::where('status', MarketplaceAccessRequest::STATUS_PENDING)
            ->where('token_expires_at', '<', now())
            ->update([
                'status' => MarketplaceAccessRequest::STATUS_EXPIRED,
            ]);

        if ($count > 0) {
            Log::info('marketplace:expire-requests: expired requests', ['count' => $count]);
            $this->info("Expired {$count} marketplace access request(s).");
        } else {
            $this->info('No marketplace requests to expire.');
        }

        return self::SUCCESS;
    }
}
