<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Services\Maritime\QualificationCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualificationCheckController extends Controller
{
    public function __construct(
        private readonly QualificationCheckService $service,
    ) {}

    public function upsert(string $candidateId, string $qualificationKey, Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:self_declared,uploaded,verified,rejected',
            'notes' => 'nullable|string|max:2000',
            'evidence_url' => 'nullable|url|max:2000',
        ]);

        $check = $this->service->upsert(
            $candidateId,
            strtolower($qualificationKey),
            $data['status'],
            $data['notes'] ?? null,
            $data['evidence_url'] ?? null,
            $request->user()?->id,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $check->id,
                'candidate_id' => $check->candidate_id,
                'qualification_key' => $check->qualification_key,
                'status' => $check->status,
                'evidence_url' => $check->evidence_url,
                'notes' => $check->notes,
                'verified_by' => $check->verified_by,
                'verified_at' => $check->verified_at?->toIso8601String(),
                'updated_at' => $check->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
