<?php

namespace App\Console\Commands;

use App\Jobs\ComputeTechnicalScoreJob;
use App\Models\PoolCandidate;
use Illuminate\Console\Command;

class ComputePendingRankStcwCommand extends Command
{
    protected $signature = 'trust:rank-stcw:compute-pending {--limit=200} {--dry-run} {--force}';
    protected $description = 'Dispatch Rank & STCW technical score computation jobs for candidates with contracts';

    public function handle(): int
    {
        if (!config('maritime.rank_stcw_v1')) {
            $this->warn('Rank & STCW v1 feature flag is disabled. Aborting.');
            return self::SUCCESS;
        }

        if (!config('maritime.rank_stcw_auto_compute') && !$this->option('force')) {
            $this->warn('Rank & STCW auto-compute is disabled. Use --force to override. Aborting.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        // Find maritime candidates with contracts that need (re)computation:
        // - Has contracts
        // - Trust profile has no rank_stcw data, OR contracts updated since last computation
        $candidates = PoolCandidate::query()
            ->where('seafarer', true)
            ->whereHas('contracts')
            ->where(function ($q) {
                // No trust profile at all
                $q->whereDoesntHave('trustProfile')
                // Or trust profile exists but no rank_stcw data
                ->orWhereHas('trustProfile', function ($sub) {
                    $sub->whereRaw("JSON_EXTRACT(detail_json, '$.rank_stcw') IS NULL");
                })
                // Or contracts updated after last computation
                ->orWhereHas('contracts', function ($sub) {
                    $sub->whereRaw('candidate_contracts.updated_at > COALESCE((
                        SELECT JSON_UNQUOTE(JSON_EXTRACT(ctp.detail_json, \'$.rank_stcw.computed_at\'))
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
            $this->info('No candidates need Rank & STCW computation.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("DRY RUN: Found {$candidates->count()} candidates needing Rank & STCW computation:");
            foreach ($candidates as $c) {
                $contractCount = $c->contracts()->count();
                $this->line("  - {$c->id} | {$c->full_name} | {$contractCount} contracts");
            }
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($candidates as $candidate) {
            ComputeTechnicalScoreJob::dispatch($candidate->id);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} Rank & STCW computation jobs.");

        return self::SUCCESS;
    }
}
