<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\CompanyApplyLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyApplyResolverController extends Controller
{
    /**
     * Resolve a company apply link from slug + token.
     * Public endpoint (no auth required).
     *
     * GET /api/v1/maritime/apply/resolve?company={slug}&t={token}
     */
    public function resolve(Request $request): JsonResponse
    {
        $slug = $request->query('company');
        $token = $request->query('t');

        if (!$slug || !$token) {
            return response()->json([
                'success' => false,
                'message' => 'Missing company or token parameter.',
            ], 422);
        }

        // Look up without tenant scope (public endpoint)
        $link = CompanyApplyLink::withoutTenantScope()
            ->where('slug', $slug)
            ->where('token', $token)
            ->first();

        if (!$link || !$link->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired apply link.',
            ], 404);
        }

        $company = $link->company;

        // Increment click count
        $link->incrementClicks();

        return response()->json([
            'success' => true,
            'data' => [
                'company_name' => $company?->name,
                'company_slug' => $link->slug,
                'logo_url' => $company?->getLogoUrl(),
                'label' => $link->label,
                'brand_color' => $company?->getBrandColor(),
            ],
        ]);
    }
}
