<?php

namespace App\Console\Commands;

use App\Models\DataErasureRequest;
use App\Services\KVKK\DataErasureService;
use Illuminate\Console\Command;

class ProcessErasureRequests extends Command
{
    protected $signature = 'kvkk:process-erasure-requests';
    protected $description = 'Process pending data erasure requests (Right to be Forgotten)';

    public function handle(DataErasureService $erasureService): int
    {
        $this->info('Processing pending erasure requests...');
        $this->newLine();

        $pendingRequests = DataErasureRequest::pending()
            ->with('candidate')
            ->get();

        if ($pendingRequests->isEmpty()) {
            $this->info('No pending erasure requests found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$pendingRequests->count()} pending request(s)");
        $this->newLine();

        $success = 0;
        $failed = 0;

        foreach ($pendingRequests as $request) {
            $this->line("Processing request {$request->id} for candidate {$request->candidate_id}...");

            try {
                $result = $erasureService->processErasureRequest($request);

                if ($result['success']) {
                    $this->info("  ✓ Successfully erased: " . implode(', ', $result['erased_types']));
                    $success++;
                } else {
                    $this->error("  ✗ Failed: {$result['error']}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Exception: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Completed: {$success} successful, {$failed} failed");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
