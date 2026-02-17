<?php

namespace App\Http\Controllers\Api\Admin\ML;

use App\Http\Controllers\Controller;
use App\Models\ModelWeight;
use App\Services\ML\MlLearningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningController extends Controller
{
    public function __construct(
        protected MlLearningService $learningService
    ) {
    }
    /**
     * Get learning health metrics.
     *
     * GET /v1/admin/ml/learning-health
     */
    public function health(Request $request): JsonResponse
    {
        $data = $request->validate([
            'industry' => ['nullable', 'string', 'max:64'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $industry = $data['industry'] ?? null;
        $days = $data['days'] ?? 30;
        $from = now()->subDays($days)->toDateString();

        // Learning events summary
        $eventsQuery = DB::table('learning_events')
            ->where('created_at', '>=', $from)
            ->when($industry, fn($q) => $q->where('industry_code', $industry));

        $eventsSummary = $eventsQuery->clone()
            ->selectRaw('
                COUNT(*) as total_events,
                AVG(ABS(error)) as mae,
                SUM(CASE WHEN is_false_positive THEN 1 ELSE 0 END) as false_positives,
                SUM(CASE WHEN is_false_negative THEN 1 ELSE 0 END) as false_negatives,
                AVG(error) as mean_error
            ')
            ->first();

        // Error trend by day
        $errorTrend = $eventsQuery->clone()
            ->selectRaw('DATE(created_at) as date, AVG(ABS(error)) as mae, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->limit(30)
            ->get();

        // Top features by weight impact
        $topFeatures = DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('sample_count', '>=', 3)
            ->orderByDesc(DB::raw('ABS(current_weight)'))
            ->limit(20)
            ->select([
                'feature_name',
                'industry_code',
                'current_weight',
                'sample_count',
                'positive_impact_count',
                'negative_impact_count',
                'last_updated_at',
            ])
            ->get()
            ->map(function ($f) {
                $total = $f->positive_impact_count + $f->negative_impact_count;
                $f->positive_rate = $total > 0
                    ? round(($f->positive_impact_count / $total) * 100, 1)
                    : null;
                $f->stability = $total >= 10
                    ? round(abs($f->positive_impact_count - $f->negative_impact_count) / $total * 100, 1)
                    : null;
                return $f;
            });

        // Unstable features (inconsistent impact direction)
        $unstableFeatures = DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('sample_count', '>=', 10)
            ->whereRaw('positive_impact_count > 0 AND negative_impact_count > 0')
            ->selectRaw('*,
                ABS(positive_impact_count - negative_impact_count) / (positive_impact_count + negative_impact_count) as stability')
            ->having('stability', '<', 0.3)
            ->orderBy('stability')
            ->limit(10)
            ->get();

        // Learning cycles history
        $learningCycles = DB::table('learning_cycles')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->orderByDesc('created_at')
            ->limit(10)
            ->select([
                'id',
                'model_version_before',
                'model_version_after',
                'industry_code',
                'trigger',
                'samples_processed',
                'improvement_pct',
                'created_at',
            ])
            ->get();

        // Source channel performance
        $sourceChannels = DB::table('source_channel_metrics')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('total_outcomes', '>=', 3)
            ->orderByDesc('total_outcomes')
            ->get()
            ->map(function ($s) {
                $s->success_rate = $s->total_outcomes > 0
                    ? round(($s->successful_outcomes / $s->total_outcomes) * 100, 1)
                    : null;
                return $s;
            });

        // Current model version
        $currentModel = DB::table('model_weights')
            ->orderByDesc('created_at')
            ->first(['model_version', 'notes', 'created_at']);

        // False positive patterns
        $fpPatterns = DB::table('learning_patterns')
            ->where('pattern_type', 'false_positive')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->orderByDesc('occurrence_count')
            ->limit(10)
            ->get();

        // False negative patterns
        $fnPatterns = DB::table('learning_patterns')
            ->where('pattern_type', 'false_negative')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->orderByDesc('occurrence_count')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $from,
                    'to' => now()->toDateString(),
                    'days' => $days,
                ],
                'current_model' => $currentModel,
                'events_summary' => [
                    'total' => (int) ($eventsSummary->total_events ?? 0),
                    'mae' => $eventsSummary->mae ? round($eventsSummary->mae, 2) : null,
                    'mean_error' => $eventsSummary->mean_error ? round($eventsSummary->mean_error, 2) : null,
                    'false_positives' => (int) ($eventsSummary->false_positives ?? 0),
                    'false_negatives' => (int) ($eventsSummary->false_negatives ?? 0),
                ],
                'error_trend' => $errorTrend,
                'top_features' => $topFeatures,
                'unstable_features' => $unstableFeatures,
                'learning_cycles' => $learningCycles,
                'source_channels' => $sourceChannels,
                'fp_patterns' => $fpPatterns,
                'fn_patterns' => $fnPatterns,
            ],
        ]);
    }

    /**
     * Get feature importance details.
     *
     * GET /v1/admin/ml/features
     */
    public function features(Request $request): JsonResponse
    {
        $data = $request->validate([
            'industry' => ['nullable', 'string', 'max:64'],
            'type' => ['nullable', 'string', 'in:risk_flag,meta,source,industry,base'],
            'sort' => ['nullable', 'string', 'in:weight,samples,stability'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $query = DB::table('model_feature_importance')
            ->when($data['industry'] ?? null, fn($q, $v) => $q->where('industry_code', $v))
            ->when($data['type'] ?? null, fn($q, $v) => $q->where('feature_name', 'like', $v . ':%'));

        $sort = $data['sort'] ?? 'weight';
        $direction = $data['direction'] ?? 'desc';

        $query->orderBy(match ($sort) {
            'weight' => DB::raw('ABS(current_weight)'),
            'samples' => 'sample_count',
            'stability' => DB::raw('ABS(positive_impact_count - negative_impact_count) / NULLIF(positive_impact_count + negative_impact_count, 0)'),
            default => DB::raw('ABS(current_weight)'),
        }, $direction);

        $features = $query->limit(100)->get()->map(function ($f) {
            $total = $f->positive_impact_count + $f->negative_impact_count;
            return [
                'feature_name' => $f->feature_name,
                'industry_code' => $f->industry_code,
                'current_weight' => round($f->current_weight, 4),
                'sample_count' => $f->sample_count,
                'positive_impact_count' => $f->positive_impact_count,
                'negative_impact_count' => $f->negative_impact_count,
                'positive_rate' => $total > 0 ? round(($f->positive_impact_count / $total) * 100, 1) : null,
                'stability' => $total >= 5
                    ? round(abs($f->positive_impact_count - $f->negative_impact_count) / $total * 100, 1)
                    : null,
                'correlation_score' => $f->correlation_score,
                'last_updated_at' => $f->last_updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }

    /**
     * Get learning events log.
     *
     * GET /v1/admin/ml/learning-events
     */
    public function events(Request $request): JsonResponse
    {
        $data = $request->validate([
            'industry' => ['nullable', 'string', 'max:64'],
            'type' => ['nullable', 'string', 'in:fp,fn,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = DB::table('learning_events')
            ->when($data['industry'] ?? null, fn($q, $v) => $q->where('industry_code', $v))
            ->when(($data['type'] ?? null) === 'fp', fn($q) => $q->where('is_false_positive', true))
            ->when(($data['type'] ?? null) === 'fn', fn($q) => $q->where('is_false_negative', true));

        $total = $query->clone()->count();

        $events = $query
            ->orderByDesc('created_at')
            ->limit($data['limit'] ?? 50)
            ->offset($data['offset'] ?? 0)
            ->select([
                'id',
                'form_interview_id',
                'model_version',
                'industry_code',
                'source_channel',
                'predicted_score',
                'actual_score',
                'error',
                'predicted_label',
                'actual_label',
                'is_false_positive',
                'is_false_negative',
                'created_at',
            ])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'events' => $events,
            ],
        ]);
    }

    /**
     * List model weight versions.
     *
     * GET /v1/admin/ml/versions
     */
    public function versions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $versions = ModelWeight::listVersions($data['limit'] ?? 20);

        return response()->json([
            'success' => true,
            'data' => $versions,
        ]);
    }

    /**
     * Rollback to a specific model version.
     *
     * POST /v1/admin/ml/rollback
     */
    public function rollback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'model_version' => ['required', 'string', 'max:64'],
        ]);

        $result = $this->learningService->rollbackToVersion($data['model_version']);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['reason'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'active_version' => $result['active_version'],
                'message' => $result['message'],
            ],
        ]);
    }

    /**
     * Freeze a model weight version.
     *
     * POST /v1/admin/ml/versions/{id}/freeze
     */
    public function freeze(Request $request, string $id): JsonResponse
    {
        $weight = ModelWeight::findOrFail($id);
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $weight->freeze($data['notes'] ?? null);

        return response()->json([
            'success' => true,
            'data' => [
                'version' => $weight->model_version,
                'is_frozen' => true,
                'frozen_at' => $weight->fresh()->frozen_at,
            ],
        ]);
    }

    /**
     * Unfreeze a model weight version.
     *
     * POST /v1/admin/ml/versions/{id}/unfreeze
     */
    public function unfreeze(string $id): JsonResponse
    {
        $weight = ModelWeight::findOrFail($id);
        $weight->unfreeze();

        return response()->json([
            'success' => true,
            'data' => [
                'version' => $weight->model_version,
                'is_frozen' => false,
            ],
        ]);
    }

    /**
     * Get ML stability metrics.
     *
     * GET /v1/admin/ml/stability
     */
    public function stability(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 7);
        $from = now()->subDays($days)->toDateString();

        $activeWeights = ModelWeight::where('is_active', true)->first();

        $volatilityBlocks = DB::table('system_events')
            ->where('type', 'ml_volatility_block')
            ->where('created_at', '>=', $from)
            ->count();

        $suddenShiftBlocks = DB::table('system_events')
            ->where('type', 'ml_sudden_shift_block')
            ->where('created_at', '>=', $from)
            ->count();

        $learningCycles = DB::table('learning_cycles')
            ->where('created_at', '>=', $from)
            ->count();

        $eventCounts = DB::table('learning_events')
            ->where('created_at', '>=', $from)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'active_version' => $activeWeights?->model_version,
                'is_frozen' => $activeWeights?->is_frozen ?? false,
                'frozen_at' => $activeWeights?->frozen_at,
                'frozen_notes' => $activeWeights?->frozen_notes,
                'canary_mode' => config('ml.canary_mode_enabled', false),
                'canary_industry' => config('ml.canary_industry'),
                'volatility_blocks' => $volatilityBlocks,
                'sudden_shift_blocks' => $suddenShiftBlocks,
                'learning_cycles' => $learningCycles,
                'event_counts' => $eventCounts,
                'period_days' => $days,
            ],
        ]);
    }

    /**
     * Get fairness metrics by groups.
     *
     * GET /v1/admin/ml/fairness
     */
    public function fairness(Request $request): JsonResponse
    {
        $data = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $days = $data['days'] ?? 30;
        $from = now()->subDays($days)->toDateString();

        $scoreAlertThreshold = config('ml.fairness.score_delta_alert', 10);
        $precisionAlertThreshold = config('ml.fairness.precision_gap_alert', 15);

        $groups = [];
        $alerts = [];

        // Group by country_code
        $countryMetrics = $this->calculateGroupMetrics('country_code', $from);
        $groups['country_code'] = $countryMetrics;
        $alerts = array_merge($alerts, $this->detectAlerts($countryMetrics, 'country_code', $scoreAlertThreshold, $precisionAlertThreshold));

        // Group by language
        $languageMetrics = $this->calculateGroupMetrics('language', $from);
        $groups['language'] = $languageMetrics;
        $alerts = array_merge($alerts, $this->detectAlerts($languageMetrics, 'language', $scoreAlertThreshold, $precisionAlertThreshold));

        // Group by industry_code
        $industryMetrics = $this->calculateGroupMetrics('industry_code', $from);
        $groups['industry_code'] = $industryMetrics;
        $alerts = array_merge($alerts, $this->detectAlerts($industryMetrics, 'industry_code', $scoreAlertThreshold, $precisionAlertThreshold));

        // Get latest fairness reports
        $latestReports = DB::table('ml_fairness_reports')
            ->where('report_date', '>=', now()->subDays(7)->toDateString())
            ->where('has_alert', true)
            ->orderByDesc('report_date')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $from,
                    'to' => now()->toDateString(),
                    'days' => $days,
                ],
                'thresholds' => [
                    'score_delta' => $scoreAlertThreshold,
                    'precision_gap' => $precisionAlertThreshold,
                ],
                'groups' => $groups,
                'alerts' => $alerts,
                'recent_alert_reports' => $latestReports,
            ],
        ]);
    }

    /**
     * Calculate metrics for a group type.
     */
    protected function calculateGroupMetrics(string $groupField, string $from): array
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

    /**
     * Detect fairness alerts for a group type.
     */
    protected function detectAlerts(array $metrics, string $groupType, float $scoreDelta, float $precisionGap): array
    {
        if (count($metrics) < 2) {
            return [];
        }

        $alerts = [];

        // Calculate global averages
        $totalSamples = array_sum(array_column($metrics, 'sample_count'));
        $avgPredicted = $totalSamples > 0
            ? array_sum(array_map(fn($m) => $m['avg_predicted_score'] * $m['sample_count'], $metrics)) / $totalSamples
            : 0;

        $outcomeSamples = array_filter($metrics, fn($m) => $m['avg_outcome_score'] !== null);
        $avgOutcome = count($outcomeSamples) > 0
            ? array_sum(array_map(fn($m) => $m['avg_outcome_score'] * $m['sample_count'], $outcomeSamples)) /
              array_sum(array_column($outcomeSamples, 'sample_count'))
            : null;

        $precisionSamples = array_filter($metrics, fn($m) => $m['hire_precision'] !== null);
        $avgPrecision = count($precisionSamples) > 0
            ? array_sum(array_column($precisionSamples, 'hire_precision')) / count($precisionSamples)
            : null;

        foreach ($metrics as $metric) {
            $alertDetails = [];

            // Check predicted score delta
            if (abs($metric['avg_predicted_score'] - $avgPredicted) > $scoreDelta) {
                $alertDetails[] = [
                    'type' => 'predicted_score_delta',
                    'value' => round($metric['avg_predicted_score'] - $avgPredicted, 2),
                    'threshold' => $scoreDelta,
                ];
            }

            // Check outcome score delta
            if ($metric['avg_outcome_score'] !== null && $avgOutcome !== null) {
                if (abs($metric['avg_outcome_score'] - $avgOutcome) > $scoreDelta) {
                    $alertDetails[] = [
                        'type' => 'outcome_score_delta',
                        'value' => round($metric['avg_outcome_score'] - $avgOutcome, 2),
                        'threshold' => $scoreDelta,
                    ];
                }
            }

            // Check precision gap
            if ($metric['hire_precision'] !== null && $avgPrecision !== null) {
                if (abs($metric['hire_precision'] - $avgPrecision) > $precisionGap) {
                    $alertDetails[] = [
                        'type' => 'precision_gap',
                        'value' => round($metric['hire_precision'] - $avgPrecision, 2),
                        'threshold' => $precisionGap,
                    ];
                }
            }

            if (!empty($alertDetails)) {
                $alerts[] = [
                    'group_type' => $groupType,
                    'group_value' => $metric['group_value'],
                    'alerts' => $alertDetails,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Generate and store fairness report (for daily job).
     *
     * POST /v1/admin/ml/fairness/generate
     */
    public function generateFairnessReport(Request $request): JsonResponse
    {
        $reportDate = now()->toDateString();
        $from = now()->subDays(30)->toDateString();

        $scoreAlertThreshold = config('ml.fairness.score_delta_alert', 10);
        $precisionAlertThreshold = config('ml.fairness.precision_gap_alert', 15);

        $groupTypes = ['country_code', 'language', 'industry_code'];
        $reportsCreated = 0;

        foreach ($groupTypes as $groupType) {
            $metrics = $this->calculateGroupMetrics($groupType, $from);
            $alerts = $this->detectAlerts($metrics, $groupType, $scoreAlertThreshold, $precisionAlertThreshold);

            foreach ($metrics as $metric) {
                $alertsForGroup = array_filter($alerts, fn($a) => $a['group_value'] === $metric['group_value']);
                $hasAlert = !empty($alertsForGroup);

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
                        'alert_details_json' => $hasAlert ? json_encode($alertsForGroup) : null,
                        'created_at' => now(),
                    ]
                );

                $reportsCreated++;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'report_date' => $reportDate,
                'reports_created' => $reportsCreated,
            ],
        ]);
    }
}
