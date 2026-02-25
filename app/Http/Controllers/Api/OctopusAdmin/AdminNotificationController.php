<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminPushSubscription;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function __construct(
        private AdminNotificationService $service,
    ) {}

    /**
     * GET /notifications — paginated list with optional type filter.
     */
    public function index(Request $request): JsonResponse
    {
        $type    = $request->query('type');
        $page    = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 20), 50);

        $paginator = $this->service->list($page, $perPage, $type);

        return response()->json([
            'success' => true,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /notifications/unread-count
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => ['count' => $this->service->unreadCount()],
        ]);
    }

    /**
     * POST /notifications/mark-read — { ids: [...] } or { all: true }
     */
    public function markRead(Request $request): JsonResponse
    {
        if ($request->boolean('all')) {
            $count = $this->service->markAllRead();
        } else {
            $request->validate(['ids' => 'required|array', 'ids.*' => 'uuid']);
            $count = $this->service->markRead($request->input('ids'));
        }

        return response()->json(['success' => true, 'data' => ['marked' => $count]]);
    }

    /**
     * POST /notifications/push-subscriptions — register Web Push subscription.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'   => 'required|url|max:2048',
            'public_key' => 'required|string|max:255',
            'auth_token' => 'required|string|max:255',
        ]);

        $user = $request->user();

        AdminPushSubscription::updateOrCreate(
            [
                'admin_user_id' => $user->id,
                'endpoint_hash' => hash('sha256', $data['endpoint']),
            ],
            [
                'endpoint'   => $data['endpoint'],
                'public_key' => $data['public_key'],
                'auth_token' => $data['auth_token'],
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /notifications/push-subscriptions — unregister subscription.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|url|max:2048',
        ]);

        AdminPushSubscription::where('admin_user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->delete();

        return response()->json(['success' => true]);
    }
}
