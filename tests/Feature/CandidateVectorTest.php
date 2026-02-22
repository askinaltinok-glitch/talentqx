<?php

namespace Tests\Feature;

use App\Models\BehavioralProfile;
use App\Models\CandidateScoringVector;
use App\Models\FormInterview;
use App\Models\LanguageAssessment;
use App\Models\PoolCandidate;
use App\Services\Maritime\CandidateVectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateVectorTest extends TestCase
{
    use RefreshDatabase;

    private PoolCandidate $candidate;
    private CandidateVectorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['maritime.vector_v1' => true]);
        config(['maritime.behavioral_v1' => true]);
        config(['maritime.behavioral_interview_v1' => true]);

        $this->candidate = PoolCandidate::create([
            'first_name' => 'Test',
            'last_name' => 'Vector',
            'email' => 'vector@example.com',
            'preferred_language' => 'en',
            'country_code' => 'TR',
            'source_channel' => 'test',
            'status' => PoolCandidate::STATUS_IN_POOL,
            'primary_industry' => 'maritime',
            'seafarer' => true,
        ]);

        $this->service = new CandidateVectorService();
    }

    private function createCompletedInterview(array $overrides = []): FormInterview
    {
        return FormInterview::create(array_merge([
            'pool_candidate_id' => $this->candidate->id,
            'type' => 'standard',
            'version' => 'v1',
            'language' => 'en',
            'position_code' => 'test_position',
            'template_position_code' => 'test_position',
            'industry_code' => 'maritime',
            'status' => FormInterview::STATUS_COMPLETED,
            'completed_at' => now(),
        ], $overrides));
    }

    public function test_vector_computed_with_all_signals(): void
    {
        // Create completed interview with score
        $this->createCompletedInterview([
            'final_score' => 75.5,
            'calibrated_score' => 78.0,
            'decision' => 'hire',
        ]);

        // Create behavioral profile
        BehavioralProfile::create([
            'candidate_id' => $this->candidate->id,
            'version' => 'v1',
            'status' => BehavioralProfile::STATUS_FINAL,
            'confidence' => 0.85,
            'dimensions_json' => [
                'DISCIPLINE_COMPLIANCE' => ['score' => 80, 'level' => 'high', 'evidence' => [], 'flags' => []],
                'TEAM_COOPERATION' => ['score' => 70, 'level' => 'high', 'evidence' => [], 'flags' => []],
                'COMM_CLARITY' => ['score' => 65, 'level' => 'mid', 'evidence' => [], 'flags' => []],
                'STRESS_CONTROL' => ['score' => 75, 'level' => 'high', 'evidence' => [], 'flags' => []],
                'CONFLICT_RISK' => ['score' => 20, 'level' => 'low', 'evidence' => [], 'flags' => []],
                'LEARNING_GROWTH' => ['score' => 60, 'level' => 'mid', 'evidence' => [], 'flags' => []],
                'RELIABILITY_STABILITY' => ['score' => 70, 'level' => 'high', 'evidence' => [], 'flags' => []],
            ],
        ]);

        // Create English assessment
        LanguageAssessment::create([
            'candidate_id' => $this->candidate->id,
            'overall_score' => 72,
            'estimated_level' => 'B2',
            'confidence' => 0.78,
        ]);

        $vector = $this->service->computeVector($this->candidate->id);

        $this->assertNotNull($vector);
        $this->assertNotNull($vector->technical_score);
        $this->assertNotNull($vector->behavioral_score);
        $this->assertNotNull($vector->english_proficiency);
        $this->assertNull($vector->personality_score); // v1 placeholder
        $this->assertNotNull($vector->composite_score);
        $this->assertEquals('B2', $vector->english_level);
        $this->assertEquals('v1', $vector->version);
    }

    public function test_vector_with_missing_signals_redistributes_weight(): void
    {
        // Only technical signal, no behavioral or English
        $this->createCompletedInterview([
            'final_score' => 80.0,
            'decision' => 'hire',
        ]);

        $vector = $this->service->computeVector($this->candidate->id);

        $this->assertNotNull($vector);
        $this->assertEquals(80.0, $vector->technical_score);
        $this->assertNull($vector->behavioral_score);
        $this->assertNull($vector->english_proficiency);
        // Composite should still be computed from available signals
        $this->assertNotNull($vector->composite_score);
        // With only technical available, composite should equal technical
        $this->assertEquals(80.0, $vector->composite_score);
    }

    public function test_safety_rules_prevent_english_only_rejection(): void
    {
        // Good technical + behavioral scores
        $this->createCompletedInterview([
            'final_score' => 70.0,
        ]);

        BehavioralProfile::create([
            'candidate_id' => $this->candidate->id,
            'version' => 'v1',
            'status' => BehavioralProfile::STATUS_FINAL,
            'confidence' => 0.80,
            'dimensions_json' => [
                'DISCIPLINE_COMPLIANCE' => ['score' => 60, 'level' => 'mid', 'evidence' => [], 'flags' => []],
                'TEAM_COOPERATION' => ['score' => 65, 'level' => 'mid', 'evidence' => [], 'flags' => []],
                'COMM_CLARITY' => ['score' => 55, 'level' => 'mid', 'evidence' => [], 'flags' => []],
                'STRESS_CONTROL' => ['score' => 60, 'level' => 'mid', 'evidence' => [], 'flags' => []],
                'CONFLICT_RISK' => ['score' => 30, 'level' => 'low', 'evidence' => [], 'flags' => []],
                'LEARNING_GROWTH' => ['score' => 55, 'level' => 'mid', 'evidence' => [], 'flags' => []],
                'RELIABILITY_STABILITY' => ['score' => 60, 'level' => 'mid', 'evidence' => [], 'flags' => []],
            ],
        ]);

        // Very low English score
        LanguageAssessment::create([
            'candidate_id' => $this->candidate->id,
            'overall_score' => 15,
            'estimated_level' => 'A1',
        ]);

        $vector = $this->service->computeVector($this->candidate->id);

        $this->assertNotNull($vector);
        // Composite should not drop below safety floor when technical + behavioral are solid
        $this->assertGreaterThanOrEqual(40.0, (float) $vector->composite_score);
    }

    public function test_personality_placeholder_is_null_and_weight_redistributed(): void
    {
        $this->createCompletedInterview([
            'final_score' => 75.0,
        ]);

        $vector = $this->service->computeVector($this->candidate->id);

        $this->assertNotNull($vector);
        $this->assertNull($vector->personality_score);

        // Check that vector_json includes weights info
        $vectorJson = $vector->vector_json;
        $this->assertArrayHasKey('weights_used', $vectorJson);
        // Personality weight should be 0 (redistributed)
        $this->assertEquals(0, $vectorJson['weights_used']['personality'] ?? -1);
    }

    public function test_vector_persisted_and_retrievable(): void
    {
        $this->createCompletedInterview([
            'final_score' => 85.0,
        ]);

        $vector = $this->service->computeVector($this->candidate->id);

        // Retrieve from DB
        $stored = CandidateScoringVector::where('candidate_id', $this->candidate->id)
            ->where('version', 'v1')
            ->first();

        $this->assertNotNull($stored);
        $this->assertEquals($vector->id, $stored->id);
        $this->assertEquals($vector->composite_score, $stored->composite_score);
        $this->assertNotNull($stored->computed_at);
    }
}
