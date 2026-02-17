<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobListingController extends Controller
{
    public function index(Request $request)
    {
        $industry = $request->query('industry', 'general');
        $q = trim((string) $request->query('q', ''));
        $perPage = min(max((int) $request->query('per_page', 20), 10), 50);

        $jobs = JobListing::query()
            ->where('industry_code', $industry)
            ->where('is_published', true)
            ->when($q !== '', fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('company_name', 'like', "%{$q}%")
                  ->orWhere('location', 'like', "%{$q}%");
            }))
            ->orderByDesc('published_at')
            ->paginate($perPage);

        return response()->json($jobs);
    }

    public function show(string $slug)
    {
        $job = JobListing::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return response()->json($job);
    }
}
