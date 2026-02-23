<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaritimeInsightsController extends Controller
{
    public function insights(): JsonResponse
    {
        // Queue health
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $oldestJob = DB::table('jobs')->min('created_at');
        $oldestAgeSeconds = $oldestJob ? now()->diffInSeconds($oldestJob) : 0;

        // Invite runs (last 24h)
        $inviteRuns = DB::table('maritime_invite_runs')
            ->where('started_at', '>=', now()->subDay())
            ->selectRaw("
                COUNT(*) as total_runs,
                COALESCE(SUM(eligible_count), 0) as total_eligible,
                COALESCE(SUM(sent_count), 0) as total_sent,
                COALESCE(SUM(skipped_count), 0) as total_skipped,
                COALESCE(SUM(error_count), 0) as total_errors
            ")
            ->first();

        // Vector compute throughput (last 24h)
        $vectorComputed = DB::table('candidate_scoring_vectors')
            ->where('computed_at', '>=', now()->subDay())
            ->count();

        // Behavioral profiles computed (last 24h)
        $behavioralComputed = DB::table('behavioral_profiles')
            ->where('computed_at', '>=', now()->subDay())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'queue_health' => [
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                    'oldest_job_age_seconds' => $oldestAgeSeconds,
                ],
                'invite_runs_24h' => [
                    'total_runs' => (int) $inviteRuns->total_runs,
                    'total_eligible' => (int) $inviteRuns->total_eligible,
                    'total_sent' => (int) $inviteRuns->total_sent,
                    'total_skipped' => (int) $inviteRuns->total_skipped,
                    'total_errors' => (int) $inviteRuns->total_errors,
                ],
                'vector_throughput_24h' => $vectorComputed,
                'behavioral_throughput_24h' => $behavioralComputed,
            ],
        ]);
    }

    public function inviteRuns(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = DB::table('maritime_invite_runs')
            ->when($data['from'] ?? null, fn($q, $v) => $q->where('started_at', '>=', $v))
            ->when($data['to'] ?? null, fn($q, $v) => $q->where('started_at', '<=', $v . ' 23:59:59'))
            ->orderByDesc('started_at');

        $perPage = $data['per_page'] ?? 25;
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    public function candidateSignals(string $id): JsonResponse
    {
        $candidate = DB::table('pool_candidates')
            ->where('id', $id)
            ->where('primary_industry', 'maritime')
            ->first(['id', 'first_name', 'last_name']);

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        // Behavioral dimensions
        $behavioral = DB::table('behavioral_profiles')
            ->where('candidate_id', $id)
            ->orderByDesc('computed_at')
            ->first(['dimensions_json', 'fit_json', 'flags_json', 'confidence', 'status', 'computed_at']);

        // Scoring vector
        $vector = DB::table('candidate_scoring_vectors')
            ->where('candidate_id', $id)
            ->orderByDesc('computed_at')
            ->first([
                'technical_score', 'behavioral_score', 'reliability_score',
                'personality_score', 'english_proficiency', 'english_level',
                'composite_score', 'computed_at', 'version',
            ]);

        // English assessment (language_assessments table)
        $english = DB::table('language_assessments')
            ->where('candidate_id', $id)
            ->first(['estimated_level', 'overall_score', 'mcq_score', 'writing_score', 'interview_score', 'confidence', 'created_at']);

        // Vessel fit (from behavioral fit_json if available)
        $vesselFit = null;
        if ($behavioral && $behavioral->fit_json) {
            $vesselFit = json_decode($behavioral->fit_json, true);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'candidate' => [
                    'id' => $candidate->id,
                    'name' => trim($candidate->first_name . ' ' . $candidate->last_name),
                ],
                'behavioral' => $behavioral ? [
                    'dimensions' => json_decode($behavioral->dimensions_json, true),
                    'flags' => json_decode($behavioral->flags_json, true),
                    'confidence' => $behavioral->confidence,
                    'status' => $behavioral->status,
                    'computed_at' => $behavioral->computed_at,
                ] : null,
                'scoring_vector' => $vector ? [
                    'technical' => (float) $vector->technical_score,
                    'behavioral' => (float) $vector->behavioral_score,
                    'reliability' => (float) $vector->reliability_score,
                    'personality' => (float) $vector->personality_score,
                    'english' => (float) $vector->english_proficiency,
                    'english_level' => $vector->english_level,
                    'composite' => (float) $vector->composite_score,
                    'version' => $vector->version,
                    'computed_at' => $vector->computed_at,
                ] : null,
                'english_assessment' => $english ? [
                    'overall_cefr' => $english->estimated_level,
                    'overall_score' => $english->overall_score ? (float) $english->overall_score : null,
                    'mcq_score' => $english->mcq_score ? (float) $english->mcq_score : null,
                    'writing_score' => $english->writing_score ? (float) $english->writing_score : null,
                    'interview_score' => $english->interview_score ? (float) $english->interview_score : null,
                    'confidence' => $english->confidence,
                    'assessed_at' => $english->created_at,
                ] : null,
                'vessel_fit' => $vesselFit,
            ],
        ]);
    }
}
