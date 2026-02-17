<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\MessageOutbox;
use App\Models\User;
use App\Services\Outbox\OutboxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PasswordResetService
{
    // Token expiry in minutes
    protected const TOKEN_EXPIRY_MINUTES = 60;

    // Base URL for reset links
    protected string $baseUrl;

    public function __construct(
        protected OutboxService $outboxService
    ) {
        $this->baseUrl = config('app.frontend_url', config('app.url', 'https://talentqx.com'));
    }

    /**
     * Create a password reset token and send email via Outbox.
     *
     * @param User $user
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return bool
     */
    public function sendResetLink(User $user, ?string $ipAddress = null, ?string $userAgent = null): bool
    {
        try {
            // Generate secure token
            $token = $this->createToken($user->email);

            // Build reset URL
            $resetUrl = $this->buildResetUrl($token, $user->email);

            // Queue email via Outbox
            $this->queueResetEmail($user, $resetUrl);

            // Audit log
            $this->logAuditEvent(
                'password_reset_requested',
                $user,
                $ipAddress,
                $userAgent,
                ['email_queued' => true]
            );

            Log::info('Password reset requested', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Password reset request failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify reset token and return user if valid.
     *
     * @param string $email
     * @param string $token
     * @return User|null
     */
    public function verifyToken(string $email, string $token): ?User
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record) {
            return null;
        }

        // Check if token matches
        if (!Hash::check($token, $record->token)) {
            return null;
        }

        // Check if token is expired
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(self::TOKEN_EXPIRY_MINUTES)->isPast()) {
            // Clean up expired token
            $this->deleteToken($email);
            return null;
        }

        return User::where('email', $email)->first();
    }

    /**
     * Reset the password for a user.
     *
     * @param User $user
     * @param string $newPassword
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return bool
     */
    public function resetPassword(
        User $user,
        string $newPassword,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): bool {
        try {
            DB::beginTransaction();

            // Update password
            $user->update([
                'password' => $newPassword, // Will be hashed by cast
                'must_change_password' => false,
                'password_changed_at' => now(),
            ]);

            // Delete the used token
            $this->deleteToken($user->email);

            // Invalidate all existing tokens for security
            $user->tokens()->delete();

            // Audit log
            $this->logAuditEvent(
                'password_reset_completed',
                $user,
                $ipAddress,
                $userAgent
            );

            DB::commit();

            Log::info('Password reset completed', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Password reset failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Change password for authenticated user (including first login).
     *
     * @param User $user
     * @param string $currentPassword
     * @param string $newPassword
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param bool $isFirstLogin
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function changePassword(
        User $user,
        string $currentPassword,
        string $newPassword,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        bool $isFirstLogin = false
    ): array {
        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            return [
                'success' => false,
                'error' => 'current_password_invalid',
            ];
        }

        // Ensure new password is different
        if (Hash::check($newPassword, $user->password)) {
            return [
                'success' => false,
                'error' => 'password_same_as_current',
            ];
        }

        try {
            DB::beginTransaction();

            // Update password
            $user->update([
                'password' => $newPassword,
                'must_change_password' => false,
                'password_changed_at' => now(),
            ]);

            // Audit log
            $action = $isFirstLogin ? 'first_login_password_changed' : 'password_changed';
            $this->logAuditEvent($action, $user, $ipAddress, $userAgent);

            DB::commit();

            Log::info('Password changed', [
                'user_id' => $user->id,
                'is_first_login' => $isFirstLogin,
            ]);

            return ['success' => true, 'error' => null];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Password change failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'server_error',
            ];
        }
    }

    /**
     * Create a password reset token.
     *
     * @param string $email
     * @return string Plain token (to be sent to user)
     */
    protected function createToken(string $email): string
    {
        // Delete any existing tokens for this email
        $this->deleteToken($email);

        // Generate secure random token
        $token = Str::random(64);

        // Store hashed token
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        return $token;
    }

    /**
     * Delete password reset token.
     *
     * @param string $email
     * @return void
     */
    protected function deleteToken(string $email): void
    {
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();
    }

    /**
     * Build the reset URL.
     *
     * @param string $token
     * @param string $email
     * @return string
     */
    protected function buildResetUrl(string $token, string $email): string
    {
        return $this->baseUrl . '/reset-password?' . http_build_query([
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Queue password reset email via Outbox.
     *
     * @param User $user
     * @param string $resetUrl
     * @return void
     */
    protected function queueResetEmail(User $user, string $resetUrl): void
    {
        try {
            $this->outboxService->queueFromTemplate(
                'password_reset',
                MessageOutbox::CHANNEL_EMAIL,
                $user->email,
                [
                    'name' => $user->full_name,
                    'reset_link' => $resetUrl,
                    'minutes' => self::TOKEN_EXPIRY_MINUTES,
                ],
                [
                    'company_id' => $user->company_id,
                    'recipient_name' => $user->full_name,
                    'related_type' => 'user',
                    'related_id' => $user->id,
                    'priority' => 20, // High priority for security emails
                    'metadata' => [
                        'type' => 'password_reset',
                        'token_expiry_minutes' => self::TOKEN_EXPIRY_MINUTES,
                    ],
                ]
            );
        } catch (\Exception $e) {
            // If template doesn't exist, queue raw message
            Log::warning('Password reset template not found, using raw message', [
                'error' => $e->getMessage(),
            ]);

            $this->outboxService->queue([
                'company_id' => $user->company_id,
                'channel' => MessageOutbox::CHANNEL_EMAIL,
                'recipient' => $user->email,
                'recipient_name' => $user->full_name,
                'subject' => 'Şifre Sıfırlama Talebi - TalentQX',
                'body' => $this->buildDefaultResetEmailBody($user, $resetUrl),
                'related_type' => 'user',
                'related_id' => $user->id,
                'priority' => 20,
                'metadata' => [
                    'type' => 'password_reset',
                    'token_expiry_minutes' => self::TOKEN_EXPIRY_MINUTES,
                ],
            ]);
        }
    }

    /**
     * Build default reset email body (fallback if template missing).
     *
     * @param User $user
     * @param string $resetUrl
     * @return string
     */
    protected function buildDefaultResetEmailBody(User $user, string $resetUrl): string
    {
        return "Merhaba {$user->full_name},\n\n" .
            "TalentQX hesabınız için şifre sıfırlama talebi aldık.\n\n" .
            "Aşağıdaki bağlantı " . self::TOKEN_EXPIRY_MINUTES . " dakika boyunca geçerlidir:\n" .
            "{$resetUrl}\n\n" .
            "Eğer bu talebi siz yapmadıysanız bu e-postayı dikkate almayın.\n\n" .
            "TalentQX";
    }

    /**
     * Log an audit event.
     *
     * @param string $action
     * @param User $user
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param array $extraData
     * @return void
     */
    protected function logAuditEvent(
        string $action,
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $extraData = []
    ): void {
        AuditLog::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => $action,
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'new_values' => array_merge([
                'email' => $user->email,
            ], $extraData),
        ]);
    }

    /**
     * Send welcome email for newly created user with must_change_password flag.
     *
     * @param User $user
     * @return void
     */
    public function sendWelcomeEmail(User $user): void
    {
        try {
            $loginUrl = $this->baseUrl . '/login';

            $this->outboxService->queueFromTemplate(
                'welcome_first_login',
                MessageOutbox::CHANNEL_EMAIL,
                $user->email,
                [
                    'name' => $user->full_name,
                    'company' => $user->company?->name ?? 'TalentQX',
                    'login_link' => $loginUrl,
                ],
                [
                    'company_id' => $user->company_id,
                    'recipient_name' => $user->full_name,
                    'related_type' => 'user',
                    'related_id' => $user->id,
                    'priority' => 15,
                    'metadata' => [
                        'type' => 'welcome_first_login',
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Welcome email template not found', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get token expiry in minutes (for API responses).
     *
     * @return int
     */
    public static function getTokenExpiryMinutes(): int
    {
        return self::TOKEN_EXPIRY_MINUTES;
    }
}
