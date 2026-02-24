<?php

namespace App\Console\Commands\Maritime;

use App\Models\RoleFitEvaluation;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RoleFitRetentionCleanupCommand extends Command
{
    protected $signature = 'maritime:role-fit:retention-cleanup
        {--days=180 : Delete evaluations older than N days}
        {--dry-run : Show what would be deleted without deleting}
        {--batch=500 : Batch size for deletion}
        {--force : Skip confirmation prompt}';

    protected $description = 'Delete old role_fit_evaluations beyond retention period (batched)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $batchSize = (int) $this->option('batch');
        $force = (bool) $this->option('force');

        $cutoff = Carbon::now()->subDays($days);

        $totalCount = RoleFitEvaluation::where('created_at', '<', $cutoff)->count();

        if ($totalCount === 0) {
            $this->info("No evaluations older than {$days} days found. Nothing to do.");
            return 0;
        }

        $this->info("Found {$totalCount} evaluations older than {$days} days (before {$cutoff->toDateString()}).");

        if ($dryRun) {
            $this->warn("[DRY-RUN] Would delete {$totalCount} records in batches of {$batchSize}.");
            return 0;
        }

        if (!$force && !$this->confirm("Delete {$totalCount} records? This cannot be undone.")) {
            $this->info('Cancelled.');
            return 0;
        }

        $deleted = 0;
        $batches = 0;

        do {
            $count = RoleFitEvaluation::where('created_at', '<', $cutoff)
                ->limit($batchSize)
                ->delete();

            $deleted += $count;
            $batches++;

            if ($count > 0) {
                $this->info("  Batch {$batches}: deleted {$count} (total: {$deleted}/{$totalCount})");
            }
        } while ($count > 0);

        $this->info("Retention cleanup complete. Deleted {$deleted} evaluations in {$batches} batches.");

        return 0;
    }
}
