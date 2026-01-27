<?php

namespace App\Console\Commands;

use App\Services\KVKK\RetentionService;
use Illuminate\Console\Command;

class ProcessDataRetention extends Command
{
    protected $signature = 'kvkk:process-retention {--dry-run : Run without actually deleting data}';
    protected $description = 'Process data retention policies and delete expired data (KVKK compliance)';

    public function handle(RetentionService $retentionService): int
    {
        $this->info('Starting data retention processing...');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No data will be deleted');
            $this->newLine();
        }

        try {
            $results = $retentionService->processExpiredData();

            $this->info('Retention processing completed:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Candidates Erased', $results['candidates_erased']],
                    ['Interviews Erased', $results['interviews_erased']],
                    ['Media Files Deleted', $results['media_deleted']],
                    ['Errors', count($results['errors'])],
                ]
            );

            if (!empty($results['errors'])) {
                $this->newLine();
                $this->error('Errors encountered:');
                foreach ($results['errors'] as $error) {
                    $this->line("  - Candidate {$error['candidate_id']}: {$error['error']}");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Retention processing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
