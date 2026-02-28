<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\MessageOutbox;
use App\Models\User;
use App\Services\Outbox\OutboxService;
use App\Support\BrandConfig;
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
        $this->baseUrl = config('app.frontend_url', config('app.url', 'https://octopus-ai.net'));
    }

    /**
     * Create a password reset token and send email via Outbox.
     *
     * @param User $user
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return bool
     */
    public function sendResetLink(User $user, ?string $ipAddress = null, ?string $userAgent = null, ?string $platform = null): bool
    {
        try {
            // Use the user's DB connection for token storage
            $connection = $user->getConnectionName() ?: config('database.default');

            // Generate secure token
            $token = $this->createToken($user->email, $connection);

            // Build reset URL (use platform hint if user has no company)
            $resetUrl = $this->buildResetUrl($token, $user, $platform);

            // Queue email via Outbox (pass platform for brand name)
            $this->queueResetEmail($user, $resetUrl, $connection, $platform);

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
        // Try both DB connections for the token
        $record = null;
        $tokenConnection = null;

        foreach ([config('database.default'), $this->altConnection()] as $conn) {
            $record = DB::connection($conn)->table('password_reset_tokens')
                ->where('email', $email)
                ->first();
            if ($record) {
                $tokenConnection = $conn;
                break;
            }
        }

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
            $this->deleteToken($email, $tokenConnection);
            return null;
        }

        // Find user in either DB
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::on($this->altConnection())->where('email', $email)->first();
        }

        return $user;
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
            $connection = $user->getConnectionName() ?: config('database.default');
            DB::connection($connection)->beginTransaction();

            // Update password
            $user->update([
                'password' => $newPassword, // Will be hashed by cast
                'must_change_password' => false,
                'password_changed_at' => now(),
            ]);

            // Delete the used token (check both connections)
            $this->deleteToken($user->email, $connection);
            // Also clean from the other DB if it exists there
            $this->deleteToken($user->email, $this->altConnection($connection));

            // Invalidate all existing tokens for security
            $user->tokens()->delete();

            // Audit log
            $this->logAuditEvent(
                'password_reset_completed',
                $user,
                $ipAddress,
                $userAgent
            );

            DB::connection($connection)->commit();

            Log::info('Password reset completed', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::connection($connection)->rollBack();

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
    protected function createToken(string $email, ?string $connection = null): string
    {
        $conn = $connection ?: config('database.default');

        // Delete any existing tokens for this email (from both DBs)
        $this->deleteToken($email, $conn);
        $this->deleteToken($email, $this->altConnection($conn));

        // Generate secure random token
        $token = Str::random(64);

        // Store hashed token on the user's DB connection
        DB::connection($conn)->table('password_reset_tokens')->insert([
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
    protected function deleteToken(string $email, ?string $connection = null): void
    {
        $conn = $connection ?: config('database.default');
        try {
            DB::connection($conn)->table('password_reset_tokens')
                ->where('email', $email)
                ->delete();
        } catch (\Exception $e) {
            // Silently ignore if table doesn't exist on this connection
        }
    }

    /**
     * Build the reset URL.
     *
     * @param string $token
     * @param User|string $userOrEmail
     * @return string
     */
    protected function buildResetUrl(string $token, User|string $userOrEmail, ?string $platformHint = null): string
    {
        if ($userOrEmail instanceof User) {
            $user = $userOrEmail;
            $email = $user->email;
        } else {
            $email = $userOrEmail;
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::on($this->altConnection())->where('email', $email)->first();
            }
        }

        // Frontend hint takes priority (user's current domain), then company platform, then fallback
        $platform = $platformHint ?? $user?->company?->platform ?? 'talentqx';
        $brand = BrandConfig::for($platform);
        $domain = $brand['domain'] ?? 'talentqx.com';
        $frontendBase = $domain === 'talentqx.com'
            ? 'https://app.talentqx.com'
            : 'https://' . $domain;

        return $frontendBase . '/reset-password?' . http_build_query([
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
    protected function queueResetEmail(User $user, string $resetUrl, ?string $connection = null, ?string $platformHint = null): void
    {
        // Use the user's DB connection for outbox insertion
        $prevDefault = config('database.default');
        $conn = $connection ?: $user->getConnectionName() ?: $prevDefault;

        // Temporarily switch default connection so MessageOutbox model uses the right DB
        if ($conn !== $prevDefault) {
            config(['database.default' => $conn]);
        }

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
                    'priority' => 20,
                    'metadata' => [
                        'type' => 'password_reset',
                        'platform' => $platformHint ?? $user->company?->platform ?? 'talentqx',
                        'token_expiry_minutes' => self::TOKEN_EXPIRY_MINUTES,
                    ],
                ]
            );
        } catch (\Exception $e) {
            // If template doesn't exist, queue raw message
            Log::warning('Password reset template not found, using raw message', [
                'error' => $e->getMessage(),
            ]);

            $resolvedPlatform = $platformHint ?? $user->company?->platform ?? 'talentqx';

            $this->outboxService->queue([
                'company_id' => $user->company_id,
                'channel' => MessageOutbox::CHANNEL_EMAIL,
                'recipient' => $user->email,
                'recipient_name' => $user->full_name,
                'subject' => 'Şifre Sıfırlama Talebi - ' . $this->brandNameForUser($user, $platformHint),
                'body' => $this->buildDefaultResetEmailBody($user, $resetUrl, $platformHint),
                'related_type' => 'user',
                'related_id' => $user->id,
                'priority' => 20,
                'metadata' => [
                    'type' => 'password_reset',
                    'platform' => $resolvedPlatform,
                    'token_expiry_minutes' => self::TOKEN_EXPIRY_MINUTES,
                ],
            ]);
        } finally {
            // Restore original default connection
            if ($conn !== $prevDefault) {
                config(['database.default' => $prevDefault]);
            }
        }
    }

    /**
     * Build default reset email body (fallback if template missing).
     *
     * @param User $user
     * @param string $resetUrl
     * @return string
     */
    protected function buildDefaultResetEmailBody(User $user, string $resetUrl, ?string $platformHint = null): string
    {
        $brand = $this->brandNameForUser($user, $platformHint);
        return "Merhaba {$user->full_name},\n\n" .
            "{$brand} hesabınız için şifre sıfırlama talebi aldık.\n\n" .
            "Aşağıdaki bağlantı " . self::TOKEN_EXPIRY_MINUTES . " dakika boyunca geçerlidir:\n" .
            "{$resetUrl}\n\n" .
            "Eğer bu talebi siz yapmadıysanız bu e-postayı dikkate almayın.\n\n" .
            $brand;
    }

    /**
     * Get the alternate database connection name.
     */
    private function altConnection(?string $current = null): string
    {
        $conn = $current ?: config('database.default');
        return $conn === 'mysql_talentqx' ? 'mysql' : 'mysql_talentqx';
    }

    private function brandNameForUser(User $user, ?string $platformHint = null): string
    {
        // Frontend hint takes priority (user's current domain), then company, then header
        if ($platformHint) {
            return BrandConfig::brandName($platformHint);
        }
        $platform = $user->company?->platform;
        if (!$platform) {
            $brandKey = request()->header('X-Brand-Key');
            $platform = $brandKey === 'talentqx' ? 'talentqx' : 'octopus';
        }
        return BrandConfig::brandName($platform);
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
        try {
            // Use user's DB connection for audit log to avoid FK constraint issues
            $conn = $user->getConnectionName() ?: config('database.default');
            $audit = new AuditLog();
            $audit->setConnection($conn);
            $audit->fill([
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
            $audit->save();
        } catch (\Exception $e) {
            // Audit log failure should not prevent password operations
            Log::warning('Audit log failed for password operation', [
                'action' => $action,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
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
                    'company' => $user->company?->name ?? $this->brandNameForUser($user),
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
