<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalCandidateController extends Controller
{
    /**
     * List candidates for the authenticated company's jobs.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Şirket bilgisi bulunamadı',
            ], 403);
        }

        $query = Candidate::where('company_id', $companyId)
            ->with(['job:id,title,status', 'latestInterview' => function ($q) {
                $q->select('interviews.id', 'interviews.candidate_id', 'interviews.status', 'interviews.completed_at');
            }]);

        // Filters
        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        $data = $paginated->getCollection()->map(function ($candidate) {
            $interview = $candidate->latestInterview;
            return [
                'id' => $candidate->id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'status' => $candidate->status,
                'job_title' => $candidate->job?->title,
                'job_id' => $candidate->job_id,
                'interview_status' => $interview?->status,
                'interview_score' => $interview?->final_score,
                'applied_at' => $candidate->created_at->toIso8601String(),
                'created_at' => $candidate->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Show single candidate detail with interviews.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $candidate = Candidate::where('company_id', $companyId)
            ->with([
                'job:id,title,status,slug',
                'interviews' => function ($q) {
                    $q->select('id', 'candidate_id', 'status', 'completed_at', 'created_at')
                      ->orderByDesc('created_at');
                },
            ])
            ->find($id);

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Aday bulunamadı',
            ], 404);
        }

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
                'source' => $candidate->source,
                'tags' => $candidate->tags,
                'job' => $candidate->job ? [
                    'id' => $candidate->job->id,
                    'title' => $candidate->job->title,
                    'status' => $candidate->job->status,
                ] : null,
                'interviews' => $candidate->interviews->map(function ($interview) {
                    return [
                        'id' => $interview->id,
                        'status' => $interview->status,
                        'final_score' => $interview->final_score ?? null,
                        'decision' => $interview->decision ?? null,
                        'decision_reason' => $interview->decision_reason ?? null,
                        'completed_at' => $interview->completed_at?->toIso8601String(),
                        'created_at' => $interview->created_at->toIso8601String(),
                    ];
                }),
                'applied_at' => $candidate->created_at->toIso8601String(),
                'created_at' => $candidate->created_at->toIso8601String(),
            ],
        ]);
    }
}
