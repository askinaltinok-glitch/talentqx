<?php

namespace App\Services\Voice;

use App\Exceptions\WhisperTranscriptionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * HTTP client for the self-hosted AiModelsPanel Whisper transcription endpoint.
 *
 * Endpoint: POST {base_url}/v1/audio/transcriptions
 * Auth:     Authorization: Bearer {api_key}
 * Body:     multipart/form-data (file, model, language, temperature, response_format)
 * Response: { "text": "..." }
 */
class AiModelsPanelWhisperClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;
    private bool $verifySsl;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('services.ai_models_panel.base_url'), '/');
        $this->apiKey    = config('services.ai_models_panel.api_key') ?? '';
        $this->timeout   = config('services.ai_models_panel.timeout', 60);
        $this->verifySsl = config('services.ai_models_panel.verify_ssl', true);
    }

    /**
     * Transcribe an audio file via AiModelsPanel Whisper.
     *
     * @param  string  $storagePath  Path within Laravel Storage (private disk)
     * @param  string  $mime         Audio MIME type (e.g. audio/webm)
     * @param  string  $language     Language code (default: from config)
     * @return array{text: string, language: string, confidence: float|null, raw: array}
     *
     * @throws WhisperTranscriptionException on failure
     */
    public function transcribe(string $storagePath, string $mime, ?string $language = null): array
    {
        if (empty($this->apiKey)) {
            throw new WhisperTranscriptionException('AiModelsPanel API key is not configured.');
        }

        $fileContents = Storage::get($storagePath);
        if ($fileContents === null) {
            throw new WhisperTranscriptionException("Audio file not found: {$storagePath}");
        }

        $filename = basename($storagePath);
        $whisperConfig = config('services.whisper');

        $endpoint = "{$this->baseUrl}/v1/audio/transcriptions";

        $fields = [
            'model'           => $whisperConfig['model'] ?? 'large-v3',
            'language'        => $language ?? ($whisperConfig['language'] ?? 'en'),
            'temperature'     => (string) ($whisperConfig['temperature'] ?? 0),
            'response_format' => $whisperConfig['response_format'] ?? 'json',
        ];

        if (!empty($whisperConfig['prompt'])) {
            $fields['prompt'] = $whisperConfig['prompt'];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->withOptions(['verify' => $this->verifySsl])
                ->attach('file', $fileContents, $filename, ['Content-Type' => $mime])
                ->post($endpoint, $fields);
        } catch (\Throwable $e) {
            Log::error('AiModelsPanelWhisperClient: HTTP error', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);
            throw new WhisperTranscriptionException('Connection to AiModelsPanel failed: ' . $e->getMessage(), 0, $e);
        }

        if (!$response->successful()) {
            $body = $response->body();
            Log::error('AiModelsPanelWhisperClient: non-200 response', [
                'status' => $response->status(),
                'body'   => mb_substr($body, 0, 500),
            ]);
            throw new WhisperTranscriptionException(
                "Whisper API returned HTTP {$response->status()}: " . mb_substr($body, 0, 200)
            );
        }

        $data = $response->json();

        if (!isset($data['text'])) {
            Log::error('AiModelsPanelWhisperClient: missing "text" in response', ['data' => $data]);
            throw new WhisperTranscriptionException('Whisper response missing "text" field.');
        }

        return [
            'text'       => $data['text'],
            'language'   => $data['language'] ?? ($language ?? $whisperConfig['language'] ?? 'en'),
            'confidence' => $data['confidence'] ?? null,
            'raw'        => $data,
        ];
    }
}
