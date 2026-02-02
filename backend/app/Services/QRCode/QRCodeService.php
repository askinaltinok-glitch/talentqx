<?php

namespace App\Services\QRCode;

use App\Models\Job;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QRCodeService
{
    protected string $bucket = 'talentqx-files';
    protected string $baseUrl;
    protected bool $qrAvailable = false;

    public function __construct()
    {
        $this->baseUrl = config('app.url', 'https://talentqx.com');
        $this->qrAvailable = class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class);
    }

    /**
     * Generate QR code for a job post's apply URL.
     */
    public function generateForJob(Job $job): ?string
    {
        if (!$this->qrAvailable) {
            Log::info('QR code library not available, skipping generation');
            // Still set the apply_url even without QR
            $applyUrl = $this->buildApplyUrl($job);
            $job->update(['apply_url' => $applyUrl]);
            return null;
        }

        if (!$job->company || !$job->branch || !$job->role_code) {
            Log::warning('Cannot generate QR: missing company, branch, or role_code', [
                'job_id' => $job->id,
            ]);
            return null;
        }

        $applyUrl = $this->buildApplyUrl($job);
        $fileName = $this->generateFileName($job);
        $filePath = "qr-codes/{$job->company_id}/{$fileName}";

        try {
            // Generate QR code as PNG
            $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(400)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($applyUrl);

            // Store in MinIO/S3
            $stored = Storage::disk('s3')->put($filePath, $qrImage, [
                'visibility' => 'public',
                'ContentType' => 'image/png',
            ]);

            if (!$stored) {
                Log::error('Failed to store QR code', ['path' => $filePath]);
                return null;
            }

            // Update job record
            $job->update([
                'apply_url' => $applyUrl,
                'qr_file_path' => $filePath,
            ]);

            Log::info('QR code generated', [
                'job_id' => $job->id,
                'path' => $filePath,
                'url' => $applyUrl,
            ]);

            return $filePath;

        } catch (\Exception $e) {
            Log::error('QR code generation failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate QR code and return as base64 for preview.
     */
    public function generateBase64(string $url, int $size = 300): string
    {
        if (!$this->qrAvailable) {
            throw new \RuntimeException('QR code library not available');
        }

        $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size($size)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($url);

        return 'data:image/png;base64,' . base64_encode($qrImage);
    }

    /**
     * Get the public URL for a stored QR code.
     */
    public function getPublicUrl(Job $job): ?string
    {
        if (!$job->qr_file_path) {
            return null;
        }

        return Storage::disk('s3')->url($job->qr_file_path);
    }

    /**
     * Build the apply URL for a job.
     */
    public function buildApplyUrl(Job $job): string
    {
        $companySlug = $job->company->slug;
        $branchSlug = $job->branch->slug;
        $roleCode = strtoupper($job->role_code);

        return "{$this->baseUrl}/apply/{$companySlug}/{$branchSlug}/{$roleCode}";
    }

    /**
     * Generate a unique filename for the QR code.
     */
    protected function generateFileName(Job $job): string
    {
        $branchSlug = $job->branch->slug ?? 'branch';
        $roleCode = strtoupper($job->role_code ?? 'JOB');
        $timestamp = now()->format('Ymd');

        return "{$branchSlug}_{$roleCode}_{$timestamp}.png";
    }

    /**
     * Regenerate QR code if apply URL changed.
     */
    public function regenerateIfNeeded(Job $job): bool
    {
        $currentUrl = $this->buildApplyUrl($job);

        if ($job->apply_url !== $currentUrl || !$job->qr_file_path) {
            return $this->generateForJob($job) !== null;
        }

        return true;
    }

    /**
     * Delete QR code for a job.
     */
    public function delete(Job $job): bool
    {
        if (!$job->qr_file_path) {
            return true;
        }

        try {
            Storage::disk('s3')->delete($job->qr_file_path);

            $job->update([
                'qr_file_path' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete QR code', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
