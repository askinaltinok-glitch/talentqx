<?php

namespace App\Console\Commands;

use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Mark interviews as no-show if candidate didn't join within grace period.
 *
 * Conditions:
 * - scheduled_at is in the past (by more than grace period)
 * - joined_at IS NULL
 * - no_show_marked_at IS NULL (not already marked)
 * - status = pending
 *
 * Runs every 5 minutes via scheduler.
 */
class MarkNoShowInterviews extends Command
{
    protected $signature = 'interviews:mark-no-show
                            {--dry-run : Show what would be marked without actually marking}
                            {--grace-minutes=10 : Grace period in minutes after scheduled time}';

    protected $description = 'Mark interviews as no-show if candidate did not join within grace period';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $graceMinutes = (int) $this->option('grace-minutes');
        $now = Carbon::now();
        $cutoff = $now->copy()->subMinutes($graceMinutes);

        $this->info("🕐 Şu an: {$now->format('Y-m-d H:i')}");
        $this->info("⏰ Grace period: {$graceMinutes} dakika");
        $this->info("📅 Cutoff time: {$cutoff->format('Y-m-d H:i')}");
        $this->newLine();

        // Find interviews that should be marked as no-show
        $interviews = Interview::query()
            ->where('status', Interview::STATUS_PENDING)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $cutoff)
            ->whereNull('joined_at')
            ->whereNull('no_show_marked_at')
            ->with(['candidate', 'job.company'])
            ->get();

        if ($interviews->isEmpty()) {
            $this->info('✓ No-show olarak işaretlenecek mülakat yok.');
            return Command::SUCCESS;
        }

        $this->info("📋 {$interviews->count()} mülakat no-show olarak işaretlenecek:");
        $this->newLine();

        $marked = 0;
        $skipped = 0;

        foreach ($interviews as $interview) {
            $candidate = $interview->candidate;
            $job = $interview->job;
            $scheduledAt = $interview->scheduled_at;
            $minutesLate = $scheduledAt->diffInMinutes($now);

            $line = sprintf(
                "  • %s %s - %s @ %s (%d dk geç)",
                $candidate?->first_name ?? 'N/A',
                $candidate?->last_name ?? '',
                $job?->title ?? 'N/A',
                $scheduledAt->format('d.m H:i'),
                $minutesLate
            );

            $this->line($line);

            if ($dryRun) {
                $marked++;
                continue;
            }

            try {
                $interview->forceFill([
                    'no_show_marked_at' => $now,
                    'late_minutes' => $minutesLate,
                ])->save();

                $marked++;
                $this->info("    ✓ Marked");

                Log::info('Interview marked as no-show', [
                    'interview_id' => $interview->id,
                    'candidate_id' => $candidate?->id,
                    'scheduled_at' => $scheduledAt->toIso8601String(),
                    'minutes_late' => $minutesLate,
                ]);
            } catch (\Exception $e) {
                $skipped++;
                $this->error("    ✗ {$e->getMessage()}");
                Log::error('Failed to mark interview as no-show', [
                    'interview_id' => $interview->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📧 Toplam: Marked={$marked}, Skipped={$skipped}");

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN: Hiçbir değişiklik yapılmadı.');
        }

        return Command::SUCCESS;
    }
}
