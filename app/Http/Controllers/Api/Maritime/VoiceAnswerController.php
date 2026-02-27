<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Jobs\TranscribeVoiceAnswerJob;
use App\Models\FormInterview;
use App\Models\InterviewInvitation;
use App\Models\VoiceTranscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Voice answer upload + transcription status for the Clean Interview flow.
 *
 * POST /v1/maritime/interview/voice-answers     → upload audio, create pending transcription
 * GET  /v1/maritime/interview/voice-answers/{qid} → poll transcription status
 */
class VoiceAnswerController extends Controller
{
    private const ALLOWED_MIMES = [
        'audio/webm',
        'audio/wav',
        'audio/x-wav',
        'audio/mpeg',
        'audio/mp3',
        'audio/mp4',
        'audio/m4a',
        'audio/ogg',
    ];

    private const ALLOWED_EXTENSIONS = 'webm,wav,mp3,mpeg,mp4,m4a,ogg';

    /**
     * POST /v1/maritime/interview/voice-answers
     *
     * Upload an audio recording for a specific question slot.
     * Creates a VoiceTranscription record and dispatches the transcribe job.
     */
    public function store(Request $request): JsonResponse
    {
        $maxMb = config('services.voice.max_upload_mb', 12);

        $validator = Validator::make($request->all(), [
            'invitation_token' => 'required|string',
            'slot'             => 'required|integer|min:1|max:25',
            'question_id'      => 'required|string|max:50',
            'file'             => "required|file|max:{$this->kbFromMb($maxMb)}",
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // ── Auth: invitation token ─────────────────────────────────
        $invitation = InterviewInvitation::findByTokenHash($request->invitation_token);
        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation token.',
            ], 403);
        }

        if ($invitation->isExpired()) {
            if ($invitation->status !== InterviewInvitation::STATUS_EXPIRED) {
                $invitation->markExpired();
            }
            return response()->json([
                'success' => false,
                'message' => 'This invitation has expired.',
                'code'    => 'INVITATION_EXPIRED',
            ], 410);
        }

        if ($invitation->status !== InterviewInvitation::STATUS_STARTED) {
            return response()->json([
                'success' => false,
                'message' => 'Interview not started.',
            ], 400);
        }

