<?php

namespace App\Services\Voice;

use App\Exceptions\WhisperTranscriptionException;
use App\Services\AI\OpenAIProvider;
use Illuminate\Support\Facades\Log;

/**
 * Unified Whisper transcription facade.
 *
 * Primary:  OpenAI whisper-1 (~3s, $0.006/min)
 * Fallback: AiModelsPanel self-hosted large-v3 (~9s)
 *
 * Returns normalized: {text, confidence, language, raw, provider}
 */
class WhisperTranscriber
{
    public function __construct(
        private OpenAIProvider $openai,
        private AiModelsPanelWhisperClient $aiModelsPanel,
    ) {}

    /**
     * Transcribe audio via OpenAI (primary) with AiModelsPanel fallback.
     *
     * @return array{text: string, confidence: float|null, language: string, raw: array, provider: string}
     * @throws WhisperTranscriptionException when both providers fail
     */
    public function transcribe(string $storagePath, string $mime, ?string $language = null): array
    {
        // ── Primary: OpenAI ──────────────────────────────────
        try {
            $result = $this->openai->transcribeAudio(
                $storagePath,
                $language ?? 'en',
            );

            Log::info('WhisperTranscriber: OpenAI success', [
                'path'     => $storagePath,
                'language' => $result['language'] ?? $language,
                'chars'    => mb_strlen($result['transcript'] ?? ''),
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
            $result = $this->aiModelsPanel->transcribe($storagePath, $mime, $language);

            Log::info('WhisperTranscriber: AiModelsPanel fallback success', [
                'path'     => $storagePath,
                'language' => $result['language'] ?? $language,
                'chars'    => mb_strlen($result['text'] ?? ''),
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
    }

    /**
     * Normalize OpenAI response {transcript, confidence, language, duration, segments}
     * → {text, confidence, language, raw, provider}
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
     * Normalize AiModelsPanel response {text, language, confidence, raw}
     * → {text, confidence, language, raw, provider}
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
