<?php

namespace App\Jobs;

use App\Models\InterviewResponse;
use App\Services\Interview\TranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class TranscribeAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public $tries = 3;
    public $backoff = [30, 60, 120];

    public function __construct(
        public InterviewResponse $response
    ) {
        $this->captureBrand();
    }

    public function handle(TranscriptionService $transcriptionService): void
    {
        $this->setBrandDatabase();
        Log::info('Starting audio transcription', [
            'response_id' => $this->response->id,
            'interview_id' => $this->response->interview_id,
        ]);

        try {
            $transcriptionService->transcribeResponse($this->response);

            Log::info('Audio transcription completed', [
                'response_id' => $this->response->id,
                'transcript_length' => strlen($this->response->fresh()->transcript ?? ''),
            ]);

        } catch (\Exception $e) {
            Log::error('Audio transcription failed', [
                'response_id' => $this->response->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('TranscribeAudioJob permanently failed', [
            'response_id' => $this->response->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
