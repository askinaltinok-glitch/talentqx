<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyProfileController extends Controller
{
    /**
     * GET /v1/portal/company-profile
     */
    public function show(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'No company associated'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatProfile($company),
        ]);
    }

    /**
     * PUT /v1/portal/company-profile
     */
    public function update(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'No company associated'], 422);
        }

        $data = $request->validate([
            'trade_name' => 'nullable|string|max:255',
            'tax_office' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'brand_primary_color' => 'nullable|string|max:7',
        ]);

        $company->update($data);

        return response()->json([
            'success' => true,
            'data' => $this->formatProfile($company),
        ]);
    }

    /**
     * POST /v1/portal/company-profile/logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'No company associated'], 422);
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $file = $request->file('logo');
        $ext = $file->getClientOriginalExtension();
        $filename = "company-logos/{$company->id}.{$ext}";

        // Delete old logo if exists (different extension)
        foreach (['jpeg', 'jpg', 'png', 'webp'] as $oldExt) {
            $old = "company-logos/{$company->id}.{$oldExt}";
            if (Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
        }

        $stored = Storage::disk('public')->putFileAs('company-logos', $file, "{$company->id}.{$ext}");
        if (!$stored) {
            return response()->json(['success' => false, 'message' => 'Logo kaydedilemedi.'], 500);
        }

        $url = '/storage/' . $filename;
        $company->update(['logo_url' => $url]);

        return response()->json([
            'success' => true,
            'data' => ['logo_url' => $url],
        ]);
    }

    /**
     * DELETE /v1/portal/company-profile/logo
     */
    public function deleteLogo(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'No company associated'], 422);
        }

        // Delete all possible extensions
        foreach (['jpeg', 'jpg', 'png', 'webp'] as $ext) {
            $path = "company-logos/{$company->id}.{$ext}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $company->update(['logo_url' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Logo deleted',
        ]);
    }

    private function formatProfile($company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'trade_name' => $company->trade_name,
            'logo_url' => $company->logo_url,
            'tax_office' => $company->tax_office,
            'tax_number' => $company->tax_number,
            'address' => $company->address,
            'district' => $company->district,
            'city' => $company->city,
            'phone' => $company->phone,
            'website' => $company->website,
            'email' => $company->email,
            'brand_primary_color' => $company->brand_primary_color,
        ];
    }
}
