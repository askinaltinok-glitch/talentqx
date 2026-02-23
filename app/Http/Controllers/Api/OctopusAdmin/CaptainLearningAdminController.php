<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\CrewConflictReport;
use App\Models\CrewFeedback;
use App\Models\CrewOutcome;
use App\Models\SynergyWeightSet;
use App\Services\Fleet\MemoryLearningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaptainLearningAdminController extends Controller
{
    public function __construct(
        private MemoryLearningService $learning,
    ) {}

    /**
     * GET /v1/octopus/admin/learning/suspicious-feedback
     * List suspicious crew feedback + conflict reports.
     */
    public function suspiciousFeedback(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);

        // Suspicious crew feedback
        $suspiciousFeedback = CrewFeedback::query()
            ->where('status', CrewFeedback::STATUS_FLAGGED)
            ->orWhere(function ($q) {
                $q->where('rating_overall', 1)
                    ->orWhere('rating_overall', 5);
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->filter(fn ($f) => $f->isSuspicious())
            ->values();

        // Suspicious conflict reports
        $suspiciousConflicts = CrewConflictReport::withoutTenantScope()
            ->where('is_suspicious', true)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'suspicious_feedback' => $suspiciousFeedback->map(fn ($f) => [
                    'id' => $f->id,
                    'type' => 'crew_feedback',
                    'vessel_id' => $f->vessel_id,
                    'rating_overall' => $f->rating_overall,
                    'feedback_type' => $f->feedback_type,
                    'status' => $f->status,
                    'created_at' => $f->created_at?->toIso8601String(),
                ]),
                'suspicious_conflicts' => $suspiciousConflicts->map(fn ($c) => [
                    'id' => $c->id,
                    'type' => 'conflict_report',
                    'vessel_id' => $c->vessel_id,
                    'category' => $c->category,
                    'rating' => $c->rating,
                    'is_suspicious' => $c->is_suspicious,
                    'suspicion_reason' => $c->suspicion_reason,
                    'created_at' => $c->created_at?->toIso8601String(),
                ]),
                'total_suspicious' => $suspiciousFeedback->count() + $suspiciousConflicts->count(),
            ],
        ]);
    }

    /**
     * GET /v1/octopus/admin/learning/metrics
     * Learning readiness: sample size counts, weight sets status.
     */
    public function metrics(Request $request): JsonResponse
    {
        $globalStatus = $this->learning->getStatus('global');

        // Count outcomes by type
        $outcomeCounts = CrewOutcome::withoutTenantScope()
            ->where('created_at', '>=', now()->subDays(90))
            ->selectRaw('outcome_type, count(*) as count')
            ->groupBy('outcome_type')
            ->pluck('count', 'outcome_type')
            ->toArray();

        // Count weight sets
        $weightSetCount = SynergyWeightSet::count();
        $trainableCount = SynergyWeightSet::where('sample_size', '>=', SynergyWeightSet::MIN_SAMPLE_SIZE)->count();

        // Count total feedback
        $feedbackCount = CrewFeedback::count();
        $conflictCount = CrewConflictReport::withoutTenantScope()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'global_learning' => $globalStatus,
                'outcome_counts_90d' => $outcomeCounts,
                'total_outcomes_90d' => array_sum($outcomeCounts),
                'weight_sets' => [
                    'total' => $weightSetCount,
                    'trainable' => $trainableCount,
                ],
                'feedback_counts' => [
                    'crew_feedback' => $feedbackCount,
                    'conflict_reports' => $conflictCount,
                ],
            ],
        ]);
    }

    /**
     * POST /v1/octopus/admin/learning/retrain-global
     */
    public function retrainGlobal(Request $request): JsonResponse
    {
        $windowDays = (int) $request->input('window_days', 90);
        $result = $this->learning->retrain('global', null, $windowDays);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
