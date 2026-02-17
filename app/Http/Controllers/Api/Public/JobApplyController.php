<?php

namespace App\Http\Controllers\Api\Public;

use App\Config\MaritimeRole;
use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\JobApplication;
use App\Models\JobApplicationFile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobApplyController extends Controller
{

    public function store(Request $request, JobListing $jobListing)
    {
        if (!$jobListing->is_published) {
            return response()->json(['message' => 'Job is not published.'], 404);
        }

        $isMaritime = $jobListing->industry_code === 'maritime';

        $rules = [
            'full_name'       => ['required', 'string', 'max:120'],
            'email'           => ['nullable', 'email', 'max:190'],
            'phone'           => ['nullable', 'string', 'max:40'],
            'country_code'    => ['nullable', 'string', 'size:2'],
            'city'            => ['nullable', 'string', 'max:120'],
            'role_code'       => [$isMaritime ? 'required' : 'nullable', 'string', 'max:60'],
            'consent_terms'   => ['required', 'boolean'],
            'consent_contact' => ['nullable', 'boolean'],
            'cv'              => ['nullable', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png'],
            'files.*'         => ['nullable', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png'],
            'file_types.*'    => ['nullable', 'string', 'max:40'],
        ];

        $data = $request->validate($rules);

        if (!$data['consent_terms']) {
            return response()->json(['message' => 'Consent is required.'], 422);
        }

        // Auto-resolve department from role_code
        $roleCode = strtolower(trim((string)($data['role_code'] ?? ''))) ?: null;
        $department = null;

        if ($roleCode) {
            $normalized = MaritimeRole::normalize($roleCode);
            if (!$normalized) {
                return response()->json(['message' => 'Invalid role_code.'], 422);
            }
            $roleCode = $normalized;
            $department = MaritimeRole::departmentFor($roleCode);
        }

        if ($isMaritime && (!$roleCode || !$department)) {
            return response()->json(['message' => 'Role is required for maritime applications.'], 422);
        }

        $ip = (string) $request->ip();
        $ua = (string) $request->userAgent();

        $app = JobApplication::create([
            'job_listing_id'  => $jobListing->id,
            'full_name'       => $data['full_name'],
            'email'           => $data['email'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'country_code'    => $data['country_code'] ?? null,
            'city'            => $data['city'] ?? null,
            'role_code'       => $roleCode,
            'department'      => $department,
            'source'          => 'job_apply',
            'status'          => 'new',
            'consent_terms'   => (bool) $data['consent_terms'],
            'consent_contact' => (bool) ($data['consent_contact'] ?? false),
            'ip_hash'         => $ip ? hash('sha256', $ip) : null,
            'ua_hash'         => $ua ? hash('sha256', $ua) : null,
        ]);

        // Save CV
        if ($request->hasFile('cv')) {
            $this->storeFile($app, $request->file('cv'), 'cv');
        }

        // Save extra files
        $files = $request->file('files', []);
        $types = $request->input('file_types', []);

        foreach ($files as $i => $file) {
            $t = $types[$i] ?? 'other';
            $safeType = preg_replace('/[^a-z0-9_]/i', '', (string) $t) ?: 'other';
            $this->storeFile($app, $file, $safeType);
        }

        return response()->json([
            'ok' => true,
            'application_id' => $app->id,
        ], 201);
    }

    private function storeFile(JobApplication $app, $file, string $type): void
    {
        $dir = 'job-applications/' . $app->id;
        $name = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($dir, $name, ['disk' => config('filesystems.default')]);

        JobApplicationFile::create([
            'job_application_id' => $app->id,
            'type'               => $type,
            'path'               => $path,
            'original_name'      => $file->getClientOriginalName(),
            'mime'               => $file->getClientMimeType(),
            'size'               => $file->getSize(),
        ]);
    }
}
