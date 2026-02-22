<?php

namespace Tests\Feature;

use App\Models\BehavioralProfile;
use App\Models\FormInterview;
use App\Models\InterviewTemplate;
use App\Models\PoolCandidate;
use App\Services\Behavioral\BehavioralScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BehavioralInterviewTest extends TestCase
{
    use RefreshDatabase;

    private PoolCandidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();
        config(['maritime.behavioral_v1' => true]);
        config(['maritime.behavioral_interview_v1' => true]);

        $this->candidate = PoolCandidate::create([
            'first_name' => 'Test',
            'last_name' => 'Seafarer',
            'email' => 'test@example.com',
            'preferred_language' => 'en',
            'country_code' => 'TR',
            'source_channel' => 'test',
            'status' => PoolCandidate::STATUS_NEW,
            'primary_industry' => 'maritime',
            'seafarer' => true,
        ]);
    }

    private function createBehavioralInterview(string $status = 'in_progress'): FormInterview
    {
        return FormInterview::create([
            'pool_candidate_id' => $this->candidate->id,
            'type' => 'behavioral',
            'version' => 'v1',
            'language' => 'en',
            'position_code' => '__behavioral__',
            'template_position_code' => '__behavioral__',
            'industry_code' => 'maritime',
            'status' => $status,
        ]);
    }

    public function test_template_endpoint_returns_12_questions(): void
    {
        // Seed template (use updateOrCreate in case seeded data exists)
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'en', 'position_code' => '__behavioral__'],
            [
            'type' => 'behavioral',
            'title' => 'Behavioral (EN)',
            'template_json' => json_encode([
                'type' => 'behavioral',
                'version' => 'v1',
                'language' => 'en',
                'categories' => [
                    ['key' => 'discipline_procedure', 'title' => 'Discipline', 'dimensions' => ['DISCIPLINE_COMPLIANCE'], 'questions' => [
                        ['id' => 'dp1', 'slot' => 1, 'text' => 'Q1?', 'type' => 'open_text', 'scoring_hints' => []],
                        ['id' => 'dp2', 'slot' => 2, 'text' => 'Q2?', 'type' => 'open_text', 'scoring_hints' => []],
                        ['id' => 'dp3', 'slot' => 3, 'text' => 'Q3?', 'type' => 'open_text', 'scoring_hints' => []],
                    ]],
                    ['key' => 'stress_crisis', 'title' => 'Stress', 'dimensions' => ['STRESS_CONTROL'], 'questions' => [
                        ['id' => 'sc1', 'slot' => 4, 'text' => 'Q4?', 'type' => 'open_text', 'scoring_hints' => []],
                        ['id' => 'sc2', 'slot' => 5, 'text' => 'Q5?', 'type' => 'open_text', 'scoring_hints' => []],
                        ['id' => 'sc3', 'slot' => 6, 'text' => 'Q6?', 'type' => 'open_text', 'scoring_hints' => []],
                    ]],
                    ['key' => 'team_compatibility', 'title' => 'Team', 'dimensions' => ['TEAM_COOPERATION'], 'questions' => [
                        ['id' => 'tc1', 'slot' => 7, 'text' => 'Q7?', 'type' => 'open_text', 'scoring_hints' => []],
                        ['id' => 'tc2', 'slot' => 8, 'text' => 'Q8?', 'type' => 'open_text', 'scoring_hints' => []],
                        ['id' => 'tc3', 'slot' => 9, 'text' => 'Q9?', 'type' => 'open_text', 'scoring_hints' => []],
                    ]],
                    ['key' => 'leadership_responsibility', 'title' => 'Leadership', 'dimensions' => ['LEARNING_GROWTH'], 'questions' => [
                        ['id' => 'lr1', 'slot' => 10, 'text' => 'Q10?', 'type' => 'open_text', 'scoring_hints' => []],
                        ['id' => 'lr2', 'slot' => 11, 'text' => 'Q11?', 'type' => 'open_text', 'scoring_hints' => []],
                        ['id' => 'lr3', 'slot' => 12, 'text' => 'Q12?', 'type' => 'open_text', 'scoring_hints' => []],
                    ]],
                ],
                'scoring' => ['scale' => ['min' => 1, 'max' => 5]],
            ]),
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/maritime/candidates/{$this->candidate->id}/behavioral/template");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_questions', 12);

        $categories = $response->json('data.categories');
        $this->assertCount(4, $categories);

        $totalQuestions = 0;
        foreach ($categories as $cat) {
            $totalQuestions += count($cat['questions']);
        }
        $this->assertEquals(12, $totalQuestions);
    }

    public function test_answer_submission_upserts_correctly(): void
    {
        $response = $this->postJson(
            "/api/v1/maritime/candidates/{$this->candidate->id}/behavioral/answers",
            [
                'answers' => [
                    ['slot' => 1, 'question_id' => 'dp1', 'text' => 'I followed the procedure carefully because safety is paramount.', 'category' => 'discipline_procedure'],
                    ['slot' => 2, 'question_id' => 'dp2', 'text' => 'I reported the issue to the chief officer diplomatically.', 'category' => 'discipline_procedure'],
                ],
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.answered', 2)
            ->assertJsonPath('data.total', 12);

        // Upsert: submit same slot with different text
        $response2 = $this->postJson(
            "/api/v1/maritime/candidates/{$this->candidate->id}/behavioral/answers",
            [
                'answers' => [
                    ['slot' => 1, 'question_id' => 'dp1', 'text' => 'Updated answer with more detail about following the checklist.', 'category' => 'discipline_procedure'],
                ],
            ]
        );

        $response2->assertOk()
            ->assertJsonPath('data.answered', 2); // Still 2, slot 1 was upserted
    }

    public function test_completion_requires_all_12_answers(): void
    {
        $interview = $this->createBehavioralInterview();

        for ($i = 1; $i <= 3; $i++) {
            $interview->answers()->create([
                'slot' => $i,
                'competency' => 'discipline_procedure',
                'answer_text' => 'Test answer for slot ' . $i,
            ]);
        }

        $response = $this->postJson(
            "/api/v1/maritime/candidates/{$this->candidate->id}/behavioral/complete"
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_scoring_produces_valid_behavioral_profile(): void
    {
        $service = new BehavioralScoringService();

        $interview = $this->createBehavioralInterview();

        // Create 12 answers with meaningful text
        $texts = [
            'I always follow the ISM code and SOLAS procedures. Protocol compliance is critical for safety at sea.',
            'I reported the violation to the chief officer through proper channels. Communication is key.',
            'I double-check every entry on the checklist before signing off. Documentation prevents mistakes.',
            'During the engine room fire, I remained calm and followed the emergency procedure step by step.',
            'I assessed the situation quickly, prioritized crew safety, and executed the contingency plan.',
            'I maintain a strict sleep schedule and use stress management techniques to stay alert during watches.',
            'I discussed the disagreement respectfully with my superior, presenting my reasoning clearly.',
            'I used simple English, hand signals, and the SMCP phrases to communicate with the Filipino crew.',
            'I organized crew social events and ensured everyone had time to rest. Morale improved significantly.',
            'I took full responsibility for the navigation error and documented lessons learned for the team.',
            'I mentored the deck cadet using a step-by-step approach, demonstrating each procedure on deck.',
            'The most important lesson I learned is that safety never takes a day off. Continuous improvement matters.',
        ];

        for ($i = 0; $i < 12; $i++) {
            $interview->answers()->create([
                'slot' => $i + 1,
                'competency' => 'test',
                'answer_text' => $texts[$i],
            ]);
        }

        $categoryScores = [
            'discipline_procedure' => ['q1' => 5, 'q2' => 4, 'q3' => 5],
            'stress_crisis' => ['q4' => 4, 'q5' => 4, 'q6' => 3],
            'team_compatibility' => ['q7' => 4, 'q8' => 4, 'q9' => 5],
            'leadership_responsibility' => ['q10' => 5, 'q11' => 4, 'q12' => 4],
        ];

        $profile = $service->scoreStructuredInterview($interview, $categoryScores);

        $this->assertNotNull($profile);
        $this->assertEquals(BehavioralProfile::STATUS_FINAL, $profile->status);
        $this->assertGreaterThan(0, $profile->confidence);

        // Check all 7 dimensions exist
        $dims = $profile->dimensions_json;
        $expectedDims = ['DISCIPLINE_COMPLIANCE', 'TEAM_COOPERATION', 'COMM_CLARITY', 'STRESS_CONTROL', 'CONFLICT_RISK', 'LEARNING_GROWTH', 'RELIABILITY_STABILITY'];
        foreach ($expectedDims as $dim) {
            $this->assertArrayHasKey($dim, $dims, "Missing dimension: {$dim}");
            $this->assertArrayHasKey('score', $dims[$dim]);
            $this->assertArrayHasKey('level', $dims[$dim]);
            $this->assertGreaterThanOrEqual(0, $dims[$dim]['score']);
            $this->assertLessThanOrEqual(100, $dims[$dim]['score']);
        }
    }

    public function test_vessel_fit_map_computed(): void
    {
        $service = new BehavioralScoringService();

        $interview = $this->createBehavioralInterview();

        for ($i = 1; $i <= 12; $i++) {
            $interview->answers()->create([
                'slot' => $i,
                'competency' => 'test',
                'answer_text' => 'Detailed answer about following procedures and working with the team safely.',
            ]);
        }

        $categoryScores = [
            'discipline_procedure' => ['q1' => 4, 'q2' => 4, 'q3' => 4],
            'stress_crisis' => ['q4' => 3, 'q5' => 3, 'q6' => 3],
            'team_compatibility' => ['q7' => 4, 'q8' => 4, 'q9' => 4],
            'leadership_responsibility' => ['q10' => 3, 'q11' => 3, 'q12' => 3],
        ];

        $profile = $service->scoreStructuredInterview($interview, $categoryScores);

        $this->assertNotNull($profile);
        $this->assertNotNull($profile->fit_json);

        // Should have fit scores for multiple vessel types
        $fitMap = $profile->fit_json;
        $this->assertArrayHasKey('TANKER', $fitMap);
        $this->assertArrayHasKey('PASSENGER', $fitMap);
        $this->assertArrayHasKey('normalized_fit', $fitMap['TANKER']);
    }
}
