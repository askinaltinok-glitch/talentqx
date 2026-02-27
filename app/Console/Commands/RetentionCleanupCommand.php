<?php

namespace App\Console\Commands;

use App\Models\CandidateConsent;
use App\Models\FormInterview;
use App\Models\RetentionAuditLog;
use App\Models\VoiceTranscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RetentionCleanupCommand extends Command
{
    protected $signature = 'retention:cleanup
                            {--dry-run : Run without making changes (still writes audit log)}
                            {--batch-size=1000 : Number of records to process per batch}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Clean up expired data according to retention policy';

    private int $deletedIncompleteCount = 0;
    private int $anonymizedCompletedCount = 0;
    private int $deletedOrphanConsentsCount = 0;
    private int $deletedVoiceAudioCount = 0;
    private int $anonymizedVoiceTranscriptsCount = 0;
    private int $errorsCount = 0;

    private int $batchSize;
    private bool $dryRun;

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');
        $this->batchSize = (int) $this->option('batch-size');
        $startTime = microtime(true);

        // Config dump
        $this->info('========================================');
        $this->info('Retention Cleanup Started');
        $this->info('========================================');
        $this->info('Mode: ' . ($this->dryRun ? 'DRY RUN (no DB changes)' : 'LIVE'));
        $this->info('Batch size: ' . $this->batchSize);
        $this->info('Incomplete threshold: 90 days');
        $this->info('Anonymize threshold: 730 days (2 years)');
        $this->newLine();

        if (!$this->dryRun && !$this->option('force')) {
            if (!$this->confirm('This will permanently delete/anonymize data. Continue?')) {
                $this->warn('Aborted.');
                return 1;
            }
        }

        // Step 1: Delete incomplete > 90d
        $this->deleteIncompleteInterviews();

        // Step 2: Anonymize completed > 2y
        $this->anonymizeCompletedInterviews();

        // Step 3: Cleanup orphan consents
        $this->cleanupOrphanConsents();

        // Step 4: Voice audio cleanup (delete files > 1y, anonymize transcripts > 2y)
        $this->cleanupVoiceData();

        // Calculate duration
        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        // Write audit log (even for dry-run)
        $this->writeAuditLog($durationMs);

        // Summary
        $this->newLine();
        $this->info('========================================');
        $this->info('Summary');
        $this->info('========================================');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Deleted (incomplete > 90d)', $this->deletedIncompleteCount],
                ['Anonymized (completed > 2y)', $this->anonymizedCompletedCount],
                ['Orphan consents deleted', $this->deletedOrphanConsentsCount],
                ['Voice audio deleted (> 1y)', $this->deletedVoiceAudioCount],
                ['Voice transcripts anonymized (> 2y)', $this->anonymizedVoiceTranscriptsCount],
                ['Errors', $this->errorsCount],
                ['Duration', $durationMs . 'ms'],
            ]
        );

        if ($this->dryRun) {
            $this->warn('DRY RUN - No actual changes were made to the database.');
        }

        return 0;
    }

    /**
     * Step 1: Delete incomplete interviews older than 90 days
     */
    private function deleteIncompleteInterviews(): void
    {
        $cutoff = now()->subDays(90);

        $this->info("Step 1: Deleting incomplete interviews older than 90 days ({$cutoff->toDateString()})...");

        $query = FormInterview::query()
            ->whereIn('status', [FormInterview::STATUS_DRAFT, FormInterview::STATUS_IN_PROGRESS])
            ->where('created_at', '<', $cutoff)
            ->orderBy('created_at');

        $total = $query->count();
        $this->info("  Found {$total} interviews to delete");

        if ($total === 0) {
            return;
        }

        $processed = 0;

        while (true) {
            $batch = FormInterview::query()
                ->whereIn('status', [FormInterview::STATUS_DRAFT, FormInterview::STATUS_IN_PROGRESS])
                ->where('created_at', '<', $cutoff)
                ->orderBy('created_at')
                ->limit($this->batchSize)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $interview) {
                try {
                    if ($this->dryRun) {
                        $processed++;
                        continue;
                    }

                    DB::transaction(function () use ($interview) {
                        // Delete answers
                        $interview->answers()->delete();
                        // Delete consents
                        $interview->consents()->delete();
                        // Delete interview
                        $interview->delete();
                    });

                    $processed++;
                } catch (\Exception $e) {
                    $this->errorsCount++;
                    Log::error('Retention cleanup: Failed to delete interview', [
                        'interview_id' => $interview->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  Error deleting {$interview->id}: {$e->getMessage()}");
                }
            }

            $this->info("  Batch deleted: {$processed}/{$total}");
        }

        $this->deletedIncompleteCount = $processed;
        $this->info("  Total deleted: {$processed}");
    }

    /**
     * Step 2: Anonymize completed interviews older than 2 years
     */
    private function anonymizeCompletedInterviews(): void
    {
        $cutoff = now()->subDays(730); // 2 years

        $this->info("Step 2: Anonymizing completed interviews older than 2 years ({$cutoff->toDateString()})...");

        // Target: completed, not yet anonymized, completed_at (or created_at if null) < cutoff
        $query = FormInterview::query()
            ->where('status', FormInterview::STATUS_COMPLETED)
            ->whereNull('anonymized_at')
            ->where(function ($q) use ($cutoff) {
                $q->where('completed_at', '<', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('completed_at')
                            ->where('created_at', '<', $cutoff);
                    });
            })
            ->orderBy('created_at');

        $total = $query->count();
        $this->info("  Found {$total} interviews to anonymize");

        if ($total === 0) {
            return;
        }

        $processed = 0;

        while (true) {
            $batch = FormInterview::query()
                ->where('status', FormInterview::STATUS_COMPLETED)
                ->whereNull('anonymized_at')
                ->where(function ($q) use ($cutoff) {
                    $q->where('completed_at', '<', $cutoff)
                        ->orWhere(function ($q2) use ($cutoff) {
                            $q2->whereNull('completed_at')
                                ->where('created_at', '<', $cutoff);
                        });
                })
                ->orderBy('created_at')
                ->limit($this->batchSize)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $interview) {
                try {
                    if ($this->dryRun) {
                        $processed++;
                        continue;
                    }

                    DB::transaction(function () use ($interview) {
                        // Anonymize PII fields (keep analytics fields)
                        // PII fields to null/scrub:
                        // - meta (candidate context)
                        // - admin_notes
                        // - template_json (keep sha256)
                        // - raw_decision_reason (might contain PII)
                        //
                        // Keep for analytics:
                        // - final_score, decision, risk_flags, competency_scores
                        // - calibrated_score, z_score, position_mean_score, etc.
                        // - template_json_sha256
                        // - policy_code, policy_version

                        $interview->forceFill([
                            'meta' => null,
                            'admin_notes' => null,
                            'template_json' => null,
                            'raw_decision_reason' => null,
                            'anonymized_at' => now(),
                        ])->saveQuietly(); // Skip model events (immutability guard)

                        // Anonymize answer texts
                        $interview->answers()->update(['answer_text' => '[ANONYMIZED]']);

                        // Delete consents (safest approach)
                        $interview->consents()->delete();
                    });

                    $processed++;
                } catch (\Exception $e) {
                    $this->errorsCount++;
                    Log::error('Retention cleanup: Failed to anonymize interview', [
                        'interview_id' => $interview->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  Error anonymizing {$interview->id}: {$e->getMessage()}");
                }
            }

            $this->info("  Batch anonymized: {$processed}/{$total}");
        }

        $this->anonymizedCompletedCount = $processed;
        $this->info("  Total anonymized: {$processed}");
    }

    /**
     * Step 3: Cleanup orphan consents (form_interview_id no longer exists)
     */
    private function cleanupOrphanConsents(): void
    {
        $this->info("Step 3: Cleaning up orphaned consents...");

        // Find consents where form_interview no longer exists
        $orphanedQuery = CandidateConsent::query()
            ->whereNotNull('form_interview_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('form_interviews')
                    ->whereColumn('form_interviews.id', 'candidate_consents.form_interview_id');
            });

        $total = $orphanedQuery->count();
        $this->info("  Found {$total} orphaned consents");

        if ($total === 0) {
            return;
        }

        if ($this->dryRun) {
            $this->deletedOrphanConsentsCount = $total;
            return;
        }

        // Delete in batches
        $deleted = 0;
        while (true) {
            $batch = CandidateConsent::query()
                ->whereNotNull('form_interview_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('form_interviews')
                        ->whereColumn('form_interviews.id', 'candidate_consents.form_interview_id');
                })
                ->limit($this->batchSize)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            try {
                $count = CandidateConsent::whereIn('id', $batch->pluck('id'))->delete();
                $deleted += $count;
                $this->info("  Batch deleted: {$deleted}/{$total}");
            } catch (\Exception $e) {
                $this->errorsCount++;
                Log::error('Retention cleanup: Failed to delete orphan consents', [
                    'error' => $e->getMessage(),
                ]);
                $this->error("  Error deleting orphan consents: {$e->getMessage()}");
                break;
            }
        }

        $this->deletedOrphanConsentsCount = $deleted;
        $this->info("  Total deleted: {$deleted}");
    }

    /**
     * Step 4: Cleanup voice data — delete audio files > 1 year, anonymize transcripts > 2 years
     */
    private function cleanupVoiceData(): void
    {
        $audioCutoff = now()->subDays(config('retention.periods.voice_audio.default', 365));
        $transcriptCutoff = now()->subDays(config('retention.periods.voice_transcripts.default', 730));

        // 4a: Delete audio files older than retention period
        $this->info("Step 4a: Deleting voice audio files older than 1 year ({$audioCutoff->toDateString()})...");

        $audioQuery = VoiceTranscription::whereNotNull('audio_path')
            ->where('audio_path', '!=', '')
            ->where('created_at', '<', $audioCutoff);

        $audioTotal = $audioQuery->count();
        $this->info("  Found {$audioTotal} voice audio records to purge");

        if ($audioTotal > 0) {
            $deletedAudio = 0;

            $audioQuery->chunkById($this->batchSize, function ($batch) use (&$deletedAudio) {
                foreach ($batch as $vt) {
                    try {
                        if (!$this->dryRun && $vt->audio_path) {
                            Storage::disk('local')->delete($vt->audio_path);
                            $vt->update([
                                'audio_path' => null,
                                'audio_size_bytes' => null,
                                'audio_sha256' => null,
                            ]);
                        }
                        $deletedAudio++;
                    } catch (\Exception $e) {
                        $this->errorsCount++;
                        Log::error('Retention cleanup: Failed to delete voice audio', [
                            'voice_transcription_id' => $vt->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

            $this->deletedVoiceAudioCount = $deletedAudio;
            $this->info("  Total audio files purged: {$deletedAudio}");
        }

        // 4b: Anonymize transcripts older than 2 years
        $this->info("Step 4b: Anonymizing voice transcripts older than 2 years ({$transcriptCutoff->toDateString()})...");

        $transcriptQuery = VoiceTranscription::whereNotNull('transcript_text')
            ->where('transcript_text', '!=', '[ANONYMIZED]')
            ->where('created_at', '<', $transcriptCutoff);

        $transcriptTotal = $transcriptQuery->count();
        $this->info("  Found {$transcriptTotal} transcripts to anonymize");

        if ($transcriptTotal > 0 && !$this->dryRun) {
            $updated = $transcriptQuery->update(['transcript_text' => '[ANONYMIZED]']);
            $this->anonymizedVoiceTranscriptsCount = $updated;
        } else {
            $this->anonymizedVoiceTranscriptsCount = $transcriptTotal;
        }

        $this->info("  Total transcripts anonymized: {$this->anonymizedVoiceTranscriptsCount}");
    }

    /**
     * Write audit log entry
     */
    private function writeAuditLog(int $durationMs): void
    {
        $notes = [];
        if ($this->dryRun) {
            $notes[] = 'dry-run';
        }

        $voiceNotes = [];
        if ($this->deletedVoiceAudioCount > 0) {
            $voiceNotes[] = "voice_audio_deleted={$this->deletedVoiceAudioCount}";
        }
        if ($this->anonymizedVoiceTranscriptsCount > 0) {
            $voiceNotes[] = "voice_transcripts_anonymized={$this->anonymizedVoiceTranscriptsCount}";
        }
        $notes = array_merge($notes, $voiceNotes);

        RetentionAuditLog::create([
            'run_at' => now(),
            'dry_run' => $this->dryRun,
            'batch_size' => $this->batchSize,
            'deleted_incomplete_count' => $this->deletedIncompleteCount,
            'anonymized_completed_count' => $this->anonymizedCompletedCount,
            'deleted_orphan_consents_count' => $this->deletedOrphanConsentsCount,
            'errors_count' => $this->errorsCount,
            'duration_ms' => $durationMs,
            'notes' => !empty($notes) ? implode(', ', $notes) : null,
        ]);

        $this->info('Audit log written.');
    }
}
