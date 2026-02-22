<?php

namespace App\Console\Commands;

use App\Jobs\VerifyContractAisJob;
use App\Models\CandidateContract;
use App\Models\ContractAisVerification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyPendingAisCommand extends Command
{
    protected $signature = 'trust:ais:verify-pending {--limit=100} {--dry-run}';
    protected $description = 'Dispatch AIS verification jobs for contracts that need verification';

    public function handle(): int
    {
        if (!config('maritime.ais_v1')) {
            $this->warn('AIS v1 feature flag is disabled. Aborting.');
            return self::SUCCESS;
        }

        if (!config('maritime.ais_auto_verify')) {
            $this->warn('AIS auto-verify is disabled. Aborting.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        // Find contracts that need verification:
        // - Must have vessel_imo
        // - Must have end_date (completed contracts only)
        // - Latest contract_ais_verifications status NOT 'verified', or no record
        // - Also no 'verified' in legacy ais_verifications table
        $contracts = CandidateContract::query()
            ->whereNotNull('vessel_imo')
            ->whereNotNull('end_date')
            ->where(function ($q) {
                $q->whereDoesntHave('contractAisVerifications', function ($sub) {
                    $sub->where('status', ContractAisVerification::STATUS_VERIFIED);
                });
            })
            ->where(function ($q) {
                $q->whereDoesntHave('aisVerification', function ($sub) {
                    $sub->where('status', 'verified');
                });
            })
            ->orderBy('start_date')
            ->limit($limit)
            ->get();

        if ($contracts->isEmpty()) {
            $this->info('No contracts need AIS verification.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("DRY RUN: Found {$contracts->count()} contracts needing verification:");
            foreach ($contracts as $c) {
                $this->line("  - {$c->id} | IMO: {$c->vessel_imo} | {$c->vessel_name} | {$c->start_date->toDateString()} → {$c->end_date->toDateString()}");
            }
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($contracts as $contract) {
            VerifyContractAisJob::dispatch($contract->id, 'cron');
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} verification jobs.");

        return self::SUCCESS;
    }
}
