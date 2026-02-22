<?php

namespace App\Console\Commands;

use App\Jobs\ComputeStabilityRiskJob;
use App\Models\PoolCandidate;
use Illuminate\Console\Command;

class ComputePendingStabilityRiskCommand extends Command
{
    protected $signature = 'trust:stability:compute-pending {--limit=200} {--dry-run} {--force}';
    protected $description = 'Dispatch stability & risk computation jobs for candidates with contracts';

    public function handle(): int
    {
        if (!config('maritime.stability_v1')) {
            $this->warn('Stability v1 feature flag is disabled. Aborting.');
            return self::SUCCESS;
        }

        if (!config('maritime.stability_auto_compute') && !$this->option('force')) {
            $this->warn('Stability auto-compute is disabled. Use --force to override. Aborting.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        // Find maritime candidates with contracts that need (re)computation
        $candidates = PoolCandidate::query()
            ->where('seafarer', true)
            ->whereHas('contracts')
            ->where(function ($q) {
                $q->whereDoesntHave('trustProfile')
                ->orWhereHas('trustProfile', function ($sub) {
                    $sub->whereNull('risk_tier');
                })
                ->orWhereHas('contracts', function ($sub) {
                    $sub->whereRaw('candidate_contracts.updated_at > COALESCE((
                        SELECT JSON_UNQUOTE(JSON_EXTRACT(ctp.detail_json, \'$.stability_risk.computed_at\'))
                        FROM candidate_trust_profiles ctp
                        WHERE ctp.pool_candidate_id = candidate_contracts.pool_candidate_id
                        LIMIT 1
                    ), \'1970-01-01\')');
                });
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No candidates need stability & risk computation.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("DRY RUN: Found {$candidates->count()} candidates needing stability & risk computation:");
            foreach ($candidates as $c) {
                $contractCount = $c->contracts()->count();
                $this->line("  - {$c->id} | {$c->full_name} | {$contractCount} contracts");
            }
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($candidates as $candidate) {
            ComputeStabilityRiskJob::dispatch($candidate->id);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} stability & risk computation jobs.");

        return self::SUCCESS;
    }
}
