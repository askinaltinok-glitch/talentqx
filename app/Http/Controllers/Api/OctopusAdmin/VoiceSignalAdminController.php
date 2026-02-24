<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\VoiceBehaviorProfile;
use App\Models\VoiceBehavioralSignal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceSignalAdminController extends Controller
{
    /**
     * GET /v1/octopus/admin/voice-signals
     *
     * Paginated list of candidates with voice behavior profiles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = VoiceBehaviorProfile::query()
            ->with(['poolCandidate:id,first_name,last_name,nationality,rank'])
            ->whereNotNull('overall_voice_score')
            ->orderByDesc('created_at');

        // Optional minimum score filter
        if ($request->filled('min_score')) {
            $query->where('overall_voice_score', '>=', (float) $request->input('min_score'));
        }

        // Optional date range
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to') . ' 23:59:59');
        }

        $profiles = $query->paginate($request->input('per_page', 20));

        $profiles->getCollection()->transform(function ($profile) {
            $candidate = $profile->poolCandidate;
            return [
                'id' => $profile->id,
                'candidate_name' => $candidate
                    ? trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? ''))
                    : 'Unknown',
                'candidate_id' => $profile->pool_candidate_id,
                'nationality' => $candidate->nationality ?? null,
                'rank' => $candidate->rank ?? null,
                'overall_voice_score' => $profile->overall_voice_score,
                'indices' => $profile->getIndicesArray(),
                'interview_id' => $profile->form_interview_id,
                'computed_at' => $profile->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $profiles,
        ]);
    }

    /**
     * GET /v1/octopus/admin/voice-signals/{profileId}
     *
     * Detail view: per-question signals + behavioral indices + computation meta.
     */
    public function show(string $profileId): JsonResponse
    {
        $profile = VoiceBehaviorProfile::with(['poolCandidate:id,first_name,last_name,nationality,rank'])
            ->findOrFail($profileId);

        $signals = VoiceBehavioralSignal::where('form_interview_id', $profile->form_interview_id)
            ->orderBy('question_slot')
            ->get()
            ->map(fn ($s) => [
                'question_slot' => $s->question_slot,
                'utterance_count' => $s->utterance_count,
                'total_word_count' => $s->total_word_count,
                'total_duration_s' => $s->total_duration_s,
                'avg_confidence' => $s->avg_confidence,
                'min_confidence' => $s->min_confidence,
                'avg_wpm' => $s->avg_wpm,
                'total_pause_count' => $s->total_pause_count,
                'total_long_pause_count' => $s->total_long_pause_count,
                'total_filler_count' => $s->total_filler_count,
                'avg_filler_ratio' => $s->avg_filler_ratio,
            ]);

        $candidate = $profile->poolCandidate;

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'id' => $profile->id,
                    'candidate_name' => $candidate
                        ? trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? ''))
                        : 'Unknown',
                    'candidate_id' => $profile->pool_candidate_id,
                    'nationality' => $candidate->nationality ?? null,
                    'rank' => $candidate->rank ?? null,
                    'interview_id' => $profile->form_interview_id,
                    'overall_voice_score' => $profile->overall_voice_score,
                    'indices' => $profile->getIndicesArray(),
                    'computation_meta' => $profile->computation_meta,
                    'computed_at' => $profile->created_at->toIso8601String(),
                ],
                'per_question_signals' => $signals,
            ],
        ]);
    }
}
