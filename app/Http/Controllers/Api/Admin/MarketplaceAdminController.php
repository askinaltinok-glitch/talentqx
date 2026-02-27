<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MarketplaceAccessRespondedMail;
use App\Models\MarketplaceAccessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Admin endpoints for managing marketplace access requests.
 * Auth: auth:sanctum + platform.octopus_admin
 */
class MarketplaceAdminController extends Controller
{
    /**
     * GET /octopus/admin/marketplace/requests
     * List all access requests with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->input('per_page', 20), 50);

        $query = MarketplaceAccessRequest::with([
            'requestingCompany:id,name',
            'requestingUser:id,first_name,last_name,email',
            'candidate' => fn($q) => $q->select('id', 'first_name', 'last_name', 'status'),
        ])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('company_id')) {
            $query->where('requesting_company_id', $request->input('company_id'));
        }

        $requests = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $requests->getCollection()->map(fn($r) => $this->transform($r)),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
                'last_page' => $requests->lastPage(),
            ],
        ]);
    }

    /**
     * GET /octopus/admin/marketplace/stats
     * Summary stats for marketplace requests.
     */
    public function stats(): JsonResponse
    {
        $total = MarketplaceAccessRequest::count();
        $pending = MarketplaceAccessRequest::where('status', 'pending')->count();
        $approved = MarketplaceAccessRequest::where('status', 'approved')->count();
        $rejected = MarketplaceAccessRequest::where('status', 'rejected')->count();
        $expired = MarketplaceAccessRequest::where('status', 'expired')->count();
        $last30d = MarketplaceAccessRequest::where('created_at', '>=', now()->subDays(30))->count();

        return response()->json([
            'success' => true,
            'data' => compact('total', 'pending', 'approved', 'rejected', 'expired', 'last30d'),
        ]);
    }

    /**
     * POST /octopus/admin/marketplace/requests/{id}/approve
     * Admin approves an access request.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $accessRequest = MarketplaceAccessRequest::findOrFail($id);

        if (!$accessRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_PENDING', 'message' => 'This request is no longer pending.'],
            ], 400);
        }

        $validated = $request->validate(['message' => 'nullable|string|max:500']);

        $accessRequest->approve($validated['message'] ?? null);

        Log::info('Marketplace request approved by admin', [
            'request_id' => $id,
            'admin_id' => $request->user()->id,
        ]);

        $requesterEmail = $accessRequest->requestingUser?->email;
        if ($requesterEmail) {
            Mail::to($requesterEmail)->queue(new MarketplaceAccessRespondedMail($accessRequest, 'approved'));

            try {
                app(\App\Services\AdminNotificationService::class)->notifyEmailSent(
                    'marketplace_access_approved',
                    $requesterEmail,
                    "Marketplace access approved (admin): {$accessRequest->id}",
                    ['request_id' => $accessRequest->id]
                );
            } catch (\Throwable) {}
        }

        return response()->json(['success' => true, 'data' => $this->transform($accessRequest->fresh())]);
    }

    /**
     * POST /octopus/admin/marketplace/requests/{id}/reject
     * Admin rejects an access request.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $accessRequest = MarketplaceAccessRequest::findOrFail($id);

        if (!$accessRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_PENDING', 'message' => 'This request is no longer pending.'],
            ], 400);
        }

        $validated = $request->validate(['message' => 'nullable|string|max:500']);

        $accessRequest->reject($validated['message'] ?? null);

        Log::info('Marketplace request rejected by admin', [
            'request_id' => $id,
            'admin_id' => $request->user()->id,
        ]);

        $requesterEmail = $accessRequest->requestingUser?->email;
        if ($requesterEmail) {
            Mail::to($requesterEmail)->queue(new MarketplaceAccessRespondedMail($accessRequest, 'rejected'));

            try {
                app(\App\Services\AdminNotificationService::class)->notifyEmailSent(
                    'marketplace_access_rejected',
                    $requesterEmail,
                    "Marketplace access rejected (admin): {$accessRequest->id}",
                    ['request_id' => $accessRequest->id]
                );
            } catch (\Throwable) {}
        }

        return response()->json(['success' => true, 'data' => $this->transform($accessRequest->fresh())]);
    }

    private function transform(MarketplaceAccessRequest $r): array
    {
        return [
            'id' => $r->id,
            'status' => $r->status,
            'requesting_company' => $r->requestingCompany?->name ?? '—',
            'requesting_user' => trim(($r->requestingUser?->first_name ?? '') . ' ' . ($r->requestingUser?->last_name ?? '')),
            'requesting_user_email' => $r->requestingUser?->email,
            'candidate_name' => trim(($r->candidate?->first_name ?? '') . ' ' . ($r->candidate?->last_name ?? '')),
            'candidate_id' => $r->candidate_id,
            'request_message' => $r->request_message,
            'response_message' => $r->response_message,
            'created_at' => $r->created_at->toIso8601String(),
            'responded_at' => $r->responded_at?->toIso8601String(),
            'token_expires_at' => $r->token_expires_at->toIso8601String(),
        ];
    }
}
