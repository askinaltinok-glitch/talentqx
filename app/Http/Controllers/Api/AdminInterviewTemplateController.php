<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterviewTemplate;
use App\Models\TemplateAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Admin CRUD for Interview Templates
 *
 * Requires: auth:sanctum + platform.admin middleware
 * Rate limit: 120/min read, 30/min write
 */
class AdminInterviewTemplateController extends Controller
{
    /**
     * GET /v1/admin/interview-templates
     * List all templates with optional filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = InterviewTemplate::query();

        // Filters
        if ($request->filled('version')) {
            $query->where('version', $request->version);
        }
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }
        if ($request->filled('position_code')) {
            $query->where('position_code', $request->position_code);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('position_code', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['title', 'position_code', 'version', 'language', 'is_active', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 20), 100);
        $templates = $query->paginate($perPage);

        // Transform - exclude heavy template_json from list
        $templates->getCollection()->transform(function ($template) {
            return [
                'id' => $template->id,
                'version' => $template->version,
                'language' => $template->language,
                'position_code' => $template->position_code,
                'title' => $template->title,
                'is_active' => $template->is_active,
                'template_json_length' => strlen($template->template_json ?? ''),
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $templates->items(),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    /**
     * GET /v1/admin/interview-templates/{id}
     * Get single template with full template_json
     */
    public function show(string $id): JsonResponse
    {
        $template = InterviewTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Template not found',
            ], 404);
        }

        // Calculate SHA256
        $sha256 = $template->template_json ? hash('sha256', $template->template_json) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $template->id,
                'version' => $template->version,
                'language' => $template->language,
                'position_code' => $template->position_code,
                'title' => $template->title,
                'template_json' => $template->template_json,
                'template_json_sha256' => $sha256,
                'is_active' => $template->is_active,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
            ],
        ]);
    }

    /**
     * GET /v1/admin/interview-templates/{id}/audit-logs
     * Get audit history for a template
     */
    public function auditLogs(Request $request, string $id): JsonResponse
    {
        $template = InterviewTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Template not found',
            ], 404);
        }

        $limit = min((int) $request->get('limit', 20), 100);
        $logs = TemplateAuditLog::forTemplate($id, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'admin_email' => $log->admin_email,
                    'before_sha' => $log->before_sha,
                    'after_sha' => $log->after_sha,
                    'changes' => $log->changes,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at,
                ];
            }),
        ]);
    }

    /**
     * POST /v1/admin/interview-templates
     * Create new template
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'version' => 'required|string|max:10',
            'language' => 'required|string|max:5',
            'position_code' => 'required|string|max:100',
            'title' => 'required|string|max:200',
            'template_json' => 'required|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate JSON with schema
        $jsonValidation = $this->validateTemplateJson($request->template_json, $request->language);
        if ($jsonValidation !== true) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_json',
                'message' => 'Template JSON validation failed',
                'validation_errors' => is_array($jsonValidation) ? $jsonValidation : ['json' => $jsonValidation],
            ], 422);
        }

        // Check unique constraint
        $exists = InterviewTemplate::where('version', $request->version)
            ->where('language', $request->language)
            ->where('position_code', $request->position_code)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => 'duplicate',
                'message' => 'A template with this version, language, and position_code already exists',
            ], 409);
        }

        $template = InterviewTemplate::create([
            'id' => (string) Str::uuid(),
            'version' => $request->version,
            'language' => $request->language,
            'position_code' => $request->position_code,
            'title' => $request->title,
            'template_json' => $request->template_json,
            'is_active' => $request->boolean('is_active', false),
        ]);

        // Audit log
        $this->logAudit('create', $template, $request, null, [
            'title' => $template->title,
            'is_active' => $template->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'data' => [
                'id' => $template->id,
                'version' => $template->version,
                'language' => $template->language,
                'position_code' => $template->position_code,
                'title' => $template->title,
                'is_active' => $template->is_active,
                'template_json_sha256' => hash('sha256', $template->template_json),
                'created_at' => $template->created_at,
            ],
        ], 201);
    }

    /**
     * PUT /v1/admin/interview-templates/{id}
     * Update template metadata and/or template_json
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $template = InterviewTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Template not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:200',
            'template_json' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            // version, language, position_code are immutable after creation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate JSON if provided
        if ($request->has('template_json')) {
            $jsonValidation = $this->validateTemplateJson($request->template_json, $template->language);
            if ($jsonValidation !== true) {
                return response()->json([
                    'success' => false,
                    'error' => 'invalid_json',
                    'message' => 'Template JSON validation failed',
                    'validation_errors' => is_array($jsonValidation) ? $jsonValidation : ['json' => $jsonValidation],
                ], 422);
            }
        }

        // Track changes
        $beforeSha = $template->template_json ? hash('sha256', $template->template_json) : null;
        $changes = [];

        // Update fields
        if ($request->has('title') && $request->title !== $template->title) {
            $changes['title'] = ['from' => $template->title, 'to' => $request->title];
            $template->title = $request->title;
        }
        if ($request->has('template_json')) {
            $newSha = hash('sha256', $request->template_json);
            if ($newSha !== $beforeSha) {
                $changes['template_json'] = ['sha_changed' => true];
                $template->template_json = $request->template_json;
            }
        }
        if ($request->has('is_active') && $request->boolean('is_active') !== $template->is_active) {
            $changes['is_active'] = ['from' => $template->is_active, 'to' => $request->boolean('is_active')];
            $template->is_active = $request->boolean('is_active');
        }

        if (empty($changes)) {
            return response()->json([
                'success' => true,
                'message' => 'No changes detected',
                'data' => [
                    'id' => $template->id,
                    'version' => $template->version,
                    'language' => $template->language,
                    'position_code' => $template->position_code,
                    'title' => $template->title,
                    'is_active' => $template->is_active,
                    'template_json_sha256' => hash('sha256', $template->template_json),
                    'updated_at' => $template->updated_at,
                ],
            ]);
        }

        $template->save();

        // Audit log
        $this->logAudit('update', $template, $request, $beforeSha, $changes);

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
            'data' => [
                'id' => $template->id,
                'version' => $template->version,
                'language' => $template->language,
                'position_code' => $template->position_code,
                'title' => $template->title,
                'is_active' => $template->is_active,
                'template_json_sha256' => hash('sha256', $template->template_json),
                'updated_at' => $template->updated_at,
            ],
        ]);
    }

    /**
     * POST /v1/admin/interview-templates/{id}/activate
     * Toggle active status
     */
    public function activate(Request $request, string $id): JsonResponse
    {
        $template = InterviewTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Template not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'message' => 'is_active field is required (true/false)',
                'errors' => $validator->errors(),
            ], 422);
        }

        $beforeSha = $template->template_json ? hash('sha256', $template->template_json) : null;
        $wasActive = $template->is_active;
        $template->is_active = $request->boolean('is_active');
        $template->save();

        // Audit log
        $action = $template->is_active ? 'activate' : 'deactivate';
        $this->logAudit($action, $template, $request, $beforeSha, [
            'is_active' => ['from' => $wasActive, 'to' => $template->is_active],
        ]);

        return response()->json([
            'success' => true,
            'message' => $template->is_active ? 'Template activated' : 'Template deactivated',
            'data' => [
                'id' => $template->id,
                'is_active' => $template->is_active,
            ],
        ]);
    }

    /**
     * POST /v1/admin/interview-templates/{id}/clone
     * Clone template with new version or language
     */
    public function clone(Request $request, string $id): JsonResponse
    {
        $template = InterviewTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Template not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'new_version' => 'sometimes|string|max:10',
            'new_language' => 'sometimes|string|max:5',
            'new_position_code' => 'sometimes|string|max:100',
            'new_title' => 'sometimes|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Determine new values
        $newVersion = $request->get('new_version', $template->version);
        $newLanguage = $request->get('new_language', $template->language);
        $newPositionCode = $request->get('new_position_code', $template->position_code);

        // At least one must be different
        if ($newVersion === $template->version &&
            $newLanguage === $template->language &&
            $newPositionCode === $template->position_code) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_clone',
                'message' => 'Clone must have at least one different field: new_version, new_language, or new_position_code',
            ], 422);
        }

        // Check unique constraint
        $exists = InterviewTemplate::where('version', $newVersion)
            ->where('language', $newLanguage)
            ->where('position_code', $newPositionCode)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => 'duplicate',
                'message' => 'A template with this version, language, and position_code already exists',
            ], 409);
        }

        // Create clone
        $newTitle = $request->get('new_title', $template->title . ' (Clone)');

        $clone = InterviewTemplate::create([
            'id' => (string) Str::uuid(),
            'version' => $newVersion,
            'language' => $newLanguage,
            'position_code' => $newPositionCode,
            'title' => $newTitle,
            'template_json' => $template->template_json,
            'is_active' => false, // Clones start inactive
        ]);

        // Audit log for clone
        $this->logAudit('clone', $clone, $request, null, [
            'cloned_from' => $template->id,
            'source_version' => $template->version,
            'source_language' => $template->language,
            'source_position_code' => $template->position_code,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template cloned successfully',
            'data' => [
                'id' => $clone->id,
                'version' => $clone->version,
                'language' => $clone->language,
                'position_code' => $clone->position_code,
                'title' => $clone->title,
                'is_active' => $clone->is_active,
                'cloned_from' => $template->id,
                'template_json_sha256' => hash('sha256', $clone->template_json),
                'created_at' => $clone->created_at,
            ],
        ], 201);
    }

    /**
     * DELETE /v1/admin/interview-templates/{id}
     * Soft delete (deactivate) or hard delete template
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $template = InterviewTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Template not found',
            ], 404);
        }

        // Check if used by any sessions
        $usageCount = DB::table('form_interviews')
            ->where('template_position_code', $template->position_code)
            ->where('version', $template->version)
            ->where('language', $template->language)
            ->count();

        if ($usageCount > 0 && !$request->boolean('force')) {
            return response()->json([
                'success' => false,
                'error' => 'in_use',
                'message' => "Template is used by {$usageCount} interview sessions. Use ?force=true to delete anyway.",
                'usage_count' => $usageCount,
            ], 409);
        }

        // Audit log before delete
        $beforeSha = $template->template_json ? hash('sha256', $template->template_json) : null;
        $this->logAudit('delete', $template, $request, $beforeSha, [
            'force' => $request->boolean('force'),
            'usage_count' => $usageCount,
        ]);

        // Hard delete
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully',
        ]);
    }

    /**
     * POST /v1/admin/interview-templates/{id}/publish
     * Safe publish: deactivate current active, activate this one (transaction)
     */
    public function publish(Request $request, string $id): JsonResponse
    {
        $template = InterviewTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Template not found',
            ], 404);
        }

        if ($template->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'already_active',
                'message' => 'Template is already active',
            ], 400);
        }

        // Validate JSON before publishing
        $jsonValidation = $this->validateTemplateJson($template->template_json, $template->language);
        if ($jsonValidation !== true) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_json',
                'message' => 'Cannot publish: template JSON validation failed',
                'validation_errors' => is_array($jsonValidation) ? $jsonValidation : ['json' => $jsonValidation],
            ], 422);
        }

        $deactivated = [];

        DB::transaction(function () use ($template, $request, &$deactivated) {
            // Find and deactivate current active template(s) with same key
            $activeTemplates = InterviewTemplate::where('version', $template->version)
                ->where('language', $template->language)
                ->where('position_code', $template->position_code)
                ->where('is_active', true)
                ->where('id', '!=', $template->id)
                ->get();

            foreach ($activeTemplates as $active) {
                $beforeSha = $active->template_json ? hash('sha256', $active->template_json) : null;
                $active->is_active = false;
                $active->save();

                $this->logAudit('deactivate', $active, $request, $beforeSha, [
                    'reason' => 'replaced_by_publish',
                    'replacement_id' => $template->id,
                ]);

                $deactivated[] = $active->id;
            }

            // Activate this template
            $template->is_active = true;
            $template->save();

            $this->logAudit('activate', $template, $request, null, [
                'via' => 'publish',
                'replaced_ids' => $deactivated,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Template published successfully',
            'data' => [
                'id' => $template->id,
                'is_active' => true,
                'deactivated_templates' => $deactivated,
            ],
        ]);
    }

    /**
     * GET /v1/admin/interview-templates/{id}/export
     * Export template JSON as downloadable file
     */
    public function export(string $id): JsonResponse
    {
        $template = InterviewTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'version' => $template->version,
                'language' => $template->language,
                'position_code' => $template->position_code,
                'title' => $template->title,
                'template_json' => $template->template_json,
                'exported_at' => now()->toIso8601String(),
                'sha256' => hash('sha256', $template->template_json),
            ],
        ]);
    }

    /**
     * POST /v1/admin/interview-templates/import
     * Import templates from JSON (bulk create)
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'templates' => 'required|array|min:1|max:50',
            'templates.*.version' => 'required|string|max:10',
            'templates.*.language' => 'required|string|max:5',
            'templates.*.position_code' => 'required|string|max:100',
            'templates.*.title' => 'required|string|max:200',
            'templates.*.template_json' => 'required|string',
            'templates.*.is_active' => 'boolean',
            'skip_duplicates' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $skipDuplicates = $request->boolean('skip_duplicates', true);
        $created = [];
        $skipped = [];
        $errors = [];

        foreach ($request->templates as $i => $tpl) {
            // Check duplicate
            $exists = InterviewTemplate::where('version', $tpl['version'])
                ->where('language', $tpl['language'])
                ->where('position_code', $tpl['position_code'])
                ->exists();

            if ($exists) {
                if ($skipDuplicates) {
                    $skipped[] = [
                        'index' => $i,
                        'key' => "{$tpl['version']}/{$tpl['language']}/{$tpl['position_code']}",
                    ];
                    continue;
                } else {
                    $errors[] = [
                        'index' => $i,
                        'error' => 'duplicate',
                        'key' => "{$tpl['version']}/{$tpl['language']}/{$tpl['position_code']}",
                    ];
                    continue;
                }
            }

            // Validate JSON
            $jsonValidation = $this->validateTemplateJson($tpl['template_json'], $tpl['language']);
            if ($jsonValidation !== true) {
                $errors[] = [
                    'index' => $i,
                    'error' => 'invalid_json',
                    'details' => is_array($jsonValidation) ? $jsonValidation : ['json' => $jsonValidation],
                ];
                continue;
            }

            // Create
            $template = InterviewTemplate::create([
                'id' => (string) Str::uuid(),
                'version' => $tpl['version'],
                'language' => $tpl['language'],
                'position_code' => $tpl['position_code'],
                'title' => $tpl['title'],
                'template_json' => $tpl['template_json'],
                'is_active' => $tpl['is_active'] ?? false,
            ]);

            $this->logAudit('create', $template, $request, null, [
                'via' => 'import',
                'import_index' => $i,
            ]);

            $created[] = [
                'index' => $i,
                'id' => $template->id,
                'key' => "{$template->version}/{$template->language}/{$template->position_code}",
            ];
        }

        return response()->json([
            'success' => count($errors) === 0,
            'message' => sprintf(
                'Import completed: %d created, %d skipped, %d errors',
                count($created),
                count($skipped),
                count($errors)
            ),
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
            ],
        ], count($errors) > 0 ? 207 : 201); // 207 Multi-Status if partial success
    }

    /**
     * Log audit entry
     */
    private function logAudit(
        string $action,
        InterviewTemplate $template,
        Request $request,
        ?string $beforeSha = null,
        ?array $changes = null
    ): void {
        try {
            TemplateAuditLog::log(
                $action,
                $template,
                $request->user(),
                $beforeSha,
                $changes,
                $request->ip(),
                $request->userAgent()
            );
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            \Log::error('Failed to create audit log', [
                'action' => $action,
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate template JSON structure with strict schema
     *
     * @return true|array True if valid, array of field errors if invalid
     */
    private function validateTemplateJson(string $json, string $language): bool|array
    {
        $errors = [];

        $decoded = json_decode($json, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return ['json' => 'Invalid JSON: ' . json_last_error_msg()];
        }

        // Schema version (optional but recommended)
        // if (!isset($decoded['schema_version'])) {
        //     $errors['schema_version'] = 'Missing schema_version field';
        // }

        // Template type detection
        $templateType = isset($decoded['generic_template']) ? 'generic' :
                       (isset($decoded['positions']) ? 'multi_position' :
                       (isset($decoded['template']) || isset($decoded['questions']) ? 'position' : 'unknown'));

        // Find questions array
        $questions = $decoded['questions']
            ?? $decoded['generic_template']['questions']
            ?? $decoded['template']['questions']
            ?? $decoded['position']['template']['questions']
            ?? null;

        if (!$questions && !isset($decoded['positions'])) {
            $errors['structure'] = 'JSON must contain a questions array or positions array';
            return $errors;
        }

        // Valid competencies
        $validCompetencies = [
            'communication',
            'accountability',
            'teamwork',
            'stress_resilience',
            'adaptability',
            'learning_agility',
            'integrity',
            'role_competence',
        ];

        // Valid methods
        $validMethods = ['STAR', 'SITUATIONAL', 'BEHAVIORAL', 'BEI', 'TECHNICAL', 'OPEN'];

        // Valid red flag codes
        $validRfCodes = ['RF_BLAME', 'RF_INCONSIST', 'RF_EGO', 'RF_AVOID', 'RF_AGGRESSION', 'RF_UNSTABLE'];

        if ($questions) {
            $totalWeight = 0;
            $seenSlots = [];

            foreach ($questions as $i => $q) {
                $prefix = "questions[{$i}]";

                // Required: slot (int, 1..N)
                if (!isset($q['slot'])) {
                    $errors["{$prefix}.slot"] = 'Missing slot field';
                } elseif (!is_int($q['slot']) || $q['slot'] < 1) {
                    $errors["{$prefix}.slot"] = 'Slot must be a positive integer';
                } elseif (in_array($q['slot'], $seenSlots)) {
                    $errors["{$prefix}.slot"] = "Duplicate slot number: {$q['slot']}";
                } else {
                    $seenSlots[] = $q['slot'];
                }

                // Required: competency (whitelist)
                if (!isset($q['competency'])) {
                    $errors["{$prefix}.competency"] = 'Missing competency field';
                } elseif (!in_array($q['competency'], $validCompetencies)) {
                    $errors["{$prefix}.competency"] = "Invalid competency '{$q['competency']}'. Valid: " . implode(', ', $validCompetencies);
                }

                // Required: question text
                if (!isset($q['question']) || !is_string($q['question']) || strlen($q['question']) < 10) {
                    $errors["{$prefix}.question"] = 'Question must be a string with at least 10 characters';
                }

                // Optional but validated: weight
                if (isset($q['weight'])) {
                    if (!is_numeric($q['weight']) || $q['weight'] < 0) {
                        $errors["{$prefix}.weight"] = 'Weight must be a non-negative number';
                    } else {
                        $totalWeight += (float) $q['weight'];
                    }
                }

                // Optional but validated: method
                if (isset($q['method']) && !in_array($q['method'], $validMethods)) {
                    $errors["{$prefix}.method"] = "Invalid method '{$q['method']}'. Valid: " . implode(', ', $validMethods);
                }

                // Optional: red_flag_hooks validation
                if (isset($q['red_flag_hooks']) && is_array($q['red_flag_hooks'])) {
                    foreach ($q['red_flag_hooks'] as $j => $rf) {
                        if (isset($rf['code']) && !in_array($rf['code'], $validRfCodes)) {
                            $errors["{$prefix}.red_flag_hooks[{$j}].code"] = "Invalid RF code '{$rf['code']}'. Valid: " . implode(', ', $validRfCodes);
                        }
                    }
                }
            }

            // Weight normalization check (if weights are provided)
            if ($totalWeight > 0 && abs($totalWeight - 100) > 5) {
                $errors['weights'] = "Total weight is {$totalWeight}, should be ~100 (±5 tolerance)";
            }

            // Minimum questions check
            if (count($questions) < 1) {
                $errors['questions'] = 'At least 1 question is required';
            }
        }

        // Validate positions array if present
        if (isset($decoded['positions']) && is_array($decoded['positions'])) {
            foreach ($decoded['positions'] as $pi => $pos) {
                $pprefix = "positions[{$pi}]";

                if (!isset($pos['position_code'])) {
                    $errors["{$pprefix}.position_code"] = 'Missing position_code';
                }

                $posQuestions = $pos['template']['questions'] ?? $pos['questions'] ?? null;
                if (!$posQuestions) {
                    $errors["{$pprefix}.questions"] = 'Position must have questions array';
                }
            }
        }

        return empty($errors) ? true : $errors;
    }
}
