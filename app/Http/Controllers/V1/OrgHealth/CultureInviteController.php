<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Http\Controllers\Controller;
use App\Models\OrgCultureInvite;
use App\Models\OrgEmployeeConsent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CultureInviteController extends Controller
{
    public function validate(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string', 'size:64'],
        ]);

        $invite = OrgCultureInvite::query()
            ->where('token', $request->input('token'))
            ->first();

        if (!$invite) {
            return response()->json([
                'error' => 'invalid_token',
                'message' => 'Invite not found or expired.',
            ], 404);
        }

        $employee = $invite->employee;

        if (!$employee || $employee->status !== 'active') {
            return response()->json([
                'error' => 'employee_inactive',
                'message' => 'Employee is not active.',
            ], 422);
        }

        if ($invite->tenant_id !== $employee->tenant_id) {
            return response()->json([
                'error' => 'tenant_mismatch',
                'message' => 'Tenant mismatch.',
            ], 422);
        }

        // Check consent
        $consent = OrgEmployeeConsent::query()
            ->where('tenant_id', $invite->tenant_id)
            ->where('employee_id', $employee->id)
            ->where('consent_version', 'orghealth_v1')
            ->first();

        if (!$consent || !$consent->consented_at || $consent->withdrawn_at) {
            return response()->json([
                'error' => 'consent_required',
                'message' => 'OrgHealth consent must be accepted before proceeding.',
                'redirect' => '/workstyle/consent',
                'employee_id' => $employee->id,
                'tenant_id' => $invite->tenant_id,
            ], 422);
        }

        // Mark opened
        if (!$invite->opened_at) {
            $invite->update(['opened_at' => now()]);
        }

        // Find or create a User so we can issue a Sanctum token
        // Email is globally unique in users table, so search by email first
        $user = User::where('email', $employee->email)->first();

        if (!$user) {
            $user = User::create([
                'company_id' => $invite->tenant_id,
                'email' => $employee->email,
                'first_name' => $employee->full_name,
                'last_name' => '',
                'password' => Hash::make(Str::random(32)),
                'is_active' => true,
            ]);
        } elseif ($user->company_id !== $invite->tenant_id) {
            // Ensure user's company matches the invite tenant for downstream controllers
            $user->company_id = $invite->tenant_id;
            $user->save();
        }

        // Short-lived token (24 hours)
        $token = $user->createToken('culture-invite', ['*'], now()->addDay());

        return response()->json([
            'ok' => true,
            'tenant_id' => $invite->tenant_id,
            'employee_id' => $employee->id,
            'token' => $token->plainTextToken,
            'redirect' => '/culture/start',
        ]);
    }
}
