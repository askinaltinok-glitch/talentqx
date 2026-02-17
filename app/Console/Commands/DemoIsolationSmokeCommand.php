<?php

namespace App\Console\Commands;

use App\Models\CandidateNotification;
use App\Models\CrmDeal;
use App\Models\CrmLead;
use App\Models\FormInterview;
use App\Models\InterviewOutcome;
use App\Models\ModelFeature;
use App\Models\ModelPrediction;
use App\Models\PoolCandidate;
use App\Models\VesselReview;
use App\Services\CandidateNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoIsolationSmokeCommand extends Command
{
    protected $signature = 'demo:isolation-smoke';
    protected $description = 'Smoke-test demo isolation across all layers';

    private int $passed = 0;
    private int $failed = 0;

    public function handle(): int
    {
        $this->info('=== Demo Isolation Smoke Test ===');
        $this->newLine();

        DB::beginTransaction();

        try {
            // Baseline counts (before creating demo data)
            $baselineCounts = $this->getBaselineCounts();

            // --- TEST 1: Create demo candidate with is_demo=true ---
            $this->assert(
                'Demo candidate creation: is_demo=true',
                function () {
                    $candidate = PoolCandidate::create([
                        'first_name' => 'Smoke',
                        'last_name' => 'Test',
                        'email' => 'smoke_' . time() . '@demo.test',
                        'phone' => '+905550000999',
                        'country_code' => 'TR',
                        'preferred_language' => 'en',
                        'english_level_self' => 'B2',
                        'source_channel' => 'demo',
                        'status' => PoolCandidate::STATUS_NEW,
                        'primary_industry' => 'maritime',
                        'seafarer' => true,
                        'is_demo' => true,
                    ]);
                    return $candidate->is_demo === true;
                }
            );

            // --- TEST 2: Create demo form interview ---
            $this->assert(
                'Demo form interview: is_demo=true',
                function () {
                    $candidate = PoolCandidate::withoutGlobalScope('exclude_demo')
                        ->where('email', 'like', 'smoke_%@demo.test')
                        ->first();

                    $interview = FormInterview::create([
                        'pool_candidate_id' => $candidate->id,
                        'version' => 'v3',
                        'language' => 'en',
                        'position_code' => 'deck_officer',
                        'template_position_code' => 'deck_officer',
                        'industry_code' => 'maritime',
                        'status' => FormInterview::STATUS_DRAFT,
                        'template_json' => '{}',
                        'template_json_sha256' => hash('sha256', '{}'),
                        'is_demo' => true,
                    ]);
                    return $interview->is_demo === true;
                }
            );

            // --- TEST 3: Default queries exclude demo ---
            $this->assert(
                'Default PoolCandidate::count() excludes demo',
                function () use ($baselineCounts) {
                    // In console context, global scope forces is_demo=false
                    $currentCount = PoolCandidate::count();
                    return $currentCount === $baselineCounts['pool_candidates'];
                }
            );

            // --- TEST 4: Default FormInterview query excludes demo ---
            $this->assert(
                'Default FormInterview::count() excludes demo',
                function () use ($baselineCounts) {
                    $currentCount = FormInterview::count();
                    return $currentCount === $baselineCounts['form_interviews'];
                }
            );

            // --- TEST 5: onlyDemo() returns demo records ---
            $this->assert(
                'PoolCandidate::onlyDemo() returns demo records',
                function () {
                    return PoolCandidate::onlyDemo()
                        ->where('email', 'like', 'smoke_%@demo.test')
                        ->exists();
                }
            );

            // --- TEST 6: includeDemo() shows both ---
            $this->assert(
                'PoolCandidate::includeDemo() includes demo records',
                function () use ($baselineCounts) {
                    $total = PoolCandidate::includeDemo()->count();
                    return $total > $baselineCounts['pool_candidates'];
                }
            );

            // --- TEST 7: Demo ModelFeature / ModelPrediction excluded ---
            $this->assert(
                'Demo ModelFeature/ModelPrediction excluded from default queries',
                function () use ($baselineCounts) {
                    $candidate = PoolCandidate::withoutGlobalScope('exclude_demo')
                        ->where('email', 'like', 'smoke_%@demo.test')
                        ->first();
                    $interview = FormInterview::withoutGlobalScope('exclude_demo')
                        ->where('pool_candidate_id', $candidate->id)
                        ->first();

                    ModelFeature::create([
                        'form_interview_id' => $interview->id,
                        'industry_code' => 'maritime',
                        'position_code' => 'deck_officer',
                        'raw_final_score' => 75,
                        'is_demo' => true,
                    ]);
                    ModelPrediction::create([
                        'form_interview_id' => $interview->id,
                        'model_version' => 'smoke_test',
                        'predicted_outcome_score' => 75,
                        'predicted_label' => 'GOOD',
                        'prediction_type' => 'baseline',
                        'is_demo' => true,
                        'created_at' => now(),
                    ]);

                    return ModelFeature::count() === $baselineCounts['model_features']
                        && ModelPrediction::count() === $baselineCounts['model_predictions'];
                }
            );

            // --- TEST 8: Demo InterviewOutcome skips ML learning ---
            $this->assert(
                'Demo InterviewOutcome::saved() skips ML learning',
                function () {
                    $candidate = PoolCandidate::withoutGlobalScope('exclude_demo')
                        ->where('email', 'like', 'smoke_%@demo.test')
                        ->first();
                    $interview = FormInterview::withoutGlobalScope('exclude_demo')
                        ->where('pool_candidate_id', $candidate->id)
                        ->first();

                    $learningBefore = DB::table('learning_events')->count();

                    InterviewOutcome::create([
                        'form_interview_id' => $interview->id,
                        'hired' => true,
                        'started' => true,
                        'still_employed_30d' => true,
                        'outcome_source' => 'admin',
                        'recorded_at' => now(),
                        'is_demo' => true,
                    ]);

                    $learningAfter = DB::table('learning_events')->count();

                    // No new learning events should be created
                    return $learningAfter === $learningBefore;
                }
            );

            // --- TEST 9: Notification service returns null for demo ---
            $this->assert(
                'CandidateNotificationService::notifyStatusChange() returns null for demo',
                function () {
                    $candidate = PoolCandidate::withoutGlobalScope('exclude_demo')
                        ->where('email', 'like', 'smoke_%@demo.test')
                        ->first();

                    $service = app(CandidateNotificationService::class);
                    $result = $service->notifyStatusChange($candidate, 'hired', [
                        'company_name' => 'Smoke Test Corp',
                    ]);

                    return $result === null;
                }
            );

            // --- TEST 10: CRM demo isolation ---
            $this->assert(
                'CrmLead/CrmDeal demo isolation',
                function () use ($baselineCounts) {
                    CrmLead::create([
                        'lead_name' => 'Demo Lead Smoke',
                        'industry_code' => 'maritime',
                        'source_channel' => 'website_demo',
                        'stage' => CrmLead::STAGE_NEW,
                        'is_demo' => true,
                    ]);

                    // Default query should exclude
                    return CrmLead::count() === $baselineCounts['crm_leads'];
                }
            );

        } finally {
            DB::rollBack();
        }

        $this->newLine();
        $total = $this->passed + $this->failed;
        $this->info("=== Results: {$this->passed}/{$total} passed ===");

        if ($this->failed > 0) {
            $this->error("{$this->failed} test(s) FAILED");
            return Command::FAILURE;
        }

        $this->info('ALL TESTS PASSED');
        return Command::SUCCESS;
    }

    private function assert(string $label, callable $test): void
    {
        try {
            $result = $test();
            if ($result) {
                $this->passed++;
                $this->line("  <fg=green>PASS</> {$label}");
            } else {
                $this->failed++;
                $this->line("  <fg=red>FAIL</> {$label}");
            }
        } catch (\Throwable $e) {
            $this->failed++;
            $this->line("  <fg=red>FAIL</> {$label} — {$e->getMessage()}");
        }
    }

    private function getBaselineCounts(): array
    {
        return [
            'pool_candidates' => PoolCandidate::count(),
            'form_interviews' => FormInterview::count(),
            'model_features' => ModelFeature::count(),
            'model_predictions' => ModelPrediction::count(),
            'interview_outcomes' => InterviewOutcome::count(),
            'crm_leads' => CrmLead::count(),
            'crm_deals' => CrmDeal::count(),
            'notifications' => CandidateNotification::count(),
            'vessel_reviews' => VesselReview::count(),
        ];
    }
}
