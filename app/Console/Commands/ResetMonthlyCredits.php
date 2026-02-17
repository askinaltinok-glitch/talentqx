<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Billing\CreditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetMonthlyCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credits:reset-monthly {--dry-run : Show what would be reset without actually resetting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset monthly credits for all companies at the start of each billing period';

    public function __construct(
        private CreditService $creditService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $today = now();
        $startOfMonth = $today->copy()->startOfMonth();

        $this->info("Running monthly credit reset for {$startOfMonth->format('F Y')}");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get all companies that need reset
        // Companies whose period hasn't started this month yet
        $companies = Company::query()
            ->where(function ($query) use ($startOfMonth) {
                $query->whereNull('credits_period_start')
                    ->orWhere('credits_period_start', '<', $startOfMonth);
            })
            ->get();

        $this->info("Found {$companies->count()} companies to reset");

        $resetCount = 0;
        $errorCount = 0;

        foreach ($companies as $company) {
            try {
                $previousUsed = $company->credits_used;
                $previousRemaining = $company->getRemainingCredits();

                if ($isDryRun) {
                    $this->line("  [DRY RUN] Would reset {$company->name}: {$previousUsed} used -> 0");
                } else {
                    $this->creditService->resetPeriod($company);
                    $company->refresh();

                    $this->line("  Reset {$company->name}: {$previousUsed} used -> 0 (new remaining: {$company->getRemainingCredits()})");
                }

                $resetCount++;

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("  Failed to reset {$company->name}: {$e->getMessage()}");

                Log::error('Credit reset failed', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  - Companies processed: {$resetCount}");

        if ($errorCount > 0) {
            $this->error("  - Errors: {$errorCount}");
        }

        if ($isDryRun) {
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        }

        Log::info('Monthly credit reset completed', [
            'month' => $startOfMonth->format('Y-m'),
            'companies_reset' => $resetCount,
            'errors' => $errorCount,
            'dry_run' => $isDryRun,
        ]);

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
