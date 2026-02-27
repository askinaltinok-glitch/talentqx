<?php

namespace App\Services\QrApply;

use App\Mail\QrApplyVerificationCodeMail;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Job;
use Illuminate\Support\Facades\Mail;

class QrApplyEmailVerificationService
{
    /**
     * Generate and send a 6-digit verification code.
     */
    public function sendCode(Interview $interview, Candidate $candidate, Job $job): void
    {
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $hash = hash('sha256', $code . '|' . $interview->id . '|' . $candidate->email);

        $interview->update([
            'email_verification_code_hash' => $hash,
            'email_verification_expires_at' => now()->addMinutes(10),
            'email_verification_attempts' => 0,
        ]);

        Mail::to($candidate->email)
            ->queue(new QrApplyVerificationCodeMail($candidate, $job, $code));
    }

    /**
     * Verify the submitted code.
     *
     * @return true|string  true on success, error key on failure
     */
    public function verifyCode(Interview $interview, Candidate $candidate, string $code): true|string
    {
        // Already verified
        if ($interview->isEmailVerified()) {
            return true;
        }

        // Max attempts exceeded
        if ($interview->email_verification_attempts >= 5) {
            return 'max_attempts_exceeded';
        }

        // Code expired
        if (!$interview->email_verification_expires_at || $interview->email_verification_expires_at->isPast()) {
            return 'code_expired';
        }

        // Increment attempts FIRST (timing attack protection)
        $interview->increment('email_verification_attempts');

        $expectedHash = hash('sha256', $code . '|' . $interview->id . '|' . $candidate->email);

        if (!hash_equals($interview->email_verification_code_hash ?? '', $expectedHash)) {
            return 'invalid_code';
        }

        // Success — mark verified and clear hash
        $interview->update([
            'email_verified_at' => now(),
            'email_verification_code_hash' => null,
        ]);

        return true;
    }
}
