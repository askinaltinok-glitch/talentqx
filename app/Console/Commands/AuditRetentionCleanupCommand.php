<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditRetentionCleanupCommand extends Command
{
    protected $signature = 'retention:audit-cleanup
                            {--dry-run : Run without making changes}
                            {--batch-size=5000 : Number of records to delete per batch}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Purge audit_logs older than the configured retention period (default 7 years)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $retentionDays = config('retention.periods.audit_logs.default', 365 * 7);
        $cutoff = now()->subDays($retentionDays);

        $this->info('========================================');
        $this->info('Audit Log Retention Cleanup');
        $this->info('========================================');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->info("Retention: {$retentionDays} days");
        $this->info("Cutoff: {$cutoff->toDateString()}");
        $this->info("Batch size: {$batchSize}");
        $this->newLine();

        $total = AuditLog::where('created_at', '<', $cutoff)->count();
        $this->info("Found {$total} audit log records older than cutoff");

        if ($total === 0) {
            $this->info('Nothing to purge.');
            return 0;
        }

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm("This will permanently delete {$total} audit log records. Continue?")) {
                $this->warn('Aborted.');
                return 1;
            }
        }

        if ($dryRun) {
            $this->warn("DRY RUN — would delete {$total} records.");
            return 0;
        }

        $deleted = 0;

        while (true) {
            $count = AuditLog::where('created_at', '<', $cutoff)
                ->limit($batchSize)
                ->delete();

            if ($count === 0) {
                break;
            }

            $deleted += $count;
            $this->info("  Deleted batch: {$deleted}/{$total}");
        }

        Log::info('Audit retention cleanup completed', [
            'deleted' => $deleted,
            'cutoff' => $cutoff->toIso8601String(),
        ]);

        $this->newLine();
        $this->info("Total deleted: {$deleted}");

        return 0;
    }
}
