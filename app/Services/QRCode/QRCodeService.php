<?php

namespace App\Services\QRCode;

use App\Models\Job;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QRCodeService
{
    protected string $bucket = 'talentqx-files';
    protected string $baseUrl;
    protected ?bool $qrAvailable = null;

    public function __construct()
    {
        $this->baseUrl = config('app.url', 'https://talentqx.com');
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

        if (!$this->isQrAvailable()) {
            Log::info('QR code library not available, skipping generation');
            $job->update(['apply_url' => $applyUrl]);
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
     * URL format: /i/{public_token}
     */
    public function buildApplyUrl(Job $job): string
    {
        // Ensure public token exists
        if (!$job->public_token) {
            $job->regeneratePublicToken();
            $job->refresh();
        }

        return "{$this->baseUrl}/i/{$job->public_token}";
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
