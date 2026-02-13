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

        $this->info("ML Learning Cycle");
        $this->info("Window: {$window} days");
        $this->info("Industry: " . ($industry ?: 'all'));
        $this->info("Dry run: " . ($dryRun ? 'yes' : 'no'));
        $this->newLine();

        // Get current state
        $this->showCurrentState($industry);

        if ($dryRun) {
            $this->warn("Dry run mode - no changes will be made");
            $this->showWhatWouldChange($window, $industry);
            return Command::SUCCESS;
        }

        // Run learning
        $this->info("Starting learning cycle...");
        $result = $this->learningService->batchLearn($window, $industry);

        $this->newLine();
        $this->info("Results:");
        $this->info("  Processed: {$result['processed']} outcomes");
        $this->info("  Errors: {$result['errors']}");
        
        if ($result['new_weights_version']) {
            $this->info("  New weights version: {$result['new_weights_version']}");
        } else {
            $this->warn("  No new weights created (not enough data or no changes)");
        }

        // Show weight deltas
        $this->showWeightChanges($industry);

        // Show unstable features
        $this->showUnstableFeatures($industry);

        return Command::SUCCESS;
    }

    protected function showCurrentState(?string $industry): void
    {
        $this->info("Current State:");

        // Learning events
        $eventCount = DB::table('learning_events')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->count();
        $this->info("  Learning events: {$eventCount}");

        // Feature importance entries
        $featureCount = DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('sample_count', '>', 0)
            ->count();
        $this->info("  Active features: {$featureCount}");

        // Current weight version
        $latest = DB::table('model_weights')->orderByDesc('created_at')->first();
        if ($latest) {
            $this->info("  Current weight version: {$latest->model_version}");
        }

        // Error stats
        $errors = DB::table('learning_events')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->selectRaw('AVG(ABS(error)) as mae, COUNT(*) as total')
            ->first();
        
        if ($errors && $errors->total > 0) {
            $this->info("  Current MAE: " . round($errors->mae, 2));
        }

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

    protected function showWeightChanges(?string $industry): void
    {
        $features = DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('sample_count', '>=', 5)
            ->orderByDesc(DB::raw('ABS(current_weight)'))
            ->limit(15)
            ->get();

        if ($features->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->info("Top weight changes:");
        $this->table(
            ['Feature', 'Weight Delta', 'Samples', '+Impact', '-Impact'],
            $features->map(fn($f) => [
                $f->feature_name,
                $f->current_weight > 0 ? "+{$f->current_weight}" : $f->current_weight,
                $f->sample_count,
                $f->positive_impact_count,
                $f->negative_impact_count,
            ])->toArray()
        );
    }

    protected function showUnstableFeatures(?string $industry): void
    {
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

        if ($features->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn("Unstable features (inconsistent impact direction):");
        $this->table(
            ['Feature', '+/-', 'Stability'],
            $features->map(fn($f) => [
                $f->feature_name,
                "{$f->positive_impact_count}/{$f->negative_impact_count}",
                round($f->stability * 100, 1) . '%',
            ])->toArray()
        );
    }
}
