<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Verify reCAPTCHA v3 token.
     *
     * @param string $token The reCAPTCHA token from frontend
     * @param string|null $expectedAction The expected action name
     * @return array{success: bool, score: float|null, error: string|null}
     */
    public function verify(string $token, ?string $expectedAction = null): array
    {
        // Check if reCAPTCHA is enabled
        if (!config('recaptcha.enabled', true)) {
            return [
                'success' => true,
                'score' => 1.0,
                'error' => null,
            ];
        }

        $secretKey = config('recaptcha.secret_key');
        if (empty($secretKey)) {
            Log::warning('reCAPTCHA secret key not configured');
            return [
                'success' => true, // Allow if not configured
                'score' => 1.0,
                'error' => null,
            ];
        }

        try {
            $response = Http::asForm()->post(self::VERIFY_URL, [
                'secret' => $secretKey,
                'response' => $token,
            ]);

            if (!$response->successful()) {
                Log::error('reCAPTCHA API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'score' => null,
                    'error' => 'reCAPTCHA verification failed',
                ];
            }

            $data = $response->json();

            // Check if verification was successful
            if (!($data['success'] ?? false)) {
                Log::warning('reCAPTCHA verification failed', [
                    'error_codes' => $data['error-codes'] ?? [],
                ]);
                return [
                    'success' => false,
                    'score' => null,
                    'error' => 'Invalid reCAPTCHA response',
                ];
            }

            $score = $data['score'] ?? 0.0;
            $action = $data['action'] ?? null;

            // Verify action matches (if expected)
            if ($expectedAction && $action !== $expectedAction) {
                Log::warning('reCAPTCHA action mismatch', [
                    'expected' => $expectedAction,
                    'actual' => $action,
                ]);
                return [
                    'success' => false,
                    'score' => $score,
                    'error' => 'Invalid reCAPTCHA action',
                ];
            }

            // Check score threshold
            $minScore = config('recaptcha.min_score', 0.3);
            if ($score < $minScore) {
                Log::warning('reCAPTCHA score too low', [
                    'score' => $score,
                    'min_score' => $minScore,
                ]);
                return [
                    'success' => false,
                    'score' => $score,
                    'error' => 'reCAPTCHA score too low (suspected bot)',
                ];
            }

            Log::info('reCAPTCHA verification passed', [
                'score' => $score,
                'action' => $action,
            ]);

            return [
                'success' => true,
                'score' => $score,
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::error('reCAPTCHA exception', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'score' => null,
                'error' => 'reCAPTCHA verification error',
            ];
        }
    }

    /**
     * Get the site key for frontend.
     */
    public function getSiteKey(): ?string
    {
        return config('recaptcha.site_key');
    }

    /**
     * Check if reCAPTCHA is enabled.
     */
    public function isEnabled(): bool
    {
        return config('recaptcha.enabled', true) && !empty(config('recaptcha.secret_key'));
    }
}
