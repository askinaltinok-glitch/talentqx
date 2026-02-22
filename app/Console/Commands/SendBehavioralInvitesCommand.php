<?php

namespace App\Console\Commands;

use App\Jobs\SendCandidateEmailJob;
use App\Models\CandidateEmailLog;
use App\Models\PoolCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBehavioralInvitesCommand extends Command
{
    protected $signature = 'maritime:send-behavioral-invites {--limit=50}';
    protected $description = 'Send behavioral interview invites to candidates who applied 3+ hours ago';

    public function handle(): int
    {
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
            ->whereHas('formInterviews', function ($q) {
                $q->where('status', 'completed');
            })
            ->whereNotExists(function ($q) {
                $q->select(\DB::raw(1))
                    ->from('candidate_email_logs')
                    ->whereColumn('candidate_email_logs.pool_candidate_id', 'pool_candidates.id')
                    ->where('mail_type', 'behavioral_interview_invite')
                    ->whereIn('status', ['sent', 'sending']);
            })
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No candidates eligible for behavioral invite.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($candidates as $candidate) {
            try {
                SendCandidateEmailJob::dispatchSafe(
                    $candidate->id,
                    'behavioral_interview_invite',
                );
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('SendBehavioralInvites: dispatch failed', [
                    'candidate_id' => $candidate->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Dispatched {$sent} behavioral interview invites.");
        Log::info('maritime:send-behavioral-invites', [
            'eligible' => $candidates->count(),
            'dispatched' => $sent,
            'delay_minutes' => $delayMinutes,
        ]);

        return self::SUCCESS;
    }
}
