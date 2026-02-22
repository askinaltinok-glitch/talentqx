<?php

namespace App\Console\Commands;

use App\Jobs\ComputeCompliancePackJob;
use App\Models\PoolCandidate;
use Illuminate\Console\Command;

class ComputePendingComplianceCommand extends Command
{
    protected $signature = 'trust:compliance:compute-pending {--limit=500} {--dry-run} {--force}';
    protected $description = 'Dispatch compliance pack computation jobs for candidates with CRI scores';

    public function handle(): int
    {
        if (!config('maritime.compliance_v1')) {
            $this->warn('Compliance v1 feature flag is disabled. Aborting.');
            return self::SUCCESS;
        }

        if (!config('maritime.compliance_auto_compute') && !$this->option('force')) {
            $this->warn('Compliance auto-compute is disabled. Use --force to override. Aborting.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        // Find maritime candidates with CRI that need (re)computation
        $candidates = PoolCandidate::query()
            ->where('seafarer', true)
            ->whereHas('trustProfile', fn($q) => $q->whereNotNull('cri_score'))
            ->where(function ($q) {
                $q->whereHas('trustProfile', function ($sub) {
                    $sub->whereNull('compliance_computed_at');
                })
                ->orWhereHas('trustProfile', function ($sub) {
                    $sub->whereColumn('compliance_computed_at', '<', 'computed_at');
                });
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No candidates need compliance pack computation.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("DRY RUN: Found {$candidates->count()} candidates needing compliance pack computation:");
            foreach ($candidates as $c) {
                $tp = $c->trustProfile;
                $this->line("  - {$c->id} | {$c->full_name} | CRI: {$tp->cri_score}");
            }
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($candidates as $candidate) {
            ComputeCompliancePackJob::dispatch($candidate->id)->delay(now()->addSeconds(60));
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} compliance pack computation jobs (60s delay).");

        return self::SUCCESS;
    }
}
