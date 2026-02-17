<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobApplicantsController extends Controller
{
    public function index(Request $request, JobListing $jobListing)
    {
        $perPage = min(max((int) $request->query('per_page', 25), 10), 100);

        $apps = $jobListing->applications()
            ->with('files')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($apps);
    }
}
