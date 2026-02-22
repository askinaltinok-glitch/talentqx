<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateContactPoint;
use App\Models\CandidateCredential;
use App\Models\CandidateProfile;
use App\Models\CandidateTimelineEvent;
use App\Models\PoolCandidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CandidateProfileController extends Controller
{
    /**
     * POST /v1/candidates/register-passive
     *
     * Public passive registration. Creates candidate + profile(status=passive).
     * Minimal: name, email OR phone, language, data_processing consent required.
     */
    public function registerPassive(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:128'],
            'last_name' => ['required', 'string', 'max:128'],
            'email' => ['required_without:phone', 'nullable', 'email', 'max:255'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:32'],
            'country_code' => ['required', 'string', 'size:2'],
            'language' => ['sometimes', 'string', 'max:10'],
            'data_processing_consent' => ['required', 'accepted'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
            'reminders_opt_in' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;
        $language = $data['language'] ?? 'en';

        // Check for existing candidate by email
        if ($email) {
            $existing = PoolCandidate::where('email', $email)->first();
            if ($existing) {
                // Ensure profile exists
                $existing->ensureProfile(CandidateProfile::STATUS_PASSIVE, $language);
                return response()->json([
                    'success' => true,
                    'data' => [
                        'candidate_id' => $existing->id,
                        'status' => 'passive',
                        'message' => 'Already registered. Profile updated.',
                    ],
                ]);
            }
        }

        $candidate = DB::transaction(function () use ($data, $email, $phone, $language) {
            $candidate = PoolCandidate::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $email ?? '',
                'phone' => $phone,
                'country_code' => $data['country_code'],
                'preferred_language' => $language,
                'source_channel' => 'organic',
                'candidate_source' => PoolCandidate::CANDIDATE_SOURCE_PUBLIC,
                'status' => PoolCandidate::STATUS_NEW,
                'primary_industry' => 'maritime',
            ]);

            // Create profile with passive status
            CandidateProfile::create([
                'pool_candidate_id' => $candidate->id,
                'status' => CandidateProfile::STATUS_PASSIVE,
                'preferred_language' => $language,
                'data_processing_consent_at' => now(),
                'marketing_opt_in' => $data['marketing_opt_in'] ?? false,
                'marketing_consent_at' => ($data['marketing_opt_in'] ?? false) ? now() : null,
                'reminders_opt_in' => $data['reminders_opt_in'] ?? false,
                'reminders_consent_at' => ($data['reminders_opt_in'] ?? false) ? now() : null,
            ]);

            // Create contact point for email
            if ($email) {
                CandidateContactPoint::create([
                    'pool_candidate_id' => $candidate->id,
                    'type' => CandidateContactPoint::TYPE_EMAIL,
                    'value' => $email,
                    'is_primary' => true,
                    'is_verified' => false,
                ]);
            }

            // Create contact point for phone
            if ($phone) {
                CandidateContactPoint::create([
                    'pool_candidate_id' => $candidate->id,
                    'type' => CandidateContactPoint::TYPE_PHONE,
                    'value' => $phone,
                    'is_primary' => true,
                    'is_verified' => false,
                ]);
            }

            // Timeline event
            CandidateTimelineEvent::record(
                $candidate->id,
                CandidateTimelineEvent::TYPE_PASSIVE_REGISTERED,
                CandidateTimelineEvent::SOURCE_PUBLIC,
                ['language' => $language]
            );

            return $candidate;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'candidate_id' => $candidate->id,
                'status' => 'passive',
                'message' => 'Registered successfully. Email verification required for full features.',
            ],
        ], 201);
    }

    /**
     * GET /v1/maritime/candidates/{id}/credentials
     *
     * Get candidate's credentials (credential wallet).
     */
    public function credentials(string $id): JsonResponse
    {
        $candidate = PoolCandidate::find($id);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        $credentials = $candidate->credentials()
            ->orderBy('credential_type')
            ->get()
            ->map(fn(CandidateCredential $c) => [
                'id' => $c->id,
                'credential_type' => $c->credential_type,
                'credential_number' => $c->credential_number,
                'issuer' => $c->issuer,
                'issued_at' => $c->issued_at?->toDateString(),
                'expires_at' => $c->expires_at?->toDateString(),
                'days_until_expiry' => $c->days_until_expiry,
                'file_url' => $c->file_url,
                'verification_status' => $c->verification_status,
                'created_at' => $c->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $credentials,
        ]);
    }

    /**
     * POST /v1/maritime/candidates/{id}/credentials
     *
     * Add a credential to candidate's wallet.
     */
    public function storeCredential(Request $request, string $id): JsonResponse
    {
        $candidate = PoolCandidate::find($id);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'credential_type' => ['required', 'string', 'max:50'],
            'credential_number' => ['nullable', 'string', 'max:100'],
            'issuer' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'file_url' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $credential = CandidateCredential::create([
            'pool_candidate_id' => $candidate->id,
            ...$validator->validated(),
            'verification_status' => CandidateCredential::VERIFICATION_SELF_DECLARED,
        ]);

        // Timeline event
        CandidateTimelineEvent::record(
            $candidate->id,
            CandidateTimelineEvent::TYPE_CREDENTIAL_UPLOADED,
            CandidateTimelineEvent::SOURCE_PUBLIC,
            [
                'credential_id' => $credential->id,
                'credential_type' => $credential->credential_type,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $credential->id,
                'credential_type' => $credential->credential_type,
                'expires_at' => $credential->expires_at?->toDateString(),
            ],
        ], 201);
    }

    /**
     * PUT /v1/maritime/candidates/{candidateId}/credentials/{credentialId}
     *
     * Update a credential.
     */
    public function updateCredential(Request $request, string $candidateId, string $credentialId): JsonResponse
    {
        $credential = CandidateCredential::where('pool_candidate_id', $candidateId)
            ->find($credentialId);

        if (!$credential) {
            return response()->json(['success' => false, 'message' => 'Credential not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'credential_type' => ['sometimes', 'string', 'max:50'],
            'credential_number' => ['nullable', 'string', 'max:100'],
            'issuer' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'file_url' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $credential->update($validator->validated());

        // Timeline event
        CandidateTimelineEvent::record(
            $candidateId,
            CandidateTimelineEvent::TYPE_CREDENTIAL_UPDATED,
            CandidateTimelineEvent::SOURCE_PUBLIC,
            [
                'credential_id' => $credential->id,
                'credential_type' => $credential->credential_type,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $credential->id,
                'credential_type' => $credential->credential_type,
                'expires_at' => $credential->expires_at?->toDateString(),
            ],
        ]);
    }

    /**
     * GET /v1/maritime/candidates/{id}/timeline
     *
     * Get candidate timeline events (public: limited types).
     */
    public function timeline(string $id): JsonResponse
    {
        $candidate = PoolCandidate::find($id);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        $events = $candidate->timelineEvents()
            ->portalSafe()
            ->limit(20)
            ->get()
            ->map(fn(CandidateTimelineEvent $e) => [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'source' => $e->source,
                'payload' => $e->payload_json,
                'created_at' => $e->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }
}