        $interview = $invitation->interview;
        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Interview not found.',
            ], 404);
        }

        // ── File validation ────────────────────────────────────────
        $file = $request->file('file');
        $mime = $file->getMimeType();

        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported audio format. Allowed: webm, wav, mp3, mp4, m4a, ogg.',
                'code'    => 'INVALID_AUDIO_TYPE',
            ], 422);
        }

        // ── Guard: duplicate question at different slot ────────────
        $existingAtOtherSlot = $interview->answers()
            ->where('competency', $request->question_id)
            ->where('slot', '!=', $request->slot)
            ->exists();

        if ($existingAtOtherSlot) {
            return response()->json([
                'success' => false,
                'message' => 'This question has already been answered at a different slot.',
                'code'    => 'DUPLICATE_QUESTION',
            ], 422);
        }

        // ── Guard: already has a pending/done transcription for this slot ──
        $existingTx = VoiceTranscription::where('interview_id', $interview->id)
            ->where('slot', $request->slot)
            ->first();

        if ($existingTx) {
            if ($existingTx->isDone()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This question already has a completed voice answer.',
                    'code'    => 'ALREADY_TRANSCRIBED',
                ], 422);
            }
            if ($existingTx->isPending()) {
                // Allow re-upload: delete old pending, will create new one
                Storage::delete($existingTx->audio_path);
                $existingTx->delete();
            }
            // If failed, allow re-upload (already deleted above or falls through)
            if ($existingTx->exists && $existingTx->isFailed()) {
                Storage::delete($existingTx->audio_path);
                $existingTx->delete();
            }
        }

        // ── Guard: audio duration via ffprobe ────────────────────────
        $durationMs = $this->getAudioDurationMs($file->getRealPath());
        $minDurationMs = 2000; // 2 seconds minimum
        $maxDurationSec = config('services.voice.max_duration_seconds', 120);

        if ($durationMs !== null && $durationMs < $minDurationMs) {
            return response()->json([
                'success' => false,
                'message' => 'Audio is too short. Please record at least 2 seconds.',
                'code'    => 'AUDIO_TOO_SHORT',
            ], 422);
        }

        if ($durationMs !== null && $durationMs > ($maxDurationSec * 1000)) {
            return response()->json([
                'success' => false,
                'message' => "Audio exceeds maximum duration of {$maxDurationSec} seconds.",
                'code'    => 'AUDIO_TOO_LONG',
            ], 422);
        }

        // ── Store audio file ───────────────────────────────────────
        $fileContents = file_get_contents($file->getRealPath());
        $sha256 = hash('sha256', $fileContents);
        $ext = $file->getClientOriginalExtension() ?: 'webm';
        $storagePath = "private/interviews/{$interview->id}/voice/" . Str::uuid() . ".{$ext}";

        Storage::put($storagePath, $fileContents);

        // ── Create VoiceTranscription record ───────────────────────
        $transcription = VoiceTranscription::create([
            'company_id'       => $interview->company_id,
            'interview_id'     => $interview->id,
            'candidate_id'     => $interview->pool_candidate_id,
            'question_id'      => $request->question_id,
            'slot'             => (int) $request->slot,
            'audio_path'       => $storagePath,
            'audio_mime'       => $mime,
            'audio_size_bytes' => strlen($fileContents),
            'audio_sha256'     => $sha256,
            'duration_ms'      => $durationMs,
            'provider'         => 'ai_models_panel',
            'model'            => config('services.whisper.model'),
            'language'         => config('services.whisper.language', 'en'),
            'status'           => VoiceTranscription::STATUS_PENDING,
        ]);

        // ── Dispatch transcription job ─────────────────────────────
        TranscribeVoiceAnswerJob::dispatch($transcription->id);

        Log::info('VoiceAnswerController::store: voice upload queued', [
            'transcription_id' => $transcription->id,
            'interview_id'     => $interview->id,
            'slot'             => $request->slot,
            'question_id'      => $request->question_id,
            'size_bytes'       => $transcription->audio_size_bytes,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'transcription_id' => $transcription->id,
                'status'           => 'pending',
            ],
        ], 202);
    }

    /**
     * GET /v1/maritime/interview/voice-answers/{questionId}
     *
     * Poll transcription status for a question.
     */
    public function show(Request $request, string $questionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invitation_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $invitation = InterviewInvitation::findByTokenHash($request->invitation_token);
        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation token.',
            ], 403);
        }

        $interview = $invitation->interview;
        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'Interview not found.',
            ], 404);
        }

        $transcription = VoiceTranscription::where('interview_id', $interview->id)
            ->where('question_id', $questionId)
            ->latest()
            ->first();

        if (!$transcription) {
            return response()->json([
                'success' => false,
                'message' => 'No voice answer found for this question.',
            ], 404);
        }

        $data = [
            'transcription_id' => $transcription->id,
            'status'           => $transcription->status,
            'updated_at'       => $transcription->updated_at->toIso8601String(),
        ];

        if ($transcription->isDone()) {
            $data['transcript_text'] = $transcription->transcript_text;
            $data['confidence']      = $transcription->confidence;
        }

        if ($transcription->isFailed()) {
            $data['error'] = $transcription->error_message;
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    private function kbFromMb(int $mb): int
    {
        return $mb * 1024;
    }

    /**
     * Get audio duration in milliseconds via ffprobe.
     * Returns null if ffprobe fails (fail-open: let the job decide).
     */
    private function getAudioDurationMs(string $filePath): ?int
    {
        try {
            $process = new \Symfony\Component\Process\Process([
                '/usr/bin/ffprobe', '-v', 'quiet',
                '-show_entries', 'format=duration',
                '-of', 'csv=p=0',
                $filePath,
            ]);
            $process->setTimeout(5);
            $process->run();

            $output = trim($process->getOutput());

            if ($output === '' || !is_numeric($output)) {
                Log::warning('VoiceAnswerController: ffprobe returned empty/non-numeric', [
                    'file' => $filePath,
                    'output' => $output,
                    'stderr' => $process->getErrorOutput(),
                    'exit' => $process->getExitCode(),
                ]);
                return null;
            }

            return (int) round((float) $output * 1000);
        } catch (\Throwable $e) {
            Log::warning('VoiceAnswerController: ffprobe failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
