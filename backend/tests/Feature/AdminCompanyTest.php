<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Admin Company subscription management endpoints.
 */
class AdminCompanyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create platform admin
        $this->admin = User::factory()->create([
            'is_platform_admin' => true,
        ]);

        // Create regular user (not platform admin)
        $this->regularUser = User::factory()->create([
            'is_platform_admin' => false,
        ]);

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'subscription_plan' => 'starter',
            'subscription_ends_at' => now()->addMonths(6),
            'is_premium' => false,
            'grace_period_ends_at' => null,
        ]);
    }

    /**
     * Test: Non-admin user cannot access admin endpoints.
     */
    public function test_non_admin_cannot_access_companies_list(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/companies');

        $response->assertStatus(403);
    }

    /**
     * Test: Platform admin can list companies.
     */
    public function test_admin_can_list_companies(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/companies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'subscription_plan',
                        'is_premium',
                        'subscription_ends_at',
                        'grace_period_ends_at',
                        'computed_status' => [
                            'status',
                            'is_active',
                            'is_in_grace_period',
                            'has_marketplace_access',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test: Platform admin can view single company.
     */
    public function test_admin_can_view_company(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/companies/{$this->company->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->company->id,
                    'name' => 'Test Company',
                    'subscription_plan' => 'starter',
                ],
            ]);
    }

    /**
     * Test: Platform admin can update subscription.
     */
    public function test_admin_can_update_subscription(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/companies/{$this->company->id}/subscription", [
                'subscription_plan' => 'pro',
                'is_premium' => true,
                'subscription_ends_at' => now()->addYear()->toIso8601String(),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'subscription_plan' => 'pro',
                    'is_premium' => true,
                ],
            ]);

        // Verify database
        $this->company->refresh();
        $this->assertEquals('pro', $this->company->subscription_plan);
        $this->assertTrue($this->company->is_premium);
    }

    /**
     * Test: Computed status is correct for active subscription.
     */
    public function test_computed_status_active(): void
    {
        $this->company->update([
            'subscription_ends_at' => now()->addMonths(6),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/companies/{$this->company->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'computed_status' => [
                        'status' => 'active',
                        'is_active' => true,
                        'is_in_grace_period' => false,
                    ],
                ],
            ]);
    }

    /**
     * Test: Computed status is correct for expired subscription.
     */
    public function test_computed_status_expired(): void
    {
        $this->company->update([
            'subscription_ends_at' => now()->subMonths(3),
            'grace_period_ends_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/companies/{$this->company->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'computed_status' => [
                        'status' => 'expired',
                        'is_active' => false,
                        'is_in_grace_period' => false,
                    ],
                ],
            ]);
    }

    /**
     * Test: Computed status is correct for grace period.
     */
    public function test_computed_status_grace_period(): void
    {
        $this->company->update([
            'subscription_ends_at' => now()->subDays(10),
            'grace_period_ends_at' => now()->addDays(50),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/companies/{$this->company->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'computed_status' => [
                        'status' => 'grace_period',
                        'is_active' => false,
                        'is_in_grace_period' => true,
                    ],
                ],
            ]);
    }

    /**
     * Test: Marketplace access requires premium + active.
     */
    public function test_marketplace_access_requires_premium_and_active(): void
    {
        // Premium but expired - no access
        $this->company->update([
            'is_premium' => true,
            'subscription_ends_at' => now()->subMonth(),
            'grace_period_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/companies/{$this->company->id}");

        $response->assertJson([
            'data' => [
                'computed_status' => [
                    'has_marketplace_access' => false,
                ],
            ],
        ]);

        // Active but not premium - no access
        $this->company->update([
            'is_premium' => false,
            'subscription_ends_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/companies/{$this->company->id}");

        $response->assertJson([
            'data' => [
                'computed_status' => [
                    'has_marketplace_access' => false,
                ],
            ],
        ]);

        // Premium + active - has access
        $this->company->update([
            'is_premium' => true,
            'subscription_ends_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/companies/{$this->company->id}");

        $response->assertJson([
            'data' => [
                'computed_status' => [
                    'has_marketplace_access' => true,
                ],
            ],
        ]);
    }

    /**
     * Test: Invalid plan is rejected.
     */
    public function test_invalid_plan_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/companies/{$this->company->id}/subscription", [
                'subscription_plan' => 'invalid_plan',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test: Grace period must be after subscription end.
     */
    public function test_grace_period_must_be_after_subscription_end(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/companies/{$this->company->id}/subscription", [
                'subscription_ends_at' => now()->addMonth()->toIso8601String(),
                'grace_period_ends_at' => now()->toIso8601String(), // Before subscription end
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => [
                    'code' => 'validation_error',
                ],
            ]);
    }

    /**
     * Test: Audit log is created on update.
     */
    public function test_audit_log_created_on_update(): void
    {
        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/companies/{$this->company->id}/subscription", [
                'subscription_plan' => 'enterprise',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'company_id' => $this->company->id,
            'action' => 'admin.subscription.update',
            'entity_type' => 'company',
            'entity_id' => $this->company->id,
        ]);
    }
}
