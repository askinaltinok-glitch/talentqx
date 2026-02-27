<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Domains\OrgHealth\WorkStyle\WorkstyleScoringService;
use App\Http\Controllers\Controller;
use App\Models\OrgAssessment;
use App\Models\OrgEmployee;
use App\Models\OrgEmployeeConsent;
use App\Models\OrgQuestionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkstyleAssessmentController extends Controller
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

        $consent = OrgEmployeeConsent::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employee->id)
            ->where('consent_version', 'orghealth_v1')
            ->first();

        if (!$consent || !$consent->consented_at || $consent->withdrawn_at) {
            throw ValidationException::withMessages(['consent' => 'Consent is required.']);
        }

        // 30-day cooldown check
        $lastCompleted = OrgAssessment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employee->id)
            ->where('status', 'completed')
            ->whereNotNull('next_due_at')
            ->orderByDesc('completed_at')
            ->first();

        if ($lastCompleted && $lastCompleted->next_due_at && now()->lessThan($lastCompleted->next_due_at)) {
            return response()->json([
                'error' => 'cooldown',
                'message' => 'Assessment cooldown period has not ended yet.',
                'next_due_at' => $lastCompleted->next_due_at->toIso8601String(),
            ], 422);
        }

        $questionnaire = OrgQuestionnaire::query()
            ->where('code', 'workstyle')
            ->where('status', 'active')
            ->where(function ($qq) use ($tenantId) {
                $qq->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->orderByRaw('tenant_id is null')
            ->firstOrFail();

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
            'answers' => ['required','array','min:1'],
            'answers.*.question_id' => ['required','uuid'],
            'answers.*.value' => ['required','integer','min:1','max:5'],
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
                        ->update(['value' => (int)$a['value']]);
                } else {
                    DB::table('org_assessment_answers')->insert([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'assessment_id' => $assessment->id,
                        'question_id' => $a['question_id'],
                        'value' => (int)$a['value'],
                        'created_at' => now(),
                    ]);
                }
            }
        });

        return response()->json(['ok' => true]);
    }

    public function complete(Request $request, string $assessmentId, WorkstyleScoringService $scoring)
    {
        $tenantId = $request->user()->company_id;

        $assessment = OrgAssessment::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $assessmentId)
            ->with(['questionnaire','answers'])
            ->firstOrFail();

        $profile = $scoring->completeAndScore($assessment);

        return response()->json([
            'assessment_id' => $assessment->id,
            'profile' => [
                'planning_score' => $profile->planning_score,
                'social_score' => $profile->social_score,
                'cooperation_score' => $profile->cooperation_score,
                'stability_score' => $profile->stability_score,
                'adaptability_score' => $profile->adaptability_score,
                'computed_at' => $profile->computed_at,
            ],
            'next_due_at' => $assessment->next_due_at,
        ]);
    }
}
