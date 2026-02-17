<?php

namespace App\Console\Commands;

use App\Models\ModelWeight;
use App\Models\SystemEvent;
use App\Services\System\SystemEventService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MlStabilityCheckCommand extends Command
{
    protected $signature = 'ml:stability-check {--days=7}';
    protected $description = 'Check ML model stability metrics';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("ML Stability Check (last {$days} days)");
        $this->info('====================================');
        $this->newLine();

        $since = now()->subDays($days);
        $metrics = [];

        // 1. Learning events by status
        $eventsByStatus = DB::table('learning_events')
            ->where('created_at', '>=', $since)
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
        $totalEvents = array_sum($eventsByStatus);
        $statusSummary = collect($eventsByStatus)
            ->map(fn($cnt, $status) => "{$status}={$cnt}")
            ->implode(', ');

        $metrics[] = ['Learning Events (total)', (string) $totalEvents];
        $metrics[] = ['Learning Events (by status)', $statusSummary ?: 'none'];

        // 2. Active ModelWeight and freeze status
        $activeWeight = ModelWeight::active();
        $metrics[] = ['Active Model Version', $activeWeight ? $activeWeight->model_version : 'none'];
        $metrics[] = ['Model Frozen', $activeWeight ? ($activeWeight->is_frozen ? 'YES' : 'no') : 'n/a'];

        // 3. Volatility blocks and sudden shift blocks
        $volatilityBlocks = SystemEvent::where('type', 'ml_volatility_block')
            ->where('created_at', '>=', $since)
            ->count();
        $suddenShiftBlocks = SystemEvent::where('type', 'ml_sudden_shift_block')
            ->where('created_at', '>=', $since)
            ->count();

        $metrics[] = ['Volatility Blocks', (string) $volatilityBlocks];
        $metrics[] = ['Sudden Shift Blocks', (string) $suddenShiftBlocks];

        // 4. Learning cycles in window
        $cycleCount = DB::table('learning_cycles')
            ->where('created_at', '>=', $since)
            ->count();
        $metrics[] = ['Learning Cycles', (string) $cycleCount];

        // 5. Features with > 2 sigma change
        // From model_feature_importance, features where ABS(current_weight) > 2 * AVG(ABS(current_weight))
        $avgAbsWeight = DB::table('model_feature_importance')
            ->selectRaw('AVG(ABS(current_weight)) as avg_abs')
            ->value('avg_abs');

        $highVarianceFeatures = [];
        if ($avgAbsWeight && $avgAbsWeight > 0) {
            $threshold = 2 * $avgAbsWeight;
            $highVarianceFeatures = DB::table('model_feature_importance')
                ->whereRaw('ABS(current_weight) > ?', [$threshold])
                ->pluck('feature_name')
                ->toArray();
        }

        $metrics[] = ['High-Variance Features (>2sigma)', count($highVarianceFeatures) > 0 ? implode(', ', $highVarianceFeatures) : 'none'];

        // Output table
        $this->table(['Metric', 'Value'], $metrics);
        $this->newLine();

        // Emit warning if stability issues detected
        if ($volatilityBlocks > 0 || $suddenShiftBlocks > 0) {
            $this->warn('Stability issues detected!');

            SystemEventService::warn(
                'ml_stability_issue',
                'MlStabilityCheck',
                "ML stability issues detected in last {$days} days",
                [
                    'volatility_blocks' => $volatilityBlocks,
                    'sudden_shift_blocks' => $suddenShiftBlocks,
                    'learning_cycles' => $cycleCount,
                    'high_variance_features' => $highVarianceFeatures,
                    'days' => $days,
                ]
            );
        } else {
            $this->info('ML model stability: OK');
        }

        return 0;
    }
}
