<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Copilot\CopilotService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CopilotController extends Controller
{
    public function __construct(
        private CopilotService $copilotService
    ) {}

    /**
     * POST /v1/copilot/chat
     * Send a message and get AI response.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'context' => 'nullable|array',
            'context.type' => 'required_with:context|string|in:candidate,interview,job,comparison',
            'context.id' => 'required_with:context|string',
            'conversation_id' => 'nullable|uuid',
        ]);

        try {
            $result = $this->copilotService->chat(
                $request->user(),
                $validated['message'],
                $validated['context'] ?? null,
                $validated['conversation_id'] ?? null
            );

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Copilot chat error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COPILOT_ERROR',
                    'message' => 'An error occurred while processing your request. Please try again.',
                ],
            ], 500);
        }
    }

    /**
     * GET /v1/copilot/context/{type}/{id}
     * Preview context data for an entity.
     */
    public function contextPreview(Request $request, string $type, string $id): JsonResponse
    {
        $allowedTypes = ['candidate', 'interview', 'job'];

        if (!in_array($type, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CONTEXT_TYPE',
                    'message' => 'Invalid context type. Allowed types: ' . implode(', ', $allowedTypes),
                ],
            ], 400);
        }

        try {
            $preview = $this->copilotService->getContextPreview(
                $request->user(),
                $type,
                $id
            );

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (Exception $e) {
            Log::error('Copilot context preview error', [
                'user_id' => $request->user()->id,
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CONTEXT_ERROR',
                    'message' => 'Failed to retrieve context preview.',
                ],
            ], 500);
        }
    }

    /**
     * GET /v1/copilot/history
     * Get conversation history.
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => 'nullable|uuid',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $result = $this->copilotService->getHistory(
                $request->user(),
                $validated['conversation_id'] ?? null,
                $validated['limit'] ?? 20
            );

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Copilot history error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'HISTORY_ERROR',
                    'message' => 'Failed to retrieve conversation history.',
                ],
            ], 500);
        }
    }
}
