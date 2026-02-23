<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $email = Str::lower((string) $request->input('email', ''));
        $ip = (string) $request->ip();
        $key = 'octopus-login:' . sha1($email . '|' . $ip);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please try again later.',
                'retry_after' => $seconds,
            ], 429);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 600);
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Account is not active.'],
            ]);
        }

        if (!$user->is_octopus_admin) {
            RateLimiter::hit($key, 600);
            throw ValidationException::withMessages([
                'email' => ['You do not have Octopus admin access.'],
            ]);
        }

        RateLimiter::clear($key);

        $user->update(['last_login_at' => now()]);

        // Create token with scoped ability — NOT wildcard
        $token = $user->createToken('octopus-admin', ['octopus.admin'], now()->addDay());

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'is_octopus_admin' => $user->isOctopusAdmin(),
                ],
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('company');

        $company = $user->company;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar_url' => $user->avatar_url,
                'is_octopus_admin' => $user->isOctopusAdmin(),
                'company' => $company ? [
                    'name' => $company->name,
                    'plan' => $company->subscription_plan,
                    'plan_code' => $company->subscription_plan,
                    'plan_name' => ucfirst($company->subscription_plan),
                ] : null,
                'credits' => $company ? [
                    'remaining' => $company->getRemainingCredits(),
                    'total' => $company->getTotalCredits(),
                    'used' => (int) $company->credits_used,
                ] : null,
            ],
        ]);
    }
}
