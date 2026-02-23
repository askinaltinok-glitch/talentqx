<?php

namespace App\Console\Commands;

use App\Models\PoolCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TrashUnverifiedCandidates extends Command
{
    protected $signature = 'candidates:trash-unverified
        {--hours=48 : Hours after registration to archive}
        {--dry-run : Preview without making changes}';

    protected $description = 'Archive candidates who have not verified their email within the specified time window.';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($hours);

        $query = PoolCandidate::query()
            ->where('status', PoolCandidate::STATUS_NEW)
            ->whereNull('email_verified_at')
            ->where('created_at', '<', $cutoff);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No unverified candidates older than ' . $hours . 'h found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} unverified candidates older than {$hours}h.");

        if ($dryRun) {
            $this->warn('DRY RUN — no changes made.');
            $query->each(function (PoolCandidate $c) {
                $this->line("  Would archive: {$c->id} ({$c->email}, created {$c->created_at})");
            });
            return Command::SUCCESS;
        }

        $archived = 0;
        $query->chunkById(100, function ($candidates) use (&$archived) {
            foreach ($candidates as $candidate) {
                $candidate->archive();
                $archived++;
            }
        });

        $this->info("Archived {$archived} unverified candidates.");

        Log::channel('single')->info('TrashUnverifiedCandidates: archived ' . $archived . ' candidates older than ' . $hours . 'h');

        return Command::SUCCESS;
    }
}
