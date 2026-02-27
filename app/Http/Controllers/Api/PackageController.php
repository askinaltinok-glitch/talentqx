<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * List active credit packages.
     */
    public function index(Request $request): JsonResponse
    {
        $currency = strtoupper($request->get('currency', 'TRY'));

        $packages = CreditPackage::active()
            ->sorted()
            ->get()
            ->map(fn($package) => $package->toApiResponse($currency));

        return response()->json([
            'success' => true,
            'data' => $packages,
            'meta' => [
                'currency' => $currency,
                'count' => $packages->count(),
            ],
        ]);
    }

    /**
     * Get single package by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $package = CreditPackage::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Paket bulunamadı',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'credits' => $package->credits,
                'price_try' => (float) $package->price_try,
                'price_eur' => (float) $package->price_eur,
                'description' => $package->description,
                'features' => $package->features,
                'is_featured' => $package->is_featured,
            ],
        ]);
    }
}
