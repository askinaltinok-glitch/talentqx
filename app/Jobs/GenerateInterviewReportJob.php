<?php

namespace App\Jobs;

use App\Models\InterviewReport;
use App\Services\Report\InterviewReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class GenerateInterviewReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public string $sessionId,
        public ?string $tenantId = null,
        public string $locale = 'tr',
        public ?array $branding = null
    ) {
        $this->captureBrand();
    }

    public function handle(InterviewReportService $reportService): void
    {
        $this->setBrandDatabase();
        try {
            $report = $reportService->generate(
                $this->sessionId,
                $this->tenantId,
                $this->locale,
                $this->branding
            );

            Log::info("Report generated for session {$this->sessionId}", [
                'report_id' => $report->id,
                'file_size' => $report->file_size,
            ]);

        } catch (\Exception $e) {
            Log::error("Report generation failed for session {$this->sessionId}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
