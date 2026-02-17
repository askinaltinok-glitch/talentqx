<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateFairnessReportCommand extends Command
{
    protected $signature = 'ml:fairness-report {--days=30 : Lookback period in days}';
    protected $description = 'Generate and store ML fairness reports for all group dimensions';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $from = now()->subDays($days)->toDateString();
        $reportDate = now()->toDateString();

        $scoreDelta = config('ml.fairness.score_delta_alert', 10);
        $precisionGap = config('ml.fairness.precision_gap_alert', 15);

        $groupTypes = ['country_code', 'language', 'industry_code'];
        $reportsCreated = 0;
        $alertCount = 0;

        foreach ($groupTypes as $groupType) {
            $metrics = $this->calculateGroupMetrics($groupType, $from);
            $alerts = $this->detectAlerts($metrics, $groupType, $scoreDelta, $precisionGap);

            foreach ($metrics as $metric) {
                $alertsForGroup = array_filter($alerts, fn($a) => $a['group_value'] === $metric['group_value']);
                $hasAlert = !empty($alertsForGroup);
                if ($hasAlert) $alertCount++;

                DB::table('ml_fairness_reports')->updateOrInsert(
                    [
                        'report_date' => $reportDate,
                        'group_type' => $groupType,
                        'group_value' => $metric['group_value'],
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'sample_count' => $metric['sample_count'],
                        'avg_predicted_score' => $metric['avg_predicted_score'],
                        'avg_outcome_score' => $metric['avg_outcome_score'],
                        'hire_precision' => $metric['hire_precision'],
                        'has_alert' => $hasAlert,
                        'alert_details_json' => $hasAlert ? json_encode(array_values($alertsForGroup)) : null,
                        'created_at' => now(),
                    ]
                );

                $reportsCreated++;
            }
        }

        $this->info("Fairness report generated: {$reportsCreated} rows, {$alertCount} alerts.");

        if ($alertCount > 0) {
            $this->warn("ALERT: {$alertCount} fairness alerts detected — review /admin/ml/fairness");
        }

        return Command::SUCCESS;
    }

    private function calculateGroupMetrics(string $groupField, string $from): array
    {
        $results = DB::table('model_features as mf')
            ->join('model_predictions as mp', 'mp.form_interview_id', '=', 'mf.form_interview_id')
            ->leftJoin('interview_outcomes as io', 'io.form_interview_id', '=', 'mf.form_interview_id')
            ->join('form_interviews as fi', 'fi.id', '=', 'mf.form_interview_id')
            ->join('pool_candidates as pc', 'pc.id', '=', 'fi.pool_candidate_id')
            ->where('pc.is_demo', false)
            ->where('mf.created_at', '>=', $from)
            ->whereNotNull("mf.{$groupField}")
            ->groupBy("mf.{$groupField}")
            ->selectRaw("
                mf.{$groupField} as group_value,
                COUNT(*) as sample_count,
                AVG(mp.predicted_outcome_score) as avg_predicted_score,
                AVG(io.outcome_score) as avg_outcome_score,
                SUM(CASE WHEN mp.predicted_label = 'GOOD' AND io.outcome_score >= 50 THEN 1 ELSE 0 END) as true_positives,
                SUM(CASE WHEN mp.predicted_label = 'GOOD' THEN 1 ELSE 0 END) as predicted_good
            ")
            ->having('sample_count', '>=', 5)
            ->get();

        return $results->map(function ($row) {
            $hirePrecision = $row->predicted_good > 0
                ? round(($row->true_positives / $row->predicted_good) * 100, 2)
                : null;

            return [
                'group_value' => $row->group_value,
                'sample_count' => $row->sample_count,
                'avg_predicted_score' => round($row->avg_predicted_score, 2),
                'avg_outcome_score' => $row->avg_outcome_score ? round($row->avg_outcome_score, 2) : null,
                'hire_precision' => $hirePrecision,
            ];
        })->toArray();
    }

    private function detectAlerts(array $metrics, string $groupType, float $scoreDelta, float $precisionGap): array
    {
        if (empty($metrics)) return [];

        $totalSamples = array_sum(array_column($metrics, 'sample_count'));
        if ($totalSamples === 0) return [];

        $globalPredicted = 0;
        $globalOutcome = 0;
        $globalPrecision = 0;
        $precisionCount = 0;

        foreach ($metrics as $m) {
            $weight = $m['sample_count'] / $totalSamples;
            $globalPredicted += $m['avg_predicted_score'] * $weight;
            if ($m['avg_outcome_score'] !== null) {
                $globalOutcome += $m['avg_outcome_score'] * $weight;
            }
            if ($m['hire_precision'] !== null) {
                $globalPrecision += $m['hire_precision'];
                $precisionCount++;
            }
        }
        $globalPrecisionAvg = $precisionCount > 0 ? $globalPrecision / $precisionCount : null;

        $alerts = [];
        foreach ($metrics as $m) {
            $predDiff = abs($m['avg_predicted_score'] - $globalPredicted);
            if ($predDiff > $scoreDelta) {
                $alerts[] = [
                    'group_type' => $groupType,
                    'group_value' => $m['group_value'],
                    'alert_type' => 'predicted_score_delta',
                    'value' => round($predDiff, 2),
                    'threshold' => $scoreDelta,
                ];
            }

            if ($m['avg_outcome_score'] !== null) {
                $outDiff = abs($m['avg_outcome_score'] - $globalOutcome);
                if ($outDiff > $scoreDelta) {
                    $alerts[] = [
                        'group_type' => $groupType,
                        'group_value' => $m['group_value'],
                        'alert_type' => 'outcome_score_delta',
                        'value' => round($outDiff, 2),
                        'threshold' => $scoreDelta,
                    ];
                }
            }

            if ($m['hire_precision'] !== null && $globalPrecisionAvg !== null) {
                $precDiff = abs($m['hire_precision'] - $globalPrecisionAvg);
                if ($precDiff > $precisionGap) {
                    $alerts[] = [
                        'group_type' => $groupType,
                        'group_value' => $m['group_value'],
                        'alert_type' => 'precision_gap',
                        'value' => round($precDiff, 2),
                        'threshold' => $precisionGap,
                    ];
                }
            }
        }

        return $alerts;
    }
}
