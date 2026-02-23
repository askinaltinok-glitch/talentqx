<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemoRequestAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);

        $demos = DemoRequest::query()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $demos->items(),
            'meta' => [
                'current_page' => $demos->currentPage(),
                'per_page' => $demos->perPage(),
                'total' => $demos->total(),
                'last_page' => $demos->lastPage(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        $total = DemoRequest::count();
        $last30 = DemoRequest::where('created_at', '>=', now()->subDays(30))->count();
        $last7 = DemoRequest::where('created_at', '>=', now()->subDays(7))->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'last_30_days' => $last30,
                'last_7_days' => $last7,
            ],
        ]);
    }
}
