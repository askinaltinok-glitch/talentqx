<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\ComputeSeaTimeJob;
use App\Jobs\ComputeStabilityRiskJob;
use App\Jobs\ComputeTechnicalScoreJob;
use App\Jobs\RecomputeCriJob;
use App\Jobs\VerifyContractAisJob;
use App\Models\AisVerification;
use App\Models\CandidateContract;
use App\Models\PoolCandidate;
use App\Models\TrustEvent;
use App\Models\Company;
use App\Models\Vessel;
use App\Services\Ais\AisProviderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    /**
     * List contracts for a candidate (chronological).
     */
    public function index(string $id): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);

        $contracts = $candidate->contracts()
            ->orderBy('start_date')
            ->get()
            ->map(fn(CandidateContract $c) => [
                'id' => $c->id,
                'vessel_name' => $c->vessel_name,
                'vessel_imo' => $c->vessel_imo,
                'vessel_type' => $c->vessel_type,
                'company_name' => $c->company_name,
                'rank_code' => $c->rank_code,
                'start_date' => $c->start_date->toDateString(),
                'end_date' => $c->end_date?->toDateString(),
                'duration_months' => $c->durationMonths(),
                'trading_area' => $c->trading_area,
                'dwt_range' => $c->dwt_range,
                'source' => $c->source,
                'verified' => $c->verified,
                'verified_at' => $c->verified_at?->toIso8601String(),
                'notes' => $c->notes,
                'created_at' => $c->created_at?->toIso8601String(),
            ]);

        return response()->json(['success' => true, 'data' => $contracts]);
    }

    /**
     * Add a new contract.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);

        $validated = $request->validate([
            'vessel_name' => 'required|string|max:120',
            'vessel_imo' => 'nullable|string|max:20',
            'vessel_type' => 'nullable|string|max:30',
            'company_name' => 'required|string|max:120',
            'rank_code' => 'required|string|max:40',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'trading_area' => 'nullable|string|max:60',
            'dwt_range' => 'nullable|string|max:30',
            'source' => 'nullable|in:self_declared,ais,company_verified',
            'verified' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        // Auto-link vessel when IMO is provided
        if (!empty($validated['vessel_imo'])) {
            $vessel = Vessel::findOrCreateByImo(
                $validated['vessel_imo'],
                $validated['vessel_name'] ?? null,
                $validated['vessel_type'] ?? null,
            );
            $validated['vessel_id'] = $vessel->id;
        }

        $contract = $candidate->contracts()->create($validated);

        // Audit event
        try {
            TrustEvent::create([
                'pool_candidate_id' => $candidate->id,
                'event_type' => TrustEvent::TYPE_CONTRACT_ADDED,
                'payload_json' => [
                    'contract_id' => $contract->id,
                    'vessel_name' => $contract->vessel_name,
                    'rank_code' => $contract->rank_code,
                    'start_date' => $contract->start_date->toDateString(),
                    'end_date' => $contract->end_date?->toDateString(),
                ],
            ]);
        } catch (\Throwable) {}

        // Async CRI recompute + sea-time
        RecomputeCriJob::dispatch($candidate->id, 'contract_added');
        if (config('maritime.sea_time_v1')) {
            ComputeSeaTimeJob::dispatch($candidate->id);
        }
        if (config('maritime.rank_stcw_v1')) {
            ComputeTechnicalScoreJob::dispatch($candidate->id);
        }
        if (config('maritime.stability_v1')) {
            ComputeStabilityRiskJob::dispatch($candidate->id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $contract->id,
                'vessel_name' => $contract->vessel_name,
                'rank_code' => $contract->rank_code,
                'start_date' => $contract->start_date->toDateString(),
            ],
        ], 201);
    }

    /**
     * Update a contract.
     */
    public function update(Request $request, string $id, string $contractId): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);
        $contract = $candidate->contracts()->findOrFail($contractId);

        $validated = $request->validate([
            'vessel_name' => 'sometimes|required|string|max:120',
            'vessel_imo' => 'nullable|string|max:20',
            'vessel_type' => 'nullable|string|max:30',
            'company_name' => 'sometimes|required|string|max:120',
            'rank_code' => 'sometimes|required|string|max:40',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after:start_date',
            'trading_area' => 'nullable|string|max:60',
            'dwt_range' => 'nullable|string|max:30',
            'source' => 'nullable|in:self_declared,ais,company_verified',
            'verified' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        // Auto-link vessel when IMO is provided or changed
        if (!empty($validated['vessel_imo'])) {
            $vessel = Vessel::findOrCreateByImo(
                $validated['vessel_imo'],
                $validated['vessel_name'] ?? $contract->vessel_name,
                $validated['vessel_type'] ?? $contract->vessel_type,
            );
            $validated['vessel_id'] = $vessel->id;
        }

        $contract->update($validated);

        // Audit event
        try {
            TrustEvent::create([
                'pool_candidate_id' => $candidate->id,
                'event_type' => TrustEvent::TYPE_CONTRACT_UPDATED,
                'payload_json' => [
                    'contract_id' => $contract->id,
                    'changes' => array_keys($validated),
                ],
            ]);
        } catch (\Throwable) {}

        // Async CRI recompute + sea-time
        RecomputeCriJob::dispatch($candidate->id, 'contract_updated');
        if (config('maritime.sea_time_v1')) {
            ComputeSeaTimeJob::dispatch($candidate->id);
        }
        if (config('maritime.rank_stcw_v1')) {
            ComputeTechnicalScoreJob::dispatch($candidate->id);
        }
        if (config('maritime.stability_v1')) {
            ComputeStabilityRiskJob::dispatch($candidate->id);
        }

        return response()->json(['success' => true, 'message' => 'Contract updated']);
    }

    /**
     * Delete a contract.
     */
    public function destroy(string $id, string $contractId): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);
        $contract = $candidate->contracts()->findOrFail($contractId);

        $payload = [
            'contract_id' => $contract->id,
            'vessel_name' => $contract->vessel_name,
            'rank_code' => $contract->rank_code,
        ];

        $contract->delete();

        // Audit event
        try {
            TrustEvent::create([
                'pool_candidate_id' => $candidate->id,
                'event_type' => TrustEvent::TYPE_CONTRACT_DELETED,
                'payload_json' => $payload,
            ]);
        } catch (\Throwable) {}

        // Async CRI recompute + sea-time
        RecomputeCriJob::dispatch($candidate->id, 'contract_deleted');
        if (config('maritime.sea_time_v1')) {
            ComputeSeaTimeJob::dispatch($candidate->id);
        }
        if (config('maritime.rank_stcw_v1')) {
            ComputeTechnicalScoreJob::dispatch($candidate->id);
        }
        if (config('maritime.stability_v1')) {
            ComputeStabilityRiskJob::dispatch($candidate->id);
        }

        return response()->json(['success' => true, 'message' => 'Contract deleted']);
    }

    /**
     * Verify a contract (company-verified).
     */
    public function verify(Request $request, string $id, string $contractId): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);
        $contract = $candidate->contracts()->findOrFail($contractId);

        if ($contract->verified) {
            return response()->json(['success' => false, 'message' => 'Contract already verified'], 422);
        }

        $validated = $request->validate([
            'company_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')->where('platform', Company::PLATFORM_OCTOPUS)],
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $contract->update([
            'source' => CandidateContract::SOURCE_COMPANY_VERIFIED,
            'verified' => true,
            'verified_by_company_id' => $validated['company_id'] ?? null,
            'verified_by_user_id' => $user?->id,
            'verified_at' => now(),
            'notes' => $validated['notes'] ?? $contract->notes,
        ]);

        // Audit event
        try {
            TrustEvent::create([
                'pool_candidate_id' => $candidate->id,
                'event_type' => TrustEvent::TYPE_CONTRACT_VERIFIED,
                'payload_json' => [
                    'contract_id' => $contract->id,
                    'vessel_name' => $contract->vessel_name,
                    'verified_by_user_id' => $user?->id,
                    'verified_by_company_id' => $validated['company_id'] ?? null,
                ],
            ]);
        } catch (\Throwable) {}

        // Async CRI recompute + sea-time
        RecomputeCriJob::dispatch($candidate->id, 'contract_verified');
        if (config('maritime.sea_time_v1')) {
            ComputeSeaTimeJob::dispatch($candidate->id);
        }
        if (config('maritime.rank_stcw_v1')) {
            ComputeTechnicalScoreJob::dispatch($candidate->id);
        }
        if (config('maritime.stability_v1')) {
            ComputeStabilityRiskJob::dispatch($candidate->id);
        }

        return response()->json(['success' => true, 'message' => 'Contract verified']);
    }

    /**
     * POST /candidates/{id}/contracts/{contractId}/ais/verify
     * Trigger AIS verification for a contract.
     */
    public function aisVerify(Request $request, string $id, string $contractId): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);
        $contract = $candidate->contracts()->findOrFail($contractId);

        if (!$contract->vessel_imo) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot verify: no IMO number on this contract',
            ], 422);
        }

        // Find or create vessel stub
        $vessel = Vessel::findOrCreateByImo(
            $contract->vessel_imo,
            $contract->vessel_name,
            $contract->vessel_type
        );

        // Upsert AIS verification record
        $ais = AisVerification::updateOrCreate(
            ['candidate_contract_id' => $contract->id],
            [
                'vessel_id' => $vessel->id,
                'status' => AisVerification::STATUS_PENDING,
                'triggered_by_user_id' => $request->user()?->id,
            ]
        );

        // Audit
        try {
            TrustEvent::create([
                'pool_candidate_id' => $candidate->id,
                'event_type' => 'ais_verify_triggered',
                'payload_json' => [
                    'contract_id' => $contract->id,
                    'vessel_imo' => $contract->vessel_imo,
                    'vessel_id' => $vessel->id,
                ],
            ]);
        } catch (\Throwable) {}

        // Dispatch engine verification if ais_v1 enabled
        if (config('maritime.ais_v1')) {
            VerifyContractAisJob::dispatch($contract->id, 'admin', $request->user()?->id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'verification_id' => $ais->id,
                'status' => $ais->status,
                'vessel_id' => $vessel->id,
            ],
        ]);
    }

    /**
     * PUT /candidates/{id}/contracts/{contractId}/vessel-imo
     * Set or update vessel IMO on a contract.
     */
    public function setVesselImo(Request $request, string $id, string $contractId): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);
        $contract = $candidate->contracts()->findOrFail($contractId);

        $validated = $request->validate([
            'vessel_imo' => 'required|string|max:20',
        ]);

        $oldImo = $contract->vessel_imo;

        // Auto-link vessel when IMO is set
        $vessel = Vessel::findOrCreateByImo(
            $validated['vessel_imo'],
            $contract->vessel_name,
            $contract->vessel_type,
        );
        $contract->update([
            'vessel_imo' => $validated['vessel_imo'],
            'vessel_id' => $vessel->id,
        ]);

        // If IMO changed, reset existing AIS verification
        if ($oldImo !== $validated['vessel_imo'] && $contract->aisVerification) {
            $contract->aisVerification->delete();
        }

        // Audit
        try {
            TrustEvent::create([
                'pool_candidate_id' => $candidate->id,
                'event_type' => 'vessel_imo_set',
                'payload_json' => [
                    'contract_id' => $contract->id,
                    'old_imo' => $oldImo,
                    'new_imo' => $validated['vessel_imo'],
                ],
            ]);
        } catch (\Throwable) {}

        RecomputeCriJob::dispatch($candidate->id, 'vessel_imo_set');
        if (config('maritime.sea_time_v1')) {
            ComputeSeaTimeJob::dispatch($candidate->id);
        }
        if (config('maritime.rank_stcw_v1')) {
            ComputeTechnicalScoreJob::dispatch($candidate->id);
        }
        if (config('maritime.stability_v1')) {
            ComputeStabilityRiskJob::dispatch($candidate->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vessel IMO updated',
            'data' => ['vessel_imo' => $validated['vessel_imo']],
        ]);
    }

    /**
     * POST /vessels/{imo}/refresh
     * Stub: refresh vessel data from AIS source.
     */
    public function refreshVessel(string $imo): JsonResponse
    {
        $vessel = Vessel::where('imo', $imo)->first();

        if (!$vessel) {
            return response()->json([
                'success' => false,
                'message' => 'Vessel not found',
            ], 404);
        }

        $message = 'AIS refresh not yet connected. Showing cached data.';

        // If AIS v1 enabled, fetch fresh static data from provider
        if (config('maritime.ais_v1')) {
            try {
                $provider = app(AisProviderInterface::class);
                $static = $provider->fetchVesselStatic($imo);
                if ($static) {
                    $vessel->update(array_filter([
                        'name' => $static->name,
                        'mmsi' => $static->mmsi,
                        'type' => $static->type,
                        'flag' => $static->flag,
                        'dwt' => $static->dwt,
                        'gt' => $static->gt,
                        'length_m' => $static->lengthM,
                        'beam_m' => $static->beamM,
                        'year_built' => $static->yearBuilt,
                        'last_seen_at' => $static->lastSeenAt,
                    ], fn($v) => $v !== null));
                    $vessel->refresh();
                    $message = 'Vessel data refreshed from AIS provider.';
                }
            } catch (\Throwable) {
                // fail-open: return cached data
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'imo' => $vessel->imo,
                'name' => $vessel->name,
                'type' => $vessel->type,
                'flag' => $vessel->flag,
                'dwt' => $vessel->dwt,
                'gt' => $vessel->gt,
                'length_m' => $vessel->length_m,
                'beam_m' => $vessel->beam_m,
                'year_built' => $vessel->year_built,
                'last_seen_at' => $vessel->last_seen_at?->toIso8601String(),
                'data_source' => $vessel->data_source,
            ],
            'message' => $message,
        ]);
    }
}
