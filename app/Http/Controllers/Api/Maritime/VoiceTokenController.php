<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InterviewInvitation;
use App\Models\VoiceBehavioralSignal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
            'field_slot'       => 'required|integer|min:1|max:25',
            'lang'             => 'required|string|max:5',
            'duration_ms'      => 'required|integer|min:0|max:120000',
            'reason'           => 'required|string|in:client_stop,idle_timeout,max_duration,speech_final_silence,silence,time_limit,error',
            'had_transcript'   => 'required|boolean',
            'question_slot'    => 'nullable|integer|min:1|max:25',
            'voice_signals'    => 'nullable|array',
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
                'has_signals'   => $request->has('voice_signals'),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Store per-question voice signals for behavioral analysis
        if ($request->has('voice_signals') && config('maritime.voice_behavioral_signals_v1')) {
            try {
                $signals = $request->input('voice_signals');
                $questionSlot = $request->input('question_slot') ?? $request->input('field_slot');
                $interviewId = $request->input('interview_id');

                if ($interviewId && $questionSlot && is_array($signals) && !empty($signals['utterance_count'])) {
                    VoiceBehavioralSignal::updateOrCreate(
                        ['form_interview_id' => $interviewId, 'question_slot' => $questionSlot],
                        [
                            'utterance_count'       => (int) ($signals['utterance_count'] ?? 0),
                            'total_word_count'      => (int) ($signals['total_word_count'] ?? 0),
                            'total_duration_s'      => (float) ($signals['total_duration_s'] ?? 0),
                            'avg_confidence'        => (float) ($signals['avg_confidence'] ?? 0),
                            'min_confidence'        => (float) ($signals['min_confidence'] ?? 0),
                            'avg_wpm'               => (float) ($signals['avg_wpm'] ?? 0),
                            'total_pause_count'     => (int) ($signals['total_pause_count'] ?? 0),
                            'total_long_pause_count' => (int) ($signals['total_long_pause_count'] ?? 0),
                            'total_filler_count'    => (int) ($signals['total_filler_count'] ?? 0),
                            'avg_filler_ratio'      => (float) ($signals['avg_filler_ratio'] ?? 0),
                            'utterance_signals_json' => $signals['utterance_signals'] ?? null,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::debug('Voice signal storage failed', [
                    'interview_id' => $request->input('interview_id'),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['success' => true]);
    }
}
