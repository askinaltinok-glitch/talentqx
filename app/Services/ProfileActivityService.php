<?php

namespace App\Services;

use App\Models\CandidateProfileView;

class ProfileActivityService
{
    /**
     * Get profile activity for a candidate, gated by membership tier.
     *
     * - free: total views, unique companies, summary text
     * - plus: + companies[] with view counts
     * - pro: + full view details
     */
    public function getProfileActivity(string $candidateId, string $tier = 'free', int $days = 30): array
    {
        $views = CandidateProfileView::forCandidate($candidateId)
            ->recent($days)
            ->orderByDesc('viewed_at')
            ->get();

        $totalViews = $views->count();

        $uniqueCompanies = $views
            ->filter(fn ($v) => $v->company_name || $v->viewer_name)
            ->unique(fn ($v) => $v->company_name ?: $v->viewer_name)
            ->count();

        $result = [
            'total_views' => $totalViews,
            'unique_companies' => $uniqueCompanies,
            'period_days' => $days,
            'tier' => $tier,
        ];

        // Free: just summary text
        if ($tier === 'free') {
            $result['summary'] = $uniqueCompanies > 0
                ? "{$uniqueCompanies} " . ($uniqueCompanies === 1 ? 'company' : 'companies') . " viewed your profile in the last {$days} days."
                : "No profile views in the last {$days} days.";
            return $result;
        }

        // Plus: add company breakdown
        $companies = $views
            ->filter(fn ($v) => $v->company_name || $v->viewer_name)
            ->groupBy(fn ($v) => $v->company_name ?: $v->viewer_name)
            ->map(fn ($group, $name) => [
                'company_name' => $name,
                'view_count' => $group->count(),
                'last_viewed_at' => $group->first()->viewed_at->toIso8601String(),
            ])
            ->values()
            ->toArray();

        $result['companies'] = $companies;

        if ($tier === 'plus') {
            return $result;
        }

        // Pro: add full view details
        $result['views'] = $views->map(fn ($v) => [
            'id' => $v->id,
            'viewer_name' => $v->viewer_name,
            'company_name' => $v->company_name,
            'context' => $v->context,
            'context_meta' => $v->context_meta,
            'view_duration_seconds' => $v->view_duration_seconds,
            'viewed_at' => $v->viewed_at->toIso8601String(),
        ])->toArray();

        return $result;
    }
}
