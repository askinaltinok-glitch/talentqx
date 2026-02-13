<?php

namespace App\Http\Controllers\Api\Admin\ML;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningController extends Controller
{
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
}
