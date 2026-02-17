<?php

namespace App\Console\Commands;

use App\Services\ML\MlLearningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MlLearnCommand extends Command
{
    protected $signature = 'ml:learn
        {--window=90 : Number of days to look back}
        {--industry= : Filter by industry code}
        {--dry-run : Show what would happen without making changes}';

    protected $description = 'Run ML learning cycle from historical outcomes';

    public function __construct(
        private readonly MlLearningService $learningService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $window = (int) $this->option('window');
        $industry = $this->option('industry');
        $dryRun = $this->option('dry-run');

        $this->info("╔══════════════════════════════════════════════════════════╗");
        $this->info("║               ML LEARNING CYCLE                          ║");
        $this->info("╚══════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->info("Configuration:");
        $this->line("  Window: {$window} days");
        $this->line("  Industry: " . ($industry ?: 'all'));
        $this->line("  Mode: " . ($dryRun ? '<fg=yellow>DRY RUN</>' : '<fg=green>LIVE</>'));
        $this->line("  Learning Rate: " . config('ml.learning_rate', 0.02));
        $this->line("  Warmup Min Samples: " . config('ml.warmup_min_samples', 50));
        $this->line("  Max Delta Per Update: " . config('ml.max_delta_per_update', 0.15));
        $this->newLine();

        // Show current state
        $this->showCurrentState($industry);

        // Show warmup status
        $this->showWarmupStatus($industry);

        if ($dryRun) {
            $this->warn("═══ DRY RUN MODE - No changes will be made ═══");
            $this->showWhatWouldChange($window, $industry);
            return Command::SUCCESS;
        }

        // Run learning
        $this->info("Starting learning cycle...");
        $this->newLine();

        $result = $this->learningService->batchLearn($window, $industry, false);

        $this->showResults($result);
        $this->showTopChangedFeatures($industry);
        $this->showUnstableFeatures($industry);

        return Command::SUCCESS;
    }

    protected function showCurrentState(?string $industry): void
    {
        $this->info("┌─ Current State ─────────────────────────────────────────┐");

        // Learning events
        $eventCount = DB::table('learning_events')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->count();
        $this->line("│  Learning events total: {$eventCount}");

        // Events by status
        $statusCounts = DB::table('learning_events')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        if (!empty($statusCounts)) {
            $this->line("│  Status breakdown:");
            foreach ($statusCounts as $status => $count) {
                $statusLabel = match ($status) {
                    'applied' => '<fg=green>applied</>',
                    'warmup_only' => '<fg=yellow>warmup_only</>',
                    'skipped_unstable_features' => '<fg=red>skipped_unstable</>',
                    'skipped_small_error' => '<fg=gray>skipped_small_error</>',
                    default => $status,
                };
                $this->line("│    - {$statusLabel}: {$count}");
            }
        }

        // Feature importance entries
        $featureCount = DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('sample_count', '>', 0)
            ->count();
        $this->line("│  Active features: {$featureCount}");

        // Current weight version
        $latest = DB::table('model_weights')
            ->where('is_active', true)
            ->first();
        if (!$latest) {
            $latest = DB::table('model_weights')->orderByDesc('created_at')->first();
        }
        if ($latest) {
            $activeLabel = $latest->is_active ?? false ? '<fg=green>[ACTIVE]</>' : '<fg=gray>[inactive]</>';
            $this->line("│  Current model: {$latest->model_version} {$activeLabel}");
        }

        // Error stats
        $errors = DB::table('learning_events')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('status', 'applied')
            ->selectRaw('AVG(ABS(error)) as mae, COUNT(*) as total')
            ->first();

        if ($errors && $errors->total > 0) {
            $this->line("│  Applied events MAE: " . round($errors->mae, 2));
        }

        $this->info("└─────────────────────────────────────────────────────────┘");
        $this->newLine();
    }

    protected function showWarmupStatus(?string $industry): void
    {
        $minSamples = config('ml.warmup_min_samples', 50);

        if (!$industry) {
            $this->warn("Warmup status: Skipped (no industry filter)");
            $this->newLine();
            return;
        }

        $totalSamples = DB::table('learning_events')
            ->where('industry_code', $industry)
            ->count();

        $progressPct = min(100, round(($totalSamples / $minSamples) * 100, 1));
        $isReady = $totalSamples >= $minSamples;

        $this->info("┌─ Warmup Status [{$industry}] ─────────────────────────────┐");
        $this->line("│  Min samples required: {$minSamples}");
        $this->line("│  Current samples: {$totalSamples}");

        // Progress bar
        $barLength = 30;
        $filled = (int) round(($progressPct / 100) * $barLength);
        $empty = $barLength - $filled;
        $bar = str_repeat('█', $filled) . str_repeat('░', $empty);

        $statusColor = $isReady ? 'green' : 'yellow';
        $statusLabel = $isReady ? 'READY' : 'WARMUP';
        $this->line("│  Progress: [{$bar}] {$progressPct}%");
        $this->line("│  Status: <fg={$statusColor}>{$statusLabel}</>");

        if (!$isReady) {
            $this->line("│  <fg=yellow>Note: Weight updates will be computed but not applied</>");
        }

        $this->info("└─────────────────────────────────────────────────────────┘");
        $this->newLine();
    }

    protected function showWhatWouldChange(int $window, ?string $industry): void
    {
        $from = now()->subDays($window)->toDateString();

        $outcomes = DB::table('interview_outcomes as io')
            ->join('form_interviews as fi', 'io.form_interview_id', '=', 'fi.id')
            ->join('model_features as mf', 'mf.form_interview_id', '=', 'fi.id')
            ->join('model_predictions as mp', 'mp.form_interview_id', '=', 'fi.id')
            ->where('io.created_at', '>=', $from)
            ->when($industry, fn($q) => $q->where('mf.industry_code', $industry))
            ->count();

        $this->info("Would process: {$outcomes} outcomes");

        // Sample some predictions vs outcomes
        $samples = DB::table('interview_outcomes as io')
            ->join('model_predictions as mp', 'mp.form_interview_id', '=', 'io.form_interview_id')
            ->where('io.created_at', '>=', $from)
            ->select([
                'mp.predicted_outcome_score',
                'io.outcome_score',
                DB::raw('io.outcome_score - mp.predicted_outcome_score as error'),
            ])
            ->whereNotNull('io.outcome_score')
            ->limit(10)
            ->get();

        if ($samples->isNotEmpty()) {
            $this->newLine();
            $this->info("Sample predictions vs outcomes:");
            $this->table(
                ['Predicted', 'Actual', 'Error'],
                $samples->map(fn($s) => [
                    $s->predicted_outcome_score,
                    $s->outcome_score,
                    $s->error > 0 ? "+{$s->error}" : $s->error,
                ])->toArray()
            );
        }
    }

    protected function showResults(array $result): void
    {
        $this->info("┌─ Results ───────────────────────────────────────────────┐");
        $this->line("│  Total outcomes: {$result['total']}");
        $this->line("│  Processed: {$result['processed']}");
        $this->line("│  <fg=green>Applied</>: {$result['applied']}");
        $this->line("│  <fg=yellow>Warmup only</>: {$result['warmup_only']}");
        $this->line("│  <fg=red>Skipped (unstable)</>: {$result['skipped_unstable']}");
        $this->line("│  <fg=gray>Skipped (small error)</>: {$result['skipped_small_error']}");
        $this->line("│  Errors: {$result['errors']}");

        if ($result['new_weights_version'] ?? null) {
            $this->line("│  <fg=green>New weights version: {$result['new_weights_version']}</>");
        } else {
            $this->line("│  <fg=yellow>No new weights created</>");
        }
        $this->info("└─────────────────────────────────────────────────────────┘");
        $this->newLine();
    }

    protected function showTopChangedFeatures(?string $industry): void
    {
        $features = DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('sample_count', '>=', 3)
            ->orderByDesc(DB::raw('ABS(current_weight)'))
            ->limit(10)
            ->get();

        if ($features->isEmpty()) {
            return;
        }

        $this->info("┌─ Top 10 Changed Features ──────────────────────────────┐");
        $this->table(
            ['Feature', 'Weight Δ', 'Samples', '+Impact', '-Impact'],
            $features->map(fn($f) => [
                $f->feature_name,
                $f->current_weight > 0 ? "<fg=green>+{$f->current_weight}</>" : "<fg=red>{$f->current_weight}</>",
                $f->sample_count,
                $f->positive_impact_count,
                $f->negative_impact_count,
            ])->toArray()
        );
        $this->newLine();
    }

    protected function showUnstableFeatures(?string $industry): void
    {
        $unstableThreshold = config('ml.unstable_feature_threshold', 0.15);

        // Features with high variance in impact direction
        $features = DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('sample_count', '>=', 10)
            ->whereRaw('positive_impact_count > 0 AND negative_impact_count > 0')
            ->selectRaw('*,
                ABS(positive_impact_count - negative_impact_count) / (positive_impact_count + negative_impact_count) as stability')
            ->having('stability', '<', 0.3) // Less than 30% difference = unstable
            ->orderBy('stability')
            ->limit(10)
            ->get();

        // Also count skipped unstable events
        $skippedCount = DB::table('learning_events')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('status', 'skipped_unstable_features')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($features->isEmpty() && $skippedCount === 0) {
            $this->info("<fg=green>No unstable features detected.</>");
            return;
        }

        $this->warn("┌─ Unstable Features (inconsistent impact) ──────────────┐");
        $this->line("│  Skipped updates (last 30d): {$skippedCount}");
        $this->line("│  Threshold: ±{$unstableThreshold} per update");

        if ($features->isNotEmpty()) {
            $this->table(
                ['Feature', '+/-', 'Stability %'],
                $features->map(fn($f) => [
                    $f->feature_name,
                    "{$f->positive_impact_count}/{$f->negative_impact_count}",
                    round($f->stability * 100, 1) . '%',
                ])->toArray()
            );
        }
        $this->warn("└─────────────────────────────────────────────────────────┘");
    }
}
