<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterviewTemplate;
use App\Services\Interview\InterviewTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewTemplateController extends Controller
{
    public function __construct(
        private InterviewTemplateService $templateService
    ) {}

    /**
     * GET /v1/interview-templates
     * List all active templates
     */
    public function index(Request $request): JsonResponse
    {
        $language = $request->query('language', 'tr');
        $templates = $this->templateService->listActiveTemplates($language);

        return response()->json([
            'success' => true,
            'data' => $templates->map(fn($t) => [
                'id' => $t->id,
                'version' => $t->version,
                'language' => $t->language,
                'position_code' => $t->position_code,
                'title' => $t->title,
                'is_active' => $t->is_active,
                'created_at' => $t->created_at,
                'updated_at' => $t->updated_at,
            ]),
            'meta' => [
                'total' => $templates->count(),
                'generic_position_code' => InterviewTemplateService::GENERIC_POSITION_CODE,
            ],
        ]);
    }

    /**
     * GET /v1/interview-templates/{version}/{language}/{positionCode?}
     *
     * Fetch interview template with fallback logic:
     * - If position_code is omitted, return __generic__
     * - If position_code is provided but not found, fallback to __generic__
     * - Returns template_json as EXACT string (no decode/re-encode)
     *
     * @param string $version
     * @param string $language
     * @param string|null $positionCode
     * @return JsonResponse
     */
    public function show(string $version, string $language, ?string $positionCode = null): JsonResponse
    {
        // If position_code is omitted, use __generic__
        $requestedPositionCode = $positionCode ?? InterviewTemplateService::GENERIC_POSITION_CODE;

        try {
            $template = $this->templateService->getTemplate($version, $language, $requestedPositionCode);
            $usedFallback = $template->position_code !== $requestedPositionCode;

            // Return template_json as EXACT string - DO NOT use json_decode/json_encode
            // Access $template->template_json directly (NOT $template->template which is the accessor)
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $template->id,
                    'version' => $template->version,
                    'language' => $template->language,
                    'position_code' => $template->position_code,
                    'title' => $template->title,
                    'template_json' => $template->template_json, // EXACT string from DB
                    'is_active' => $template->is_active,
                    'created_at' => $template->created_at,
                    'updated_at' => $template->updated_at,
                ],
                'meta' => [
                    'requested_position_code' => $requestedPositionCode,
                    'used_fallback' => $usedFallback,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Template not found',
                'message' => "No template found for version='{$version}', language='{$language}', position_code='{$requestedPositionCode}' and no generic fallback available.",
            ], 404);
        }
    }

    /**
     * GET /v1/interview-templates/{version}/{language}/{positionCode}/parsed
     * Get template with parsed JSON (decoded as object)
     */
    public function showParsed(string $version, string $language, string $positionCode): JsonResponse
    {
        try {
            $template = $this->templateService->getTemplate($version, $language, $positionCode);
            $usedFallback = $template->position_code !== $positionCode;
            $parsedJson = $this->templateService->getTemplateAsArray($template);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $template->id,
                    'version' => $template->version,
                    'language' => $template->language,
                    'position_code' => $template->position_code,
                    'title' => $template->title,
                    'template' => $parsedJson, // Parsed as object
                    'is_active' => $template->is_active,
                    'created_at' => $template->created_at,
                    'updated_at' => $template->updated_at,
                ],
                'meta' => [
                    'requested_position_code' => $positionCode,
                    'used_fallback' => $usedFallback,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Template not found',
                'message' => "No template found for position_code '{$positionCode}' and no generic fallback available.",
            ], 404);
        }
    }

    /**
     * GET /v1/interview-templates/check/{version}/{language}/{positionCode}
     * Check if a position has a dedicated template
     */
    public function check(string $version, string $language, string $positionCode): JsonResponse
    {
        $hasTemplate = $this->templateService->hasPositionTemplate($positionCode, $language, $version);

        return response()->json([
            'success' => true,
            'data' => [
                'version' => $version,
                'language' => $language,
                'position_code' => $positionCode,
                'has_dedicated_template' => $hasTemplate,
                'will_use_fallback' => !$hasTemplate,
                'fallback_position_code' => $hasTemplate ? null : InterviewTemplateService::GENERIC_POSITION_CODE,
            ],
        ]);
    }
}
