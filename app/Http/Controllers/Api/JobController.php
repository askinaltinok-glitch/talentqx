<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobPosition;
use App\Models\PositionTemplate;
use App\Services\Interview\QuestionGenerator;
use App\Services\QRCode\QRCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobController extends Controller
{
    public function __construct(
        private QuestionGenerator $questionGenerator,
        private QRCodeService $qrCodeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Job::with(['template', 'jobPosition.subdomain.domain', 'creator', 'company'])
            ->withCount(['candidates', 'interviews as completed_interviews_count' => function ($q) {
                $q->where('status', 'completed');
            }]);

        // Platform admin sees all, regular users see only their company
        if (!$user->is_platform_admin) {
            $query->where('company_id', $user->company_id);
        } elseif ($request->has('company_id')) {
            // Platform admin can filter by company
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $jobs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $jobs->map(fn($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'slug' => $job->slug,
                'status' => $job->status,
                'location' => $job->location,
                'company' => $job->company ? [
                    'id' => $job->company->id,
                    'name' => $job->company->name,
                ] : null,
                'template' => $job->template ? [
                    'id' => $job->template->id,
                    'name' => $job->template->name,
                ] : null,
                'job_position' => $job->jobPosition ? [
                    'id' => $job->jobPosition->id,
                    'code' => $job->jobPosition->code,
                    'name' => $job->jobPosition->name_tr,
                    'subdomain' => $job->jobPosition->subdomain ? [
                        'id' => $job->jobPosition->subdomain->id,
                        'name' => $job->jobPosition->subdomain->name_tr,
                    ] : null,
                    'domain' => $job->jobPosition->subdomain?->domain ? [
                        'id' => $job->jobPosition->subdomain->domain->id,
                        'name' => $job->jobPosition->subdomain->domain->name_tr,
                    ] : null,
                ] : null,
                'candidates_count' => $job->candidates_count,
                'interviews_completed' => $job->completed_interviews_count,
                'published_at' => $job->published_at,
                'closes_at' => $job->closes_at,
            ]),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'template_id' => 'nullable|uuid|exists:position_templates,id',
            'job_position_id' => 'nullable|uuid|exists:job_positions,id',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'role_code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full_time,part_time,contract',
            'experience_years' => 'nullable|integer|min:0',
            'competencies' => 'nullable|array',
            'interview_settings' => 'nullable|array',
            'closes_at' => 'nullable|date|after:today',
        ]);

        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;

        while (Job::where('company_id', $request->user()->company_id)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        // If using taxonomy position, populate competencies from position
        $competencies = $validated['competencies'] ?? null;
        $experienceYears = $validated['experience_years'] ?? 0;

        if (!empty($validated['job_position_id'])) {
            $position = JobPosition::with(['competencies', 'archetype'])->find($validated['job_position_id']);
            if ($position) {
                // Build competencies array from position's competencies
                $competencies = $position->competencies->map(fn($c) => [
                    'code' => $c->code,
                    'name' => $c->name_tr,
                    'weight' => $c->pivot->weight,
                    'description' => $c->description_tr,
                    'is_critical' => $c->pivot->is_critical,
                ])->toArray();

                // Use position's experience range
                $experienceYears = $position->experience_min_years;
            }
        }

        $job = Job::create([
            'company_id' => $request->user()->company_id,
            'branch_id' => $validated['branch_id'] ?? null,
            'created_by' => $request->user()->id,
            'template_id' => $validated['template_id'] ?? null,
            'job_position_id' => $validated['job_position_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $slug,
            'role_code' => isset($validated['role_code']) ? strtoupper($validated['role_code']) : null,
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'employment_type' => $validated['employment_type'] ?? 'full_time',
            'experience_years' => $experienceYears,
            'competencies' => $competencies,
            'interview_settings' => $validated['interview_settings'] ?? null,
            'closes_at' => $validated['closes_at'] ?? null,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'title' => $job->title,
                'slug' => $job->slug,
                'role_code' => $job->role_code,
                'status' => $job->status,
                'apply_url' => $job->apply_url,
                'created_at' => $job->created_at,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $query = Job::with(['template', 'jobPosition.subdomain.domain', 'jobPosition.archetype', 'questions', 'creator', 'branch', 'company']);

        // Platform admin can see any job
        if (!$user->is_platform_admin) {
            $query->where('company_id', $user->company_id);
        }

        $job = $query->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'title' => $job->title,
                'slug' => $job->slug,
                'role_code' => $job->role_code,
                'description' => $job->description,
                'location' => $job->location,
                'employment_type' => $job->employment_type,
                'experience_years' => $job->experience_years,
                'status' => $job->status,
                'template' => $job->template ? [
                    'id' => $job->template->id,
                    'name' => $job->template->name,
                    'slug' => $job->template->slug,
                ] : null,
                'job_position' => $job->jobPosition ? [
                    'id' => $job->jobPosition->id,
                    'code' => $job->jobPosition->code,
                    'name' => $job->jobPosition->name_tr,
                    'name_en' => $job->jobPosition->name_en,
                    'subdomain' => $job->jobPosition->subdomain ? [
                        'id' => $job->jobPosition->subdomain->id,
                        'name' => $job->jobPosition->subdomain->name_tr,
                    ] : null,
                    'domain' => $job->jobPosition->subdomain?->domain ? [
                        'id' => $job->jobPosition->subdomain->domain->id,
                        'name' => $job->jobPosition->subdomain->domain->name_tr,
                    ] : null,
                    'archetype' => $job->jobPosition->archetype ? [
                        'code' => $job->jobPosition->archetype->code,
                        'name' => $job->jobPosition->archetype->name_tr,
                        'level' => $job->jobPosition->archetype->level,
                    ] : null,
                    'experience_min_years' => $job->jobPosition->experience_min_years,
                    'experience_max_years' => $job->jobPosition->experience_max_years,
                ] : null,
                'branch' => $job->branch ? [
                    'id' => $job->branch->id,
                    'name' => $job->branch->name,
                    'slug' => $job->branch->slug,
                ] : null,
                'competencies' => $job->getEffectiveCompetencies(),
                'red_flags' => $job->getEffectiveRedFlags(),
                'question_rules' => $job->getEffectiveQuestionRules(),
                'scoring_rubric' => $job->getEffectiveScoringRubric(),
                'interview_settings' => $job->interview_settings,
                'questions' => $job->questions->map(fn($q) => [
                    'id' => $q->id,
                    'order' => $q->question_order,
                    'type' => $q->question_type,
                    'text' => $q->question_text,
                    'competency_code' => $q->competency_code,
                    'time_limit_seconds' => $q->time_limit_seconds,
                ]),
                'apply_url' => $job->apply_url,
                'qr_code_url' => $job->qr_file_path ? $this->qrCodeService->getPublicUrl($job) : null,
                'published_at' => $job->published_at,
                'closes_at' => $job->closes_at,
                'created_at' => $job->created_at,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Job::query();

        if (!$user->is_platform_admin) {
            $query->where('company_id', $user->company_id);
        }

        $job = $query->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full_time,part_time,contract',
            'experience_years' => 'nullable|integer|min:0',
            'competencies' => 'nullable|array',
            'interview_settings' => 'nullable|array',
            'closes_at' => 'nullable|date',
        ]);

        $job->update($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'title' => $job->title,
                'status' => $job->status,
                'updated_at' => $job->updated_at,
            ],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Job::query();

        if (!$user->is_platform_admin) {
            $query->where('company_id', $user->company_id);
        }

        $job = $query->findOrFail($id);

        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Is ilani silindi.',
        ]);
    }

    public function publish(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Job::query();

        if (!$user->is_platform_admin) {
            $query->where('company_id', $user->company_id);
        }

        $job = $query->findOrFail($id);

        if ($job->questions()->count() === 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NO_QUESTIONS',
                    'message' => 'Is ilanini yayinlamadan once sorular olusturmalisiniz.',
                ],
            ], 422);
        }

        $job->update([
            'status' => 'active',
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'status' => $job->status,
                'published_at' => $job->published_at,
            ],
        ]);
    }

    public function generateQuestions(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Job::with('template');

        if (!$user->is_platform_admin) {
            $query->where('company_id', $user->company_id);
        }

        $job = $query->findOrFail($id);

        $regenerate = $request->boolean('regenerate', false);

        try {
            $questions = $this->questionGenerator->generateForJob($job, $regenerate);

            return response()->json([
                'success' => true,
                'data' => [
                    'questions' => collect($questions)->map(fn($q) => [
                        'id' => $q->id ?? $q['id'] ?? null,
                        'question_order' => $q->question_order ?? $q['question_order'] ?? null,
                        'question_type' => $q->question_type ?? $q['question_type'] ?? null,
                        'question_text' => $q->question_text ?? $q['question_text'] ?? null,
                        'competency_code' => $q->competency_code ?? $q['competency_code'] ?? null,
                        'ideal_answer_points' => $q->ideal_answer_points ?? $q['ideal_answer_points'] ?? [],
                        'time_limit_seconds' => $q->time_limit_seconds ?? $q['time_limit_seconds'] ?? 180,
                    ]),
                    'generated_at' => now(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GENERATION_FAILED',
                    'message' => 'Soru uretimi basarisiz oldu: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }

    public function questions(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Job::with('questions');

        if (!$user->is_platform_admin) {
            $query->where('company_id', $user->company_id);
        }

        $job = $query->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job->questions->map(fn($q) => [
                'id' => $q->id,
                'order' => $q->question_order,
                'type' => $q->question_type,
                'text' => $q->question_text,
                'competency_code' => $q->competency_code,
                'ideal_answer_points' => $q->ideal_answer_points,
                'time_limit_seconds' => $q->time_limit_seconds,
                'is_required' => $q->is_required,
            ]),
        ]);
    }

    /**
     * Generate or regenerate QR code for a job post.
     */
    public function generateQRCode(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Job::with(['company', 'branch']);

        if (!$user->is_platform_admin) {
            $query->where('company_id', $user->company_id);
        }

        $job = $query->findOrFail($id);

        $path = $this->qrCodeService->generateForJob($job);

        $job->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'apply_url' => $job->apply_url,
                'public_token' => $job->public_token,
                'qr_code_url' => $this->qrCodeService->getPublicUrl($job),
                'qr_file_path' => $job->qr_file_path,
            ],
        ]);
    }

    /**
     * Get QR code as base64 for preview (no storage).
     */
    public function previewQRCode(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Job::with(['company', 'branch']);

        if (!$user->is_platform_admin) {
            $query->where('company_id', $user->company_id);
        }

        $job = $query->findOrFail($id);

        $applyUrl = $this->qrCodeService->buildApplyUrl($job);

        try {
            $base64 = $this->qrCodeService->generateBase64($applyUrl, 400);
        } catch (\Exception $e) {
            // Fallback: return URL without QR image
            return response()->json([
                'success' => true,
                'data' => [
                    'apply_url' => $applyUrl,
                    'public_token' => $job->public_token,
                    'qr_base64' => null,
                    'message' => 'QR kütüphanesi yüklü değil.',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'apply_url' => $applyUrl,
                'public_token' => $job->public_token,
                'qr_base64' => $base64,
            ],
        ]);
    }

    /**
     * Get QR info for modal display.
     */
    public function qrInfo(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = Job::with(['company', 'branch']);

        if (!$user->is_platform_admin) {
            $query->where('company_id', $user->company_id);
        }

        $job = $query->findOrFail($id);

        // Ensure public token exists
        if (!$job->public_token) {
            $job->regeneratePublicToken();
            $job->refresh();
        }

        $applyUrl = $this->qrCodeService->buildApplyUrl($job);

        $data = [
            'job_id' => $job->id,
            'job_title' => $job->title,
            'company_name' => $job->company->name ?? 'TalentQX',
            'apply_url' => $applyUrl,
            'public_token' => $job->public_token,
            'qr_code_url' => $job->qr_file_path ? $this->qrCodeService->getPublicUrl($job) : null,
        ];

        // Try to generate base64 preview
        try {
            $data['qr_base64'] = $this->qrCodeService->generateBase64($applyUrl, 400);
        } catch (\Exception $e) {
            $data['qr_base64'] = null;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
