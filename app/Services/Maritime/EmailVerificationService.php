<?php

namespace App\Services\Maritime;

use App\Models\PoolCandidate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationService
{
    private const TOKEN_TTL_HOURS = 1;

    /**
     * Generate token, store hash, send verification email.
     */
    public function sendVerification(PoolCandidate $candidate): void
    {
        $plainToken = Str::random(64);
        $hash = hash('sha256', $plainToken);

        $candidate->updateQuietly([
            'email_verification_token_hash' => $hash,
            'email_verification_sent_at' => now(),
        ]);

        $verifyUrl = url("/api/v1/maritime/candidates/verify-email?token={$plainToken}&email=" . urlencode($candidate->email));

        try {
            Mail::raw(
                $this->buildEmailBody($candidate, $verifyUrl),
                function ($message) use ($candidate) {
                    $message->to($candidate->email)
                        ->subject('Verify your email — Octopus AI Maritime');
                }
            );
        } catch (\Throwable $e) {
            Log::channel('single')->warning('EmailVerification::send failed', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify token. Returns candidate on success, null on failure.
     */
    public function verify(string $email, string $token): ?PoolCandidate
    {
        $hash = hash('sha256', $token);

        $candidate = PoolCandidate::where('email', $email)
            ->where('email_verification_token_hash', $hash)
            ->whereNull('email_verified_at')
            ->first();

        if (!$candidate) {
            return null;
        }

        // Check TTL
        if ($candidate->email_verification_sent_at &&
            $candidate->email_verification_sent_at->diffInHours(now()) > self::TOKEN_TTL_HOURS) {
            return null;
        }

        $candidate->update([
            'email_verified_at' => now(),
            'email_verification_token_hash' => null,
        ]);

        return $candidate;
    }

    /**
     * Check if candidate can proceed without verification.
     * Rule: allow 1 interview attempt, then lock.
     */
    public function canProceedUnverified(PoolCandidate $candidate): bool
    {
        if ($candidate->email_verified_at) {
            return true;
        }

        // Allow if no completed interviews yet (1 attempt grace)
        $completedInterviews = $candidate->formInterviews()
            ->where('status', 'completed')
            ->count();

        return $completedInterviews < 1;
    }

    private function buildEmailBody(PoolCandidate $candidate, string $verifyUrl): string
    {
        $name = $candidate->first_name ?: 'Candidate';
        return <<<TEXT
Hello {$name},

Thank you for your application to Octopus AI Maritime.

Please verify your email address by clicking the link below:

{$verifyUrl}

This link is valid for 1 hour.

If you did not create an account, please ignore this email.

Best regards,
Octopus AI Maritime Team
TEXT;
    }
}
