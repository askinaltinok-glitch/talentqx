<?php

namespace App\Jobs;

use App\Services\Mail\InboundEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class ProcessInboundEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 3;
    public array $backoff = [30, 60];
    public int $timeout = 120;

    public function __construct(
        public array $emailData
    ) {
        $this->captureBrand();
    }

    public function handle(InboundEmailService $service): void
    {
        $this->setBrandDatabase();
        Log::info('ProcessInboundEmailJob: Processing', [
            'from' => $this->emailData['from_email'] ?? 'unknown',
            'subject' => $this->emailData['subject'] ?? '(no subject)',
        ]);

        $service->process($this->emailData);
    }
}
