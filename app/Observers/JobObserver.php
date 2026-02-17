<?php

namespace App\Observers;

use App\Models\Job;
use App\Services\QRCode\QRCodeService;
use Illuminate\Support\Facades\Log;

class JobObserver
{
    public function __construct(
        protected ?QRCodeService $qrCodeService = null
    ) {}

    /**
     * Handle the Job "created" event.
     */
    public function created(Job $job): void
    {
        // Generate QR code when job is published with branch and role_code
        if ($this->qrCodeService && $this->shouldGenerateQR($job)) {
            try {
                $this->qrCodeService->generateForJob($job);
            } catch (\Exception $e) {
                Log::warning('QR code generation skipped: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Job "updated" event.
     */
    public function updated(Job $job): void
    {
        // Regenerate QR if relevant fields changed
        if ($this->qrCodeService && $this->shouldRegenerateQR($job)) {
            try {
                $this->qrCodeService->regenerateIfNeeded($job);
            } catch (\Exception $e) {
                Log::warning('QR code regeneration skipped: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Job "deleted" event.
     */
    public function deleted(Job $job): void
    {
        // Clean up QR code file
        if ($this->qrCodeService) {
            try {
                $this->qrCodeService->delete($job);
            } catch (\Exception $e) {
                Log::warning('QR code deletion skipped: ' . $e->getMessage());
            }
        }
    }

    /**
     * Check if QR should be generated for new job.
     */
    protected function shouldGenerateQR(Job $job): bool
    {
        return $job->branch_id
            && $job->role_code
            && $job->status === 'active';
    }

    /**
     * Check if QR should be regenerated.
     */
    protected function shouldRegenerateQR(Job $job): bool
    {
        // Check if relevant fields changed
        $changedFields = ['branch_id', 'role_code', 'status'];

        foreach ($changedFields as $field) {
            if ($job->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }
}
