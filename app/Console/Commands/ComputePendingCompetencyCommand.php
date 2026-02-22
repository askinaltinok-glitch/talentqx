<?php

namespace App\Console\Commands;

use App\Jobs\ComputeCompetencyAssessmentJob;
use App\Models\PoolCandidate;
use Illuminate\Console\Command;

class ComputePendingCompetencyCommand extends Command
{
    protected $signature = 'trust:competency:compute-pending {--limit=200} {--dry-run} {--force}';
    protected $description = 'Dispatch competency assessment jobs for candidates with completed interviews';

    public function handle(): int
    {
        if (!config('maritime.competency_v1')) {
            $this->warn('Competency v1 feature flag is disabled. Aborting.');
            return self::SUCCESS;
        }

        if (!config('maritime.competency_auto_compute') && !$this->option('force')) {
            $this->warn('Competency auto-compute is disabled. Use --force to override. Aborting.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        // Find maritime candidates with completed interviews that need (re)computation
        $candidates = PoolCandidate::query()
            ->where('seafarer', true)
            ->whereHas('formInterviews', function ($q) {
                $q->where('status', 'completed')
                  ->whereNotNull('completed_at');
            })
            ->where(function ($q) {
                $q->whereDoesntHave('trustProfile')
                ->orWhereHas('trustProfile', function ($sub) {
                    $sub->whereNull('competency_status');
                })
                ->orWhereHas('formInterviews', function ($sub) {
                    $sub->where('status', 'completed')
                        ->whereNotNull('completed_at')
                        ->whereRaw('completed_at > COALESCE((
                            SELECT competency_computed_at
                            FROM candidate_trust_profiles ctp
                            WHERE ctp.pool_candidate_id = form_interviews.pool_candidate_id
                            LIMIT 1
                        ), \'1970-01-01\')');
                });
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No candidates need competency assessment.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("DRY RUN: Found {$candidates->count()} candidates needing competency assessment:");
            foreach ($candidates as $c) {
                $interviewCount = $c->formInterviews()->where('status', 'completed')->count();
                $this->line("  - {$c->id} | {$c->full_name} | {$interviewCount} completed interview(s)");
            }
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($candidates as $candidate) {
            ComputeCompetencyAssessmentJob::dispatch($candidate->id);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} competency assessment jobs.");

        return self::SUCCESS;
    }
}
