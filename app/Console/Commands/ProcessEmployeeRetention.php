<?php

namespace App\Console\Commands;

use App\Services\KVKK\EmployeeDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessEmployeeRetention extends Command
{
    protected $signature = 'kvkk:process-employee-retention
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Process employee data retention - delete expired records per KVKK compliance';

    public function handle(): int
    {
        $this->info('Starting employee data retention processing...');
        Log::info('KVKK: Employee retention processing started');

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }

        $service = new EmployeeDataService();

        if ($isDryRun) {
            // Just show what would be processed
            $stats = $service->getRetentionStats();
            $this->info("Approaching expiry (30 days): {$stats['approaching_expiry']} employees");
            $this->info("Already erased: {$stats['erased_employees']} employees");
            return Command::SUCCESS;
        }

        try {
            $processed = $service->processExpiredRetention();

            $erased = collect($processed)->where('status', 'erased')->count();
            $failed = collect($processed)->where('status', 'failed')->count();

            $this->info("Processed: {$erased} employees erased, {$failed} failed");

            if ($failed > 0) {
                $this->warn('Some erasures failed. Check logs for details.');
                foreach (collect($processed)->where('status', 'failed') as $failure) {
                    $this->error("  - Employee {$failure['employee_id']}: {$failure['error']}");
                }
            }

            Log::info('KVKK: Employee retention processing completed', [
                'erased' => $erased,
                'failed' => $failed,
            ]);

            return $failed > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Retention processing failed: ' . $e->getMessage());
            Log::error('KVKK: Employee retention processing failed', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
