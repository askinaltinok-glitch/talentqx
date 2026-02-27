<?php

namespace App\Services\Voice;

use App\Exceptions\WhisperTranscriptionException;
use App\Services\AI\OpenAIProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Unified Whisper transcription facade.
 *
 * Primary:  OpenAI whisper-1 (~3s, $0.006/min)
 * Fallback: AiModelsPanel self-hosted large-v3 (~9s)
 *
 * Pre-processing: ffmpeg webm→mp3 conversion for better compatibility.
 * Validation: file size, silence detection.
 *
 * Returns normalized: {text, confidence, language, raw, provider}
 */
class WhisperTranscriber
{
    private const MIN_FILE_SIZE_BYTES = 1024;       // 1 KB — below this, file is likely corrupt/empty
    private const MIN_AUDIO_DURATION_SEC = 1.0;     // minimum 1 second of audio
    private const SILENCE_THRESHOLD_DB = -40;       // dBFS — below this = silence
    private const SILENCE_MAX_RATIO = 0.90;         // if 90%+ is silence, skip transcription

    public function __construct(
        private OpenAIProvider $openai,
        private AiModelsPanelWhisperClient $aiModelsPanel,
    ) {}

    /**
     * Transcribe audio via OpenAI (primary) with AiModelsPanel fallback.
     *
     * Pre-processes audio with ffmpeg for better Whisper compatibility.
     *
     * @return array{text: string, confidence: float|null, language: string, raw: array, provider: string}
     * @throws WhisperTranscriptionException when both providers fail or audio is invalid
     */
    public function transcribe(string $storagePath, string $mime, ?string $language = null): array
    {
        // ── Pre-validation: file size ─────────────────────────
        $fileSize = $this->getFileSize($storagePath);
        if ($fileSize !== null && $fileSize < self::MIN_FILE_SIZE_BYTES) {
            throw new WhisperTranscriptionException(
                "Audio file too small ({$fileSize} bytes). Likely corrupt or empty."
            );
        }

        // ── Pre-processing: convert to mp3 for compatibility ──
        $processedPath = $this->preprocessAudio($storagePath);
        $useProcessed = $processedPath !== null;
        $transcribePath = $useProcessed ? $processedPath : $storagePath;

        try {
            // ── Silence detection on processed file ───────────
            if ($useProcessed) {
                $silenceInfo = $this->detectSilence($processedPath);
                if ($silenceInfo['is_silence']) {
                    throw new WhisperTranscriptionException(
                        'Audio contains mostly silence (ratio: ' . round($silenceInfo['silence_ratio'], 2) . '). '
                        . 'Duration: ' . round($silenceInfo['duration'], 1) . 's'
                    );
                }

                if ($silenceInfo['duration'] < self::MIN_AUDIO_DURATION_SEC) {
                    throw new WhisperTranscriptionException(
                        'Audio too short: ' . round($silenceInfo['duration'], 1) . 's (min: ' . self::MIN_AUDIO_DURATION_SEC . 's)'
                    );
                }
            }

            // ── Primary: OpenAI ──────────────────────────────────
            try {
                $result = $this->openai->transcribeAudio(
                    $transcribePath,
                    $language ?? 'en',
                );

                Log::info('WhisperTranscriber: OpenAI success', [
                    'path'        => $storagePath,
                    'preprocessed' => $useProcessed,
                    'language'    => $result['language'] ?? $language,
                    'chars'       => mb_strlen($result['transcript'] ?? ''),
                ]);

                return $this->normalizeOpenAI($result);
            } catch (\Throwable $e) {
                Log::warning('WhisperTranscriber: OpenAI failed, falling back to AiModelsPanel', [
                    'path'  => $storagePath,
                    'error' => $e->getMessage(),
                ]);
            }

            // ── Fallback: AiModelsPanel ──────────────────────────
            try {
                $result = $this->aiModelsPanel->transcribe($transcribePath, 'audio/mpeg', $language);

                Log::info('WhisperTranscriber: AiModelsPanel fallback success', [
                    'path'        => $storagePath,
                    'preprocessed' => $useProcessed,
                    'language'    => $result['language'] ?? $language,
                    'chars'       => mb_strlen($result['text'] ?? ''),
                ]);

                return $this->normalizeAiModelsPanel($result);
            } catch (\Throwable $e) {
                Log::error('WhisperTranscriber: both providers failed', [
                    'path'  => $storagePath,
                    'error' => $e->getMessage(),
                ]);

                throw new WhisperTranscriptionException(
                    'All transcription providers failed. Last error: ' . $e->getMessage(),
                    0,
                    $e,
                );
            }
        } finally {
            // Clean up temporary mp3 file
            if ($useProcessed) {
                $this->cleanupTempFile($processedPath);
            }
        }
    }

