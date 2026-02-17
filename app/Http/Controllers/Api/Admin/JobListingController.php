<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobListingController extends Controller
{
    public function index(Request $request)
    {
        $industry = $request->query('industry');
        $q = trim((string) $request->query('q', ''));
        $perPage = min(max((int) $request->query('per_page', 25), 10), 100);

        $jobs = JobListing::query()
            ->when($industry, fn ($qb) => $qb->where('industry_code', $industry))
            ->when($q !== '', fn ($qb) => $qb->where('title', 'like', "%{$q}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($jobs);
    }

    public function show(JobListing $jobListing)
    {
        return response()->json($jobListing);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'industry_code'   => ['required', 'string', 'max:40'],
            'title'           => ['required', 'string', 'max:190'],
            'company_name'    => ['nullable', 'string', 'max:190'],
            'location'        => ['nullable', 'string', 'max:190'],
            'employment_type' => ['nullable', 'string', 'max:60'],
            'description'     => ['nullable', 'string'],
            'requirements'    => ['nullable', 'string'],
        ]);

        $slugBase = Str::slug($data['title']);
        $slug = $this->uniqueSlug($slugBase);

        $job = JobListing::create([
            ...$data,
            'slug'         => $slug,
            'is_published' => false,
            'published_at' => null,
        ]);

        return response()->json($job, 201);
    }

    public function update(Request $request, JobListing $jobListing)
    {
        $data = $request->validate([
            'industry_code'   => ['sometimes', 'string', 'max:40'],
            'title'           => ['sometimes', 'string', 'max:190'],
            'company_name'    => ['sometimes', 'nullable', 'string', 'max:190'],
            'location'        => ['sometimes', 'nullable', 'string', 'max:190'],
            'employment_type' => ['sometimes', 'nullable', 'string', 'max:60'],
            'description'     => ['sometimes', 'nullable', 'string'],
            'requirements'    => ['sometimes', 'nullable', 'string'],
        ]);

        $jobListing->fill($data);
        $jobListing->save();

        return response()->json($jobListing);
    }

    public function publish(JobListing $jobListing)
    {
        $jobListing->is_published = true;
        $jobListing->published_at = now();
        $jobListing->save();

        return response()->json(['ok' => true]);
    }

    public function unpublish(JobListing $jobListing)
    {
        $jobListing->is_published = false;
        $jobListing->save();

        return response()->json(['ok' => true]);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;
        while (JobListing::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
