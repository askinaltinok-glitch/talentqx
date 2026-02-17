<?php

namespace App\Jobs;

use App\Models\CrmEmailThread;
use App\Services\Mail\ReplyDraftService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReplyDraftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [60];
    public int $timeout = 180;

    public function __construct(
        public CrmEmailThread $thread
    ) {}

    public function handle(ReplyDraftService $service): void
    {
        // Only generate drafts in draft_only mode or when thread has a lead
        if (!$this->thread->lead_id) {
            Log::info('GenerateReplyDraftJob: Skipping, no lead', ['thread_id' => $this->thread->id]);
            return;
        }

        Log::info('GenerateReplyDraftJob: Generating draft', ['thread_id' => $this->thread->id]);

        $service->generateDraft($this->thread);
    }
}
