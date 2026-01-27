<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeAssessmentJob;
use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use App\Models\AssessmentTemplate;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    // Template endpoints
    public function templates(Request $request): JsonResponse
    {
        $templates = AssessmentTemplate::active()
            ->when($request->has('role'), fn($q) => $q->where('role_category', $request->role))
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    public function templateShow(string $slug): JsonResponse
    {
        $template = AssessmentTemplate::where('slug', $slug)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    // Session management
    public function createSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'template_id' => 'required|uuid|exists:assessment_templates,id',
        ]);

        $employee = Employee::where('company_id', $request->user()->company_id)
            ->findOrFail($validated['employee_id']);

        $template = AssessmentTemplate::findOrFail($validated['template_id']);

        // Check for existing pending session
        $existingSession = AssessmentSession::where('employee_id', $employee->id)
            ->where('template_id', $template->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('token_expires_at', '>', now())
            ->first();

        if ($existingSession) {
            return response()->json([
                'success' => true,
                'data' => $existingSession,
                'message' => 'Mevcut degerlendirme oturumu kullaniliyor.',
            ]);
        }

        $session = AssessmentSession::create([
            'employee_id' => $employee->id,
            'template_id' => $template->id,
            'initiated_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $session,
            'message' => 'Degerlendirme oturumu olusturuldu.',
        ], 201);
    }

    public function bulkCreateSessions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1|max:100',
            'employee_ids.*' => 'uuid|exists:employees,id',
            'template_id' => 'required|uuid|exists:assessment_templates,id',
        ]);

        $template = AssessmentTemplate::findOrFail($validated['template_id']);
        $companyId = $request->user()->company_id;
        $userId = $request->user()->id;

        $created = 0;
        $skipped = 0;

        foreach ($validated['employee_ids'] as $employeeId) {
            $employee = Employee::where('company_id', $companyId)->find($employeeId);
            if (!$employee) {
                $skipped++;
                continue;
            }

            // Skip if active session exists
            $exists = AssessmentSession::where('employee_id', $employeeId)
                ->where('template_id', $template->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->where('token_expires_at', '>', now())
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            AssessmentSession::create([
                'employee_id' => $employeeId,
                'template_id' => $template->id,
                'initiated_by' => $userId,
            ]);
            $created++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
            ],
            'message' => "{$created} degerlendirme oturumu olusturuldu.",
        ]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $query = AssessmentSession::with(['employee', 'template', 'result'])
            ->whereHas('employee', fn($q) => $q->where('company_id', $request->user()->company_id));

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        $sessions = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $sessions->items(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    public function sessionShow(Request $request, string $id): JsonResponse
    {
        $session = AssessmentSession::with(['employee', 'template', 'result', 'initiator'])
            ->whereHas('employee', fn($q) => $q->where('company_id', $request->user()->company_id))
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }

    // Public assessment endpoints (token-based)
    public function publicShow(Request $request, string $token): JsonResponse
    {
        $session = AssessmentSession::with(['employee:id,first_name,last_name', 'template'])
            ->where('access_token', $token)
            ->firstOrFail();

        // Record access attempt
        $session->recordAccess('view', $request->ip());

        // Check token validity (includes expiry, one-time use, max attempts)
        if (!$session->canUseToken()) {
            // Determine specific error
            if ($session->isExpired()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SESSION_EXPIRED',
                        'message' => 'Degerlendirme suresi dolmus.',
                    ],
                ], 410);
            }

            if ($session->one_time_use && $session->used_at !== null) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'TOKEN_USED',
                        'message' => 'Bu link sadece bir kez kullanilabiLir ve daha once kullanilmis.',
                    ],
                ], 410);
            }

            if ($session->attempts_count >= $session->max_attempts) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'MAX_ATTEMPTS_REACHED',
                        'message' => 'Maksimum deneme sayisina ulasildi.',
                        'max_attempts' => $session->max_attempts,
                    ],
                ], 410);
            }
        }

        if ($session->isCompleted()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_COMPLETED',
                    'message' => 'Bu degerlendirme zaten tamamlanmis.',
                ],
            ], 410);
        }

        // Hide correct answers from questions
        $template = $session->template->toArray();
        $template['questions'] = collect($template['questions'])->map(function ($q) {
            unset($q['correct_answer']);
            $q['options'] = collect($q['options'])->map(function ($o) {
                unset($o['score']);
                return $o;
            })->toArray();
            return $q;
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'status' => $session->status,
                'employee_name' => $session->employee->full_name,
                'template' => $template,
                'time_limit_minutes' => $session->template->time_limit_minutes,
                'responses' => $session->responses ?? [],
                'started_at' => $session->started_at,
                'remaining_attempts' => $session->getRemainingAttempts(),
            ],
        ]);
    }

    public function publicStart(Request $request, string $token): JsonResponse
    {
        $session = AssessmentSession::where('access_token', $token)->firstOrFail();

        // Record access and increment attempts
        $session->recordAccess('start', $request->ip());
        $session->incrementAttempts();

        if (!$session->canStart() && !$session->canContinue()) {
            $errorCode = 'CANNOT_START';
            $errorMessage = 'Bu degerlendirme baslatilamaz.';

            if (!$session->canUseToken()) {
                if ($session->attempts_count >= $session->max_attempts) {
                    $errorCode = 'MAX_ATTEMPTS_REACHED';
                    $errorMessage = "Maksimum deneme sayisina ({$session->max_attempts}) ulasildi.";
                }
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $errorMessage,
                    'remaining_attempts' => $session->getRemainingAttempts(),
                ],
            ], 400);
        }

        // Check for IP change (potential security risk)
        if ($session->hasIpChanged($request->ip()) && $session->status === 'in_progress') {
            $session->recordAccess('ip_change_warning', $request->ip());
        }

        if ($session->status === 'pending') {
            $session->start();
            $session->update([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_info' => $request->get('device_info'),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'started_at' => $session->started_at,
                'status' => $session->status,
                'remaining_attempts' => $session->getRemainingAttempts(),
            ],
            'message' => 'Degerlendirme baslatildi.',
        ]);
    }

    public function publicSubmitResponse(Request $request, string $token): JsonResponse
    {
        $session = AssessmentSession::where('access_token', $token)->firstOrFail();

        if (!$session->canContinue()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_SUBMIT',
                    'message' => 'Bu degerlendirmeye yanit gonderilemez.',
                ],
            ], 400);
        }

        $validated = $request->validate([
            'question_order' => 'required|integer',
            'answer' => 'required',
            'time_spent' => 'required|integer|min:0',
        ]);

        $session->addResponse(
            $validated['question_order'],
            $validated['answer'],
            $validated['time_spent']
        );

        return response()->json([
            'success' => true,
            'message' => 'Yanit kaydedildi.',
        ]);
    }

    public function publicComplete(Request $request, string $token): JsonResponse
    {
        $session = AssessmentSession::where('access_token', $token)->firstOrFail();

        // Record completion attempt
        $session->recordAccess('complete', $request->ip());

        if ($session->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_COMPLETE',
                    'message' => 'Bu degerlendirme tamamlanamaz.',
                ],
            ], 400);
        }

        $session->complete();

        // Mark token as used if one-time use is enabled
        if ($session->one_time_use) {
            $session->markUsed();
        }

        // Dispatch analysis job
        AnalyzeAssessmentJob::dispatch($session);

        return response()->json([
            'success' => true,
            'message' => 'Degerlendirme tamamlandi. Sonuclar analiz ediliyor.',
        ]);
    }

    // Results & Reports
    public function results(Request $request): JsonResponse
    {
        $query = AssessmentResult::with([
            'session.employee',
            'session.template',
        ])
            ->whereHas('session.employee', fn($q) => $q->where('company_id', $request->user()->company_id));

        if ($request->has('risk_level')) {
            $query->where('risk_level', $request->risk_level);
        }

        if ($request->has('level_label')) {
            $query->where('level_label', $request->level_label);
        }

        if ($request->boolean('promotable_only')) {
            $query->where('promotion_suitable', true);
        }

        if ($request->has('min_score')) {
            $query->where('overall_score', '>=', $request->min_score);
        }

        if ($request->has('max_score')) {
            $query->where('overall_score', '<=', $request->max_score);
        }

        $results = $query->orderByDesc('analyzed_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    public function resultShow(Request $request, string $id): JsonResponse
    {
        $result = AssessmentResult::with([
            'session.employee',
            'session.template',
        ])
            ->whereHas('session.employee', fn($q) => $q->where('company_id', $request->user()->company_id))
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'result_ids' => 'required|array|min:2|max:10',
            'result_ids.*' => 'uuid|exists:assessment_results,id',
        ]);

        $results = AssessmentResult::with(['session.employee', 'session.template'])
            ->whereHas('session.employee', fn($q) => $q->where('company_id', $request->user()->company_id))
            ->whereIn('id', $validated['result_ids'])
            ->get();

        $comparison = $results->map(function ($result) {
            return [
                'result_id' => $result->id,
                'employee' => [
                    'id' => $result->session->employee->id,
                    'name' => $result->session->employee->full_name,
                    'role' => $result->session->employee->current_role,
                ],
                'overall_score' => $result->overall_score,
                'level_label' => $result->level_label,
                'risk_level' => $result->risk_level,
                'risk_flags_count' => $result->getRiskFlagCount(),
                'competency_scores' => $result->competency_scores,
                'promotion_suitable' => $result->promotion_suitable,
                'promotion_readiness' => $result->promotion_readiness,
            ];
        });

        $bestOverall = $comparison->sortByDesc('overall_score')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'employees' => $comparison->values(),
                'best_overall' => $bestOverall['employee']['id'] ?? null,
                'average_score' => round($comparison->avg('overall_score'), 1),
            ],
        ]);
    }

    public function roleStats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $stats = DB::table('assessment_results')
            ->join('assessment_sessions', 'assessment_results.session_id', '=', 'assessment_sessions.id')
            ->join('employees', 'assessment_sessions.employee_id', '=', 'employees.id')
            ->join('assessment_templates', 'assessment_sessions.template_id', '=', 'assessment_templates.id')
            ->where('employees.company_id', $companyId)
            ->select([
                'assessment_templates.role_category',
                DB::raw('count(*) as total_assessed'),
                DB::raw('avg(assessment_results.overall_score) as avg_score'),
                DB::raw('sum(case when assessment_results.risk_level in ("high", "critical") then 1 else 0 end) as high_risk_count'),
                DB::raw('sum(case when assessment_results.promotion_suitable = 1 then 1 else 0 end) as promotable_count'),
            ])
            ->groupBy('assessment_templates.role_category')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function dashboardStats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $totalSessions = AssessmentSession::whereHas('employee', fn($q) => $q->where('company_id', $companyId))->count();
        $completedSessions = AssessmentSession::whereHas('employee', fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'completed')->count();
        $pendingSessions = AssessmentSession::whereHas('employee', fn($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['pending', 'in_progress'])->count();

        $avgScore = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->avg('overall_score');

        $riskDistribution = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->selectRaw('risk_level, count(*) as count')
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level');

        $levelDistribution = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->selectRaw('level_label, count(*) as count')
            ->groupBy('level_label')
            ->pluck('count', 'level_label');

        // Cheating stats
        $cheatingDistribution = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->whereNotNull('cheating_level')
            ->selectRaw('cheating_level, count(*) as count')
            ->groupBy('cheating_level')
            ->pluck('count', 'cheating_level');

        $highCheatingRisk = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->where('cheating_level', 'high')
            ->count();

        // Analysis failures
        $analysisFailed = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'analysis_failed')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedSessions,
                'pending_sessions' => $pendingSessions,
                'completion_rate' => $totalSessions > 0 ? round($completedSessions / $totalSessions * 100, 1) : 0,
                'average_score' => round($avgScore ?? 0, 1),
                'risk_distribution' => $riskDistribution,
                'level_distribution' => $levelDistribution,
                'cheating_distribution' => $cheatingDistribution,
                'high_cheating_risk_count' => $highCheatingRisk,
                'analysis_failed_count' => $analysisFailed,
            ],
        ]);
    }

    /**
     * Get AI cost statistics
     */
    public function costStats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        // Total cost
        $totalCost = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->sum('cost_usd');

        // Cost by model
        $costByModel = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->selectRaw('ai_model, count(*) as count, sum(cost_usd) as total_cost, avg(cost_usd) as avg_cost')
            ->groupBy('ai_model')
            ->get();

        // Cost limited sessions
        $costLimitedCount = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->where('cost_limited', true)
            ->count();

        // Token usage
        $tokenUsage = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->selectRaw('sum(input_tokens) as total_input, sum(output_tokens) as total_output')
            ->first();

        // Monthly cost (current month)
        $monthlyCost = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->whereMonth('analyzed_at', now()->month)
            ->whereYear('analyzed_at', now()->year)
            ->sum('cost_usd');

        // Average cost per session
        $avgCostPerSession = AssessmentResult::whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->avg('cost_usd');

        return response()->json([
            'success' => true,
            'data' => [
                'total_cost_usd' => round($totalCost ?? 0, 4),
                'monthly_cost_usd' => round($monthlyCost ?? 0, 4),
                'average_cost_per_session' => round($avgCostPerSession ?? 0, 4),
                'cost_limited_sessions' => $costLimitedCount,
                'cost_by_model' => $costByModel,
                'token_usage' => [
                    'total_input_tokens' => $tokenUsage->total_input ?? 0,
                    'total_output_tokens' => $tokenUsage->total_output ?? 0,
                    'total_tokens' => ($tokenUsage->total_input ?? 0) + ($tokenUsage->total_output ?? 0),
                ],
            ],
        ]);
    }

    /**
     * Get results with high cheating risk
     */
    public function cheatingRiskResults(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $minScore = $request->get('min_score', 50);

        $results = AssessmentResult::with(['session.employee', 'session.template'])
            ->whereHas('session.employee', fn($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($minScore) {
                $q->where('cheating_risk_score', '>=', $minScore)
                    ->orWhere('cheating_level', 'high');
            })
            ->orderByDesc('cheating_risk_score')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    /**
     * Get similar responses (potential cheating)
     */
    public function similarResponses(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $minSimilarity = $request->get('min_similarity', 85);

        $similarities = \App\Models\AssessmentResponseSimilarity::with([
            'sessionA.employee',
            'sessionB.employee',
        ])
            ->whereHas('sessionA.employee', fn($q) => $q->where('company_id', $companyId))
            ->where('similarity_score', '>=', $minSimilarity)
            ->orderByDesc('similarity_score')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $similarities->items(),
            'meta' => [
                'current_page' => $similarities->currentPage(),
                'last_page' => $similarities->lastPage(),
                'per_page' => $similarities->perPage(),
                'total' => $similarities->total(),
            ],
        ]);
    }
}
