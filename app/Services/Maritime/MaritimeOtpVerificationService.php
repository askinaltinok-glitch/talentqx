<?php

namespace App\Services\Maritime;

use App\Mail\MaritimeVerificationCodeMail;
use App\Models\PoolCandidate;
use Illuminate\Support\Facades\Mail;

class MaritimeOtpVerificationService
{
    /**
     * Generate and send a 6-digit OTP code to the candidate.
     */
    public function sendCode(PoolCandidate $candidate): void
    {
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $hash = hash('sha256', $code . '|' . $candidate->id . '|' . $candidate->email);

        $candidate->update([
            'email_verification_otp_hash' => $hash,
            'email_verification_otp_expires_at' => now()->addMinutes(10),
            'email_verification_otp_attempts' => 0,
            'email_verification_sent_at' => now(),
        ]);

        Mail::to($candidate->email)
            ->queue(new MaritimeVerificationCodeMail($candidate, $code));
    }

    /**
     * Verify the submitted OTP code.
     *
     * @return true|string  true on success, error key on failure
     */
    public function verifyCode(PoolCandidate $candidate, string $code): true|string
    {
        // Already verified
        if ($candidate->email_verified_at) {
            return true;
        }

        // Max attempts exceeded
        if ($candidate->email_verification_otp_attempts >= 5) {
            return 'max_attempts_exceeded';
        }

        // Code expired
        if (!$candidate->email_verification_otp_expires_at || $candidate->email_verification_otp_expires_at->isPast()) {
            return 'code_expired';
        }

        // Increment attempts FIRST (timing attack protection)
        $candidate->increment('email_verification_otp_attempts');

        $expectedHash = hash('sha256', $code . '|' . $candidate->id . '|' . $candidate->email);

        if (!hash_equals($candidate->email_verification_otp_hash ?? '', $expectedHash)) {
            return 'invalid_code';
        }

        // Success — mark verified and clear hash
        $candidate->update([
            'email_verified_at' => now(),
            'email_verification_otp_hash' => null,
        ]);

        return true;
    }
}
