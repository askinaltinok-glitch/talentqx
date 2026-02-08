<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobDomain;
use App\Models\JobSubdomain;
use App\Models\JobPosition;
use App\Models\Competency;
use App\Models\RoleArchetype;
use App\Models\PositionQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxonomyController extends Controller
{
    /**
     * Get all job domains with subdomain counts
     */
    public function domains(Request $request): JsonResponse
    {
        $locale = $request->get('locale', 'tr');

        $domains = JobDomain::where('is_active', true)
            ->withCount(['subdomains' => fn($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($domain) => [
                'id' => $domain->id,
                'code' => $domain->code,
                'name' => $locale === 'en' ? $domain->name_en : $domain->name_tr,
                'name_tr' => $domain->name_tr,
                'name_en' => $domain->name_en,
                'icon' => $domain->icon,
                'color' => $domain->color,
                'subdomains_count' => $domain->subdomains_count,
                'sort_order' => $domain->sort_order,
            ]);

        return response()->json([
            'success' => true,
            'data' => $domains,
        ]);
    }

    /**
     * Get subdomains for a specific domain
     */
    public function subdomains(Request $request, string $domainId): JsonResponse
    {
        $locale = $request->get('locale', 'tr');

        $domain = JobDomain::where('id', $domainId)
            ->orWhere('code', $domainId)
            ->where('is_active', true)
            ->firstOrFail();

        $subdomains = $domain->subdomains()
            ->where('is_active', true)
            ->withCount(['positions' => fn($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($subdomain) => [
                'id' => $subdomain->id,
                'code' => $subdomain->code,
                'name' => $locale === 'en' ? $subdomain->name_en : $subdomain->name_tr,
                'name_tr' => $subdomain->name_tr,
                'name_en' => $subdomain->name_en,
                'domain_id' => $subdomain->domain_id,
                'positions_count' => $subdomain->positions_count,
                'sort_order' => $subdomain->sort_order,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'domain' => [
                    'id' => $domain->id,
                    'code' => $domain->code,
                    'name' => $locale === 'en' ? $domain->name_en : $domain->name_tr,
                ],
                'subdomains' => $subdomains,
            ],
        ]);
    }

    /**
     * Get positions for a specific subdomain
     */
    public function positions(Request $request, string $subdomainId): JsonResponse
    {
        $locale = $request->get('locale', 'tr');

        $subdomain = JobSubdomain::where('id', $subdomainId)
            ->orWhere('code', $subdomainId)
            ->where('is_active', true)
            ->firstOrFail();

        $positions = $subdomain->positions()
            ->where('is_active', true)
            ->with(['archetype'])
            ->withCount('competencies')
            ->orderBy('experience_min_years')
            ->get()
            ->map(fn($position) => [
                'id' => $position->id,
                'code' => $position->code,
                'name' => $locale === 'en' ? $position->name_en : $position->name_tr,
                'name_tr' => $position->name_tr,
                'name_en' => $position->name_en,
                'subdomain_id' => $position->subdomain_id,
                'archetype' => $position->archetype ? [
                    'id' => $position->archetype->id,
                    'code' => $position->archetype->code,
                    'name' => $locale === 'en' ? $position->archetype->name_en : $position->archetype->name_tr,
                    'level' => $position->archetype->level,
                ] : null,
                'experience_min_years' => $position->experience_min_years,
                'experience_max_years' => $position->experience_max_years,
                'competencies_count' => $position->competencies_count,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'subdomain' => [
                    'id' => $subdomain->id,
                    'code' => $subdomain->code,
                    'name' => $locale === 'en' ? $subdomain->name_en : $subdomain->name_tr,
                ],
                'positions' => $positions,
            ],
        ]);
    }

    /**
     * Get full position details including competencies and questions
     */
    public function positionDetail(Request $request, string $positionId): JsonResponse
    {
        $locale = $request->get('locale', 'tr');

        $position = JobPosition::where('id', $positionId)
            ->orWhere('code', $positionId)
            ->where('is_active', true)
            ->with([
                'subdomain.domain',
                'archetype',
                'competencies',
                'questions',
            ])
            ->firstOrFail();

        $competencies = $position->competencies->map(fn($comp) => [
            'id' => $comp->id,
            'code' => $comp->code,
            'name' => $locale === 'en' ? $comp->name_en : $comp->name_tr,
            'description' => $locale === 'en' ? $comp->description_en : $comp->description_tr,
            'category' => $comp->category,
            'weight' => $comp->pivot->weight,
            'is_critical' => $comp->pivot->is_critical,
            'min_score' => $comp->pivot->min_score,
            'position_specific_criteria' => $locale === 'en'
                ? $comp->pivot->position_specific_criteria_en
                : $comp->pivot->position_specific_criteria_tr,
        ]);

        $questions = $position->questions()
            ->orderBy('sort_order')
            ->get()
            ->map(fn($q) => [
                'id' => $q->id,
                'text' => $locale === 'en' ? $q->question_en : $q->question_tr,
                'text_tr' => $q->question_tr,
                'text_en' => $q->question_en,
                'type' => $q->question_type,
                'competency_code' => $q->competency?->code,
                'time_limit_seconds' => $q->time_limit_seconds ?? 120,
                'sort_order' => $q->sort_order,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $position->id,
                'code' => $position->code,
                'name' => $locale === 'en' ? $position->name_en : $position->name_tr,
                'name_tr' => $position->name_tr,
                'name_en' => $position->name_en,
                'subdomain' => [
                    'id' => $position->subdomain->id,
                    'code' => $position->subdomain->code,
                    'name' => $locale === 'en' ? $position->subdomain->name_en : $position->subdomain->name_tr,
                ],
                'domain' => [
                    'id' => $position->subdomain->domain->id,
                    'code' => $position->subdomain->domain->code,
                    'name' => $locale === 'en' ? $position->subdomain->domain->name_en : $position->subdomain->domain->name_tr,
                ],
                'archetype' => $position->archetype ? [
                    'id' => $position->archetype->id,
                    'code' => $position->archetype->code,
                    'name' => $locale === 'en' ? $position->archetype->name_en : $position->archetype->name_tr,
                    'level' => $position->archetype->level,
                ] : null,
                'experience_min_years' => $position->experience_min_years,
                'experience_max_years' => $position->experience_max_years,
                'competencies' => $competencies,
                'questions' => $questions,
            ],
        ]);
    }

    /**
     * Search positions across all domains
     */
    public function searchPositions(Request $request): JsonResponse
    {
        $locale = $request->get('locale', 'tr');
        $query = $request->get('q', '');
        $domainId = $request->get('domain_id');
        $subdomainId = $request->get('subdomain_id');
        $archetypeLevel = $request->get('archetype_level');

        $positions = JobPosition::where('is_active', true)
            ->with(['subdomain.domain', 'archetype'])
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name_tr', 'like', "%{$query}%")
                        ->orWhere('name_en', 'like', "%{$query}%")
                        ->orWhere('code', 'like', "%{$query}%");
                });
            })
            ->when($domainId, function ($q) use ($domainId) {
                $q->whereHas('subdomain', fn($sub) =>
                    $sub->where('domain_id', $domainId)
                );
            })
            ->when($subdomainId, function ($q) use ($subdomainId) {
                $q->where('subdomain_id', $subdomainId);
            })
            ->when($archetypeLevel, function ($q) use ($archetypeLevel) {
                $q->whereHas('archetype', fn($sub) =>
                    $sub->where('level', $archetypeLevel)
                );
            })
            ->limit(50)
            ->get()
            ->map(fn($position) => [
                'id' => $position->id,
                'code' => $position->code,
                'name' => $locale === 'en' ? $position->name_en : $position->name_tr,
                'name_tr' => $position->name_tr,
                'name_en' => $position->name_en,
                'subdomain' => [
                    'id' => $position->subdomain->id,
                    'code' => $position->subdomain->code,
                    'name' => $locale === 'en' ? $position->subdomain->name_en : $position->subdomain->name_tr,
                ],
                'domain' => [
                    'id' => $position->subdomain->domain->id,
                    'code' => $position->subdomain->domain->code,
                    'name' => $locale === 'en' ? $position->subdomain->domain->name_en : $position->subdomain->domain->name_tr,
                ],
                'archetype' => $position->archetype ? [
                    'code' => $position->archetype->code,
                    'name' => $locale === 'en' ? $position->archetype->name_en : $position->archetype->name_tr,
                    'level' => $position->archetype->level,
                ] : null,
                'experience_range' => "{$position->experience_min_years}-{$position->experience_max_years} yıl",
            ]);

        return response()->json([
            'success' => true,
            'data' => $positions,
        ]);
    }

    /**
     * Get all role archetypes
     */
    public function archetypes(Request $request): JsonResponse
    {
        $locale = $request->get('locale', 'tr');

        $archetypes = RoleArchetype::where('is_active', true)
            ->orderBy('level')
            ->get()
            ->map(fn($arch) => [
                'id' => $arch->id,
                'code' => $arch->code,
                'name' => $locale === 'en' ? $arch->name_en : $arch->name_tr,
                'name_tr' => $arch->name_tr,
                'name_en' => $arch->name_en,
                'level' => $arch->level,
                'description' => $locale === 'en' ? $arch->description_en : $arch->description_tr,
            ]);

        return response()->json([
            'success' => true,
            'data' => $archetypes,
        ]);
    }

    /**
     * Get all competencies (master list)
     */
    public function competencies(Request $request): JsonResponse
    {
        $locale = $request->get('locale', 'tr');
        $category = $request->get('category');

        $competencies = Competency::where('is_active', true)
            ->when($category, fn($q) => $q->where('category', $category))
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($comp) => [
                'id' => $comp->id,
                'code' => $comp->code,
                'name' => $locale === 'en' ? $comp->name_en : $comp->name_tr,
                'name_tr' => $comp->name_tr,
                'name_en' => $comp->name_en,
                'description' => $locale === 'en' ? $comp->description_en : $comp->description_tr,
                'category' => $comp->category,
            ]);

        return response()->json([
            'success' => true,
            'data' => $competencies,
        ]);
    }

    /**
     * Get hierarchical taxonomy tree (domains -> subdomains -> positions)
     */
    public function tree(Request $request): JsonResponse
    {
        $locale = $request->get('locale', 'tr');

        $domains = JobDomain::where('is_active', true)
            ->with([
                'subdomains' => fn($q) => $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with([
                        'positions' => fn($p) => $p->where('is_active', true)
                            ->orderBy('experience_min_years')
                    ])
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($domain) => [
                'id' => $domain->id,
                'code' => $domain->code,
                'name' => $locale === 'en' ? $domain->name_en : $domain->name_tr,
                'icon' => $domain->icon,
                'color' => $domain->color,
                'subdomains' => $domain->subdomains->map(fn($sub) => [
                    'id' => $sub->id,
                    'code' => $sub->code,
                    'name' => $locale === 'en' ? $sub->name_en : $sub->name_tr,
                    'positions' => $sub->positions->map(fn($pos) => [
                        'id' => $pos->id,
                        'code' => $pos->code,
                        'name' => $locale === 'en' ? $pos->name_en : $pos->name_tr,
                    ]),
                ]),
            ]);

        return response()->json([
            'success' => true,
            'data' => $domains,
        ]);
    }
}
