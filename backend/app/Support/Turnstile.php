<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class Turnstile
{
    /**
     * Verify Turnstile token
     * Returns true if:
     * - TURNSTILE_ENABLED is false/not set (disabled mode)
     * - Token is valid when enabled
     */
    public static function verify(?string $token, ?string $ip = null): bool
    {
        // Check if Turnstile is enabled
        $enabled = env('TURNSTILE_ENABLED', false);
        if ($enabled === false || $enabled === 'false' || $enabled === '0') {
            return true; // disabled -> always pass
        }

        $secret = env('TURNSTILE_SECRET_KEY');
        if (!$secret || !$token) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) data_get($response->json(), 'success', false);
        } catch (\Exception $e) {
            // Log error but don't block user if Cloudflare is down
            \Log::warning('Turnstile verification failed: ' . $e->getMessage());
            return true; // Fail open for availability
        }
    }
}
