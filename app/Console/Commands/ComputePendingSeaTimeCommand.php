<?php

namespace App\Console\Commands;

use App\Jobs\ComputeSeaTimeJob;
use App\Models\PoolCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComputePendingSeaTimeCommand extends Command
{
    protected $signature = 'trust:sea-time:compute-pending {--limit=200} {--dry-run} {--force}';
    protected $description = 'Dispatch sea-time computation jobs for candidates with contracts but no recent sea-time logs';

    public function handle(): int
    {
        if (!config('maritime.sea_time_v1')) {
            $this->warn('Sea-time v1 feature flag is disabled. Aborting.');
            return self::SUCCESS;
        }

        if (!config('maritime.sea_time_auto_compute') && !$this->option('force')) {
            $this->warn('Sea-time auto-compute is disabled. Use --force to override. Aborting.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        // Find maritime candidates with contracts but no sea-time logs,
        // or whose contracts have been updated since last computation
        $candidates = PoolCandidate::query()
            ->where('seafarer', true)
            ->whereHas('contracts')
            ->where(function ($q) {
                $q->whereDoesntHave('seaTimeLogs')
                ->orWhereHas('contracts', function ($sub) {
                    $sub->whereRaw('candidate_contracts.updated_at > COALESCE((
                        SELECT MAX(stl.computed_at)
                        FROM sea_time_logs stl
                        WHERE stl.pool_candidate_id = candidate_contracts.pool_candidate_id
                    ), \'1970-01-01\')');
                });
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No candidates need sea-time computation.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("DRY RUN: Found {$candidates->count()} candidates needing sea-time computation:");
            foreach ($candidates as $c) {
                $contractCount = $c->contracts()->count();
                $this->line("  - {$c->id} | {$c->full_name} | {$contractCount} contracts");
            }
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($candidates as $candidate) {
            ComputeSeaTimeJob::dispatch($candidate->id);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} sea-time computation jobs.");

        return self::SUCCESS;
    }
}
