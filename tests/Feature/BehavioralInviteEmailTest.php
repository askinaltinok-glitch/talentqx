<?php

namespace Tests\Feature;

use App\Jobs\SendCandidateEmailJob;
use App\Models\CandidateEmailLog;
use App\Models\FormInterview;
use App\Models\PoolCandidate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BehavioralInviteEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['maritime.behavioral_interview_v1' => true]);
        config(['maritime.behavioral_invite_delay' => 180]);
    }

    private function createCandidate(array $overrides = []): PoolCandidate
    {
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $candidate = PoolCandidate::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . uniqid() . '@test.com',
            'country_code' => 'TR',
            'source_channel' => 'test',
            'status' => PoolCandidate::STATUS_NEW,
            'primary_industry' => 'maritime',
        ], $overrides));

        // created_at is not in $fillable, so set it directly via DB
        if ($createdAt) {
            DB::table('pool_candidates')
                ->where('id', $candidate->id)
                ->update(['created_at' => $createdAt]);
            $candidate->refresh();
        }

        return $candidate;
    }

    private function createCompletedInterview(string $candidateId, array $overrides = []): FormInterview
    {
        return FormInterview::create(array_merge([
            'pool_candidate_id' => $candidateId,
            'type' => 'standard',
            'version' => 'v1',
            'language' => 'en',
            'position_code' => 'test_position',
            'template_position_code' => 'test_position',
            'industry_code' => 'maritime',
            'status' => FormInterview::STATUS_COMPLETED,
            'completed_at' => now()->subHours(3),
        ], $overrides));
    }

    public function test_command_finds_eligible_candidates(): void
    {
        Bus::fake();

        // Eligible: applied 4 hours ago, has completed interview, no invite sent
        $eligible = $this->createCandidate([
            'first_name' => 'Eligible',
            'last_name' => 'Candidate',
            'email' => 'eligible@test.com',
            'created_at' => now()->subHours(4),
        ]);

        $this->createCompletedInterview($eligible->id);

        // Not eligible: applied only 1 hour ago
        $tooRecent = $this->createCandidate([
            'first_name' => 'Recent',
            'last_name' => 'Candidate',
            'email' => 'recent@test.com',
            'created_at' => now()->subHour(),
        ]);

        $this->createCompletedInterview($tooRecent->id, [
            'completed_at' => now()->subMinutes(30),
        ]);

        $this->artisan('maritime:send-behavioral-invites')
            ->assertExitCode(0);

        Bus::assertDispatched(SendCandidateEmailJob::class, function ($job) use ($eligible) {
            return $job->candidateId === $eligible->id
                && $job->mailType === 'behavioral_interview_invite';
        });
    }

    public function test_does_not_resend_to_already_invited(): void
    {
        Bus::fake();

        $candidate = $this->createCandidate([
            'first_name' => 'Already',
            'last_name' => 'Invited',
            'email' => 'invited@test.com',
            'created_at' => now()->subHours(4),
        ]);

        $this->createCompletedInterview($candidate->id);

        // Already sent
        CandidateEmailLog::create([
            'pool_candidate_id' => $candidate->id,
            'mail_type' => 'behavioral_interview_invite',
            'status' => 'sent',
            'to_email' => 'invited@test.com',
            'language' => 'en',
            'subject' => 'Test',
            'sent_at' => now()->subHour(),
        ]);

        $this->artisan('maritime:send-behavioral-invites')
            ->assertExitCode(0);

        // Assert no job dispatched for THIS candidate (others may exist in production DB)
        Bus::assertNotDispatched(SendCandidateEmailJob::class, function ($job) use ($candidate) {
            return $job->candidateId === $candidate->id;
        });
    }

    public function test_command_disabled_when_feature_flag_off(): void
    {
        Bus::fake();
        config(['maritime.behavioral_interview_v1' => false]);

        $this->artisan('maritime:send-behavioral-invites')
            ->assertExitCode(0);

        Bus::assertNothingDispatched();
    }
}
