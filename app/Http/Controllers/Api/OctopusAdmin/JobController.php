<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JobListing::where('industry_code', 'maritime')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $val = $request->input('status');
            if ($val === 'published') {
                $query->where('is_published', true);
            } elseif ($val === 'draft') {
                $query->where('is_published', false);
            }
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $jobs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $jobs->map(fn($j) => [
                'id' => $j->id,
                'title' => $j->title,
                'company_name' => $j->company_name,
                'location' => $j->location,
                'employment_type' => $j->employment_type,
                'is_published' => $j->is_published,
                'published_at' => $j->published_at?->toIso8601String(),
                'created_at' => $j->created_at->toIso8601String(),
            ]),
            'pagination' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }
}
