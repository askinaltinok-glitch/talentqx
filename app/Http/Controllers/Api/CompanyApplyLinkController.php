<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyApplyLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyApplyLinkController extends Controller
{
    /**
     * List apply links for the current tenant.
     * GET /api/v1/company/apply-links
     */
    public function index(Request $request): JsonResponse
    {
        $links = CompanyApplyLink::orderByDesc('created_at')
            ->get()
            ->map(fn(CompanyApplyLink $link) => [
                'id' => $link->id,
                'slug' => $link->slug,
                'label' => $link->label,
                'is_active' => $link->is_active,
                'expires_at' => $link->expires_at?->toIso8601String(),
                'click_count' => $link->click_count,
                'apply_url' => $this->buildApplyUrl($link),
                'created_at' => $link->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $links,
        ]);
    }

    /**
     * Create a new apply link for the current tenant.
     * POST /api/v1/company/apply-links
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:company_apply_links,slug'],
            'label' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $company = $request->user()->company;
        $companyId = app()->bound('current_tenant_id') ? app('current_tenant_id') : $request->user()->company_id;

        $link = CompanyApplyLink::create([
            'company_id' => $companyId,
            'slug' => $data['slug'],
            'label' => $data['label'] ?? $company?->name,
            'is_active' => true,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $link->id,
                'slug' => $link->slug,
                'token' => $link->token, // Reveal token on creation only
                'label' => $link->label,
                'apply_url' => $this->buildApplyUrl($link),
                'created_at' => $link->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Show a single apply link.
     * GET /api/v1/company/apply-links/{id}
     */
    public function show(string $id): JsonResponse
    {
        $link = CompanyApplyLink::find($id);

        if (!$link) {
            return response()->json([
                'success' => false,
                'message' => 'Apply link not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $link->id,
                'slug' => $link->slug,
                'label' => $link->label,
                'is_active' => $link->is_active,
                'expires_at' => $link->expires_at?->toIso8601String(),
                'click_count' => $link->click_count,
                'apply_url' => $this->buildApplyUrl($link),
                'created_at' => $link->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Soft-delete an apply link.
     * DELETE /api/v1/company/apply-links/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $link = CompanyApplyLink::find($id);

        if (!$link) {
            return response()->json([
                'success' => false,
                'message' => 'Apply link not found.',
            ], 404);
        }

        $link->update(['is_active' => false]);
        $link->delete();

        return response()->json([
            'success' => true,
            'message' => 'Apply link deactivated.',
        ]);
    }

    private function buildApplyUrl(CompanyApplyLink $link): string
    {
        $baseUrl = config('app.frontend_url', 'https://octopus-ai.net');
        return "{$baseUrl}/maritime/apply?company={$link->slug}&t={$link->token}";
    }
}
