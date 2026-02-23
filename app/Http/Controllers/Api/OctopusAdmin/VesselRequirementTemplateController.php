<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\VesselRequirementTemplate;
use App\Services\Audit\AuditLogService;
use App\Services\Fleet\VesselProfileValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VesselRequirementTemplateController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly VesselProfileValidator $validator,
    ) {}

    /**
     * GET /vessel-requirement-templates — list all.
     */
    public function index(): JsonResponse
    {
        $templates = VesselRequirementTemplate::orderBy('vessel_type_key')->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * GET /vessel-requirement-templates/{id} — show one.
     */
    public function show(int $id): JsonResponse
    {
        $template = VesselRequirementTemplate::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    /**
     * POST /vessel-requirement-templates — create or upsert by vessel_type_key.
     * Always saves as draft (does not touch published profile_json).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'vessel_type_key' => 'required|string|max:50',
            'label' => 'required|string|max:100',
            'profile_json' => 'required|array',
        ]);

        $existing = VesselRequirementTemplate::where('vessel_type_key', $request->vessel_type_key)->first();

        if ($existing) {
            // Save as draft
            $existing->update([
                'label' => $request->label,
                'draft_profile_json' => $request->profile_json,
                'status' => 'draft',
                'is_active' => $request->boolean('is_active', $existing->is_active),
            ]);

            return response()->json([
                'success' => true,
                'data' => $existing->fresh(),
                'message' => 'Draft saved. Publish to apply changes.',
            ]);
        }

        // New template — save as draft (no published profile yet)
        $template = VesselRequirementTemplate::create([
            'vessel_type_key' => $request->vessel_type_key,
            'label' => $request->label,
            'profile_json' => [],  // empty until published
            'draft_profile_json' => $request->profile_json,
            'is_active' => $request->boolean('is_active', true),
            'status' => 'draft',
            'published_version' => 0,
            'version_history' => [],
        ]);

        return response()->json([
            'success' => true,
            'data' => $template,
            'message' => 'Template created as draft. Publish to activate.',
        ], 201);
    }

    /**
     * PUT /vessel-requirement-templates/{id} — update draft.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = VesselRequirementTemplate::findOrFail($id);

        $data = [];

        if ($request->has('label')) {
            $data['label'] = $request->input('label');
        }

        if ($request->has('profile_json')) {
            $data['draft_profile_json'] = $request->input('profile_json');
            $data['status'] = 'draft';
        }

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $template->update($data);

        return response()->json([
            'success' => true,
            'data' => $template->fresh(),
            'message' => 'Draft updated.',
        ]);
    }

    /**
     * POST /vessel-requirement-templates/{id}/publish — validate & publish draft.
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $template = VesselRequirementTemplate::findOrFail($id);

        $draftProfile = $template->draft_profile_json;

        if (!$draftProfile || !is_array($draftProfile) || empty($draftProfile)) {
            return response()->json([
                'success' => false,
                'message' => 'No draft to publish. Save changes first.',
            ], 422);
        }

        // Validate profile
        $errors = $this->validator->validate($draftProfile);
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $errors,
            ], 422);
        }

        $before = $template->profile_json;
        $newVersion = $template->published_version + 1;

        // Append to version history
        $history = $template->version_history ?? [];
        $history[] = [
            'version' => $newVersion,
            'profile_json' => $draftProfile,
            'published_at' => now()->toIso8601String(),
            'published_by' => $request->user()?->id,
        ];

        // Keep last 10 versions only
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }

        $template->update([
            'profile_json' => $draftProfile,
            'draft_profile_json' => null,
            'status' => 'published',
            'published_version' => $newVersion,
            'version_history' => $history,
        ]);

        // Audit log
        $diff = $this->audit->diff($before, $draftProfile);
        $this->audit->log('vessel_template.publish', [
            'entity_type' => 'vessel_requirement_template',
            'feature_key' => $template->vessel_type_key,
            'tenant_id' => null,
            'actor_user_id' => $request->user()?->id,
            'before' => $before,
            'after' => $draftProfile,
            'diff' => $diff,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'request_id' => $request->header('X-Request-ID'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $template->fresh(),
            'message' => "Published as version {$newVersion}.",
        ]);
    }

    /**
     * POST /vessel-requirement-templates/{id}/revert?version=X — revert to a previous version.
     */
    public function revert(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'version' => 'required|integer|min:1',
        ]);

        $template = VesselRequirementTemplate::findOrFail($id);
        $targetVersion = (int) $request->input('version');

        $profile = $template->getVersionProfile($targetVersion);
        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => "Version {$targetVersion} not found in history.",
                'available_versions' => $template->getVersionNumbers(),
            ], 404);
        }

        // Validate the old profile still passes current schema
        $errors = $this->validator->validate($profile);
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => "Version {$targetVersion} does not pass current validation.",
                'errors' => $errors,
            ], 422);
        }

        $before = $template->profile_json;
        $newVersion = $template->published_version + 1;

        // Append revert entry to history
        $history = $template->version_history ?? [];
        $history[] = [
            'version' => $newVersion,
            'profile_json' => $profile,
            'published_at' => now()->toIso8601String(),
            'published_by' => $request->user()?->id,
            'reverted_from' => $targetVersion,
        ];

        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }

        $template->update([
            'profile_json' => $profile,
            'draft_profile_json' => null,
            'status' => 'published',
            'published_version' => $newVersion,
            'version_history' => $history,
        ]);

        // Audit log
        $diff = $this->audit->diff($before, $profile);
        $this->audit->log('vessel_template.revert', [
            'entity_type' => 'vessel_requirement_template',
            'feature_key' => $template->vessel_type_key,
            'tenant_id' => null,
            'actor_user_id' => $request->user()?->id,
            'before' => $before,
            'after' => $profile,
            'diff' => $diff,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'request_id' => $request->header('X-Request-ID'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $template->fresh(),
            'message' => "Reverted to version {$targetVersion} (now version {$newVersion}).",
        ]);
    }
}
