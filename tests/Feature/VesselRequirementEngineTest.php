<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyVesselRequirementOverride;
use App\Models\FleetVessel;
use App\Models\PoolCandidate;
use App\Models\SeafarerCertificate;
use App\Models\VesselRequirementTemplate;
use App\Services\Fleet\CandidateDecisionService;
use App\Services\Fleet\VesselRequirementProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VesselRequirementEngineTest extends TestCase
{
    use RefreshDatabase;

    private VesselRequirementProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileService = new VesselRequirementProfileService();
    }

    /** @test */
    public function default_template_loads_for_matching_vessel_type(): void
    {
        config(['maritime.vessel_requirement_engine_v1' => true]);

        VesselRequirementTemplate::create([
            'vessel_type_key' => 'tanker',
            'label' => 'Tanker',
            'profile_json' => [
                'required_certificates' => [
                    ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                    ['certificate_type' => 'MEDICAL_FITNESS', 'min_remaining_months' => 3, 'mandatory' => true],
                ],
                'experience' => [
                    'vessel_type_min_months' => 12,
                    'any_vessel_min_months' => 24,
                ],
                'behavior_thresholds' => [
                    'discipline' => 0.50,
                ],
                'weights' => [
                    'cert_fit' => 0.30,
                    'experience_fit' => 0.25,
                    'behavior_fit' => 0.25,
                    'availability_fit' => 0.20,
                ],
            ],
        ]);

        $company = Company::factory()->create();
        $vessel = FleetVessel::create([
            'company_id' => $company->id,
            'name' => 'Test Tanker',
            'vessel_type' => 'Tanker',
            'status' => 'active',
        ]);

        $profile = $this->profileService->resolve($vessel);

        $this->assertNotNull($profile);
        $this->assertCount(2, $profile['required_certificates']);
        $this->assertEquals(12, $profile['experience']['vessel_type_min_months']);
        $this->assertArrayHasKey('cert_fit', $profile['weights']);
    }

    /** @test */
    public function company_override_merges_correctly(): void
    {
        config(['maritime.vessel_requirement_engine_v1' => true]);

        $company = Company::factory()->create();

        VesselRequirementTemplate::create([
            'vessel_type_key' => 'tanker',
            'label' => 'Tanker',
            'profile_json' => [
                'required_certificates' => [
                    ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                    ['certificate_type' => 'MEDICAL_FITNESS', 'min_remaining_months' => 3, 'mandatory' => true],
                ],
                'experience' => [
                    'vessel_type_min_months' => 12,
                    'any_vessel_min_months' => 24,
                ],
                'weights' => [
                    'cert_fit' => 0.30,
                    'experience_fit' => 0.25,
                    'behavior_fit' => 0.25,
                    'availability_fit' => 0.20,
                ],
            ],
        ]);

        CompanyVesselRequirementOverride::create([
            'company_id' => $company->id,
            'vessel_type_key' => 'tanker',
            'overrides_json' => [
                'required_certificates' => [
                    ['certificate_type' => 'TANKER_ENDORSEMENT', 'min_remaining_months' => 6, 'mandatory' => true],
                    ['certificate_type' => 'COC', 'min_remaining_months' => 12, 'mandatory' => true],
                ],
                'experience' => [
                    'vessel_type_min_months' => 18,
                ],
            ],
        ]);

        $vessel = FleetVessel::create([
            'company_id' => $company->id,
            'name' => 'Company Tanker',
            'vessel_type' => 'Tanker',
            'status' => 'active',
        ]);

        $profile = $this->profileService->resolve($vessel);

        $this->assertNotNull($profile);
        // Merged: 3 certs (COC updated, MEDICAL kept, TANKER_ENDORSEMENT added)
        $this->assertCount(3, $profile['required_certificates']);

        // COC should have overridden min_remaining_months
        $coc = collect($profile['required_certificates'])->firstWhere('certificate_type', 'COC');
        $this->assertEquals(12, $coc['min_remaining_months']);

        // Experience override applied
        $this->assertEquals(18, $profile['experience']['vessel_type_min_months']);
        // Unchanged value preserved
        $this->assertEquals(24, $profile['experience']['any_vessel_min_months']);

        // Weights unchanged
        $this->assertEqualsWithDelta(0.30, $profile['weights']['cert_fit'], 0.01);
    }

    /** @test */
    public function scoring_uses_vessel_profile_when_flag_enabled(): void
    {
        config(['maritime.vessel_requirement_engine_v1' => true]);

        $company = Company::factory()->create();

        VesselRequirementTemplate::create([
            'vessel_type_key' => 'tanker',
            'label' => 'Tanker',
            'profile_json' => [
                'required_certificates' => [
                    ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                    ['certificate_type' => 'TANKER_ENDORSEMENT', 'min_remaining_months' => 6, 'mandatory' => true],
                ],
                'experience' => [
                    'vessel_type_min_months' => 12,
                    'any_vessel_min_months' => 24,
                ],
                'behavior_thresholds' => [],
                'weights' => [
                    'cert_fit' => 0.40,
                    'experience_fit' => 0.20,
                    'behavior_fit' => 0.20,
                    'availability_fit' => 0.20,
                ],
            ],
        ]);

        $vessel = FleetVessel::create([
            'company_id' => $company->id,
            'name' => 'Test Tanker',
            'vessel_type' => 'Tanker',
            'status' => 'active',
        ]);

        $candidate = PoolCandidate::factory()->create([
            'availability_status' => 'available',
        ]);

        // Give candidate only 1 of 2 mandatory certs
        SeafarerCertificate::create([
            'pool_candidate_id' => $candidate->id,
            'certificate_type' => 'COC',
            'expires_at' => now()->addYear(),
        ]);

        $service = new CandidateDecisionService();
        $result = $service->computeFinalScore($candidate, $vessel, 'master');

        $this->assertEquals('vessel_profile', $result['meta']['scoring_mode']);
        $this->assertEquals('tanker', $result['meta']['vessel_type_key']);
        $this->assertArrayHasKey('cert_fit', $result['pillars']);
        $this->assertArrayHasKey('experience_fit', $result['pillars']);
        $this->assertArrayHasKey('behavior_fit', $result['pillars']);

        // Cert fit should be penalized — 1 matched, 1 mandatory missing
        $certPillar = $result['pillars']['cert_fit'];
        $this->assertEquals(1, $certPillar['matched']);
        $this->assertEquals(1, $certPillar['missing']);
        $this->assertEquals(2, $certPillar['total_required']);
        $this->assertLessThan(0.5, $certPillar['score']);
    }

    /** @test */
    public function tanker_blocked_when_missing_hard_block_cert(): void
    {
        config(['maritime.vessel_requirement_engine_v1' => true]);

        $company = Company::factory()->create();

        VesselRequirementTemplate::create([
            'vessel_type_key' => 'tanker',
            'label' => 'Tanker',
            'profile_json' => [
                'required_certificates' => [
                    ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                    ['certificate_type' => 'TANKER_ENDORSEMENT', 'min_remaining_months' => 0, 'mandatory' => true, 'hard_block' => true, 'block_reason_key' => 'missing_tanker_endorsement'],
                ],
                'experience' => ['vessel_type_min_months' => 12, 'any_vessel_min_months' => 24],
                'behavior_thresholds' => [],
                'weights' => ['cert_fit' => 0.40, 'experience_fit' => 0.20, 'behavior_fit' => 0.20, 'availability_fit' => 0.20],
            ],
        ]);

        $vessel = FleetVessel::create([
            'company_id' => $company->id,
            'name' => 'Test Tanker HB',
            'vessel_type' => 'Tanker',
            'status' => 'active',
        ]);

        $candidate = PoolCandidate::factory()->create(['availability_status' => 'available']);

        SeafarerCertificate::create([
            'pool_candidate_id' => $candidate->id,
            'certificate_type' => 'COC',
            'expires_at' => now()->addYear(),
        ]);

        $service = new CandidateDecisionService();
        $result = $service->computeFinalScore($candidate, $vessel, 'master');

        $this->assertEquals('blocked', $result['label']);
        $this->assertTrue($result['is_blocked']);
        $this->assertLessThanOrEqual(0.20, $result['final_score']);
        $this->assertNotEmpty($result['blockers']);
        $this->assertEquals('TANKER_ENDORSEMENT', $result['blockers'][0]['certificate_type']);
        $this->assertEquals('missing', $result['blockers'][0]['reason']);
        $this->assertEquals('missing_tanker_endorsement', $result['blockers'][0]['block_reason_key']);
    }

    /** @test */
    public function lng_blocked_when_missing_hard_block_cert(): void
    {
        config(['maritime.vessel_requirement_engine_v1' => true]);

        $company = Company::factory()->create();

        VesselRequirementTemplate::create([
            'vessel_type_key' => 'lng',
            'label' => 'LNG/LPG Carrier',
            'profile_json' => [
                'required_certificates' => [
                    ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                    ['certificate_type' => 'LNG_ENDORSEMENT', 'min_remaining_months' => 0, 'mandatory' => true, 'hard_block' => true, 'block_reason_key' => 'missing_lng_endorsement'],
                ],
                'experience' => ['vessel_type_min_months' => 30, 'any_vessel_min_months' => 24],
                'behavior_thresholds' => [],
                'weights' => ['cert_fit' => 0.45, 'experience_fit' => 0.20, 'behavior_fit' => 0.15, 'availability_fit' => 0.20],
            ],
        ]);

        $vessel = FleetVessel::create([
            'company_id' => $company->id,
            'name' => 'Test LNG HB',
            'vessel_type' => 'LNG Carrier',
            'status' => 'active',
        ]);

        $candidate = PoolCandidate::factory()->create(['availability_status' => 'available']);

        SeafarerCertificate::create([
            'pool_candidate_id' => $candidate->id,
            'certificate_type' => 'COC',
            'expires_at' => now()->addYear(),
        ]);

        $service = new CandidateDecisionService();
        $result = $service->computeFinalScore($candidate, $vessel, 'chief_engineer');

        $this->assertEquals('blocked', $result['label']);
        $this->assertTrue($result['is_blocked']);
        $this->assertLessThanOrEqual(0.20, $result['final_score']);
        $this->assertNotEmpty($result['blockers']);
        $this->assertEquals('LNG_ENDORSEMENT', $result['blockers'][0]['certificate_type']);
    }

    /** @test */
    public function offshore_blocked_when_missing_dp_cert(): void
    {
        config(['maritime.vessel_requirement_engine_v1' => true]);

        $company = Company::factory()->create();

        VesselRequirementTemplate::create([
            'vessel_type_key' => 'offshore',
            'label' => 'Offshore / Platform',
            'profile_json' => [
                'required_certificates' => [
                    ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                    ['certificate_type' => 'DP_CERT', 'min_remaining_months' => 0, 'mandatory' => true, 'hard_block' => true, 'block_reason_key' => 'missing_dp_certification'],
                ],
                'experience' => ['vessel_type_min_months' => 24, 'any_vessel_min_months' => 24],
                'behavior_thresholds' => [],
                'weights' => ['cert_fit' => 0.35, 'experience_fit' => 0.25, 'behavior_fit' => 0.25, 'availability_fit' => 0.15],
            ],
        ]);

        $vessel = FleetVessel::create([
            'company_id' => $company->id,
            'name' => 'Test Offshore HB',
            'vessel_type' => 'Offshore',
            'status' => 'active',
        ]);

        $candidate = PoolCandidate::factory()->create(['availability_status' => 'available']);

        SeafarerCertificate::create([
            'pool_candidate_id' => $candidate->id,
            'certificate_type' => 'COC',
            'expires_at' => now()->addYear(),
        ]);

        $service = new CandidateDecisionService();
        $result = $service->computeFinalScore($candidate, $vessel, 'chief_officer');

        $this->assertEquals('blocked', $result['label']);
        $this->assertTrue($result['is_blocked']);
        $this->assertLessThanOrEqual(0.20, $result['final_score']);
        $this->assertNotEmpty($result['blockers']);
        $this->assertEquals('DP_CERT', $result['blockers'][0]['certificate_type']);
    }

    /** @test */
    public function bulk_not_blocked_when_no_hard_block_certs(): void
    {
        config(['maritime.vessel_requirement_engine_v1' => true]);

        $company = Company::factory()->create();

        VesselRequirementTemplate::create([
            'vessel_type_key' => 'bulk',
            'label' => 'Bulk Carrier',
            'profile_json' => [
                'required_certificates' => [
                    ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                    ['certificate_type' => 'MEDICAL_FITNESS', 'min_remaining_months' => 6, 'mandatory' => true],
                ],
                'experience' => ['vessel_type_min_months' => 12, 'any_vessel_min_months' => 24],
                'behavior_thresholds' => [],
                'weights' => ['cert_fit' => 0.35, 'experience_fit' => 0.30, 'behavior_fit' => 0.20, 'availability_fit' => 0.15],
            ],
        ]);

        $vessel = FleetVessel::create([
            'company_id' => $company->id,
            'name' => 'Test Bulk HB',
            'vessel_type' => 'Bulk Carrier',
            'status' => 'active',
        ]);

        // Candidate missing COC — should NOT be blocked, just penalized
        $candidate = PoolCandidate::factory()->create(['availability_status' => 'available']);

        $service = new CandidateDecisionService();
        $result = $service->computeFinalScore($candidate, $vessel, 'bosun');

        $this->assertNotEquals('blocked', $result['label']);
        $this->assertFalse($result['is_blocked']);
        $this->assertEmpty($result['blockers']);
    }
}
