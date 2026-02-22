<?php

namespace App\Console\Commands;

use App\Jobs\ComputePredictiveRiskJob;
use App\Models\PoolCandidate;
use Illuminate\Console\Command;

class ComputePendingPredictiveRiskCommand extends Command
{
    protected $signature = 'trust:predictive:compute-pending {--limit=200} {--dry-run} {--force}';
    protected $description = 'Dispatch predictive risk computation jobs for eligible maritime candidates';

    public function handle(): int
    {
        if (!config('maritime.predictive_v1')) {
            $this->warn('Predictive risk v1 feature flag is disabled. Aborting.');
            return self::SUCCESS;
        }

        if (!config('maritime.predictive_auto_compute') && !$this->option('force')) {
            $this->warn('Predictive risk auto-compute is disabled. Use --force to override. Aborting.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        // Find maritime candidates with trust profiles that have risk data
        // (predictive risk needs at least stability/risk data to be meaningful)
        $candidates = PoolCandidate::query()
            ->where('seafarer', true)
            ->whereHas('trustProfile', function ($q) {
                $q->whereNotNull('risk_tier');
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No candidates eligible for predictive risk computation.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("DRY RUN: Found {$candidates->count()} candidates eligible for predictive risk:");
            foreach ($candidates as $c) {
                $riskTier = $c->trustProfile?->risk_tier ?? 'unknown';
                $this->line("  - {$c->id} | {$c->full_name} | risk_tier: {$riskTier}");
            }
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($candidates as $candidate) {
            ComputePredictiveRiskJob::dispatch($candidate->id);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} predictive risk computation jobs.");

        return self::SUCCESS;
    }
}
