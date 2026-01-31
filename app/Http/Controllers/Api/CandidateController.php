<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Candidate::with(['job', 'latestInterview.analysis'])
            ->whereHas('job', function ($q) use ($request) {
                $q->where('company_id', $request->user()->company_id);
            });

        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('has_red_flags') && $request->boolean('has_red_flags')) {
            $query->whereHas('latestInterview.analysis', function ($q) {
                $q->whereJsonContains('red_flag_analysis->flags_detected', true);
            });
        }

        if ($request->has('min_score')) {
            $query->whereHas('latestInterview.analysis', function ($q) use ($request) {
                $q->where('overall_score', '>=', $request->min_score);
            });
        }

        if ($request->has('max_score')) {
            $query->whereHas('latestInterview.analysis', function ($q) use ($request) {
                $q->where('overall_score', '<=', $request->max_score);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        if ($sortField === 'score') {
            $query->leftJoin('interviews', function ($join) {
                $join->on('candidates.id', '=', 'interviews.candidate_id')
                    ->whereRaw('interviews.id = (SELECT id FROM interviews WHERE candidate_id = candidates.id ORDER BY created_at DESC LIMIT 1)');
            })
                ->leftJoin('interview_analyses', 'interviews.id', '=', 'interview_analyses.interview_id')
                ->orderBy('interview_analyses.overall_score', $sortOrder)
                ->select('candidates.*');
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        $candidates = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $candidates->map(fn($candidate) => [
                'id' => $candidate->id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'status' => $candidate->status,
                'cv_match_score' => $candidate->cv_match_score,
                'job' => [
                    'id' => $candidate->job->id,
                    'title' => $candidate->job->title,
                ],
                'interview' => $candidate->latestInterview ? [
                    'id' => $candidate->latestInterview->id,
                    'status' => $candidate->latestInterview->status,
                    'completed_at' => $candidate->latestInterview->completed_at,
                ] : null,
                'analysis' => $candidate->latestInterview?->analysis ? [
                    'overall_score' => $candidate->latestInterview->analysis->overall_score,
                    'recommendation' => $candidate->latestInterview->analysis->getRecommendation(),
                    'confidence_percent' => $candidate->latestInterview->analysis->getConfidencePercent(),
                    'has_red_flags' => $candidate->latestInterview->analysis->hasRedFlags(),
                ] : null,
                'created_at' => $candidate->created_at,
            ]),
            'meta' => [
                'current_page' => $candidates->currentPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
                'last_page' => $candidates->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|uuid|exists:job_postings,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:100',
            'referrer_name' => 'nullable|string|max:255',
        ]);

        $job = Job::where('company_id', $request->user()->company_id)
            ->findOrFail($validated['job_id']);

        $candidate = Candidate::create([
            'job_id' => $job->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'source' => $validated['source'] ?? 'manual',
            'referrer_name' => $validated['referrer_name'] ?? null,
            'status' => Candidate::STATUS_APPLIED,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $candidate->id,
                'full_name' => $candidate->full_name,
                'email' => $candidate->email,
                'status' => $candidate->status,
                'created_at' => $candidate->created_at,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $candidate = Candidate::with(['job.template', 'latestInterview.analysis', 'latestInterview.responses.question'])
            ->whereHas('job', function ($q) use ($request) {
                $q->where('company_id', $request->user()->company_id);
            })
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $candidate->id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'status' => $candidate->status,
                'cv_url' => $candidate->cv_url,
                'cv_match_score' => $candidate->cv_match_score,
                'cv_parsed_data' => $candidate->cv_parsed_data,
                'source' => $candidate->source,
                'consent_given' => $candidate->consent_given,
                'consent_version' => $candidate->consent_version,
                'job' => [
                    'id' => $candidate->job->id,
                    'title' => $candidate->job->title,
                ],
                'interview' => $candidate->latestInterview ? [
                    'id' => $candidate->latestInterview->id,
                    'status' => $candidate->latestInterview->status,
                    'video_url' => $candidate->latestInterview->video_url,
                    'completed_at' => $candidate->latestInterview->completed_at,
                    'responses' => $candidate->latestInterview->responses->map(fn($r) => [
                        'id' => $r->id,
                        'question' => [
                            'id' => $r->question->id,
                            'order' => $r->question->question_order,
                            'text' => $r->question->question_text,
                            'competency_code' => $r->question->competency_code,
                        ],
                        'transcript' => $r->transcript,
                        'duration_seconds' => $r->duration_seconds,
                        'video_segment_url' => $r->video_segment_url,
                    ]),
                ] : null,
                'analysis' => $candidate->latestInterview?->analysis ? [
                    'id' => $candidate->latestInterview->analysis->id,
                    'overall_score' => $candidate->latestInterview->analysis->overall_score,
                    'competency_scores' => $candidate->latestInterview->analysis->competency_scores,
                    'behavior_analysis' => $candidate->latestInterview->analysis->behavior_analysis,
                    'red_flag_analysis' => $candidate->latestInterview->analysis->red_flag_analysis,
                    'culture_fit' => $candidate->latestInterview->analysis->culture_fit,
                    'decision_snapshot' => $candidate->latestInterview->analysis->decision_snapshot,
                    'question_analyses' => $candidate->latestInterview->analysis->question_analyses,
                ] : null,
                'internal_notes' => $candidate->internal_notes,
                'tags' => $candidate->tags,
                'created_at' => $candidate->created_at,
            ],
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:applied,interview_pending,interview_completed,under_review,shortlisted,hired,rejected',
            'note' => 'nullable|string',
        ]);

        $candidate = Candidate::whereHas('job', function ($q) use ($request) {
            $q->where('company_id', $request->user()->company_id);
        })->findOrFail($id);

        $candidate->updateStatus(
            $validated['status'],
            $validated['note'] ?? null,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $candidate->id,
                'status' => $candidate->status,
                'status_changed_at' => $candidate->status_changed_at,
            ],
        ]);
    }

    public function uploadCv(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $candidate = Candidate::whereHas('job', function ($q) use ($request) {
            $q->where('company_id', $request->user()->company_id);
        })->findOrFail($id);

        $path = $request->file('cv')->store('cvs/' . $candidate->id, 's3');

        $candidate->update([
            'cv_url' => Storage::disk('s3')->url($path),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'cv_url' => $candidate->cv_url,
            ],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $candidate = Candidate::whereHas('job', function ($q) use ($request) {
            $q->where('company_id', $request->user()->company_id);
        })->findOrFail($id);

        if ($candidate->cv_url) {
            Storage::disk('s3')->delete($candidate->cv_url);
        }

        foreach ($candidate->interviews as $interview) {
            if ($interview->video_url) {
                Storage::disk('s3')->delete($interview->video_url);
            }
        }

        $candidate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Aday ve tum iliskili veriler silindi.',
        ]);
    }
}
