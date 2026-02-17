<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmEmailThread;
use App\Models\CrmOutboundQueue;
use App\Jobs\GenerateReplyDraftJob;
use App\Jobs\SendOutboundEmailJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmInboxController extends Controller
{
    /**
     * GET /inbox/stats — Thread/draft/queue counts.
     */
    public function stats(): JsonResponse
    {
        $today = now()->startOfDay();

        return response()->json([
            'success' => true,
            'data' => [
                'open_threads' => CrmEmailThread::where('status', 'open')->count(),
                'snoozed_threads' => CrmEmailThread::where('status', 'snoozed')->count(),
                'pending_drafts' => CrmOutboundQueue::where('status', 'draft')->count(),
                'approved_queue' => CrmOutboundQueue::where('status', 'approved')->count(),
                'sent_today' => CrmOutboundQueue::where('status', 'sent')
                    ->where('sent_at', '>=', $today)->count(),
                'failed_today' => CrmOutboundQueue::where('status', 'failed')
                    ->where('updated_at', '>=', $today)->count(),
                'total_threads' => CrmEmailThread::count(),
            ],
        ]);
    }

    /**
     * GET /inbox/threads — List threads with filters.
     */
    public function threads(Request $request): JsonResponse
    {
        $query = CrmEmailThread::with(['lead.company', 'lead.contact'])
            ->orderByDesc('last_message_at');

        if ($request->filled('mailbox')) {
            $query->mailbox($request->mailbox);
        }
        if ($request->filled('status')) {
            $query->status($request->status);
        }
        if ($request->filled('industry')) {
            $query->industry($request->industry);
        }
        if ($request->filled('q')) {
            $query->search($request->q);
        }
        if ($request->filled('intent')) {
            $query->where('intent', $request->intent);
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
     * GET /inbox/threads/{id} — Thread with messages, lead, drafts.
     */
    public function threadDetail(string $id): JsonResponse
    {
        $thread = CrmEmailThread::with([
            'messages',
            'lead.company',
            'lead.contact',
            'lead.activities' => function ($q) { $q->limit(10); },
            'outboundQueue' => function ($q) { $q->whereIn('status', ['draft', 'approved']); },
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $thread,
        ]);
    }

    /**
     * PATCH /inbox/threads/{id} — Update thread status.
     */
    public function updateThread(Request $request, string $id): JsonResponse
    {
        $thread = CrmEmailThread::findOrFail($id);

        $v = $request->validate([
            'status' => 'sometimes|in:open,snoozed,closed,archived',
            'intent' => 'sometimes|string|max:64',
            'industry_code' => 'sometimes|in:general,maritime',
        ]);

        $thread->update($v);

        return response()->json([
            'success' => true,
            'message' => 'Thread updated.',
            'data' => $thread->fresh(),
        ]);
    }

    /**
     * POST /inbox/drafts/{id}/approve — Approve and queue for sending.
     */
    public function approveDraft(Request $request, string $id): JsonResponse
    {
        $draft = CrmOutboundQueue::where('status', 'draft')->findOrFail($id);

        $userId = $request->user()?->id;
        $draft->approve($userId);

        // Dispatch send job immediately
        SendOutboundEmailJob::dispatch($draft->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Draft approved and queued for sending.',
            'data' => $draft->fresh(),
        ]);
    }

    /**
     * POST /inbox/drafts/{id}/reject — Cancel draft.
     */
    public function rejectDraft(string $id): JsonResponse
    {
        $draft = CrmOutboundQueue::where('status', 'draft')->findOrFail($id);
        $draft->reject();

        return response()->json([
            'success' => true,
            'message' => 'Draft rejected.',
        ]);
    }

    /**
     * PATCH /inbox/drafts/{id} — Edit draft before approving.
     */
    public function editDraft(Request $request, string $id): JsonResponse
    {
        $draft = CrmOutboundQueue::where('status', 'draft')->findOrFail($id);

        $v = $request->validate([
            'subject' => 'sometimes|string|max:500',
            'body_text' => 'sometimes|string',
            'body_html' => 'sometimes|nullable|string',
            'to_email' => 'sometimes|email|max:255',
        ]);

        $draft->update($v);

        return response()->json([
            'success' => true,
            'message' => 'Draft updated.',
            'data' => $draft->fresh(),
        ]);
    }

    /**
     * POST /inbox/threads/{id}/regenerate-draft — Re-run AI draft generation.
     */
    public function regenerateDraft(string $id): JsonResponse
    {
        $thread = CrmEmailThread::findOrFail($id);

        // Cancel existing drafts for this thread
        CrmOutboundQueue::where('email_thread_id', $thread->id)
            ->where('status', 'draft')
            ->where('source', 'ai_reply')
            ->update(['status' => 'cancelled']);

        GenerateReplyDraftJob::dispatch($thread);

        return response()->json([
            'success' => true,
            'message' => 'Draft regeneration queued.',
        ]);
    }
}
