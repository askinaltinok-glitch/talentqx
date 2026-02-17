<?php

namespace App\Jobs;

use App\Models\CrmSequenceEnrollment;
use App\Services\Mail\SequenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunSequenceStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [60];
    public int $timeout = 120;

    public function __construct(
        public CrmSequenceEnrollment $enrollment
    ) {}

    public function handle(SequenceService $service): void
    {
        Log::info('RunSequenceStepJob: Executing step', [
            'enrollment_id' => $this->enrollment->id,
            'step' => $this->enrollment->current_step,
        ]);

        $service->executeStep($this->enrollment);
    }
}
