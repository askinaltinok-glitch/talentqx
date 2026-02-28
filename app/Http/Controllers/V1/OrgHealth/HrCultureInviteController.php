<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Http\Controllers\Controller;
use App\Mail\CultureInviteMail;
use App\Models\OrgCultureInvite;
use App\Models\OrgEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HrCultureInviteController extends Controller
{
    public function send(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin() && !$user->isPlatformAdmin() && $user->role?->name !== 'hr_manager') {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'HR manager access required.',
            ], 403);
        }

        $tenantId = $user->company_id;

        $employees = OrgEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get(['id', 'full_name', 'email']);

        $totalActive = $employees->count();
        $invitedSent = 0;
        $skippedNoEmail = 0;

        foreach ($employees as $emp) {
            if (!$emp->email) {
                $skippedNoEmail++;
                continue;
            }

            $invite = OrgCultureInvite::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $emp->id)
                ->first();

            if ($invite) {
                $invite->update([
                    'email' => $emp->email,
                    'sent_at' => now(),
                ]);
            } else {
                $invite = OrgCultureInvite::create([
                    'tenant_id' => $tenantId,
                    'employee_id' => $emp->id,
                    'email' => $emp->email,
                    'token' => Str::random(64),
                    'sent_at' => now(),
                    'created_at' => now(),
                ]);
            }

            $locale = 'tr'; // default tenant locale
            Mail::to($emp->email)->send(new CultureInviteMail($invite, $emp->full_name, $locale));

            $invitedSent++;
        }

        return response()->json([
            'total_active' => $totalActive,
            'invited_sent' => $invitedSent,
            'skipped_no_email' => $skippedNoEmail,
        ]);
    }

    public function status(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin() && !$user->isPlatformAdmin() && $user->role?->name !== 'hr_manager') {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'HR manager access required.',
            ], 403);
        }

        $tenantId = $user->company_id;

        $invitedSent = OrgCultureInvite::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('sent_at')
            ->count();

        $opened = OrgCultureInvite::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('opened_at')
            ->count();

        $completed = (int) DB::table('org_assessments as a')
            ->join('org_questionnaires as q', 'a.questionnaire_id', '=', 'q.id')
            ->where('a.tenant_id', $tenantId)
            ->where('q.code', 'culture')
            ->where('a.status', 'completed')
            ->distinct('a.employee_id')
            ->count('a.employee_id');

        $completionRate = $invitedSent > 0
            ? round(($completed / $invitedSent) * 100, 1)
            : 0;

        return response()->json([
            'invited_sent' => $invitedSent,
            'opened' => $opened,
            'completed' => $completed,
            'completion_rate' => $completionRate,
        ]);
    }
}
