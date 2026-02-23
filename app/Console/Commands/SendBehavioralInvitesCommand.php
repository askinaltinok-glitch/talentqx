<?php

namespace App\Console\Commands;

use App\Jobs\SendCandidateEmailJob;
use App\Models\CandidateEmailLog;
use App\Models\PoolCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendBehavioralInvitesCommand extends Command
{
    protected $signature = 'maritime:send-behavioral-invites {--limit=50}';
    protected $description = 'Send behavioral interview invites to candidates who applied 3+ hours ago';

    public function handle(): int
    {
        $startedAt = now();

        if (config('maritime.clean_workflow_v1')) {
            $this->info('Clean workflow active — legacy behavioral invites disabled.');
            return self::SUCCESS;
        }

        if (!config('maritime.behavioral_interview_v1')) {
            $this->info('Behavioral interview v1 is disabled. Skipping.');
            return self::SUCCESS;
        }

        $delayMinutes = (int) config('maritime.behavioral_invite_delay', 180);
        $cutoff = now()->subMinutes($delayMinutes);
        $limit = (int) $this->option('limit');

        // Find candidates who:
        // 1. Are maritime, status = new or in_pool
        // 2. Applied >= delayMinutes ago
        // 3. Have NOT received a behavioral_interview_invite email yet
        // 4. Have a completed interview (technical phase done)
        $candidates = PoolCandidate::query()
            ->where('primary_industry', 'maritime')
            ->whereIn('status', [PoolCandidate::STATUS_NEW, PoolCandidate::STATUS_IN_POOL, PoolCandidate::STATUS_ASSESSED])
            ->where('created_at', '<=', $cutoff)
            ->whereNotNull('email')
            ->whereNotNull('email_verified_at') // P1.2: require verified email
            ->whereHas('formInterviews', function ($q) {
                $q->where('status', 'completed');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('candidate_email_logs')
                    ->whereColumn('candidate_email_logs.pool_candidate_id', 'pool_candidates.id')
                    ->where('mail_type', 'behavioral_interview_invite')
                    ->whereIn('status', ['sent', 'sending']);
            })
            ->limit($limit)
            ->get();

        $eligibleCount = $candidates->count();
        $sent = 0;
        $skipped = 0;
        $errorCount = 0;
        $errors = [];

        if ($candidates->isEmpty()) {
            $this->info('No candidates eligible for behavioral invite.');
        } else {
            foreach ($candidates as $candidate) {
                try {
                    SendCandidateEmailJob::dispatchSafe(
                        $candidate->id,
                        'behavioral_interview_invite',
                    );
                    $sent++;
                } catch (\Throwable $e) {
                    $errorCount++;
                    $errors[] = "{$candidate->id}: {$e->getMessage()}";
                    Log::warning('SendBehavioralInvites: dispatch failed', [
                        'candidate_id' => $candidate->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Dispatched {$sent} behavioral interview invites.");
        }

        $skipped = $eligibleCount - $sent - $errorCount;
        $finishedAt = now();
        $durationMs = (int) ($startedAt->diffInMilliseconds($finishedAt));

        // Write observability row
        try {
            DB::table('maritime_invite_runs')->insert([
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'eligible_count' => $eligibleCount,
                'sent_count' => $sent,
                'skipped_count' => max(0, $skipped),
                'error_count' => $errorCount,
                'errors' => $errorCount > 0 ? implode("\n", array_slice($errors, 0, 10)) : null,
                'duration_ms' => $durationMs,
            ]);
        } catch (\Throwable $e) {
            Log::warning('SendBehavioralInvites: failed to write invite run log', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('maritime:send-behavioral-invites', [
            'eligible' => $eligibleCount,
            'dispatched' => $sent,
            'errors' => $errorCount,
            'duration_ms' => $durationMs,
            'delay_minutes' => $delayMinutes,
        ]);

        return self::SUCCESS;
    }
}
