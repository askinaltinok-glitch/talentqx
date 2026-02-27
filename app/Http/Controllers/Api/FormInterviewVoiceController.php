<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\TranscribeVoiceAnswerJob;
use App\Models\FormInterview;
use App\Models\VoiceTranscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Voice answer upload + transcription polling for general form interviews (TalentQX).
 *
 * POST /form-interviews/{id}/voice-answers     → upload audio, queue transcription
 * GET  /form-interviews/{id}/voice-answers/{qid} → poll transcription status
 */
class FormInterviewVoiceController extends Controller
{
    private const ALLOWED_MIMES = [
        'audio/webm',
        'video/webm',
        'audio/wav',
        'audio/x-wav',
        'audio/mpeg',
        'audio/mp3',
        'audio/mp4',
        'audio/m4a',
        'audio/ogg',
        'application/octet-stream',
    ];

    /**
     * POST /form-interviews/{id}/voice-answers
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $interview = FormInterview::find($id);

        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Interview not found.'], 404);
        }

        if ($interview->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Interview already completed.',
                'code'    => 'INTERVIEW_COMPLETED',
            ], 422);
        }

        $maxMb = config('services.voice.max_upload_mb', 12);

        $validator = Validator::make($request->all(), [
            'slot'        => 'required|integer|min:1|max:25',
            'question_id' => 'required|string|max:80',
            'file'        => "required|file|max:" . ($maxMb * 1024),
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // ── File validation ────────────────────────────────────────
        $file = $request->file('file');
        $mime = $file->getMimeType();

        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported audio format.',
                'code'    => 'INVALID_AUDIO_TYPE',
            ], 422);
        }

        // ── Guard: existing transcription for this slot ────────────
        $existingTx = VoiceTranscription::where('interview_id', $interview->id)
            ->where('slot', $request->slot)
            ->first();

        if ($existingTx) {
            if ($existingTx->isDone()) {
                // Allow re-record: delete old done transcription
                Storage::delete($existingTx->audio_path);
                $existingTx->delete();
            } elseif ($existingTx->isPending()) {
                Storage::delete($existingTx->audio_path);
                $existingTx->delete();
            } elseif ($existingTx->isFailed()) {
                Storage::delete($existingTx->audio_path);
                $existingTx->delete();
            }
        }

        // ── Audio duration check via ffprobe ───────────────────────
        $durationMs = $this->getAudioDurationMs($file->getRealPath());
        $minDurationMs = 2000;
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
        $storagePath = "private/form-interviews/{$interview->id}/voice/" . Str::uuid() . ".{$ext}";

        Storage::put($storagePath, $fileContents);

        // ── Create VoiceTranscription record ───────────────────────
        $language = $interview->language ?? 'tr';

        $transcription = VoiceTranscription::create([
            'company_id'       => $interview->company_id ?? null,
            'interview_id'     => $interview->id,
            'candidate_id'     => $interview->pool_candidate_id ?? null,
            'question_id'      => $request->question_id,
            'slot'             => (int) $request->slot,
            'audio_path'       => $storagePath,
            'audio_mime'       => $mime,
            'audio_size_bytes' => strlen($fileContents),
            'audio_sha256'     => $sha256,
            'duration_ms'      => $durationMs,
            'provider'         => 'ai_models_panel',
            'model'            => config('services.whisper.model'),
            'language'         => $language,
            'status'           => VoiceTranscription::STATUS_PENDING,
        ]);

        // ── Dispatch transcription job ─────────────────────────────
        TranscribeVoiceAnswerJob::dispatch($transcription->id);

        Log::info('FormInterviewVoiceController::store: voice upload queued', [
            'transcription_id' => $transcription->id,
            'interview_id'     => $interview->id,
            'slot'             => $request->slot,
            'question_id'      => $request->question_id,
            'language'         => $language,
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
     * GET /form-interviews/{id}/voice-answers/{questionId}
     */
    public function show(Request $request, string $id, string $questionId): JsonResponse
    {
        $interview = FormInterview::find($id);

        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Interview not found.'], 404);
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
                return null;
            }

            return (int) round((float) $output * 1000);
        } catch (\Throwable $e) {
            Log::warning('FormInterviewVoiceController: ffprobe failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
