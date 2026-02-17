<?php

namespace App\Services\KVKK;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\DataErasureRequest;
use App\Models\Interview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DataErasureService
{
    public function eraseCandidate(Candidate $candidate, string $reason = 'kvkk_request'): array
    {
        $erasedTypes = [];

        DB::beginTransaction();

        try {
            // 1. Store original data for audit log
            $originalData = $candidate->toArray();

            // 2. Erase personal information
            $candidate->update([
                'first_name' => '[SILINDI]',
                'last_name' => '[SILINDI]',
                'email' => 'erased_' . $candidate->id . '@erased.local',
                'phone' => null,
                'cv_url' => null,
                'cv_parsed_data' => null,
                'internal_notes' => null,
                'tags' => null,
                'is_erased' => true,
                'erased_at' => now(),
                'erasure_reason' => $reason,
            ]);
            $erasedTypes[] = 'personal_info';

            // 3. Delete CV file if exists
            if (!empty($originalData['cv_url'])) {
                $this->deleteFile($originalData['cv_url']);
                $erasedTypes[] = 'cv_file';
            }

            // 4. Erase interview data
            foreach ($candidate->interviews as $interview) {
                $this->eraseInterviewMedia($interview);
            }
            $erasedTypes[] = 'interview_media';

            // 5. Erase transcripts
            DB::table('interview_responses')
                ->whereIn('interview_id', $candidate->interviews->pluck('id'))
                ->update([
                    'transcript' => '[SILINDI]',
                    'video_segment_url' => null,
                ]);
            $erasedTypes[] = 'transcripts';

            // 6. Log the erasure
            AuditLog::logErasure(
                Candidate::class,
                $candidate->id,
                $reason,
                $originalData
            );

            DB::commit();

            Log::info('Candidate data erased', [
                'candidate_id' => $candidate->id,
                'reason' => $reason,
                'erased_types' => $erasedTypes,
            ]);

            return [
                'success' => true,
                'erased_types' => $erasedTypes,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Data erasure failed', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function eraseInterviewMedia(Interview $interview): void
    {
        // Delete video file
        if ($interview->video_url) {
            $this->deleteFile($interview->video_url);
        }

        // Delete response video segments
        foreach ($interview->responses as $response) {
            if ($response->video_segment_url) {
                $this->deleteFile($response->video_segment_url);
            }
        }

        $interview->update([
            'video_url' => null,
            'media_erased' => true,
            'media_erased_at' => now(),
        ]);
    }

    public function processErasureRequest(DataErasureRequest $request): array
    {
        $request->markProcessing();

        try {
            $result = $this->eraseCandidate(
                $request->candidate,
                $request->request_type
            );

            if ($result['success']) {
                $request->markCompleted($result['erased_types']);
            } else {
                $request->markFailed($result['error']);
            }

            return $result;

        } catch (\Exception $e) {
            $request->markFailed($e->getMessage());
            throw $e;
        }
    }

    protected function deleteFile(string $path): bool
    {
        try {
            // Extract relative path from URL
            $relativePath = parse_url($path, PHP_URL_PATH);
            $relativePath = ltrim($relativePath, '/storage/');

            if (Storage::disk('public')->exists($relativePath)) {
                return Storage::disk('public')->delete($relativePath);
            }

            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to delete file', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
