<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Turnstile;
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
        $key = 'login:' . sha1($email . '|' . $ip);

        // 5 deneme / 10 dk lockout
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => 'Hesap kilitlendi, lütfen biraz sonra tekrar deneyin.',
                'retry_after' => $seconds,
            ], 429);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'turnstile_token' => 'nullable|string',
        ]);

        // Turnstile doğrulama (TURNSTILE_ENABLED=true ise)
        if (!Turnstile::verify($request->input('turnstile_token'), $ip)) {
            return response()->json([
                'success' => false,
                'message' => 'Doğrulama başarısız. Lütfen tekrar deneyin.',
            ], 422);
        }

        $user = User::with(['company', 'role'])
            ->where('email', $request->email)
            ->first();

        // If not found in current brand DB, check the other DB (platform admins may be in default)
        if (!$user) {
            $altConnection = config('database.default') === 'mysql_talentqx' ? 'mysql' : 'mysql_talentqx';
            $user = User::on($altConnection)->with(['company', 'role'])->where('email', $request->email)->first();
        }


        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 600); // 10 dk pencere
            throw ValidationException::withMessages([
                'email' => ['Gecersiz kimlik bilgileri.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Hesabiniz aktif degil.'],
            ]);
        }

        // Başarılı giriş - rate limiter'ı temizle
        RateLimiter::clear($key);

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token', ['*'], now()->addDay());

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'role' => $user->role?->name,
                    'is_platform_admin' => $user->isPlatformAdmin(),
                    'is_octopus_admin' => $user->isOctopusAdmin(),
                    'must_change_password' => $user->mustChangePassword(),
                    'company' => $user->company ? [
                        'id' => $user->company->id,
                        'name' => $user->company->name,
                    ] : null,
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
            'message' => 'Basariyla cikis yapildi.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['company', 'role']);

        $company = $user->company;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'role' => $user->role?->name,
                'permissions' => $user->role?->permissions ?? [],
                'is_platform_admin' => $user->isPlatformAdmin(),
                'must_change_password' => $user->mustChangePassword(),
                'company' => $company ? [
                    'id' => $company->id,
                    'name' => $company->name,
                    'logo_url' => $company->logo_url,
                ] : null,
                'credits' => $company ? [
                    'remaining' => $company->getRemainingCredits(),
                    'total' => $company->getTotalCredits(),
                    'used' => (int) $company->credits_used,
                    'plan' => $company->subscription_plan,
                ] : null,
                'subscription' => $company ? [
                    'next_renewal_at' => $company->subscription_ends_at?->toIso8601String(),
                    'quota_resets_at' => $company->credits_period_start
                        ? $company->credits_period_start->copy()->addMonth()->toIso8601String()
                        : null,
                    'status' => $company->getSubscriptionStatus(),
                ] : null,
            ],
        ]);
    }
}
