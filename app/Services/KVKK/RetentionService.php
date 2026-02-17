<?php

namespace App\Services\KVKK;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RetentionService
{
    protected DataErasureService $erasureService;

    public function __construct(DataErasureService $erasureService)
    {
        $this->erasureService = $erasureService;
    }

    /**
     * Process all expired data based on retention policies
     */
    public function processExpiredData(): array
    {
        $results = [
            'candidates_erased' => 0,
            'interviews_erased' => 0,
            'media_deleted' => 0,
            'errors' => [],
        ];

        // Get all jobs with their retention periods
        $jobs = Job::whereNotNull('retention_days')
            ->where('retention_days', '>', 0)
            ->get();

        foreach ($jobs as $job) {
            $cutoffDate = Carbon::now()->subDays($job->retention_days);

            // Find candidates whose interviews are past retention period
            $expiredCandidates = Candidate::where('job_id', $job->id)
                ->where('is_erased', false)
                ->where('created_at', '<', $cutoffDate)
                ->whereIn('status', ['rejected', 'hired']) // Only process closed candidates
                ->get();

            foreach ($expiredCandidates as $candidate) {
                try {
                    $result = $this->erasureService->eraseCandidate(
                        $candidate,
                        'retention_expired'
                    );

                    if ($result['success']) {
                        $results['candidates_erased']++;
                        $results['interviews_erased'] += $candidate->interviews->count();
                    } else {
                        $results['errors'][] = [
                            'candidate_id' => $candidate->id,
                            'error' => $result['error'],
                        ];
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'candidate_id' => $candidate->id,
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Retention processing failed', [
                        'candidate_id' => $candidate->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Clean up orphaned media files (files without database references)
        $results['media_deleted'] = $this->cleanOrphanedMedia();

        Log::info('Retention processing completed', $results);

        return $results;
    }

    /**
     * Clean orphaned media files from storage
     */
    protected function cleanOrphanedMedia(): int
    {
        $deleted = 0;

        // Get all video files in storage
        $files = Storage::disk('public')->files('interviews');

        foreach ($files as $file) {
            $filename = basename($file);

            // Check if file is referenced in database
            $exists = Interview::where('video_url', 'like', '%' . $filename . '%')->exists()
                || DB::table('interview_responses')
                    ->where('video_segment_url', 'like', '%' . $filename . '%')
                    ->exists();

            if (!$exists) {
                // Check if file is older than 24 hours (to avoid deleting newly uploaded files)
                $lastModified = Storage::disk('public')->lastModified($file);
                if ($lastModified < now()->subHours(24)->timestamp) {
                    Storage::disk('public')->delete($file);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Get retention statistics for dashboard
     */
    public function getRetentionStats(): array
    {
        $stats = [];

        // Count by retention period
        $jobs = Job::selectRaw('retention_days, count(*) as job_count')
            ->groupBy('retention_days')
            ->get();

        foreach ($jobs as $job) {
            $cutoffDate = Carbon::now()->subDays($job->retention_days);

            $stats[] = [
                'retention_days' => $job->retention_days,
                'job_count' => $job->job_count,
                'candidates_approaching_expiry' => Candidate::whereIn('job_id', function ($q) use ($job) {
                    $q->select('id')->from('job_postings')->where('retention_days', $job->retention_days);
                })
                    ->where('is_erased', false)
                    ->whereBetween('created_at', [
                        $cutoffDate->copy()->subDays(30),
                        $cutoffDate,
                    ])
                    ->count(),
            ];
        }

        $stats['total_erased'] = Candidate::where('is_erased', true)->count();
        $stats['erasure_requests_pending'] = DB::table('data_erasure_requests')
            ->where('status', 'pending')
            ->count();

        return $stats;
    }
}
