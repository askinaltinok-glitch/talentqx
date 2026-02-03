<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for public job application routes.
 *
 * These routes must ALWAYS work regardless of company subscription status.
 * Subscription checks should only apply to authenticated customer panel actions.
 */
class PublicApplyTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a company with EXPIRED subscription
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'subscription_plan' => 'basic',
            'subscription_ends_at' => now()->subDays(30), // Expired 30 days ago
        ]);

        // Create branch
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Branch',
            'slug' => 'test-branch',
            'is_active' => true,
        ]);

        // Create active job
        $this->job = Job::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'title' => 'Test Position',
            'role_code' => 'TST001',
            'status' => 'active',
        ]);
    }

    /**
     * Test: Expired subscription company - public job info still accessible.
     *
     * GET /api/v1/apply/{companySlug}/{branchSlug}/{roleCode}
     * should return job info even when company subscription is expired.
     */
    public function test_expired_subscription_company_job_info_accessible(): void
    {
        // Verify company subscription is indeed expired
        $this->assertFalse($this->company->isSubscriptionActive());

        // Make request to get job info
        $response = $this->getJson("/api/v1/apply/{$this->company->slug}/{$this->branch->slug}/{$this->job->role_code}");

        // Should succeed with 200, NOT 403
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'company' => [
                    'name' => 'Test Company',
                ],
                'job' => [
                    'title' => 'Test Position',
                ],
            ],
        ]);

        // Should NOT contain subscription_expired error
        $response->assertJsonMissing(['error' => 'subscription_expired']);
    }

    /**
     * Test: Expired subscription company - public application submission succeeds.
     *
     * POST /api/v1/apply/{companySlug}/{branchSlug}/{roleCode}
     * should create candidate even when company subscription is expired.
     */
    public function test_expired_subscription_company_application_submission_succeeds(): void
    {
        // Verify company subscription is indeed expired
        $this->assertFalse($this->company->isSubscriptionActive());

        // Submit application
        $response = $this->postJson("/api/v1/apply/{$this->company->slug}/{$this->branch->slug}/{$this->job->role_code}", [
            'first_name' => 'Test',
            'last_name' => 'Applicant',
            'email' => 'test@example.com',
            'phone' => '+905551234567',
            'consent_given' => true,
        ]);

        // Should succeed with 201
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        // Candidate should be created in database
        $this->assertDatabaseHas('candidates', [
            'company_id' => $this->company->id,
            'job_id' => $this->job->id,
            'email' => 'test@example.com',
            'status' => Candidate::STATUS_APPLIED,
        ]);
    }

    /**
     * Test: Active subscription company - public routes work normally.
     */
    public function test_active_subscription_company_works_normally(): void
    {
        // Update company to have active subscription
        $this->company->update([
            'subscription_ends_at' => now()->addMonths(6),
        ]);

        $this->assertTrue($this->company->fresh()->isSubscriptionActive());

        // GET job info
        $response = $this->getJson("/api/v1/apply/{$this->company->slug}/{$this->branch->slug}/{$this->job->role_code}");
        $response->assertStatus(200);

        // POST application
        $response = $this->postJson("/api/v1/apply/{$this->company->slug}/{$this->branch->slug}/{$this->job->role_code}", [
            'first_name' => 'Active',
            'last_name' => 'User',
            'email' => 'active@example.com',
            'phone' => '+905559876543',
            'consent_given' => true,
        ]);
        $response->assertStatus(201);
    }

    /**
     * Test: Null subscription_ends_at (unlimited) - works normally.
     */
    public function test_null_subscription_ends_at_works_normally(): void
    {
        // Update company to have null (unlimited) subscription
        $this->company->update([
            'subscription_ends_at' => null,
        ]);

        $this->assertTrue($this->company->fresh()->isSubscriptionActive());

        // GET job info
        $response = $this->getJson("/api/v1/apply/{$this->company->slug}/{$this->branch->slug}/{$this->job->role_code}");
        $response->assertStatus(200);
    }

    /**
     * Test: Candidate data is stored normally regardless of subscription.
     */
    public function test_candidate_data_stored_normally_when_expired(): void
    {
        // Ensure expired
        $this->assertFalse($this->company->isSubscriptionActive());

        $applicationData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+905551112233',
            'consent_given' => true,
            'source' => 'qr_apply',
        ];

        $response = $this->postJson("/api/v1/apply/{$this->company->slug}/{$this->branch->slug}/{$this->job->role_code}", $applicationData);

        $response->assertStatus(201);

        // Verify all data is stored correctly
        $candidate = Candidate::where('email', 'john.doe@example.com')->first();

        $this->assertNotNull($candidate);
        $this->assertEquals('John', $candidate->first_name);
        $this->assertEquals('Doe', $candidate->last_name);
        $this->assertEquals($this->company->id, $candidate->company_id);
        $this->assertEquals($this->branch->id, $candidate->branch_id);
        $this->assertEquals($this->job->id, $candidate->job_id);
        $this->assertEquals(Candidate::STATUS_APPLIED, $candidate->status);
        $this->assertTrue($candidate->consent_given);
        $this->assertNotNull($candidate->consent_given_at);
    }

    /**
     * Test: Non-existent routes still return 404 (not subscription error).
     */
    public function test_invalid_routes_return_404_not_subscription_error(): void
    {
        // Non-existent company
        $response = $this->getJson("/api/v1/apply/nonexistent-company/{$this->branch->slug}/{$this->job->role_code}");
        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
        $response->assertJsonMissing(['error' => 'subscription_expired']);

        // Non-existent branch
        $response = $this->getJson("/api/v1/apply/{$this->company->slug}/nonexistent-branch/{$this->job->role_code}");
        $response->assertStatus(404);

        // Non-existent job
        $response = $this->getJson("/api/v1/apply/{$this->company->slug}/{$this->branch->slug}/NONEXISTENT");
        $response->assertStatus(404);
    }
}
