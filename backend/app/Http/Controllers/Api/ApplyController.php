<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Job;
use App\Models\AuditLog;
use App\Models\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApplyController extends Controller
{
    /**
     * GET /api/v1/apply/{companySlug}/{branchSlug}/{roleCode}
     * Returns job post data for the apply page.
     */
    public function show(string $companySlug, string $branchSlug, string $roleCode): JsonResponse
    {
        // Bypass tenant scope for public access
        return TenantScope::bypass(function () use ($companySlug, $branchSlug, $roleCode) {
            // Find company
            $company = Company::where('slug', $companySlug)->first();
            if (!$company) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'Şirket bulunamadı.',
                ], 404);
            }

            // Check subscription
            if (!$company->isSubscriptionActive()) {
                return response()->json([
                    'error' => 'subscription_expired',
                    'message' => 'Bu şirketin aboneliği sona ermiş.',
                ], 403);
            }

            // Find branch
            $branch = Branch::where('company_id', $company->id)
                ->where('slug', $branchSlug)
                ->where('is_active', true)
                ->first();

            if (!$branch) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'Şube bulunamadı.',
                ], 404);
            }

            // Find active job post
            $job = Job::where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->where('role_code', strtoupper($roleCode))
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('closes_at')
                      ->orWhere('closes_at', '>', now());
                })
                ->first();

            if (!$job) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'Bu pozisyon için aktif ilan bulunamadı.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'company' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'slug' => $company->slug,
                        'logo_url' => $company->logo_url,
                    ],
                    'branch' => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'slug' => $branch->slug,
                        'address' => $branch->full_address,
                        'city' => $branch->city,
                    ],
                    'job' => [
                        'id' => $job->id,
                        'title' => $job->title,
                        'role_code' => $job->role_code,
                        'description' => $job->description,
                        'location' => $job->location ?? $branch->full_address,
                        'employment_type' => $job->employment_type,
                    ],
                ],
            ]);
        });
    }

    /**
     * POST /api/v1/apply/{companySlug}/{branchSlug}/{roleCode}
     * Submit a job application.
     */
    public function submit(Request $request, string $companySlug, string $branchSlug, string $roleCode): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'consent_given' => 'required|boolean|accepted',
            'source' => 'nullable|string|max:100',
            'referrer_name' => 'nullable|string|max:255',
        ], [
            'first_name.required' => 'Ad alanı zorunludur.',
            'last_name.required' => 'Soyad alanı zorunludur.',
            'email.required' => 'E-posta alanı zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'phone.required' => 'Telefon numarası zorunludur.',
            'consent_given.required' => 'KVKK onayı zorunludur.',
            'consent_given.accepted' => 'KVKK metnini onaylamanız gerekmektedir.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'validation_failed',
                'message' => 'Lütfen tüm alanları doğru doldurunuz.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Bypass tenant scope for public access
        return TenantScope::bypass(function () use ($request, $companySlug, $branchSlug, $roleCode) {
            // Find company
            $company = Company::where('slug', $companySlug)->first();
            if (!$company) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'Şirket bulunamadı.',
                ], 404);
            }

            // Find branch
            $branch = Branch::where('company_id', $company->id)
                ->where('slug', $branchSlug)
                ->where('is_active', true)
                ->first();

            if (!$branch) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'Şube bulunamadı.',
                ], 404);
            }

            // Find active job post
            $job = Job::where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->where('role_code', strtoupper($roleCode))
                ->where('status', 'active')
                ->first();

            if (!$job) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'Bu pozisyon için aktif ilan bulunamadı.',
                ], 404);
            }

            // Check for duplicate application (same email + job)
            $existingCandidate = Candidate::where('job_id', $job->id)
                ->where('email', $request->email)
                ->first();

            if ($existingCandidate) {
                return response()->json([
                    'error' => 'duplicate_application',
                    'message' => 'Bu e-posta adresi ile daha önce başvuru yapılmış.',
                    'data' => [
                        'candidate_id' => $existingCandidate->id,
                        'applied_at' => $existingCandidate->created_at->toIso8601String(),
                    ],
                ], 409);
            }

            DB::beginTransaction();
            try {
                // Create candidate (application)
                $candidate = Candidate::create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'job_id' => $job->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $this->normalizePhone($request->phone),
                    'source' => $request->source ?? 'qr_apply',
                    'referrer_name' => $request->referrer_name,
                    'status' => Candidate::STATUS_APPLIED,
                    'consent_given' => true,
                    'consent_version' => '1.0',
                    'consent_given_at' => now(),
                    'consent_ip' => $request->ip(),
                ]);

                // Log audit
                AuditLog::create([
                    'action' => 'application_submitted',
                    'entity_type' => 'candidate',
                    'entity_id' => $candidate->id,
                    'company_id' => $company->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'new_values' => [
                        'candidate_name' => $candidate->full_name,
                        'job_title' => $job->title,
                        'branch_id' => $branch->id,
                        'source' => $candidate->source,
                    ],
                ]);

                DB::commit();

                Log::info('New application submitted', [
                    'candidate_id' => $candidate->id,
                    'company' => $company->slug,
                    'branch' => $branch->slug,
                    'role_code' => $roleCode,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Başvurunuz başarıyla alındı. En kısa sürede sizinle iletişime geçeceğiz.',
                    'data' => [
                        'candidate_id' => $candidate->id,
                        'application_number' => $this->generateApplicationNumber($candidate),
                        'applied_at' => $candidate->created_at->toIso8601String(),
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Application submission failed', [
                    'error' => $e->getMessage(),
                    'company' => $companySlug,
                    'branch' => $branchSlug,
                    'role_code' => $roleCode,
                ]);

                return response()->json([
                    'error' => 'server_error',
                    'message' => 'Başvuru işlemi sırasında bir hata oluştu. Lütfen tekrar deneyiniz.',
                ], 500);
            }
        });
    }

    /**
     * Normalize phone number to standard format.
     */
    protected function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If starts with 0, assume Turkish number and add +90
        if (str_starts_with($phone, '0')) {
            $phone = '+90' . substr($phone, 1);
        }

        // If no country code, assume Turkish
        if (!str_starts_with($phone, '+')) {
            $phone = '+90' . $phone;
        }

        return $phone;
    }

    /**
     * Generate human-readable application number.
     */
    protected function generateApplicationNumber(Candidate $candidate): string
    {
        $date = $candidate->created_at->format('Ymd');
        $shortId = strtoupper(substr($candidate->id, 0, 6));
        return "APP-{$date}-{$shortId}";
    }
}
