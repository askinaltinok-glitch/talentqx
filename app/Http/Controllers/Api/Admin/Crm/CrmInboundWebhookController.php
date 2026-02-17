<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInboundEmailJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmInboundWebhookController extends Controller
{
    /**
     * POST /webhooks/inbound-email — Receives forwarded email JSON.
     * Public endpoint: NOT under auth middleware, uses secret header.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Verify webhook secret
        $secret = config('crm_mail.webhook_secret');
        if ($secret && $request->header('X-Webhook-Secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $v = $request->validate([
            'from_email' => 'required|email|max:255',
            'from_name' => 'sometimes|nullable|string|max:255',
            'to_email' => 'required|email|max:255',
            'subject' => 'sometimes|nullable|string|max:500',
            'body_text' => 'sometimes|nullable|string',
            'body_html' => 'sometimes|nullable|string',
            'message_id' => 'sometimes|nullable|string|max:255',
            'in_reply_to' => 'sometimes|nullable|string|max:255',
            'references' => 'sometimes|nullable|string',
            'date' => 'sometimes|nullable|string',
            'mailbox' => 'sometimes|nullable|string|max:32',
        ]);

        $v['provider'] = 'webhook';

        ProcessInboundEmailJob::dispatch($v);

        return response()->json([
            'success' => true,
            'message' => 'Inbound email queued for processing.',
        ]);
    }
}
