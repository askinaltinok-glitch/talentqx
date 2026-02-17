<?php

namespace App\Http\Controllers\Api\Admin\ML;

use App\Http\Controllers\Controller;
use App\Models\ModelPrediction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Get ML model health metrics.
     *
     * GET /v1/admin/ml/health?from=YYYY-MM-DD&to=YYYY-MM-DD&industry=...
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'industry' => ['nullable', 'string', 'max:64'],
            'model_version' => ['nullable', 'string', 'max:32'],
        ]);

        $from = $data['from'] ?? now()->subDays(90)->toDateString();
        $to = $data['to'] ?? now()->toDateString();

        $query = DB::table('model_predictions as mp')
            ->join('model_features as mf', 'mf.form_interview_id', '=', 'mp.form_interview_id')
            ->leftJoin('interview_outcomes as io', 'io.form_interview_id', '=', 'mp.form_interview_id')
            ->whereBetween('mp.created_at', [$from, $to . ' 23:59:59']);

        if (!empty($data['industry'])) {
            $query->where('mf.industry_code', $data['industry']);
        }

        if (!empty($data['model_version'])) {
            $query->where('mp.model_version', $data['model_version']);
        }

        $rows = $query->select([
            'mp.predicted_outcome_score',
            'mp.predicted_label',
            'io.outcome_score as actual_outcome_score',
            'io.hired',
            'io.still_employed_30d',
            'io.still_employed_90d',
        ])->get();

        $total = $rows->count();
        $withOutcome = $rows->filter(fn($r) => $r->actual_outcome_score !== null)->count();

        if ($withOutcome === 0) {
            return response()->json([
                'success' => true,
                'data' => [
                    'range' => ['from' => $from, 'to' => $to],
                    'total_predictions' => $total,
                    'with_outcome' => 0,
                    'metrics' => null,
                    'message' => 'No predictions with outcomes yet',
                ],
            ]);
        }

        // Calculate metrics only for rows with outcomes
        $rowsWithOutcome = $rows->filter(fn($r) => $r->actual_outcome_score !== null);

        // MAE (Mean Absolute Error)
        $mae = $rowsWithOutcome->avg(fn($r) => 
            abs($r->predicted_outcome_score - $r->actual_outcome_score)
        );

        // Precision@50 (predicted good → actual >=50)
        $predictedGood = $rowsWithOutcome->filter(fn($r) => $r->predicted_label === 'GOOD');
        $truePositives = $predictedGood->filter(fn($r) => $r->actual_outcome_score >= 50)->count();
        $precision50 = $predictedGood->count() > 0 
            ? round(($truePositives / $predictedGood->count()) * 100, 2)
            : null;

        // False Negative Rate (predicted bad → actual >=70)
        $predictedBad = $rowsWithOutcome->filter(fn($r) => $r->predicted_label === 'BAD');
        $falseNegatives = $predictedBad->filter(fn($r) => $r->actual_outcome_score >= 70)->count();
        $fnRate = $predictedBad->count() > 0
            ? round(($falseNegatives / $predictedBad->count()) * 100, 2)
            : null;

        // Confusion matrix
        $matrix = [
            'predicted_good_actual_good' => $rowsWithOutcome->filter(fn($r) => 
                $r->predicted_label === 'GOOD' && $r->actual_outcome_score >= 50
            )->count(),
            'predicted_good_actual_bad' => $rowsWithOutcome->filter(fn($r) => 
                $r->predicted_label === 'GOOD' && $r->actual_outcome_score < 50
            )->count(),
            'predicted_bad_actual_good' => $rowsWithOutcome->filter(fn($r) => 
                $r->predicted_label === 'BAD' && $r->actual_outcome_score >= 50
            )->count(),
            'predicted_bad_actual_bad' => $rowsWithOutcome->filter(fn($r) => 
                $r->predicted_label === 'BAD' && $r->actual_outcome_score < 50
            )->count(),
        ];

        // Score distribution comparison
        $scoreDistribution = [
            'predicted' => [
                'mean' => round($rowsWithOutcome->avg('predicted_outcome_score'), 1),
                'min' => $rowsWithOutcome->min('predicted_outcome_score'),
                'max' => $rowsWithOutcome->max('predicted_outcome_score'),
            ],
            'actual' => [
                'mean' => round($rowsWithOutcome->avg('actual_outcome_score'), 1),
                'min' => $rowsWithOutcome->min('actual_outcome_score'),
                'max' => $rowsWithOutcome->max('actual_outcome_score'),
            ],
        ];

        // Label distribution
        $labelDistribution = [
            'predicted_good' => $rowsWithOutcome->filter(fn($r) => $r->predicted_label === 'GOOD')->count(),
            'predicted_bad' => $rowsWithOutcome->filter(fn($r) => $r->predicted_label === 'BAD')->count(),
            'actual_good' => $rowsWithOutcome->filter(fn($r) => $r->actual_outcome_score >= 50)->count(),
            'actual_bad' => $rowsWithOutcome->filter(fn($r) => $r->actual_outcome_score < 50)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'range' => ['from' => $from, 'to' => $to],
                'total_predictions' => $total,
                'with_outcome' => $withOutcome,
                'metrics' => [
                    'mae' => round($mae, 2),
                    'precision_at_50' => $precision50,
                    'fn_rate' => $fnRate,
                ],
                'confusion_matrix' => $matrix,
                'score_distribution' => $scoreDistribution,
                'label_distribution' => $labelDistribution,
            ],
        ]);
    }
}
