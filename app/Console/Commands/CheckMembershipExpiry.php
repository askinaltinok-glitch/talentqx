<?php

namespace App\Console\Commands;

use App\Models\CandidateMembership;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckMembershipExpiry extends Command
{
    protected $signature = 'memberships:check-expiry {--dry-run}';
    protected $description = 'Downgrade expired candidate memberships to free tier';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $expired = CandidateMembership::query()
            ->where('tier', '!=', 'free')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = $expired->count();

        if ($count === 0) {
            $this->info('No expired memberships found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} expired memberships.");

        if ($dryRun) {
            $this->warn('DRY RUN — no changes made.');
            foreach ($expired as $m) {
                $this->line("  Would downgrade: candidate {$m->pool_candidate_id} from {$m->tier} (expired {$m->expires_at})");
            }
            return Command::SUCCESS;
        }

        $downgraded = 0;
        foreach ($expired as $membership) {
            $oldTier = $membership->tier;
            $membership->update(['tier' => 'free']);
            $downgraded++;

            Log::info('Membership expired, downgraded to free', [
                'candidate_id' => $membership->pool_candidate_id,
                'old_tier' => $oldTier,
                'expired_at' => $membership->expires_at,
            ]);
        }

        $this->info("Downgraded {$downgraded} expired memberships to free.");
        return Command::SUCCESS;
    }
}
