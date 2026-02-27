<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDemoRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DemoRequest::query()
            ->when($request->get('q'), fn($q, $search) =>
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            )
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at');

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->get('per_page', 20)),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total' => DemoRequest::count(),
                'this_month' => DemoRequest::where('created_at', '>=', now()->startOfMonth())->count(),
                'this_week' => DemoRequest::where('created_at', '>=', now()->startOfWeek())->count(),
                'by_source' => DemoRequest::selectRaw('COALESCE(source, \'direct\') as source, count(*) as count')
                    ->groupBy('source')->pluck('count', 'source'),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $demo = DemoRequest::findOrFail($id);
        return response()->json(['success' => true, 'data' => $demo]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $demo = DemoRequest::findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:new,contacted,demo_scheduled,converted,closed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $demo->update($validated);
        return response()->json(['success' => true, 'data' => $demo->fresh()]);
    }
}
