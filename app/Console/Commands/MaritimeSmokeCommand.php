<?php

namespace App\Console\Commands;

use App\Models\FormInterview;
use App\Models\InterviewOutcome;
use App\Models\ModelFeature;
use App\Models\ModelPrediction;
use App\Models\ModelWeight;
use App\Models\PoolCandidate;
use App\Services\FormInterview\FormInterviewService;
use App\Services\ML\MlLearningService;
use App\Services\ML\MlScoringService;
use App\Services\ML\ModelFeatureService;
use App\Services\PoolCandidateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * MaritimeSmokeCommand
 *
 * End-to-end smoke test for the Maritime Supply pipeline:
 * Candidate Registration → Interview → Scoring → Pooling → [ML Learning]
 */
class MaritimeSmokeCommand extends Command
{
    protected $signature = 'maritime:smoke
        {--cleanup : Delete test data after running}
        {--full : Run full pipeline including ML learning}
        {--api : Test via API endpoints instead of direct service calls}';

    protected $description = 'Run end-to-end smoke test for Maritime Supply pipeline';

    private array $createdIds = [
        'candidates' => [],
        'interviews' => [],
    ];

    public function __construct(
        private PoolCandidateService $candidateService,
        private FormInterviewService $interviewService,
        private ModelFeatureService $featureService,
        private MlScoringService $scoringService,
        private MlLearningService $learningService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info("╔══════════════════════════════════════════════════════════╗");
        $this->info("║          MARITIME SUPPLY SMOKE TEST                      ║");
        $this->info("╚══════════════════════════════════════════════════════════╝");
        $this->newLine();

        $fullMode = $this->option('full');
        $apiMode = $this->option('api');
        $cleanup = $this->option('cleanup');

        $results = [];

        try {
            // Test 1: Candidate Registration
            $results['1_registration'] = $this->testCandidateRegistration($apiMode);

            // Test 2: Interview Creation
            $results['2_interview_creation'] = $this->testInterviewCreation($apiMode);

            // Test 3: Interview Completion & Scoring
            $results['3_scoring'] = $this->testInterviewScoring();

            // Test 4: Feature Extraction
            $results['4_features'] = $this->testFeatureExtraction();

            // Test 5: ML Prediction
            $results['5_prediction'] = $this->testMlPrediction();

            // Test 6: Pool Movement
            $results['6_pooling'] = $this->testPoolMovement();

            // Test 7: English Assessment Update (if full)
            if ($fullMode) {
                $results['7_english_assessment'] = $this->testEnglishAssessmentUpdate();
            }

            // Test 8: ML Learning (if full)
            if ($fullMode) {
                $results['8_ml_learning'] = $this->testMlLearning();
            }

            // Summary
            $this->showSummary($results);

        } catch (Throwable $e) {
            $this->error("SMOKE TEST FAILED: {$e->getMessage()}");
            $this->error("File: {$e->getFile()}:{$e->getLine()}");

            if ($cleanup) {
                $this->cleanup();
            }

            return Command::FAILURE;
        }

        if ($cleanup) {
            $this->cleanup();
        }

        $failed = collect($results)->filter(fn($r) => !$r['success'])->count();
        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function testCandidateRegistration(bool $apiMode): array
    {
        $this->info("┌─ Test 1: Candidate Registration ───────────────────────┐");

        $testEmail = 'smoke_test_' . time() . '@talentqx.test';
        $testData = [
            'first_name' => 'Smoke',
            'last_name' => 'Test',
            'email' => $testEmail,
            'phone' => '+905551234567',
            'country_code' => 'TR',
            'preferred_language' => 'tr',
            'english_level_self' => 'B1',
            'source_channel' => 'maritime_event',
            'source_meta' => [
                'event' => 'smoke_test',
                'city' => 'Istanbul',
            ],
        ];

        try {
            if ($apiMode) {
                // Test via API (would need to set up HTTP client)
                $this->line("│  API mode: Skipping (requires running server)");
                $candidate = $this->createCandidateDirect($testData);
            } else {
                $candidate = $this->createCandidateDirect($testData);
            }

            $this->createdIds['candidates'][] = $candidate->id;

            $this->line("│  <fg=green>✓</> Candidate created: {$candidate->id}");
            $this->line("│  Status: {$candidate->status}");
            $this->line("│  Industry: {$candidate->primary_industry}");
            $this->info("└─────────────────────────────────────────────────────────┘");

            return [
                'success' => true,
                'candidate_id' => $candidate->id,
            ];
        } catch (Throwable $e) {
            $this->error("│  ✗ Registration failed: {$e->getMessage()}");
            $this->info("└─────────────────────────────────────────────────────────┘");

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function testInterviewCreation(bool $apiMode): array
    {
        $this->info("┌─ Test 2: Interview Creation ───────────────────────────┐");

        $candidateId = $this->createdIds['candidates'][0] ?? null;
        if (!$candidateId) {
            $this->error("│  ✗ No candidate available");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => 'No candidate'];
        }

        try {
            $candidate = PoolCandidate::find($candidateId);

            $interview = $this->candidateService->startInterview(
                candidate: $candidate,
                positionCode: 'deck_officer',
                industryCode: 'maritime',
                consents: [
                    'privacy_policy' => true,
                    'data_processing' => true,
                ],
                countryCode: $candidate->country_code,
                regulation: 'KVKK'
            );

            $this->createdIds['interviews'][] = $interview->id;

            $this->line("│  <fg=green>✓</> Interview created: {$interview->id}");
            $this->line("│  Status: {$interview->status}");
            $this->line("│  Position: {$interview->position_code}");
            $this->line("│  Industry: {$interview->industry_code}");
            $this->info("└─────────────────────────────────────────────────────────┘");

            return [
                'success' => true,
                'interview_id' => $interview->id,
            ];
        } catch (Throwable $e) {
            $this->error("│  ✗ Interview creation failed: {$e->getMessage()}");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function testInterviewScoring(): array
    {
        $this->info("┌─ Test 3: Interview Scoring ────────────────────────────┐");

        $interviewId = $this->createdIds['interviews'][0] ?? null;
        if (!$interviewId) {
            $this->error("│  ✗ No interview available");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => 'No interview'];
        }

        try {
            $interview = FormInterview::find($interviewId);

            // Simulate interview completion with scores
            $interview->update([
                'status' => FormInterview::STATUS_COMPLETED,
                'completed_at' => now(),
                'raw_final_score' => 72,
                'calibrated_score' => 68,
                'z_score' => 0.45,
                'decision' => 'ADVANCE',
                'competency_scores' => [
                    'communication' => 70,
                    'technical' => 75,
                    'problem_solving' => 65,
                ],
            ]);

            $interview->refresh();

            $this->line("│  <fg=green>✓</> Interview scored");
            $this->line("│  Raw Score: {$interview->raw_final_score}");
            $this->line("│  Calibrated: {$interview->calibrated_score}");
            $this->line("│  Decision: {$interview->decision}");
            $this->info("└─────────────────────────────────────────────────────────┘");

            return [
                'success' => true,
                'score' => $interview->calibrated_score,
                'decision' => $interview->decision,
            ];
        } catch (Throwable $e) {
            $this->error("│  ✗ Scoring failed: {$e->getMessage()}");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function testFeatureExtraction(): array
    {
        $this->info("┌─ Test 4: Feature Extraction ───────────────────────────┐");

        $interviewId = $this->createdIds['interviews'][0] ?? null;
        if (!$interviewId) {
            $this->error("│  ✗ No interview available");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => 'No interview'];
        }

        try {
            $interview = FormInterview::find($interviewId);
            $feature = $this->featureService->upsertForInterview($interview);

            $this->line("│  <fg=green>✓</> Features extracted");
            $this->line("│  Feature ID: {$feature->id}");
            $this->line("│  Industry: {$feature->industry_code}");
            $this->line("│  Calibrated Score: {$feature->calibrated_score}");
            $this->info("└─────────────────────────────────────────────────────────┘");

            return [
                'success' => true,
                'feature_id' => $feature->id,
            ];
        } catch (Throwable $e) {
            $this->error("│  ✗ Feature extraction failed: {$e->getMessage()}");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function testMlPrediction(): array
    {
        $this->info("┌─ Test 5: ML Prediction ────────────────────────────────┐");

        $interviewId = $this->createdIds['interviews'][0] ?? null;
        if (!$interviewId) {
            $this->error("│  ✗ No interview available");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => 'No interview'];
        }

        try {
            $interview = FormInterview::find($interviewId);

            // Ensure we have weights
            $weights = ModelWeight::latest();
            if (!$weights) {
                $this->line("│  Creating default weights...");
                $weights = ModelWeight::create([
                    'model_version' => 'smoke_test_v1',
                    'weights_json' => [
                        'risk_flag_penalties' => ['default' => -3],
                        'meta_penalties' => [
                            'sparse_answers' => -5,
                            'incomplete_interview' => -10,
                        ],
                        'boosts' => [
                            'maritime_industry' => 3,
                            'referral_source' => 2,
                            'english_b2_plus' => 2,
                        ],
                        'thresholds' => ['good' => 50],
                    ],
                    'is_active' => true,
                ]);
            }

            $prediction = $this->scoringService->predictAndStore($interview);

            if (!$prediction) {
                throw new \Exception('Prediction returned null');
            }

            $this->line("│  <fg=green>✓</> Prediction generated");
            $this->line("│  Prediction ID: {$prediction->id}");
            $this->line("│  Score: {$prediction->predicted_outcome_score}");
            $this->line("│  Label: {$prediction->predicted_label}");
            $this->line("│  Model: {$prediction->model_version}");
            $this->info("└─────────────────────────────────────────────────────────┘");

            return [
                'success' => true,
                'prediction_id' => $prediction->id,
                'predicted_score' => $prediction->predicted_outcome_score,
            ];
        } catch (Throwable $e) {
            $this->error("│  ✗ Prediction failed: {$e->getMessage()}");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function testPoolMovement(): array
    {
        $this->info("┌─ Test 6: Pool Movement ────────────────────────────────┐");

        $candidateId = $this->createdIds['candidates'][0] ?? null;
        $interviewId = $this->createdIds['interviews'][0] ?? null;

        if (!$candidateId || !$interviewId) {
            $this->error("│  ✗ Missing candidate or interview");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => 'Missing data'];
        }

        try {
            $candidate = PoolCandidate::find($candidateId);
            $interview = FormInterview::find($interviewId);

            // Simulate pool movement (normally done by handleInterviewCompletion)
            $this->candidateService->handleInterviewCompletion($interview);

            $candidate->refresh();

            $this->line("│  <fg=green>✓</> Candidate moved to pool");
            $this->line("│  New Status: {$candidate->status}");
            $this->line("│  Industry: {$candidate->primary_industry}");
            $this->line("│  Seafarer: " . ($candidate->seafarer ? 'Yes' : 'No'));
            $this->info("└─────────────────────────────────────────────────────────┘");

            return [
                'success' => $candidate->status === PoolCandidate::STATUS_IN_POOL,
                'status' => $candidate->status,
            ];
        } catch (Throwable $e) {
            $this->error("│  ✗ Pool movement failed: {$e->getMessage()}");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function testEnglishAssessmentUpdate(): array
    {
        $this->info("┌─ Test 7: English Assessment Update ────────────────────┐");

        $interviewId = $this->createdIds['interviews'][0] ?? null;
        if (!$interviewId) {
            $this->error("│  ✗ No interview available");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => 'No interview'];
        }

        try {
            $result = $this->featureService->updateEnglishAssessment(
                $interviewId,
                75, // B2+ level score
                'smoke_test',
                'Smoke test assessment'
            );

            $this->line("│  <fg=green>✓</> English assessment updated");
            $this->line("│  Score: 75");
            $this->line("│  New Prediction: " . ($result['new_prediction']['predicted_score'] ?? 'N/A'));
            $this->info("└─────────────────────────────────────────────────────────┘");

            return [
                'success' => $result['success'],
                'new_prediction' => $result['new_prediction'] ?? null,
            ];
        } catch (Throwable $e) {
            $this->error("│  ✗ English assessment failed: {$e->getMessage()}");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function testMlLearning(): array
    {
        $this->info("┌─ Test 8: ML Learning Loop ─────────────────────────────┐");

        $interviewId = $this->createdIds['interviews'][0] ?? null;
        if (!$interviewId) {
            $this->error("│  ✗ No interview available");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => 'No interview'];
        }

        try {
            // Create an outcome
            $outcome = InterviewOutcome::create([
                'form_interview_id' => $interviewId,
                'outcome_type' => InterviewOutcome::TYPE_HIRED,
                'outcome_score' => 80,
                'reported_by' => 'smoke_test',
            ]);

            $this->line("│  Created outcome: {$outcome->id}");

            // Run learning (dry run to avoid affecting production weights)
            $learningResult = $this->learningService->batchLearn(
                window: 1,
                industry: 'maritime',
                dryRun: true
            );

            $this->line("│  <fg=green>✓</> Learning cycle completed (dry run)");
            $this->line("│  Total: {$learningResult['total']}");
            $this->line("│  Processed: {$learningResult['processed']}");
            $this->info("└─────────────────────────────────────────────────────────┘");

            return [
                'success' => true,
                'learning_result' => $learningResult,
            ];
        } catch (Throwable $e) {
            $this->error("│  ✗ ML learning failed: {$e->getMessage()}");
            $this->info("└─────────────────────────────────────────────────────────┘");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function createCandidateDirect(array $data): PoolCandidate
    {
        return PoolCandidate::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'country_code' => $data['country_code'],
            'preferred_language' => $data['preferred_language'] ?? 'tr',
            'english_level_self' => $data['english_level_self'],
            'source_channel' => $data['source_channel'],
            'source_meta' => $data['source_meta'] ?? null,
            'status' => PoolCandidate::STATUS_NEW,
            'primary_industry' => PoolCandidate::INDUSTRY_MARITIME,
            'seafarer' => true,
            'english_assessment_required' => true,
            'video_assessment_required' => true,
        ]);
    }

    private function showSummary(array $results): void
    {
        $this->newLine();
        $this->info("╔══════════════════════════════════════════════════════════╗");
        $this->info("║                    TEST SUMMARY                          ║");
        $this->info("╠══════════════════════════════════════════════════════════╣");

        $passed = 0;
        $failed = 0;

        foreach ($results as $name => $result) {
            $status = $result['success'] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
            $label = str_replace('_', ' ', ucfirst(substr($name, 2)));
            $this->line("║  {$status} │ {$label}");

            if ($result['success']) {
                $passed++;
            } else {
                $failed++;
            }
        }

        $this->info("╠══════════════════════════════════════════════════════════╣");
        $this->info("║  Total: " . count($results) . " │ Passed: {$passed} │ Failed: {$failed}");
        $this->info("╚══════════════════════════════════════════════════════════╝");
    }

    private function cleanup(): void
    {
        $this->newLine();
        $this->warn("Cleaning up test data...");

        // Delete in reverse order to respect foreign keys
        foreach (array_reverse($this->createdIds['interviews']) as $id) {
            ModelPrediction::where('form_interview_id', $id)->delete();
            ModelFeature::where('form_interview_id', $id)->delete();
            InterviewOutcome::where('form_interview_id', $id)->delete();
            FormInterview::where('id', $id)->delete();
            $this->line("  Deleted interview: {$id}");
        }

        foreach (array_reverse($this->createdIds['candidates']) as $id) {
            PoolCandidate::where('id', $id)->delete();
            $this->line("  Deleted candidate: {$id}");
        }

        $this->info("Cleanup complete.");
    }
}
