<?php

namespace App\Services\Interview;

use App\Models\Interview;
use App\Models\InterviewResponse;
use App\Services\AI\LLMProviderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranscriptionService
{
    public function __construct(
        private LLMProviderInterface $llmProvider
    ) {}

    public function transcribeResponse(InterviewResponse $response): InterviewResponse
    {
        $audioPath = $response->audio_segment_url ?? $response->video_segment_url;

        if (!$audioPath) {
            Log::warning('No audio/video file for transcription', [
                'response_id' => $response->id,
            ]);
            return $response;
        }

        try {
            $result = $this->llmProvider->transcribeAudio($audioPath, 'tr');

            $response->update([
                'transcript' => $result['transcript'],
                'transcript_confidence' => $result['confidence'],
                'transcript_language' => $result['language'],
            ]);

            Log::info('Transcription completed', [
                'response_id' => $response->id,
                'confidence' => $result['confidence'],
            ]);

            return $response->fresh();
        } catch (\Exception $e) {
            Log::error('Transcription failed', [
                'response_id' => $response->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function transcribeAllResponses(Interview $interview): void
    {
        $responses = $interview->responses()
            ->whereNull('transcript')
            ->get();

        foreach ($responses as $response) {
            try {
                $this->transcribeResponse($response);
            } catch (\Exception $e) {
                Log::error('Failed to transcribe response', [
                    'interview_id' => $interview->id,
                    'response_id' => $response->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function hasAllTranscripts(Interview $interview): bool
    {
        return $interview->responses()
            ->whereNull('transcript')
            ->doesntExist();
    }
}
