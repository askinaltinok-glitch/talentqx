<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PasswordController extends Controller
{
    public function __construct(
        protected PasswordResetService $passwordResetService
    ) {}

    /**
     * Request a password reset link.
     *
     * POST /api/v1/forgot-password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();

        // If not found in current brand DB, check the other DB (platform admins may be in default)
        if (!$user) {
            $altConnection = config('database.default') === 'mysql_talentqx' ? 'mysql' : 'mysql_talentqx';
            $user = User::on($altConnection)->where('email', $email)->first();
        }

        if (!$user) {
            Log::info('Password reset requested for non-existent email', [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Bu e-posta adresi sistemde kayıtlı değildir.',
                'user_found' => false,
            ], 404);
        }

        // Check if user is active
        if (!$user->is_active) {
            Log::warning('Password reset requested for inactive user', [
                'user_id' => $user->id,
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Bu hesap devre dışı bırakılmıştır. Lütfen yöneticinize başvurun.',
                'user_found' => false,
            ], 403);
        }

        // Send reset link
        $success = $this->passwordResetService->sendResetLink(
            $user,
            $request->ip(),
            $request->userAgent()
        );

        if (!$success) {
            Log::error('Failed to send password reset link', [
                'user_id' => $user->id,
                'email' => $email,
            ]);
        }

        // Always return success message
        return response()->json([
            'message' => 'Eğer bu e-posta sistemde kayıtlıysa, şifre sıfırlama bağlantısı gönderilecektir.',
            'expires_in_minutes' => PasswordResetService::getTokenExpiryMinutes(),
        ]);
    }

    /**
     * Verify reset token (for frontend validation).
     *
     * POST /api/v1/verify-reset-token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyResetToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'token' => 'required|string|min:64|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));
        $token = $request->input('token');

        $user = $this->passwordResetService->verifyToken($email, $token);

        if (!$user) {
            return response()->json([
                'message' => 'Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı.',
                'valid' => false,
            ], 400);
        }

        return response()->json([
            'message' => 'Token geçerli.',
            'valid' => true,
            'user' => [
                'email' => $user->email,
                'first_name' => $user->first_name,
            ],
        ]);
    }

    /**
     * Reset password using token.
     *
     * POST /api/v1/reset-password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'token' => 'required|string|min:64|max:64',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'confirmed',
                // At least one uppercase, one lowercase, one number
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ], [
            'password.regex' => 'Şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));
        $token = $request->input('token');
        $password = $request->input('password');

        // Verify token
        $user = $this->passwordResetService->verifyToken($email, $token);

        if (!$user) {
            return response()->json([
                'message' => 'Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı.',
            ], 400);
        }

        // Reset password
        $success = $this->passwordResetService->resetPassword(
            $user,
            $password,
            $request->ip(),
            $request->userAgent()
        );

        if (!$success) {
            return response()->json([
                'message' => 'Şifre sıfırlama başarısız. Lütfen tekrar deneyin.',
            ], 500);
        }

        return response()->json([
            'message' => 'Şifreniz başarıyla sıfırlandı. Giriş yapabilirsiniz.',
        ]);
    }

    /**
     * Change password for authenticated user.
     *
     * POST /api/v1/change-password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->passwordResetService->changePassword(
            $user,
            $request->input('current_password'),
            $request->input('password'),
            $request->ip(),
            $request->userAgent(),
            $user->mustChangePassword() // isFirstLogin
        );

        if (!$result['success']) {
            $errorMessages = [
                'current_password_invalid' => 'Mevcut şifre yanlış.',
                'password_same_as_current' => 'Yeni şifre mevcut şifreden farklı olmalıdır.',
                'server_error' => 'Şifre değiştirilirken bir hata oluştu.',
            ];

            return response()->json([
                'message' => $errorMessages[$result['error']] ?? 'Şifre değiştirme başarısız.',
                'error' => $result['error'],
            ], $result['error'] === 'server_error' ? 500 : 400);
        }

        return response()->json([
            'message' => 'Şifreniz başarıyla değiştirildi.',
        ]);
    }
}
