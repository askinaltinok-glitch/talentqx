<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbandonStaleAssessmentsCommand extends Command
{
    protected $signature = 'orghealth:abandon-stale
                            {--days=7 : Mark assessments older than N days as abandoned}
                            {--dry-run : Show what would be abandoned without making changes}';

    protected $description = 'Mark OrgHealth assessments started but not completed within the given days as abandoned';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $query = DB::table('org_assessments')
            ->where('status', 'started')
            ->where('started_at', '<', $cutoff);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No stale assessments found.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$count} stale assessments (started > {$days} days ago).");

        if (!$dryRun) {
            $updated = DB::table('org_assessments')
                ->where('status', 'started')
                ->where('started_at', '<', $cutoff)
                ->update(['status' => 'abandoned']);

            Log::info("OrgHealth: Abandoned {$updated} stale assessments older than {$days} days.");
            $this->info("Abandoned {$updated} assessments.");
        }

        return self::SUCCESS;
    }
}
