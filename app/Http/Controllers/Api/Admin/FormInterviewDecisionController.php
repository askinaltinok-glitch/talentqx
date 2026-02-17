<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormInterview;
use App\Services\DecisionEngine\MaritimeDecisionEngine;
use App\Services\System\SystemEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FormInterviewDecisionController extends Controller
{
    public function __construct(
        private readonly MaritimeDecisionEngine $engine,
    ) {}

    /**
     * GET /v1/admin/form-interviews/{id}/decision
     *
     * Returns the maritime decision summary. Computes on-demand if missing.
     */
    public function show(string $id): JsonResponse
    {
        $interview = FormInterview::find($id);

        if (!$interview) {
            return response()->json(['success' => false, 'error' => 'Interview not found'], 404);
        }

        if (!$interview->isCompleted()) {
            return response()->json(['success' => false, 'error' => 'Interview not completed'], 404);
        }

        $summary = $interview->decision_summary_json;
        $force = request()->boolean('force');
        $wasEmpty = empty($summary);

        if (!$summary || $force) {
            try {
                $summary = $this->engine->evaluate($interview);
                $interview->update(['decision_summary_json' => $summary]);

                // Only log system event on first compute — not on cache hits or force-refresh
                if ($wasEmpty) {
                    SystemEventService::log(
                        'decision_engine_applied',
                        'info',
                        'ml',
                        "Maritime decision engine applied on-demand: {$summary['decision']} (score: {$summary['final_score']})",
                        [
                            'interview_id' => $interview->id,
                            'decision' => $summary['decision'],
                            'final_score' => $summary['final_score'],
                            'confidence_pct' => $summary['confidence_pct'],
                            'industry_code' => $interview->industry_code ?? 'maritime',
                            'brand' => 'octopus',
                            'trigger' => 'on_demand',
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::channel('single')->error('MaritimeDecisionEngine on-demand failed', [
                    'interview_id' => $id,
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Decision engine evaluation failed',
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
