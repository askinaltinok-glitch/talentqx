<?php

namespace App\Console\Commands;

use App\Services\DemoCandidateService;
use Illuminate\Console\Command;

class CreateDemoCandidateCommand extends Command
{
    protected $signature = 'demo:create-candidate
        {--profile= : Profile index (0-4) to use, random if omitted}
        {--count=1 : Number of demo candidates to create}
        {--cleanup : Delete all existing demo candidates instead}';

    protected $description = 'Create a demo maritime candidate with full pipeline (interview + scoring + assessments)';

    public function __construct(
        private DemoCandidateService $demoService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!config('app.demo_mode')) {
            $this->error('Demo mode is disabled. Set DEMO_MODE=true in .env');
            return Command::FAILURE;
        }

        if ($this->option('cleanup')) {
            return $this->handleCleanup();
        }

        $count = (int) $this->option('count');
        $profileIndex = $this->option('profile') !== null ? (int) $this->option('profile') : null;

        $this->info("Creating {$count} demo candidate(s)...");
        $this->newLine();

        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $index = $profileIndex ?? ($count > 1 ? $i % 5 : null);

            try {
                $result = $this->demoService->createDemoCandidate($index);

                if ($result['success']) {
                    $data = $result['data'];
                    $this->info("  [{$data['candidate']['rank']}] {$data['candidate']['name']}");
                    $this->line("    Candidate:  {$data['candidate']['id']}");
                    $this->line("    Interview:  {$data['interview']['id']}");
                    $this->line("    Decision:   {$data['interview']['decision']}");
                    $this->line("    Score:      {$data['interview']['calibrated_score']} (raw: {$data['interview']['raw_score']})");
                    $this->line("    English:    {$data['interview']['english_score']} ({$data['candidate']['english_level']})");
                    $this->line("    Status:     {$data['candidate']['status']}");
                    $this->newLine();
                    $results[] = $data;
                } else {
                    $this->error("  Failed: {$result['error']}");
                }
            } catch (\Throwable $e) {
                $this->error("  Error: {$e->getMessage()}");
            }
        }

        $this->info("Created " . count($results) . " demo candidate(s).");
        return Command::SUCCESS;
    }

    private function handleCleanup(): int
    {
        $result = $this->demoService->cleanupDemoCandidates();

        if ($result['success']) {
            $this->info("Deleted {$result['deleted_count']} demo candidate(s).");
            return Command::SUCCESS;
        }

        $this->error('Cleanup failed.');
        return Command::FAILURE;
    }
}
