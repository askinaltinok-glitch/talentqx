<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\CrmSequence;
use App\Models\CrmSequenceEnrollment;
use App\Services\Mail\SequenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmSequenceController extends Controller
{
    /**
     * GET /sequences — List sequences.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CrmSequence::withCount('enrollments')
            ->orderByDesc('created_at');

        if ($request->filled('industry')) {
            $query->industry($request->industry);
        }
        if ($request->filled('language')) {
            $query->language($request->language);
        }
        if ($request->has('active')) {
            $query->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /**
     * POST /sequences — Create sequence.
     */
    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'name' => 'required|string|max:128',
            'industry_code' => 'sometimes|string|max:32',
            'language' => 'sometimes|string|size:2',
            'steps' => 'required|array|min:1',
            'steps.*.delay_days' => 'required|integer|min:0',
            'steps.*.template_key' => 'required|string|max:64',
            'steps.*.channel' => 'sometimes|string|in:email',
            'description' => 'sometimes|nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        $sequence = CrmSequence::create($v);

        return response()->json([
            'success' => true,
            'message' => 'Sequence created.',
            'data' => $sequence,
        ], 201);
    }

    /**
     * GET /sequences/{id} — Sequence detail with enrollments.
     */
    public function show(string $id): JsonResponse
    {
        $sequence = CrmSequence::with(['enrollments.lead.company'])
            ->withCount('enrollments')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $sequence,
        ]);
    }

    /**
     * PUT /sequences/{id} — Update sequence.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $sequence = CrmSequence::findOrFail($id);

        $v = $request->validate([
            'name' => 'sometimes|string|max:128',
            'industry_code' => 'sometimes|string|max:32',
            'language' => 'sometimes|string|size:2',
            'steps' => 'sometimes|array|min:1',
            'steps.*.delay_days' => 'required_with:steps|integer|min:0',
            'steps.*.template_key' => 'required_with:steps|string|max:64',
            'steps.*.channel' => 'sometimes|string|in:email',
            'description' => 'sometimes|nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        $sequence->update($v);

        return response()->json([
            'success' => true,
            'message' => 'Sequence updated.',
            'data' => $sequence->fresh(),
        ]);
    }

    /**
     * POST /sequences/{id}/enroll — Enroll lead in sequence.
     */
    public function enroll(Request $request, string $id, SequenceService $service): JsonResponse
    {
        $sequence = CrmSequence::findOrFail($id);

        $v = $request->validate([
            'lead_id' => 'required|uuid|exists:crm_leads,id',
        ]);

        $lead = CrmLead::findOrFail($v['lead_id']);
        $enrollment = $service->enroll($lead, $sequence);

        return response()->json([
            'success' => true,
            'message' => 'Lead enrolled in sequence.',
            'data' => $enrollment,
        ], 201);
    }

    /**
     * POST /enrollments/{id}/cancel — Cancel enrollment.
     */
    public function cancelEnrollment(string $id, SequenceService $service): JsonResponse
    {
        $enrollment = CrmSequenceEnrollment::findOrFail($id);
        $service->cancel($enrollment);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment cancelled.',
        ]);
    }
}
