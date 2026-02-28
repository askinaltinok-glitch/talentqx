<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Domains\OrgHealth\Culture\CultureScoringService;
use App\Http\Controllers\Controller;
use App\Models\OrgAssessment;
use App\Models\OrgEmployee;
use App\Models\OrgEmployeeConsent;
use App\Models\OrgQuestionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CultureAssessmentController extends Controller
{
    public function start(Request $request, string $employeeId)
    {
        $tenantId = $request->user()->company_id;

        $employee = OrgEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $employeeId)
            ->firstOrFail();

        if ($employee->status !== 'active') {
            throw ValidationException::withMessages(['employee' => 'Employee is not active.']);
        }

        // Check consent
        $consent = OrgEmployeeConsent::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employee->id)
            ->where('consent_version', 'orghealth_v1')
            ->first();

        if (!$consent || !$consent->consented_at || $consent->withdrawn_at) {
            throw ValidationException::withMessages(['consent' => 'Consent is required.']);
        }

        // Yearly cooldown check
        $lastCompleted = OrgAssessment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employee->id)
            ->where('status', 'completed')
            ->whereNotNull('next_due_at')
            ->whereHas('questionnaire', fn($q) => $q->where('code', 'culture'))
            ->orderByDesc('completed_at')
            ->first();

        if ($lastCompleted && $lastCompleted->next_due_at && now()->lessThan($lastCompleted->next_due_at)) {
            return response()->json([
                'error' => 'cooldown',
                'message' => 'Culture assessment cooldown period has not ended yet.',
                'next_due_at' => $lastCompleted->next_due_at->toIso8601String(),
            ], 422);
        }

        $questionnaire = OrgQuestionnaire::query()
            ->where('code', 'culture')
            ->where('status', 'active')
            ->where(function ($qq) use ($tenantId) {
                $qq->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->orderByRaw('tenant_id is null')
            ->firstOrFail();

        // Auto-abandon stale assessments (7 days)
        OrgAssessment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employee->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', 'started')
            ->where('started_at', '<', now()->subDays(7))
            ->update(['status' => 'abandoned']);

        // Reuse existing started assessment
        $existing = OrgAssessment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employee->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', 'started')
            ->first();

        if ($existing) {
            return response()->json(['assessment_id' => $existing->id, 'status' => $existing->status]);
        }

        $assessment = OrgAssessment::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => 'started',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        return response()->json(['assessment_id' => $assessment->id, 'status' => $assessment->status]);
    }

    public function saveAnswers(Request $request, string $assessmentId)
    {
        $tenantId = $request->user()->company_id;

        $payload = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'uuid'],
            'answers.*.current_value' => ['required', 'integer', 'min:1', 'max:5'],
            'answers.*.preferred_value' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $assessment = OrgAssessment::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $assessmentId)
            ->firstOrFail();

        if ($assessment->status !== 'started') {
            throw ValidationException::withMessages(['assessment' => 'Assessment is not in started state.']);
        }

        DB::transaction(function () use ($assessment, $payload) {
            foreach ($payload['answers'] as $a) {
                $exists = DB::table('org_assessment_answers')
                    ->where('assessment_id', $assessment->id)
                    ->where('question_id', $a['question_id'])
                    ->exists();

                if ($exists) {
                    DB::table('org_assessment_answers')
                        ->where('assessment_id', $assessment->id)
                        ->where('question_id', $a['question_id'])
                        ->update([
                            'value' => (int) $a['current_value'],
                            'preferred_value' => (int) $a['preferred_value'],
                        ]);
                } else {
                    DB::table('org_assessment_answers')->insert([
                        'id' => (string) Str::uuid(),
                        'assessment_id' => $assessment->id,
                        'question_id' => $a['question_id'],
                        'value' => (int) $a['current_value'],
                        'preferred_value' => (int) $a['preferred_value'],
                        'created_at' => now(),
                    ]);
                }
            }
        });

        return response()->json(['ok' => true]);
    }

    public function complete(Request $request, string $assessmentId, CultureScoringService $scoring)
    {
        $tenantId = $request->user()->company_id;

        $assessment = OrgAssessment::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $assessmentId)
            ->with(['questionnaire', 'answers'])
            ->firstOrFail();

        $profile = $scoring->completeAndScore($assessment);

        return response()->json([
            'assessment_id' => $assessment->id,
            'status' => 'completed',
            'next_due_at' => $assessment->next_due_at,
        ]);
    }
}
