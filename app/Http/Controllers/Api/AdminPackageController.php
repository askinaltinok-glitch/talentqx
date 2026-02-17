<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminPackageController extends Controller
{
    /**
     * List all packages (including inactive).
     */
    public function index(Request $request): JsonResponse
    {
        $packages = CreditPackage::orderBy('sort_order')
            ->orderBy('credits')
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'slug' => $package->slug,
                    'credits' => $package->credits,
                    'price_try' => (float) $package->price_try,
                    'price_eur' => (float) $package->price_eur,
                    'description' => $package->description,
                    'is_active' => $package->is_active,
                    'is_featured' => $package->is_featured,
                    'sort_order' => $package->sort_order,
                    'payments_count' => $package->payments()->count(),
                    'created_at' => $package->created_at->toIso8601String(),
                    'updated_at' => $package->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    /**
     * Create a new package.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'credits' => 'required|integer|min:1|max:100000',
            'price_try' => 'required|numeric|min:0',
            'price_eur' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Doğrulama hatası',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['name']);

        // Check for duplicate slug
        $existingSlug = CreditPackage::where('slug', $data['slug'])->exists();
        if ($existingSlug) {
            $data['slug'] = $data['slug'] . '-' . Str::random(4);
        }

        $package = CreditPackage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Paket oluşturuldu',
            'data' => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'credits' => $package->credits,
                'price_try' => (float) $package->price_try,
                'price_eur' => (float) $package->price_eur,
            ],
        ], 201);
    }

    /**
     * Get single package.
     */
    public function show(string $id): JsonResponse
    {
        $package = CreditPackage::find($id);

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
                'is_active' => $package->is_active,
                'is_featured' => $package->is_featured,
                'sort_order' => $package->sort_order,
                'payments_count' => $package->payments()->count(),
                'total_revenue' => (float) $package->payments()->completed()->sum('amount'),
                'created_at' => $package->created_at->toIso8601String(),
                'updated_at' => $package->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update a package.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $package = CreditPackage::find($id);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Paket bulunamadı',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'credits' => 'sometimes|integer|min:1|max:100000',
            'price_try' => 'sometimes|numeric|min:0',
            'price_eur' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Doğrulama hatası',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Update slug if name changed
        if (isset($data['name']) && $data['name'] !== $package->name) {
            $newSlug = Str::slug($data['name']);
            $existingSlug = CreditPackage::where('slug', $newSlug)
                ->where('id', '!=', $package->id)
                ->exists();
            if ($existingSlug) {
                $newSlug = $newSlug . '-' . Str::random(4);
            }
            $data['slug'] = $newSlug;
        }

        $package->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Paket güncellendi',
            'data' => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'credits' => $package->credits,
                'price_try' => (float) $package->price_try,
                'price_eur' => (float) $package->price_eur,
                'is_active' => $package->is_active,
            ],
        ]);
    }

    /**
     * Delete a package.
     */
    public function destroy(string $id): JsonResponse
    {
        $package = CreditPackage::find($id);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Paket bulunamadı',
            ], 404);
        }

        // Check if package has payments
        if ($package->payments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu pakete ait ödemeler var, silinemez. Bunun yerine pasif yapabilirsiniz.',
            ], 400);
        }

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paket silindi',
        ]);
    }

    /**
     * Toggle package active status.
     */
    public function toggleActive(string $id): JsonResponse
    {
        $package = CreditPackage::find($id);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Paket bulunamadı',
            ], 404);
        }

        $package->update(['is_active' => !$package->is_active]);

        return response()->json([
            'success' => true,
            'message' => $package->is_active ? 'Paket aktif edildi' : 'Paket pasif yapıldı',
            'data' => [
                'id' => $package->id,
                'is_active' => $package->is_active,
            ],
        ]);
    }

    /**
     * Reorder packages.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order' => 'required|array',
            'order.*.id' => 'required|uuid|exists:credit_packages,id',
            'order.*.sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Doğrulama hatası',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->order as $item) {
            CreditPackage::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sıralama güncellendi',
        ]);
    }

    /**
     * Get package statistics.
     */
    public function stats(): JsonResponse
    {
        $totalPackages = CreditPackage::count();
        $activePackages = CreditPackage::where('is_active', true)->count();

        $totalRevenue = \App\Models\Payment::where('status', 'completed')
            ->sum('amount');

        $totalCredits = \App\Models\Payment::where('status', 'completed')
            ->sum('credits_added');

        $topPackages = CreditPackage::withCount(['payments' => function ($query) {
            $query->where('status', 'completed');
        }])
            ->orderByDesc('payments_count')
            ->limit(5)
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'sales_count' => $package->payments_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_packages' => $totalPackages,
                'active_packages' => $activePackages,
                'total_revenue' => (float) $totalRevenue,
                'total_credits_sold' => (int) $totalCredits,
                'top_packages' => $topPackages,
            ],
        ]);
    }
}
