<?php

namespace App\Http\Controllers\Api\CompanyPanel;

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
        $key = 'company-panel-login:' . sha1($email . '|' . $ip);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => 'Çok fazla deneme. Lütfen daha sonra tekrar deneyin.',
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
                'email' => ['Geçersiz kimlik bilgileri.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Hesabınız aktif değil.'],
            ]);
        }

        if (!$user->hasCompanyPanelAccess()) {
            RateLimiter::hit($key, 600);
            throw ValidationException::withMessages([
                'email' => ['Şirket paneline erişim yetkiniz yok.'],
            ]);
        }

        RateLimiter::clear($key);

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('company-panel', ['company_panel.*'], now()->addDay());

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'avatar_url' => $user->avatar_url,
                    'company_panel_role' => $user->company_panel_role,
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
            'message' => 'Başarıyla çıkış yapıldı.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'avatar_url' => $user->avatar_url,
                'company_panel_role' => $user->company_panel_role,
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ], [
            'password.regex' => 'Şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mevcut şifre yanlış.',
            ], 400);
        }

        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Yeni şifre mevcut şifreden farklı olmalıdır.',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Şifreniz başarıyla değiştirildi.',
        ]);
    }
}
