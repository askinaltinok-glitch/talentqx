<?php

namespace Tests\Feature;

use App\Models\PoolCandidate;
use App\Services\Maritime\LanguageAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnglishAssessmentEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private LanguageAssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LanguageAssessmentService();
    }

    private function createCandidate(array $overrides = []): PoolCandidate
    {
        return PoolCandidate::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . uniqid() . '@test.com',
            'country_code' => 'TR',
            'source_channel' => 'test',
            'status' => PoolCandidate::STATUS_NEW,
            'primary_industry' => 'maritime',
        ], $overrides));
    }

    public function test_role_profile_resolution_for_captain(): void
    {
        $candidate = $this->createCandidate([
            'first_name' => 'Captain',
            'last_name' => 'Test',
            'source_meta' => ['rank' => 'captain'],
        ]);

        $profile = $this->service->resolveRoleProfile($candidate->id);
        $this->assertEquals('command', $profile);
    }

    public function test_role_profile_resolution_for_oiler(): void
    {
        $candidate = $this->createCandidate([
            'first_name' => 'Oiler',
            'last_name' => 'Test',
            'source_meta' => ['rank' => 'oiler'],
        ]);

        $profile = $this->service->resolveRoleProfile($candidate->id);
        $this->assertEquals('engine', $profile);
    }

    public function test_role_profile_resolution_for_able_seaman(): void
    {
        $candidate = $this->createCandidate([
            'first_name' => 'AB',
            'last_name' => 'Test',
            'source_meta' => ['rank' => 'able_seaman'],
        ]);

        $profile = $this->service->resolveRoleProfile($candidate->id);
        $this->assertEquals('deck_ratings', $profile);
    }

    public function test_role_profile_resolution_for_second_officer(): void
    {
        $candidate = $this->createCandidate([
            'first_name' => '2nd',
            'last_name' => 'Officer',
            'source_meta' => ['rank' => 'second_officer'],
        ]);

        $profile = $this->service->resolveRoleProfile($candidate->id);
        $this->assertEquals('officers', $profile);
    }

    public function test_role_requirements_returns_correct_min_level(): void
    {
        $candidate = $this->createCandidate([
            'first_name' => 'Chief',
            'last_name' => 'Engineer',
            'source_meta' => ['rank' => 'chief_engineer'],
        ]);

        $reqs = $this->service->resolveRoleRequirements($candidate->id);
        $this->assertNotNull($reqs);
        $this->assertEquals('B1', $reqs['min_level']);
        $this->assertEquals('command', $reqs['profile']);
    }

    public function test_role_requirements_null_for_unknown_rank(): void
    {
        $candidate = $this->createCandidate([
            'first_name' => 'Unknown',
            'last_name' => 'Rank',
            'source_meta' => ['rank' => 'space_pirate'],
        ]);

        $reqs = $this->service->resolveRoleRequirements($candidate->id);
        $this->assertNull($reqs);
    }
}
