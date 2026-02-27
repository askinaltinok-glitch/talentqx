<?php

namespace App\Services;

use App\Models\FormInterview;
use App\Models\PoolCandidate;
use App\Models\SeafarerCertificate;
use App\Services\Interview\FormInterviewService;
use App\Services\ML\ModelFeatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemoCandidateService
{
    private const DEMO_PROFILES = [
        [
            'first_name' => 'Alexei',
            'last_name' => 'Petrov',
            'country_code' => 'RU',
            'preferred_language' => 'en',
            'english_level_self' => 'B2',
            'rank' => 'chief_officer',
            'experience_years' => 12,
            'vessel_types' => ['bulk_carrier', 'tanker'],
            'certificates' => ['stcw', 'coc', 'goc', 'ecdis', 'arpa'],
        ],
        [
            'first_name' => 'Mehmet',
            'last_name' => 'Yilmaz',
            'country_code' => 'TR',
            'preferred_language' => 'en',
            'english_level_self' => 'B1',
            'rank' => 'second_engineer',
            'experience_years' => 8,
            'vessel_types' => ['container', 'bulk_carrier'],
            'certificates' => ['stcw', 'coc', 'erm'],
        ],
        [
            'first_name' => 'Dmytro',
            'last_name' => 'Kovalenko',
            'country_code' => 'UA',
            'preferred_language' => 'en',
            'english_level_self' => 'C1',
            'rank' => 'master',
            'experience_years' => 18,
            'vessel_types' => ['tanker', 'lng_carrier'],
            'certificates' => ['stcw', 'coc', 'goc', 'ecdis', 'arpa', 'brm', 'hazmat'],
        ],
        [
            'first_name' => 'Rajesh',
            'last_name' => 'Kumar',
            'country_code' => 'IN',
            'preferred_language' => 'en',
            'english_level_self' => 'B2',
            'rank' => 'third_officer',
            'experience_years' => 4,
            'vessel_types' => ['bulk_carrier'],
            'certificates' => ['stcw', 'coc', 'goc'],
        ],
        [
            'first_name' => 'Andrei',
            'last_name' => 'Ionescu',
            'country_code' => 'RO',
            'preferred_language' => 'en',
            'english_level_self' => 'B2',
            'rank' => 'bosun',
            'experience_years' => 10,
            'vessel_types' => ['container', 'ro_ro'],
            'certificates' => ['stcw', 'coc'],
        ],
    ];

    private const DEMO_ANSWERS = [
        'communication' => 'During my last vessel changeover, I needed to brief the incoming crew about critical safety procedures specific to our LNG cargo operations. I organized a structured handover meeting, created visual aids showing the cargo manifold system, and used simple maritime terminology that transcended language barriers. I confirmed understanding by asking crew members to repeat key procedures. The result was a zero-incident changeover period, and the incoming Chief Officer commended the thorough briefing.',
        'accountability' => 'While serving as Second Officer, I miscalculated our arrival ETA due to not properly accounting for a current change. When I realized the error, I immediately informed the Captain and contacted the port agent to adjust our berth window. I then recalculated using updated weather routing data and provided a corrected ETA. I documented the incident in my personal logbook and developed a checklist for ETA calculations that I now use consistently. The Captain appreciated my transparency.',
        'teamwork' => 'During a complex cargo operation in Rotterdam, our deck team and engine team had different views on the discharge sequence. The Chief Engineer wanted to prioritize ballast operations while our Bosun was concerned about deck safety during heavy weather. I facilitated a meeting where both sides presented their concerns. We developed a modified plan that addressed ballast stability while maintaining safe deck operations through adjusted watch schedules. The cargo operation completed safely and ahead of schedule.',
        'stress_resilience' => 'During a severe storm in the North Sea, we faced simultaneous challenges: cargo securing needed attention, a crew member required medical attention, and we received a navigational warning about a nearby vessel in distress. I prioritized tasks using ISM code procedures: delegated cargo securing to the Bosun with specific instructions, coordinated medical care via satellite with TMAS, and maintained safe navigation while monitoring the distress situation. We managed all three situations without incident over a 16-hour period.',
        'problem_solving' => 'Our vessel experienced a steering gear malfunction entering the Bosphorus Strait. With limited maneuvering capability in one of the busiest waterways, I immediately notified VTS Istanbul, coordinated with the pilot, and implemented our emergency steering procedures. We switched to the backup hydraulic system while the engineering team identified a failed servo valve. I arranged for replacement parts at our next port in Constanta. The entire situation was resolved within the transit without requiring tug assistance.',
        'leadership' => 'When I was promoted to Chief Officer, I inherited a crew with low morale due to extended contracts. I implemented weekly informal meetings where crew members could voice concerns, established a mentoring system pairing experienced ABs with cadets, and worked with the company to arrange crew entertainment equipment. Within two months, our safety observation reports increased by 40%, indicating better crew engagement, and the Master noted improved deck maintenance standards.',
        'adaptability' => 'Our vessel was diverted mid-voyage from a European port to West Africa due to charter changes. This required completely different cargo documentation, different weather routing, and crew preparations for a tropical environment. I reorganized the voyage plan within 24 hours, coordinated with the new port agents, ensured all tropical disease preventive measures were in place, and briefed the crew on the new port security requirements. We arrived fully prepared and commenced cargo operations without delay.',
        'safety_awareness' => 'During a routine inspection, I noticed micro-cracks forming on a crane wire that had recently passed its load test. Rather than simply logging it, I immediately took the crane out of service, documented the finding with photographs, and consulted the classification society guidelines. The wire was replaced at the next port, and I recommended to the company that inspection intervals for similar high-use equipment be shortened. This practice was adopted fleet-wide.',
    ];

    public function __construct(
        private PoolCandidateService $candidateService,
        private FormInterviewService $interviewService,
        private ModelFeatureService $featureService,
    ) {}

    public function createDemoCandidate(?int $profileIndex = null): array
    {
        if (!config('app.demo_mode')) {
            return ['success' => false, 'error' => 'Demo mode is disabled'];
        }

        $profile = self::DEMO_PROFILES[$profileIndex ?? array_rand(self::DEMO_PROFILES)];
        $timestamp = now()->timestamp;

        return DB::transaction(function () use ($profile, $timestamp) {
            // 1. Create candidate
            $candidate = PoolCandidate::create([
                'first_name' => $profile['first_name'],
                'last_name' => $profile['last_name'],
                'email' => "demo_{$timestamp}_" . strtolower($profile['last_name']) . '@octopus-ai.demo',
                'phone' => '+905550000' . rand(100, 999),
                'country_code' => $profile['country_code'],
                'preferred_language' => $profile['preferred_language'],
                'english_level_self' => $profile['english_level_self'],
                'source_channel' => 'demo',
                'source_meta' => [
                    'rank' => $profile['rank'],
                    'experience_years' => $profile['experience_years'],
                    'vessel_types' => $profile['vessel_types'],
                    'certificates' => $profile['certificates'],
                    'is_demo' => true,
                    'created_by' => 'demo_system',
                    'registration_ip' => '127.0.0.1',
                    'registration_ua' => 'Octopus AI Demo System',
                    'registered_at' => now()->toIso8601String(),
                ],
                'status' => PoolCandidate::STATUS_NEW,
                'primary_industry' => PoolCandidate::INDUSTRY_MARITIME,
                'is_demo' => true,
                'seafarer' => true,
                'english_assessment_required' => true,
                'video_assessment_required' => true,
            ]);

            Log::channel('single')->info('Demo candidate created', [
                'candidate_id' => $candidate->id,
                'name' => $candidate->full_name,
            ]);

            // 2. Start interview
            $interview = $this->candidateService->startInterview(
                candidate: $candidate,
                positionCode: $this->mapRankToPosition($profile['rank']),
                industryCode: PoolCandidate::INDUSTRY_MARITIME,
                consents: [
                    'privacy_policy' => true,
                    'data_processing' => true,
                ],
                countryCode: $profile['country_code'],
                regulation: $this->getRegulation($profile['country_code'])
            );

            // 3. Generate answers from template
            $this->generateDemoAnswers($interview);

            // 3b. Mark interview as demo
            $interview->update(['is_demo' => true]);

            // 4. Score via full pipeline
            $interview = $this->interviewService->completeAndScore($interview);

            // 5. Update English assessment
            $englishScore = $this->englishLevelToScore($profile['english_level_self']);
            $featureResult = null;
            try {
                $featureResult = $this->featureService->updateEnglishAssessment(
                    $interview->id,
                    $englishScore,
                    'demo_auto',
                    'Auto-scored from self-declared level: ' . $profile['english_level_self']
                );
            } catch (\Throwable $e) {
                Log::channel('single')->info('Demo: English assessment feature update skipped', [
                    'interview_id' => $interview->id,
                    'reason' => $e->getMessage(),
                ]);
            }

            // Update interview record
            $interview->update([
                'english_assessment_status' => 'completed',
                'english_assessment_score' => $englishScore,
            ]);

            // 6. Attach demo video
            $videoUrl = 'https://octopus-ai.net/demo/video/' . $candidate->id;
            try {
                $this->featureService->attachVideoAssessment(
                    $interview->id,
                    $videoUrl,
                    'demo_auto'
                );
            } catch (\Throwable $e) {
                Log::channel('single')->info('Demo: Video feature update skipped', [
                    'interview_id' => $interview->id,
                    'reason' => $e->getMessage(),
                ]);
            }

            $interview->update([
                'video_assessment_status' => 'completed',
                'video_assessment_url' => $videoUrl,
            ]);

            // 7. Add STCW certificates
            $this->createDemoCertificates($candidate, $profile);

            // 7b. Propagate is_demo to ML tables
            if ($interview->modelFeature) {
                $interview->modelFeature->update(['is_demo' => true]);
            }
            $interview->modelPredictions()->update(['is_demo' => true]);
            if ($interview->outcome) {
                $interview->outcome->update(['is_demo' => true]);
            }

            // 8. Move candidate to pool
            $candidate->moveToPool(PoolCandidate::INDUSTRY_MARITIME);

            $interview->refresh();
            $candidate->refresh();

            Log::channel('single')->info('Demo candidate pipeline complete', [
                'candidate_id' => $candidate->id,
                'interview_id' => $interview->id,
                'decision' => $interview->decision,
                'score' => $interview->calibrated_score,
            ]);

            return [
                'success' => true,
                'data' => [
                    'candidate' => [
                        'id' => $candidate->id,
                        'name' => $candidate->full_name,
                        'email' => $candidate->email,
                        'status' => $candidate->status,
                        'country' => $candidate->country_code,
                        'rank' => $profile['rank'],
                        'english_level' => $profile['english_level_self'],
                    ],
                    'interview' => [
                        'id' => $interview->id,
                        'status' => $interview->status,
                        'decision' => $interview->decision,
                        'raw_score' => $interview->raw_final_score,
                        'calibrated_score' => $interview->calibrated_score,
                        'final_score' => $interview->final_score,
                        'english_score' => $englishScore,
                        'video_url' => $videoUrl,
                    ],
                    'assessment' => [
                        'english' => 'completed',
                        'video' => 'completed',
                        'prediction' => $featureResult['new_prediction'] ?? null,
                    ],
                ],
            ];
        });
    }

    /**
     * List all demo candidates with their interview data.
     */
    public function listDemoCandidates(): array
    {
        return PoolCandidate::withoutGlobalScope('exclude_demo')
            ->where('source_channel', 'demo')
            ->with(['formInterviews' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($c) {
                $interview = $c->formInterviews->first();
                return [
                    'id' => $c->id,
                    'name' => $c->full_name,
                    'email' => $c->email,
                    'country' => $c->country_code,
                    'status' => $c->status,
                    'rank' => $c->source_meta['rank'] ?? null,
                    'english_level' => $c->english_level_self,
                    'interview_id' => $interview?->id,
                    'decision' => $interview?->decision,
                    'score' => $interview?->calibrated_score ?? $interview?->final_score,
                    'created_at' => $c->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }

    /**
     * Create STCW certificates for a demo candidate.
     */
    private function createDemoCertificates(PoolCandidate $candidate, array $profile): void
    {
        $certMapping = [
            'stcw' => ['type' => 'STCW_BASIC', 'authority' => 'Maritime Authority'],
            'coc' => ['type' => 'COC', 'authority' => 'Flag State Administration'],
            'goc' => ['type' => 'GOC', 'authority' => 'GMDSS Authority'],
            'ecdis' => ['type' => 'ECDIS', 'authority' => 'Maritime Training Centre'],
            'arpa' => ['type' => 'ARPA', 'authority' => 'Maritime Training Centre'],
            'brm' => ['type' => 'BRM', 'authority' => 'Maritime Academy'],
            'erm' => ['type' => 'ERM', 'authority' => 'Maritime Academy'],
            'hazmat' => ['type' => 'HAZMAT', 'authority' => 'Safety Authority'],
        ];

        foreach ($profile['certificates'] ?? [] as $certCode) {
            $mapping = $certMapping[$certCode] ?? null;
            if (!$mapping) continue;

            try {
                SeafarerCertificate::create([
                    'pool_candidate_id' => $candidate->id,
                    'certificate_type' => $mapping['type'],
                    'certificate_code' => 'DEMO-' . strtoupper($certCode) . '-' . rand(10000, 99999),
                    'issuing_authority' => $mapping['authority'],
                    'issuing_country' => $candidate->country_code,
                    'issued_at' => now()->subYears(rand(1, 3)),
                    'expires_at' => now()->addYears(rand(2, 5)),
                    'verification_status' => 'verified',
                    'verified_by' => 'demo_system',
                    'verified_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::channel('single')->info('Demo: Certificate creation skipped', [
                    'cert' => $certCode,
                    'reason' => $e->getMessage(),
                ]);
            }
        }
    }

    private function generateDemoAnswers(FormInterview $interview): void
    {
        $template = json_decode($interview->template_json, true);
        $questions = $template['generic_template']['questions']
            ?? $template['questions']
            ?? [];

        $answers = [];
        foreach ($questions as $q) {
            $competency = $q['competency'] ?? 'general';
            $answerText = self::DEMO_ANSWERS[$competency]
                ?? self::DEMO_ANSWERS['communication']; // fallback

            $answers[] = [
                'slot' => $q['slot'],
                'competency' => $competency,
                'answer_text' => $answerText,
            ];
        }

        if (!empty($answers)) {
            $this->interviewService->upsertAnswers($interview, $answers);
        }
    }

    private function mapRankToPosition(string $rank): string
    {
        return match (true) {
            in_array($rank, ['master', 'chief_officer', 'second_officer', 'third_officer']) => 'deck_officer',
            in_array($rank, ['chief_engineer', 'second_engineer', 'third_engineer', 'fourth_engineer']) => 'engineer_officer',
            in_array($rank, ['deck_cadet', 'engine_cadet']) => 'cadet',
            in_array($rank, ['bosun', 'ab_seaman', 'ordinary_seaman']) => 'deck_rating',
            in_array($rank, ['motorman', 'oiler', 'wiper', 'fitter']) => 'engine_rating',
            in_array($rank, ['chief_cook', 'cook', 'messman', 'steward']) => 'catering',
            default => '__maritime_generic__',
        };
    }

    private function getRegulation(string $countryCode): string
    {
        $euCountries = [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
            'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
            'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
        ];

        if (in_array($countryCode, $euCountries)) {
            return 'GDPR';
        }

        return match ($countryCode) {
            'GB' => 'UK_GDPR',
            'TR' => 'KVKK',
            default => 'STANDARD',
        };
    }

    private function englishLevelToScore(string $level): int
    {
        return match ($level) {
            'A1' => 20,
            'A2' => 35,
            'B1' => 50,
            'B2' => 70,
            'C1' => 85,
            'C2' => 95,
            default => 50,
        };
    }

    /**
     * Cleanup all demo candidates from the system.
     */
    public function cleanupDemoCandidates(): array
    {
        $demoCandidates = PoolCandidate::withoutGlobalScope('exclude_demo')
            ->where('source_channel', 'demo')->get();
        $count = $demoCandidates->count();

        foreach ($demoCandidates as $candidate) {
            // Delete related data in correct order
            foreach ($candidate->formInterviews as $interview) {
                $interview->modelPredictions()->delete();
                $interview->modelFeature()->delete();
                $interview->outcome()->delete();
                $interview->consents()->delete();
                $interview->answers()->delete();
                $interview->delete();
            }
            $candidate->certificates()->delete();
            $candidate->delete();
        }

        return ['success' => true, 'deleted_count' => $count];
    }
}
