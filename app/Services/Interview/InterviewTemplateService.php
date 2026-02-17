<?php

namespace App\Services\Interview;

use App\Config\MaritimeRole;
use App\Models\InterviewTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InterviewTemplateService
{
    /**
     * Generic template position code constant
     */
    public const GENERIC_POSITION_CODE = '__generic__';

    /**
     * Role → Department mapping.
     * @deprecated Use MaritimeRole::ROLE_DEPARTMENT_MAP directly.
     */
    public const ROLE_DEPARTMENT_MAP = MaritimeRole::ROLE_DEPARTMENT_MAP;

    /**
     * Get interview template with fallback to generic
     *
     * Retrieval rules:
     * 1. Try exact match: (version, language, position_code)
     * 2. Fallback to: (version, language, "__generic__")
     */
    public function getTemplate(string $version, string $language, string $positionCode): InterviewTemplate
    {
        return InterviewTemplate::query()
            ->where('version', $version)
            ->where('language', $language)
            ->where('is_active', true)
            ->whereIn('position_code', [$positionCode, self::GENERIC_POSITION_CODE])
            ->orderByRaw("position_code = ? DESC", [$positionCode]) // Prioritize exact match
            ->firstOrFail();
    }

    /**
     * Get maritime template with department isolation.
     *
     * Resolution chain (NEVER crosses department boundary):
     * 1. Try exact: {department}_{role_code}  (e.g. deck_captain)
     * 2. Fallback:  {department}___generic__   (e.g. deck___generic__)
     * 3. If language != 'tr', retry chain with language='tr' (az→tr fallback)
     * 4. If none found → return null (caller must 422)
     */
    public function getMaritimeTemplate(
        string $version,
        string $language,
        string $department,
        string $roleCode,
        ?string $operationType = null
    ): ?InterviewTemplate {
        $exactCode = "{$department}_{$roleCode}";
        $genericCode = "{$department}___generic__";

        // Build candidate codes: most specific → least specific
        $candidates = [];
        if ($operationType) {
            $candidates[] = "{$department}_{$roleCode}_{$operationType}";
        }
        $candidates[] = $exactCode;
        $candidates[] = $genericCode;

        // Try requested language first
        $template = $this->findBestTemplate($version, $language, $candidates);

        if ($template) {
            return $template;
        }

        // Language fallback: az → tr, other → tr
        if ($language !== 'tr') {
            $template = $this->findBestTemplate($version, 'tr', $candidates);

            if ($template) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Find the best matching template from a prioritized list of position codes.
     */
    private function findBestTemplate(string $version, string $language, array $candidates): ?InterviewTemplate
    {
        $templates = InterviewTemplate::query()
            ->where('version', $version)
            ->where('language', $language)
            ->where('is_active', true)
            ->whereIn('position_code', $candidates)
            ->get();

        if ($templates->isEmpty()) {
            return null;
        }

        // Return by priority order
        foreach ($candidates as $code) {
            $match = $templates->firstWhere('position_code', $code);
            if ($match) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Resolve department from role_code. Returns null if unknown.
     */
    public function departmentForRole(string $roleCode): ?string
    {
        return MaritimeRole::departmentFor($roleCode);
    }

    /**
     * Get generic template
     */
    public function getGenericTemplate(string $version = 'v1', string $language = 'tr'): InterviewTemplate
    {
        return InterviewTemplate::query()
            ->where('version', $version)
            ->where('language', $language)
            ->where('position_code', self::GENERIC_POSITION_CODE)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * Get position-specific template (no fallback)
     */
    public function getPositionTemplate(string $version, string $language, string $positionCode): ?InterviewTemplate
    {
        return InterviewTemplate::query()
            ->where('version', $version)
            ->where('language', $language)
            ->where('position_code', $positionCode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * List all active templates
     */
    public function listActiveTemplates(?string $language = null)
    {
        $query = InterviewTemplate::query()
            ->where('is_active', true);

        if ($language) {
            $query->where('language', $language);
        }

        return $query->orderBy('position_code')->get();
    }

    /**
     * Get template JSON as array (decoded)
     */
    public function getTemplateAsArray(InterviewTemplate $template): array
    {
        return json_decode($template->template_json, true) ?? [];
    }

    /**
     * Get template JSON as raw string (exact storage)
     */
    public function getTemplateAsRawJson(InterviewTemplate $template): string
    {
        return $template->template_json;
    }

    /**
     * Check if a position has a dedicated template
     */
    public function hasPositionTemplate(string $positionCode, string $language = 'tr', string $version = 'v1'): bool
    {
        return InterviewTemplate::query()
            ->where('version', $version)
            ->where('language', $language)
            ->where('position_code', $positionCode)
            ->where('is_active', true)
            ->exists();
    }
}
