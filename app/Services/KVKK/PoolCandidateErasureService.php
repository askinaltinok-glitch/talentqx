<?php

namespace App\Services\KVKK;

use App\Models\AuditLog;
use App\Models\PoolCandidate;
use App\Models\VoiceTranscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PoolCandidateErasureService
{
    /**
     * Erase a pool candidate's personal data (KVKK right-to-erasure).
     *
     * Anonymizes PII, cascades to voice files, interviews, certificates, consents.
     * Keeps anonymized analytics data for aggregate reporting.
     */
    public function erase(PoolCandidate $candidate, string $reason = 'kvkk_request', ?int $performedBy = null): array
    {
        if ($candidate->is_erased) {
            return [
                'success' => false,
                'error' => 'Candidate data has already been erased.',
            ];
        }

        $erasedTypes = [];

        DB::beginTransaction();

        try {
            $originalSnapshot = [
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
            ];

            // 1. Anonymize PII on pool_candidates
            $candidate->forceFill([
                'first_name' => '[SİLİNDİ]',
                'last_name' => '[SİLİNDİ]',
                'email' => 'erased_' . $candidate->id . '@erased.local',
                'phone' => null,
                'nationality' => null,
                'country_of_residence' => null,
                'passport_expiry' => null,
                'visa_status' => null,
                'license_country' => null,
                'license_class' => null,
                'flag_endorsement' => null,
                'source_meta' => null,
                'public_token' => null,
                'public_token_hash' => null,
                'is_erased' => true,
                'erased_at' => now(),
                'erasure_reason' => $reason,
            ])->saveQuietly();
            $erasedTypes[] = 'personal_info';

            // 2. Erase candidate profile
            if ($candidate->profile) {
                $candidate->profile->update([
                    'status' => 'erased',
                    'blocked_reason' => 'kvkk_erasure',
                    'blocked_at' => now(),
                ]);
                $erasedTypes[] = 'profile';
            }

            // 3. Delete contact points
            $candidate->contactPoints()->delete();
            $erasedTypes[] = 'contact_points';

            // 4. Anonymize form interview answers + delete consents
            $interviewIds = $candidate->formInterviews()->pluck('id');
            if ($interviewIds->isNotEmpty()) {
                // Anonymize answer texts (keep scores for analytics)
                DB::table('form_interview_answers')
                    ->whereIn('form_interview_id', $interviewIds)
                    ->update(['answer_text' => '[SİLİNDİ]']);

                // Delete consents tied to these interviews
                DB::table('candidate_consents')
                    ->whereIn('form_interview_id', $interviewIds)
                    ->delete();

                // Anonymize PII fields on interviews (keep scores)
                DB::table('form_interviews')
                    ->whereIn('id', $interviewIds)
                    ->update([
                        'meta' => null,
                        'admin_notes' => null,
                        'anonymized_at' => now(),
                    ]);

                $erasedTypes[] = 'interviews';
                $erasedTypes[] = 'consents';
            }

            // 5. Delete voice audio files + anonymize transcripts
            $voiceTranscriptions = VoiceTranscription::whereIn('interview_id', $interviewIds)->get();
            foreach ($voiceTranscriptions as $vt) {
                if ($vt->audio_path) {
                    Storage::disk('local')->delete($vt->audio_path);
                }
                $vt->update([
                    'audio_path' => null,
                    'audio_size_bytes' => null,
                    'audio_sha256' => null,
                    'transcript_text' => '[SİLİNDİ]',
                ]);
            }
            if ($voiceTranscriptions->isNotEmpty()) {
                $erasedTypes[] = 'voice_data';
            }

            // 6. Delete certificates
            $candidate->certificates()->delete();
            $erasedTypes[] = 'certificates';

            // 7. Delete credentials
            $candidate->credentials()->delete();
            $erasedTypes[] = 'credentials';

            // 8. Audit log
            AuditLog::create([
                'user_id' => $performedBy,
                'action' => 'kvkk_erasure',
                'entity_type' => PoolCandidate::class,
                'entity_id' => $candidate->id,
                'old_values' => $originalSnapshot,
                'new_values' => ['is_erased' => true, 'erasure_reason' => $reason],
                'metadata' => ['erased_types' => $erasedTypes],
            ]);

            DB::commit();

            Log::info('Pool candidate data erased (KVKK)', [
                'candidate_id' => $candidate->id,
                'reason' => $reason,
                'performed_by' => $performedBy,
                'erased_types' => $erasedTypes,
            ]);

            return [
                'success' => true,
                'erased_types' => $erasedTypes,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Pool candidate erasure failed', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export all data for a pool candidate (KVKK data portability).
     */
    public function exportData(PoolCandidate $candidate): array
    {
        $candidate->load([
            'profile',
            'contactPoints',
            'credentials',
            'certificates',
            'formInterviews.answers',
            'contracts',
            'trustProfile',
        ]);

        $interviewIds = $candidate->formInterviews->pluck('id');

        $consents = DB::table('candidate_consents')
            ->whereIn('form_interview_id', $interviewIds)
            ->get();

        $voiceTranscriptions = VoiceTranscription::whereIn('interview_id', $interviewIds)->get();

        return [
            'export_info' => [
                'exported_at' => now()->toIso8601String(),
                'data_subject' => 'Aday Havuzu Kişisel Verileri',
                'legal_basis' => 'KVKK Madde 11 — İlgili Kişinin Hakları',
            ],
            'personal_info' => [
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'nationality' => $candidate->nationality,
                'country_of_residence' => $candidate->country_of_residence,
                'country_code' => $candidate->country_code,
                'passport_expiry' => $candidate->passport_expiry?->toDateString(),
                'visa_status' => $candidate->visa_status,
                'preferred_language' => $candidate->preferred_language,
                'english_level_self' => $candidate->english_level_self,
                'status' => $candidate->status,
                'created_at' => $candidate->created_at->toIso8601String(),
            ],
            'profile' => $candidate->profile ? [
                'status' => $candidate->profile->status,
                'timezone' => $candidate->profile->timezone,
                'marketing_opt_in' => $candidate->profile->marketing_opt_in,
                'reminders_opt_in' => $candidate->profile->reminders_opt_in,
                'headhunt_opt_in' => $candidate->profile->headhunt_opt_in,
                'data_processing_consent_at' => $candidate->profile->data_processing_consent_at?->toIso8601String(),
            ] : null,
            'contact_points' => $candidate->contactPoints->map(fn($cp) => [
                'type' => $cp->type,
                'value' => $cp->value,
                'is_primary' => $cp->is_primary,
                'is_verified' => $cp->is_verified,
            ])->toArray(),
            'credentials' => $candidate->credentials->map(fn($c) => [
                'credential_type' => $c->credential_type,
                'credential_number' => $c->credential_number,
                'issuer' => $c->issuer,
                'issued_at' => $c->issued_at?->toDateString(),
                'expires_at' => $c->expires_at?->toDateString(),
            ])->toArray(),
            'certificates' => $candidate->certificates->map(fn($c) => [
                'certificate_type' => $c->certificate_type,
                'certificate_number' => $c->certificate_number ?? null,
                'issued_at' => $c->issued_at?->toDateString(),
                'expires_at' => $c->expires_at?->toDateString(),
            ])->toArray(),
            'interviews' => $candidate->formInterviews->map(fn($i) => [
                'id' => $i->id,
                'position_code' => $i->position_code,
                'status' => $i->status,
                'final_score' => $i->calibrated_score ?? $i->final_score,
                'decision' => $i->decision,
                'completed_at' => $i->completed_at?->toIso8601String(),
                'answers' => $i->answers->map(fn($a) => [
                    'slot' => $a->slot,
                    'answer_text' => $a->answer_text,
                    'competency' => $a->competency,
                ])->toArray(),
            ])->toArray(),
            'voice_transcriptions' => $voiceTranscriptions->map(fn($vt) => [
                'slot' => $vt->slot,
                'transcript_text' => $vt->transcript_text,
                'confidence' => $vt->confidence,
                'created_at' => $vt->created_at?->toIso8601String(),
            ])->toArray(),
            'consents' => $consents->map(fn($c) => [
                'consent_type' => $c->consent_type,
                'regulation' => $c->regulation,
                'granted' => $c->granted,
                'consented_at' => $c->consented_at,
            ])->toArray(),
            'contracts' => $candidate->contracts->map(fn($c) => [
                'vessel_name' => $c->vessel_name,
                'rank' => $c->rank,
                'start_date' => $c->start_date?->toDateString(),
                'end_date' => $c->end_date?->toDateString(),
            ])->toArray(),
        ];
    }
}
