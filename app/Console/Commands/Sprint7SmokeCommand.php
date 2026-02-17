<?php

namespace App\Console\Commands;

use App\Jobs\SendCandidateNotificationJob;
use App\Models\CandidateMembership;
use App\Models\CandidateNotification;
use App\Models\CandidatePushToken;
use App\Models\MaritimeJob;
use App\Models\MaritimeJobApplication;
use App\Models\PoolCandidate;
use App\Models\PoolCompany;
use App\Models\VesselReview;
use App\Services\CandidateNotificationService;
use App\Services\MaritimeJobService;
use App\Services\ProfileActivityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Sprint7SmokeCommand extends Command
{
    protected $signature = 'sprint7:smoke';
    protected $description = 'Sprint-7 smoke test — validates all new tables, services, and business logic';

    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    public function handle(): int
    {
        $this->info('Sprint-7 Smoke Test');
        $this->info('==================');

        DB::beginTransaction();

        try {
            $this->testMembership();
            $this->testProfileActivity();
            $this->testPushToken();
            $this->testMaritimeJob();
            $this->testJobApplication();
            $this->testNotificationDispatch();
            $this->testDuplicateVesselReview();
            $this->testCertificateExpiryNotification();
            $this->testDemoIsolation();
            $this->testReviewDailyLimitAndProfanity();
            $this->testCertificateUrlWhitelist();
        } catch (\Throwable $e) {
            $this->error("FATAL: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            $this->failed++;
        } finally {
            DB::rollBack();
        }

        $this->newLine();
        if ($this->failed === 0) {
            $this->info("ALL TESTS PASS ({$this->passed}/{$this->passed})");
            return 0;
        }

        $this->error("FAILED: {$this->failed} test(s) failed, {$this->passed} passed");
        foreach ($this->errors as $err) {
            $this->error("  - {$err}");
        }
        return 1;
    }

    private function assert(bool $condition, string $label): void
    {
        if ($condition) {
            $this->line("  ✓ {$label}");
            $this->passed++;
        } else {
            $this->error("  ✗ {$label}");
            $this->failed++;
            $this->errors[] = $label;
        }
    }

    private function getOrCreateCandidate(): PoolCandidate
    {
        return PoolCandidate::create([
            'first_name' => 'Smoke',
            'last_name' => 'Test',
            'email' => 'smoke-' . Str::random(8) . '@test.local',
            'country_code' => 'TR',
            'source_channel' => 'organic',
            'primary_industry' => 'maritime',
            'seafarer' => true,
        ]);
    }

    private function getOrCreateCompany(): PoolCompany
    {
        return PoolCompany::create([
            'company_name' => 'Smoke Test Shipping ' . Str::random(4),
            'contact_email' => 'smoke-' . Str::random(8) . '@test.local',
            'industry' => 'maritime',
        ]);
    }

    private function testMembership(): void
    {
        $this->info('1. CandidateMembership');

        $candidate = $this->getOrCreateCandidate();

        $membership = CandidateMembership::create([
            'pool_candidate_id' => $candidate->id,
            'tier' => 'pro',
        ]);

        $this->assert($membership->exists, 'Membership created');
        $this->assert($membership->tier === 'pro', 'Tier is pro');
        $this->assert($membership->isActive(), 'Membership is active');
        $this->assert($membership->getEffectiveTier() === 'pro', 'Effective tier is pro');

        $candidate->refresh();
        $this->assert($candidate->membership_tier === 'pro', 'Candidate accessor returns pro');
    }

    private function testProfileActivity(): void
    {
        $this->info('2. ProfileActivityService');

        $candidate = $this->getOrCreateCandidate();
        $service = app(ProfileActivityService::class);

        // Create test view
        $candidate->profileViews()->create([
            'viewer_type' => 'company',
            'viewer_name' => 'Test Corp',
            'company_name' => 'Test Corp',
            'context' => 'presentation',
            'viewed_at' => now(),
        ]);

        $free = $service->getProfileActivity($candidate->id, 'free', 30);
        $this->assert($free['total_views'] === 1, 'Free tier: total_views=1');
        $this->assert(isset($free['summary']), 'Free tier: has summary');
        $this->assert(!isset($free['companies']), 'Free tier: no companies detail');

        $plus = $service->getProfileActivity($candidate->id, 'plus', 30);
        $this->assert(isset($plus['companies']), 'Plus tier: has companies');
        $this->assert(!isset($plus['views']), 'Plus tier: no full views');

        $pro = $service->getProfileActivity($candidate->id, 'pro', 30);
        $this->assert(isset($pro['views']), 'Pro tier: has full views');
        $this->assert(isset($pro['companies']), 'Pro tier: has companies');
    }

    private function testPushToken(): void
    {
        $this->info('3. CandidatePushToken');

        $candidate = $this->getOrCreateCandidate();

        $token = CandidatePushToken::create([
            'pool_candidate_id' => $candidate->id,
            'device_type' => 'web',
            'token' => 'test-token-' . Str::random(16),
            'last_seen_at' => now(),
        ]);

        $this->assert($token->exists, 'Push token created');
        $this->assert($token->device_type === 'web', 'Device type correct');
        $this->assert(CandidatePushToken::forCandidate($candidate->id)->count() === 1, 'ForCandidate scope works');
    }

    private function testMaritimeJob(): void
    {
        $this->info('4. MaritimeJob');

        $company = $this->getOrCreateCompany();

        $job = MaritimeJob::create([
            'pool_company_id' => $company->id,
            'rank' => 'Master',
            'vessel_type' => 'tanker',
            'salary_range' => '$8000-$12000',
            'contract_length' => '4+2',
            'rotation' => '4 months on / 2 months off',
            'is_active' => true,
        ]);

        $this->assert($job->exists, 'Maritime job created');
        $this->assert($job->rank === 'Master', 'Rank correct');
        $this->assert(MaritimeJob::active()->count() >= 1, 'Active scope works');
    }

    private function testJobApplication(): void
    {
        $this->info('5. MaritimeJobApplication');

        $candidate = $this->getOrCreateCandidate();
        $company = $this->getOrCreateCompany();

        $job = MaritimeJob::create([
            'pool_company_id' => $company->id,
            'rank' => 'Chief Officer',
            'is_active' => true,
        ]);

        $service = app(MaritimeJobService::class);
        $application = $service->apply($job->id, $candidate->id, 'free');

        $this->assert($application->exists, 'Application created');
        $this->assert($application->status === 'applied', 'Status is applied');

        // Test unique constraint
        $duplicate = false;
        try {
            $service->apply($job->id, $candidate->id, 'free');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $duplicate = true;
        }
        $this->assert($duplicate, 'Duplicate application blocked');
    }

    private function testNotificationDispatch(): void
    {
        $this->info('6. Notification dispatch');

        $candidate = $this->getOrCreateCandidate();
        $service = app(CandidateNotificationService::class);

        $service->notifyStatusChange($candidate, 'hired', ['company_name' => 'Test Corp']);

        $notif = CandidateNotification::forCandidate($candidate->id)
            ->where('type', CandidateNotification::TYPE_HIRED)
            ->first();

        $this->assert($notif !== null, 'Hired notification created');
        $this->assert($notif->tier_required === 'free', 'Hired notification is free tier');
        $this->assert(str_contains($notif->body, 'Test Corp'), 'Body mentions company name');
    }

    private function testDuplicateVesselReview(): void
    {
        $this->info('7. Vessel review duplicate check');

        $candidate = $this->getOrCreateCandidate();

        // Create first review directly (bypassing deployment check for smoke test)
        VesselReview::create([
            'pool_candidate_id' => $candidate->id,
            'company_name' => 'Duplicate Test Inc',
            'vessel_name' => 'MV Smoker',
            'rating_salary' => 4,
            'rating_provisions' => 4,
            'rating_cabin' => 4,
            'rating_internet' => 3,
            'rating_bonus' => 3,
            'overall_rating' => 3.6,
            'is_anonymous' => true,
        ]);

        // Try duplicate
        $caught = false;
        try {
            VesselReview::create([
                'pool_candidate_id' => $candidate->id,
                'company_name' => 'Duplicate Test Inc',
                'vessel_name' => 'MV Smoker',
                'rating_salary' => 5,
                'rating_provisions' => 5,
                'rating_cabin' => 5,
                'rating_internet' => 5,
                'rating_bonus' => 5,
                'overall_rating' => 5.0,
                'is_anonymous' => true,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $caught = ($e->errorInfo[1] === 1062);
        }

        $this->assert($caught, 'Unique constraint catches duplicate vessel review');
    }

    private function testCertificateExpiryNotification(): void
    {
        $this->info('8. Certificate expiry notification');

        $candidate = $this->getOrCreateCandidate();
        $service = app(CandidateNotificationService::class);

        // Create a mock certificate
        $cert = $candidate->certificates()->create([
            'certificate_type' => 'STCW Basic Safety',
            'certificate_number' => 'TEST-' . Str::random(6),
            'issue_date' => now()->subYear(),
            'expiry_date' => now()->addDays(30),
            'status' => 'verified',
        ]);

        $service->notifyCertificateExpiring($candidate, $cert, 30);

        $notif = CandidateNotification::forCandidate($candidate->id)
            ->where('type', CandidateNotification::TYPE_CERTIFICATE_EXPIRING)
            ->first();

        $this->assert($notif !== null, 'Certificate expiry notification created');
        $this->assert(str_contains($notif->body, '30 days'), 'Body mentions days left');
    }

    private function testDemoIsolation(): void
    {
        $this->info('9. Demo mode isolation');

        // Create a demo candidate using withoutGlobalScope
        $demo = PoolCandidate::withoutGlobalScope('excludeDemo')->create([
            'first_name' => 'Demo',
            'last_name' => 'Candidate',
            'email' => 'demo-' . Str::random(8) . '@test.local',
            'country_code' => 'TR',
            'source_channel' => 'demo',
            'source_meta' => ['is_demo' => true],
            'primary_industry' => 'maritime',
            'seafarer' => true,
        ]);

        // Normal query should NOT find the demo candidate
        $found = PoolCandidate::where('id', $demo->id)->exists();
        $this->assert(!$found, 'Demo candidate excluded from normal queries');

        // Query with withoutGlobalScope SHOULD find it
        $foundWithScope = PoolCandidate::withoutGlobalScope('excludeDemo')
            ->where('id', $demo->id)->exists();
        $this->assert($foundWithScope, 'Demo candidate found with withoutGlobalScope');

        // Regular candidate is still visible
        $regular = $this->getOrCreateCandidate();
        $foundRegular = PoolCandidate::where('id', $regular->id)->exists();
        $this->assert($foundRegular, 'Regular candidate visible in normal queries');
    }

    private function testReviewDailyLimitAndProfanity(): void
    {
        $this->info('10. Review spam protection');

        $service = app(\App\Services\VesselReviewService::class);
        $candidate = $this->getOrCreateCandidate();

        // Test profanity filter
        $profanityCaught = false;
        try {
            $service->submit($candidate->id, [
                'company_name' => 'Test Corp',
                'vessel_name' => 'MV Test',
                'rating_salary' => 4,
                'rating_provisions' => 4,
                'rating_cabin' => 4,
                'rating_internet' => 3,
                'rating_bonus' => 3,
                'comment' => 'This company is run by idiots and morons',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $profanityCaught = str_contains(
                json_encode($e->errors()),
                'inappropriate content'
            );
        }
        $this->assert($profanityCaught, 'Profanity filter catches inappropriate content');

        // Test daily limit: create 5 reviews directly to fill quota
        for ($i = 0; $i < 5; $i++) {
            VesselReview::create([
                'pool_candidate_id' => $candidate->id,
                'company_name' => 'Limit Test Corp ' . $i,
                'vessel_name' => 'MV Limit ' . $i,
                'rating_salary' => 4,
                'rating_provisions' => 4,
                'rating_cabin' => 4,
                'rating_internet' => 3,
                'rating_bonus' => 3,
                'overall_rating' => 3.6,
                'is_anonymous' => true,
            ]);
        }

        $limitCaught = false;
        try {
            $service->submit($candidate->id, [
                'company_name' => 'Over Limit Corp',
                'vessel_name' => 'MV Over',
                'rating_salary' => 4,
                'rating_provisions' => 4,
                'rating_cabin' => 4,
                'rating_internet' => 3,
                'rating_bonus' => 3,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $limitCaught = str_contains(
                json_encode($e->errors()),
                'maximum of 5'
            );
        }
        $this->assert($limitCaught, 'Daily review limit enforced (max 5/day)');
    }

    private function testCertificateUrlWhitelist(): void
    {
        $this->info('11. Certificate URL domain whitelist');

        $controller = app(\App\Http\Controllers\Api\CertificateController::class);
        $candidate = $this->getOrCreateCandidate();

        // Test blocked domain
        $request = \Illuminate\Http\Request::create('/v1/certificates/upload', 'POST', [
            'candidate_id' => $candidate->id,
            'certificate_type' => 'STCW',
            'document_url' => 'https://evil-site.com/malware.pdf',
        ]);
        $response = $controller->upload($request);
        $data = json_decode($response->getContent(), true);
        $this->assert(
            $response->getStatusCode() === 422 && isset($data['errors']['document_url']),
            'Blocked domain rejected'
        );

        // Test allowed domain
        $request2 = \Illuminate\Http\Request::create('/v1/certificates/upload', 'POST', [
            'candidate_id' => $candidate->id,
            'certificate_type' => 'STCW',
            'document_url' => 'https://s3.amazonaws.com/talentqx-certs/cert.pdf',
        ]);
        $response2 = $controller->upload($request2);
        $data2 = json_decode($response2->getContent(), true);
        $urlNotBlocked = !isset($data2['errors']['document_url']);
        $this->assert($urlNotBlocked, 'Allowed domain accepted');

        // Test HTTP rejected (must be HTTPS)
        $request3 = \Illuminate\Http\Request::create('/v1/certificates/upload', 'POST', [
            'candidate_id' => $candidate->id,
            'certificate_type' => 'STCW',
            'document_url' => 'http://s3.amazonaws.com/cert.pdf',
        ]);
        $response3 = $controller->upload($request3);
        $data3 = json_decode($response3->getContent(), true);
        $this->assert(
            $response3->getStatusCode() === 422,
            'HTTP URL rejected (HTTPS required)'
        );
    }
}
