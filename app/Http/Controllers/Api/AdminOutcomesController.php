<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormInterview;
use App\Models\InterviewOutcome;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdminOutcomesController extends Controller
{
    /**
     * Record or update an interview outcome.
     *
     * POST /v1/admin/outcomes
     *
     * Body:
     * - form_interview_id (required, uuid)
     * - hired (bool)
     * - started (bool)
     * - still_employed_30d (bool)
     * - still_employed_90d (bool)
     * - performance_rating (1-5)
     * - incident_flag (bool)
     * - incident_notes (string)
     * - notes (string)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'form_interview_id' => 'required|uuid|exists:form_interviews,id',
            'hired' => 'nullable|boolean',
            'started' => 'nullable|boolean',
            'still_employed_30d' => 'nullable|boolean',
            'still_employed_90d' => 'nullable|boolean',
            'performance_rating' => 'nullable|integer|min:1|max:5',
            'incident_flag' => 'nullable|boolean',
            'incident_notes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $interview = FormInterview::find($request->input('form_interview_id'));

        // Must be a completed interview
        if ($interview->status !== FormInterview::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_state',
                'message' => 'Outcome can only be recorded for completed interviews',
            ], 422);
        }

        // Upsert outcome
        $outcome = InterviewOutcome::updateOrCreate(
            ['form_interview_id' => $interview->id],
            [
                'hired' => $request->input('hired'),
                'started' => $request->input('started'),
                'still_employed_30d' => $request->input('still_employed_30d'),
                'still_employed_90d' => $request->input('still_employed_90d'),
                'performance_rating' => $request->input('performance_rating'),
                'incident_flag' => $request->input('incident_flag'),
                'incident_notes' => $request->input('incident_notes'),
                'notes' => $request->input('notes'),
                'outcome_source' => InterviewOutcome::SOURCE_ADMIN,
                'recorded_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $outcome->id,
                'form_interview_id' => $outcome->form_interview_id,
                'hired' => $outcome->hired,
                'started' => $outcome->started,
                'still_employed_30d' => $outcome->still_employed_30d,
                'still_employed_90d' => $outcome->still_employed_90d,
                'performance_rating' => $outcome->performance_rating,
                'incident_flag' => $outcome->incident_flag,
                'incident_notes' => $outcome->incident_notes,
                'outcome_score' => $outcome->getOutcomeScore(),
                'is_successful' => $outcome->isSuccessful(),
                'is_strong_success' => $outcome->isStrongSuccess(),
                'notes' => $outcome->notes,
                'recorded_at' => $outcome->recorded_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Get outcome for an interview.
     *
     * GET /v1/admin/outcomes/{interview_id}
     */
    public function show(string $interviewId): JsonResponse
    {
        $interview = FormInterview::with('outcome')->find($interviewId);

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Interview not found',
            ], 404);
        }

        if (!$interview->outcome) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No outcome recorded for this interview',
            ]);
        }

        $outcome = $interview->outcome;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $outcome->id,
                'form_interview_id' => $outcome->form_interview_id,
                'hired' => $outcome->hired,
                'started' => $outcome->started,
                'still_employed_30d' => $outcome->still_employed_30d,
                'still_employed_90d' => $outcome->still_employed_90d,
                'performance_rating' => $outcome->performance_rating,
                'incident_flag' => $outcome->incident_flag,
                'incident_notes' => $outcome->incident_notes,
                'outcome_score' => $outcome->getOutcomeScore(),
                'is_successful' => $outcome->isSuccessful(),
                'is_strong_success' => $outcome->isStrongSuccess(),
                'notes' => $outcome->notes,
                'outcome_source' => $outcome->outcome_source,
                'recorded_at' => $outcome->recorded_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * List all outcomes with interview context.
     *
     * GET /v1/admin/outcomes
     * Query params: hired, incident, from, to, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->input('per_page', 20)), 100);

        $query = InterviewOutcome::with(['formInterview' => function ($q) {
            $q->select('id', 'position_code', 'decision', 'final_score', 'completed_at');
        }]);

        // Filters
        if ($request->has('hired')) {
            $query->where('hired', filter_var($request->input('hired'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('incident')) {
            $query->where('incident_flag', filter_var($request->input('incident'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('from')) {
            $query->where('recorded_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('recorded_at', '<=', $request->input('to'));
        }

        $paginated = $query->orderByDesc('recorded_at')->paginate($perPage);

        $data = $paginated->getCollection()->map(function ($outcome) {
            return [
                'id' => $outcome->id,
                'form_interview_id' => $outcome->form_interview_id,
                'interview' => $outcome->formInterview ? [
                    'position_code' => $outcome->formInterview->position_code,
                    'decision' => $outcome->formInterview->decision,
                    'final_score' => $outcome->formInterview->final_score,
                    'completed_at' => $outcome->formInterview->completed_at?->toIso8601String(),
                ] : null,
                'hired' => $outcome->hired,
                'started' => $outcome->started,
                'still_employed_30d' => $outcome->still_employed_30d,
                'still_employed_90d' => $outcome->still_employed_90d,
                'performance_rating' => $outcome->performance_rating,
                'incident_flag' => $outcome->incident_flag,
                'outcome_score' => $outcome->getOutcomeScore(),
                'is_successful' => $outcome->isSuccessful(),
                'recorded_at' => $outcome->recorded_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Get outcome statistics summary.
     *
     * GET /v1/admin/outcomes/stats
     * Query params: from, to, position_code
     */
    public function stats(Request $request): JsonResponse
    {
        $query = InterviewOutcome::query();

        if ($request->filled('from')) {
            $query->where('recorded_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('recorded_at', '<=', $request->input('to'));
        }

        if ($request->filled('position_code')) {
            $query->whereHas('formInterview', function ($q) use ($request) {
                $q->where('position_code', $request->input('position_code'));
            });
        }

        $outcomes = $query->get();
        $total = $outcomes->count();

        if ($total === 0) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => 0,
                    'message' => 'No outcomes recorded',
                ],
            ]);
        }

        // Calculate stats
        $hired = $outcomes->where('hired', true)->count();
        $started = $outcomes->where('started', true)->count();
        $retained30d = $outcomes->where('still_employed_30d', true)->count();
        $retained90d = $outcomes->where('still_employed_90d', true)->count();
        $incidents = $outcomes->where('incident_flag', true)->count();

        $avgOutcomeScore = $outcomes->avg(fn($o) => $o->getOutcomeScore());
        $avgPerformance = $outcomes->whereNotNull('performance_rating')->avg('performance_rating');

        // Correlation analysis: Compare prediction vs outcome
        $withInterview = $outcomes->load('formInterview');
        $hireDecisionOutcomes = $withInterview->filter(fn($o) =>
            $o->formInterview && $o->formInterview->decision === 'HIRE'
        );
        $hireAccuracy = $hireDecisionOutcomes->count() > 0
            ? round($hireDecisionOutcomes->filter(fn($o) => $o->isSuccessful())->count() / $hireDecisionOutcomes->count() * 100, 1)
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'hired_count' => $hired,
                'hired_rate' => round($hired / $total * 100, 1),
                'started_count' => $started,
                'started_rate' => $hired > 0 ? round($started / $hired * 100, 1) : null,
                'retained_30d_count' => $retained30d,
                'retained_30d_rate' => $started > 0 ? round($retained30d / $started * 100, 1) : null,
                'retained_90d_count' => $retained90d,
                'retained_90d_rate' => $started > 0 ? round($retained90d / $started * 100, 1) : null,
                'incident_count' => $incidents,
                'incident_rate' => $started > 0 ? round($incidents / $started * 100, 1) : null,
                'avg_outcome_score' => round($avgOutcomeScore, 1),
                'avg_performance_rating' => $avgPerformance ? round($avgPerformance, 2) : null,
                'hire_decision_accuracy' => $hireAccuracy,
            ],
        ]);
    }

    /**
     * Get model health metrics - decision accuracy and calibration quality.
     *
     * GET /v1/admin/outcomes/model-health
     * Query params: from, to, position_code, industry_code
     */
    public function modelHealth(Request $request): JsonResponse
    {
        $query = InterviewOutcome::with('formInterview');

        if ($request->filled('from')) {
            $query->where('recorded_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('recorded_at', '<=', $request->input('to'));
        }

        if ($request->filled('position_code')) {
            $query->whereHas('formInterview', function ($q) use ($request) {
                $q->where('position_code', $request->input('position_code'));
            });
        }

        if ($request->filled('industry_code')) {
            $query->whereHas('formInterview', function ($q) use ($request) {
                $q->where('industry_code', $request->input('industry_code'));
            });
        }

        $outcomes = $query->get();
        $total = $outcomes->count();

        if ($total === 0) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => 0,
                    'message' => 'No outcomes with interview data available',
                ],
            ]);
        }

        // Filter to only outcomes with valid interview data
        $validOutcomes = $outcomes->filter(fn($o) => $o->formInterview !== null);
        $validTotal = $validOutcomes->count();

        if ($validTotal === 0) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'valid_total' => 0,
                    'message' => 'No outcomes have linked interview data',
                ],
            ]);
        }

        // ======================================
        // 1. Decision → Outcome Matrix
        // ======================================
        $decisionOutcomeMatrix = [];
        $decisions = ['HIRE', 'HOLD', 'REJECT'];
        $outcomeScoreBuckets = [
            'poor' => [0, 30],      // 0-30
            'weak' => [31, 50],     // 31-50
            'good' => [51, 70],     // 51-70
            'strong' => [71, 100],  // 71-100
        ];

        foreach ($decisions as $decision) {
            $decisionOutcomes = $validOutcomes->filter(fn($o) => $o->formInterview->decision === $decision);
            $decisionCount = $decisionOutcomes->count();

            $matrix = ['total' => $decisionCount];
            foreach ($outcomeScoreBuckets as $bucketName => $range) {
                $bucketCount = $decisionOutcomes->filter(function ($o) use ($range) {
                    $score = $o->getOutcomeScore();
                    return $score >= $range[0] && $score <= $range[1];
                })->count();
                $matrix[$bucketName] = $bucketCount;
                $matrix[$bucketName . '_pct'] = $decisionCount > 0 ? round($bucketCount / $decisionCount * 100, 1) : 0;
            }
            $decisionOutcomeMatrix[$decision] = $matrix;
        }

        // ======================================
        // 2. Precision Metrics
        // ======================================
        $hireOutcomes = $validOutcomes->filter(fn($o) => $o->formInterview->decision === 'HIRE');
        $holdOutcomes = $validOutcomes->filter(fn($o) => $o->formInterview->decision === 'HOLD');
        $rejectOutcomes = $validOutcomes->filter(fn($o) => $o->formInterview->decision === 'REJECT');

        // HIRE precision: % of HIRE decisions where outcome_score >= 50
        $hirePrecision = $hireOutcomes->count() > 0
            ? round($hireOutcomes->filter(fn($o) => $o->getOutcomeScore() >= 50)->count() / $hireOutcomes->count() * 100, 1)
            : null;

        // HIRE strong precision: % of HIRE decisions where outcome_score >= 70
        $hireStrongPrecision = $hireOutcomes->count() > 0
            ? round($hireOutcomes->filter(fn($o) => $o->getOutcomeScore() >= 70)->count() / $hireOutcomes->count() * 100, 1)
            : null;

        // REJECT false-negative: % of REJECT decisions where outcome_score >= 70 (we rejected a potentially good candidate)
        $rejectFalseNegative = $rejectOutcomes->count() > 0
            ? round($rejectOutcomes->filter(fn($o) => $o->getOutcomeScore() >= 70)->count() / $rejectOutcomes->count() * 100, 1)
            : null;

        // HOLD conversion: % of HOLD decisions that were eventually hired with good outcome
        $holdConversion = $holdOutcomes->count() > 0
            ? round($holdOutcomes->filter(fn($o) => $o->hired === true && $o->getOutcomeScore() >= 50)->count() / $holdOutcomes->count() * 100, 1)
            : null;

        // ======================================
        // 3. Calibration Quality
        // ======================================
        $calibrationBuckets = [
            '0-30' => [0, 30],
            '31-50' => [31, 50],
            '51-70' => [51, 70],
            '71-85' => [71, 85],
            '86-100' => [86, 100],
        ];

        $calibrationQuality = [];
        foreach ($calibrationBuckets as $bucketName => $range) {
            $bucketOutcomes = $validOutcomes->filter(function ($o) use ($range) {
                $calScore = $o->formInterview->calibrated_score ?? $o->formInterview->final_score;
                return $calScore >= $range[0] && $calScore <= $range[1];
            });

            $bucketCount = $bucketOutcomes->count();
            $avgOutcomeScore = $bucketCount > 0 ? round($bucketOutcomes->avg(fn($o) => $o->getOutcomeScore()), 1) : null;
            $successRate = $bucketCount > 0
                ? round($bucketOutcomes->filter(fn($o) => $o->isSuccessful())->count() / $bucketCount * 100, 1)
                : null;

            $calibrationQuality[$bucketName] = [
                'count' => $bucketCount,
                'avg_outcome_score' => $avgOutcomeScore,
                'success_rate' => $successRate,
            ];
        }

        // ======================================
        // 4. Alerts
        // ======================================
        $alerts = [];

        // Alert: Poor HIRE precision
        if ($hirePrecision !== null && $hirePrecision < 60) {
            $alerts[] = [
                'type' => 'hire_precision_low',
                'severity' => 'warning',
                'message' => "HIRE precision is low: {$hirePrecision}% (threshold: 60%)",
            ];
        }

        // Alert: High REJECT false-negative
        if ($rejectFalseNegative !== null && $rejectFalseNegative > 10) {
            $alerts[] = [
                'type' => 'reject_false_negative',
                'severity' => 'warning',
                'message' => "REJECT false-negative rate: {$rejectFalseNegative}% (threshold: 10%)",
            ];
        }

        // Alert: Calibration mismatch (high scores with poor outcomes)
        $highScorePoorOutcome = $calibrationQuality['86-100'] ?? null;
        if ($highScorePoorOutcome && $highScorePoorOutcome['count'] >= 5 && $highScorePoorOutcome['success_rate'] !== null && $highScorePoorOutcome['success_rate'] < 70) {
            $alerts[] = [
                'type' => 'calibration_mismatch_high',
                'severity' => 'critical',
                'message' => "High calibrated scores (86-100) have low success rate: {$highScorePoorOutcome['success_rate']}%",
            ];
        }

        // Alert: Low sample size
        if ($validTotal < 30) {
            $alerts[] = [
                'type' => 'low_sample_size',
                'severity' => 'info',
                'message' => "Sample size is low ({$validTotal}). Metrics may not be statistically significant.",
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_outcomes' => $total,
                'valid_outcomes' => $validTotal,
                'decision_outcome_matrix' => $decisionOutcomeMatrix,
                'precision_metrics' => [
                    'hire_precision' => $hirePrecision,
                    'hire_strong_precision' => $hireStrongPrecision,
                    'reject_false_negative' => $rejectFalseNegative,
                    'hold_conversion' => $holdConversion,
                ],
                'calibration_quality' => $calibrationQuality,
                'alerts' => $alerts,
            ],
            'meta' => [
                'outcome_score_buckets' => [
                    'poor' => '0-30',
                    'weak' => '31-50',
                    'good' => '51-70',
                    'strong' => '71-100',
                ],
                'thresholds' => [
                    'hire_precision_warning' => 60,
                    'reject_false_negative_warning' => 10,
                    'min_sample_size' => 30,
                ],
            ],
        ]);
    }
}
