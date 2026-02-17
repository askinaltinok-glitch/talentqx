<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Jobs\SendOutboundEmailJob;
use App\Models\CrmOutboundQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmOutboundQueueController extends Controller
{
    /**
     * GET /outbound-queue — List queue items with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CrmOutboundQueue::with(['lead.company', 'thread'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->status($request->status);
        }
        if ($request->filled('source')) {
            $query->source($request->source);
        }

        $perPage = min((int) ($request->per_page ?? 25), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * POST /outbound-queue/{id}/approve — Approve single item.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $item = CrmOutboundQueue::where('status', 'draft')->findOrFail($id);

        $userId = $request->user()?->id;
        $item->approve($userId);

        SendOutboundEmailJob::dispatch($item->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Outbound email approved and queued.',
            'data' => $item->fresh(),
        ]);
    }

    /**
     * POST /outbound-queue/bulk-approve — Approve multiple items.
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $v = $request->validate([
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'uuid',
        ]);

        $userId = $request->user()?->id;
        $items = CrmOutboundQueue::whereIn('id', $v['ids'])
            ->where('status', 'draft')
            ->get();

        foreach ($items as $item) {
            $item->approve($userId);
            SendOutboundEmailJob::dispatch($item->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => "Approved {$items->count()} email(s).",
        ]);
    }

    /**
     * POST /outbound-queue/{id}/reject — Cancel item.
     */
    public function reject(string $id): JsonResponse
    {
        $item = CrmOutboundQueue::where('status', 'draft')->findOrFail($id);
        $item->reject();

        return response()->json([
            'success' => true,
            'message' => 'Outbound email rejected.',
        ]);
    }
}
