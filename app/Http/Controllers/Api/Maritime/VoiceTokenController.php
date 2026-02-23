<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InterviewInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VoiceTokenController extends Controller
{
    /**
     * Issue a short-lived HMAC voice token for the Deepgram streaming proxy.
     *
     * POST /api/v1/maritime/voice/token
     * Body: { "invitation_token": "..." }
     */
    public function issue(Request $request): JsonResponse
    {
        $request->validate([
            'invitation_token' => 'required|string|size:64',
        ]);

        $invitationToken = $request->input('invitation_token');
        $hash = hash('sha256', $invitationToken);

        $invitation = InterviewInvitation::where('invitation_token_hash', $hash)->first();

        if (! $invitation || (! $invitation->isAccessible() && ! $invitation->canResume())) {
            return response()->json([
                'message' => 'Invalid or expired invitation.',
            ], 403);
        }

        // Rate limit: max 60 voice tokens per interview session (multiple per question allowed)
        $cacheKey = "voice_token_count:{$invitation->id}";
        $count = (int) Cache::get($cacheKey, 0);

        if ($count >= 60) {
            return response()->json([
                'message' => 'Voice token limit reached for this session.',
            ], 429);
        }

        Cache::put($cacheKey, $count + 1, now()->addHours(48));

        $secret = config('maritime.voice_gateway_secret');
        if (empty($secret)) {
            return response()->json([
                'message' => 'Voice service not configured.',
            ], 503);
        }

        $ttl = 300; // 5 minutes
        $payload = json_encode([
            'iid' => $invitation->id,
            'exp' => time() + $ttl,
        ]);

        $payloadB64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $payload, $secret, true);
        $sigB64 = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return response()->json([
            'voice_token' => "{$payloadB64}.{$sigB64}",
            'expires_in'  => $ttl,
        ]);
    }

    /**
     * Log a completed voice dictation session.
     *
     * POST /api/v1/maritime/voice/log
     */
    public function log(Request $request): JsonResponse
    {
        $request->validate([
            'invitation_token' => 'required|string|size:64',
            'interview_id'     => 'required|string',
            'field_slot'       => 'required|integer|min:1|max:12',
            'lang'             => 'required|string|max:5',
            'duration_ms'      => 'required|integer|min:0|max:120000',
            'reason'           => 'required|string|in:client_stop,idle_timeout,max_duration,speech_final_silence,silence,time_limit,error',
            'had_transcript'   => 'required|boolean',
        ]);

        $hash = hash('sha256', $request->input('invitation_token'));
        $invitation = InterviewInvitation::where('invitation_token_hash', $hash)->first();

        if (! $invitation) {
            return response()->json(['message' => 'Invalid token.'], 403);
        }

        AuditLog::create([
            'action'      => 'voice_dictation_session',
            'entity_type' => 'form_interview',
            'entity_id'   => $request->input('interview_id'),
            'metadata'    => [
                'invitation_id' => $invitation->id,
                'field_slot'    => $request->input('field_slot'),
                'lang'          => $request->input('lang'),
                'duration_ms'   => $request->input('duration_ms'),
                'reason'        => $request->input('reason'),
                'had_transcript' => $request->boolean('had_transcript'),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true]);
    }
}
