<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\ApplyFormEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public endpoint for tracking apply form events (step views, completions, abandons).
 * No authentication required - fires from the client-side apply form.
 */
class ApplyTrackingController extends Controller
{
    /**
     * POST /v1/maritime/apply-events
     * Record a batch of apply form events.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:10'],
            'events.*.session_id' => ['required', 'string', 'max:64'],
            'events.*.event_type' => ['required', 'string', 'in:step_view,step_complete,abandon,submit'],
            'events.*.step_number' => ['required', 'integer', 'min:1', 'max:3'],
            'events.*.time_spent_seconds' => ['nullable', 'integer', 'min:0', 'max:7200'],
            'events.*.country_code' => ['nullable', 'string', 'max:4'],
            'events.*.source_channel' => ['nullable', 'string', 'max:64'],
            'events.*.meta' => ['nullable', 'array'],
        ]);

        $ipHash = hash('sha256', $request->ip() . config('app.key'));
        $userAgent = substr($request->userAgent() ?? '', 0, 512);

        foreach ($data['events'] as $event) {
            ApplyFormEvent::create([
                'session_id' => $event['session_id'],
                'event_type' => $event['event_type'],
                'step_number' => $event['step_number'],
                'time_spent_seconds' => $event['time_spent_seconds'] ?? null,
                'country_code' => $event['country_code'] ?? null,
                'source_channel' => $event['source_channel'] ?? null,
                'user_agent' => $userAgent,
                'ip_hash' => $ipHash,
                'meta' => $event['meta'] ?? null,
            ]);
        }

        return response()->json(['ok' => true], 201);
    }
}
