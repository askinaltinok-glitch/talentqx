<?php

namespace App\Services\QRCode;

use App\Models\Job;
use App\Support\BrandConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QRCodeService
{
    protected string $bucket = 'talentqx-files';
    protected string $baseUrl;
    protected ?bool $qrAvailable = null;
    protected ?bool $s3Available = null;

    public function __construct()
    {
        $this->baseUrl = config('app.url', 'https://octopus-ai.net');
    }

    /**
     * Check if QR library is available (lazy check).
     */
    protected function isQrAvailable(): bool
    {
        if ($this->qrAvailable === null) {
            $this->qrAvailable = class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class);
        }
        return $this->qrAvailable;
    }

    /**
     * Check if S3/MinIO storage is configured.
     */
    protected function isS3Available(): bool
    {
        if ($this->s3Available === null) {
            $this->s3Available = !empty(config('filesystems.disks.s3.region'))
                && !empty(config('filesystems.disks.s3.bucket'));
        }
        return $this->s3Available;
    }

    /**
     * Generate QR code for a job post's apply URL.
     */
    public function generateForJob(Job $job): ?string
    {
        // Ensure job has a public token
        if (!$job->public_token) {
            $job->regeneratePublicToken();
            $job->refresh();
        }

        $applyUrl = $this->buildApplyUrl($job);

        // Persist apply_url without triggering observers (prevents recursion via JobObserver)
        $job->apply_url = $applyUrl;
        $job->saveQuietly();

        if (!$this->isQrAvailable()) {
            Log::info('QR code library not available, skipping generation');
            return null;
        }

        // Skip S3 storage if not configured — apply_url is already set
        if (!$this->isS3Available()) {
            return null;
        }

        $fileName = $this->generateFileName($job);
        $filePath = "qr-codes/{$job->company_id}/{$fileName}";

        try {
            // Generate QR code as SVG (doesn't require imagick)
            $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(400)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($applyUrl);

            // Store in MinIO/S3
            $stored = Storage::disk('s3')->put($filePath, $qrImage, [
                'visibility' => 'public',
                'ContentType' => 'image/svg+xml',
            ]);

            if (!$stored) {
                Log::warning('Failed to store QR code to S3, apply_url still set', ['path' => $filePath]);
                return null;
            }

            // Update job record with file path (quiet to prevent observer recursion)
            $job->qr_file_path = $filePath;
            $job->saveQuietly();

            Log::info('QR code generated', [
                'job_id' => $job->id,
                'path' => $filePath,
                'url' => $applyUrl,
            ]);

            return $filePath;

        } catch (\Exception $e) {
            Log::warning('QR code file storage failed, apply_url still set', [
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
        if (!$this->isQrAvailable()) {
            throw new \RuntimeException('QR code library not available');
        }

        $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size($size)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($url);

        return 'data:image/svg+xml;base64,' . base64_encode($qrImage);
    }

    /**
     * Generate QR code as raw SVG string for inline use.
     */
    public function generateSvg(string $url, int $size = 300): string
    {
        if (!$this->isQrAvailable()) {
            throw new \RuntimeException('QR code library not available');
        }

        return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size($size)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($url);
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
     * Build the apply URL for a job using public token.
     * Brand-aware: octopus → octopus-ai.net, talentqx → app.talentqx.com
     */
    public function buildApplyUrl(Job $job): string
    {
        // Ensure public token exists
        if (!$job->public_token) {
            $job->regeneratePublicToken();
            $job->refresh();
        }

        // Resolve frontend domain from company's platform
        $platform = $job->company?->platform ?? 'talentqx';
        $frontendBase = match ($platform) {
            'octopus' => 'https://octopus-ai.net',
            default   => 'https://app.talentqx.com',
        };

        return "{$frontendBase}/i/{$job->public_token}";
    }

    /**
     * Generate a unique filename for the QR code.
     */
    protected function generateFileName(Job $job): string
    {
        $slug = $job->slug ?? 'job';
        $timestamp = now()->format('Ymd_His');

        return "{$slug}_{$timestamp}.svg";
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