    /**
     * Convert webm/wav/ogg → mp3 via ffmpeg for better Whisper compatibility.
     * Returns the new storage path, or null if conversion fails (fallback to original).
     */
    private function preprocessAudio(string $storagePath): ?string
    {
        $fullPath = Storage::path($storagePath);
        if (!file_exists($fullPath)) {
            return null;
        }

        $mp3Path = preg_replace('/\.[^.]+$/', '.mp3', $storagePath);
        $mp3FullPath = Storage::path($mp3Path);

        // Skip if already mp3
        if (str_ends_with(strtolower($storagePath), '.mp3')) {
            return null;
        }

        try {
            $process = new Process([
                '/usr/bin/ffmpeg', '-y',
                '-i', $fullPath,
                '-vn',                          // no video
                '-acodec', 'libmp3lame',
                '-ar', '16000',                 // 16kHz (Whisper optimal)
                '-ac', '1',                     // mono
                '-b:a', '64k',                  // 64kbps (enough for speech)
                '-af', 'silenceremove=start_periods=1:start_silence=0.5:start_threshold=-50dB', // trim leading silence
                $mp3FullPath,
            ]);
            $process->setTimeout(15);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::warning('WhisperTranscriber: ffmpeg conversion failed', [
                    'path'   => $storagePath,
                    'stderr' => mb_substr($process->getErrorOutput(), 0, 300),
                ]);
                return null;
            }

            $mp3Size = filesize($mp3FullPath);
            Log::info('WhisperTranscriber: preprocessed audio', [
                'original' => $storagePath,
                'mp3_path' => $mp3Path,
                'mp3_size' => $mp3Size,
            ]);

            return $mp3Path;
        } catch (\Throwable $e) {
            Log::warning('WhisperTranscriber: ffmpeg exception', [
                'path'  => $storagePath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Detect silence ratio and audio duration using ffmpeg.
     *
     * @return array{is_silence: bool, silence_ratio: float, duration: float}
     */
    private function detectSilence(string $storagePath): array
    {
        $fullPath = Storage::path($storagePath);
        $default = ['is_silence' => false, 'silence_ratio' => 0.0, 'duration' => 0.0];

        if (!file_exists($fullPath)) {
            return $default;
        }

        try {
            // Get total duration
            $durationProcess = new Process([
                '/usr/bin/ffprobe', '-v', 'quiet',
                '-show_entries', 'format=duration',
                '-of', 'csv=p=0',
                $fullPath,
            ]);
            $durationProcess->setTimeout(5);
            $durationProcess->run();

            $duration = (float) trim($durationProcess->getOutput());
            if ($duration <= 0) {
                return $default;
            }

            // Detect silence segments
            $silenceProcess = new Process([
                '/usr/bin/ffmpeg', '-i', $fullPath,
                '-af', 'silencedetect=noise=' . self::SILENCE_THRESHOLD_DB . 'dB:d=0.5',
                '-f', 'null', '-',
            ]);
            $silenceProcess->setTimeout(10);
            $silenceProcess->run();

            $stderr = $silenceProcess->getErrorOutput();

            // Parse silence_duration from ffmpeg output
            preg_match_all('/silence_duration:\s*([\d.]+)/', $stderr, $matches);
            $totalSilence = array_sum(array_map('floatval', $matches[1] ?? []));

            $silenceRatio = $duration > 0 ? $totalSilence / $duration : 0;

            return [
                'is_silence'    => $silenceRatio >= self::SILENCE_MAX_RATIO,
                'silence_ratio' => $silenceRatio,
                'duration'      => $duration,
            ];
        } catch (\Throwable $e) {
            Log::warning('WhisperTranscriber: silence detection failed', [
                'path'  => $storagePath,
                'error' => $e->getMessage(),
            ]);
            return $default;
        }
    }

    private function getFileSize(string $storagePath): ?int
    {
        try {
            return Storage::size($storagePath);
        } catch (\Throwable) {
            return null;
        }
    }

    private function cleanupTempFile(string $storagePath): void
    {
        try {
            Storage::delete($storagePath);
        } catch (\Throwable) {
            // Ignore cleanup errors
        }
    }

    /**
     * Normalize OpenAI response → {text, confidence, language, raw, provider}
     */
    private function normalizeOpenAI(array $result): array
    {
        return [
            'text'       => $result['transcript'] ?? '',
            'confidence' => $result['confidence'] ?? null,
            'language'   => $result['language'] ?? 'en',
            'raw'        => $result,
            'provider'   => 'openai',
        ];
    }

    /**
     * Normalize AiModelsPanel response → {text, confidence, language, raw, provider}
     */
    private function normalizeAiModelsPanel(array $result): array
    {
        return [
            'text'       => $result['text'] ?? '',
            'confidence' => $result['confidence'] ?? null,
            'language'   => $result['language'] ?? 'en',
            'raw'        => $result['raw'] ?? $result,
            'provider'   => 'ai_models_panel',
        ];
    }
}
