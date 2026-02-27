<?php

namespace Tests\Feature;

use App\Exceptions\WhisperTranscriptionException;
use App\Jobs\TranscribeVoiceAnswerJob;
use App\Models\VoiceTranscription;
use App\Services\Voice\AiModelsPanelWhisperClient;
use App\Services\Voice\WhisperTranscriber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhisperVoiceAnswerTest extends TestCase
{
    // ── Test 1: Routes are registered ──

    public function test_voice_answer_routes_exist(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn($r) => $r->uri())->toArray();

        $this->assertContains(
            'api/v1/maritime/interview/voice-answers',
            $routes,
            'POST voice-answers route should be registered'
        );
        $this->assertContains(
            'api/v1/maritime/interview/voice-answers/{questionId}',
            $routes,
            'GET voice-answers/{questionId} route should be registered'
        );
    }

    // ── Test 2: VoiceTranscription model CRUD ──

    public function test_voice_transcription_model_crud(): void
    {
        $tx = VoiceTranscription::create([
            'interview_id'     => '00000000-0000-0000-0000-000000000001',
            'candidate_id'     => '00000000-0000-0000-0000-000000000002',
            'question_id'      => 'eng-s1',
            'slot'             => 23,
            'audio_path'       => 'private/test/test.webm',
            'audio_mime'       => 'audio/webm',
            'audio_size_bytes' => 5000,
            'audio_sha256'     => hash('sha256', 'test-audio'),
            'provider'         => 'ai_models_panel',
            'language'         => 'en',
            'status'           => VoiceTranscription::STATUS_PENDING,
        ]);

        $this->assertNotNull($tx->id);
        $this->assertTrue($tx->isPending());
        $this->assertFalse($tx->isDone());
        $this->assertFalse($tx->isFailed());

        // Mark done
        $tx->markDone('Hello world test transcript', 0.95, ['text' => 'Hello world test transcript'], 'faster-whisper-large-v3');
        $tx->refresh();
        $this->assertTrue($tx->isDone());
        $this->assertEquals('Hello world test transcript', $tx->transcript_text);
        $this->assertEquals(0.95, $tx->confidence);
        $this->assertIsArray($tx->raw_response);

        // Cleanup
        $tx->delete();
    }

    // ── Test 3: VoiceTranscription markFailed ──

    public function test_voice_transcription_mark_failed(): void
    {
        $tx = VoiceTranscription::create([
            'interview_id'     => '00000000-0000-0000-0000-000000000001',
            'candidate_id'     => '00000000-0000-0000-0000-000000000002',
            'question_id'      => 'eng-s2',
            'slot'             => 24,
            'audio_path'       => 'private/test/fail.webm',
            'audio_mime'       => 'audio/webm',
            'audio_size_bytes' => 100,
            'audio_sha256'     => hash('sha256', 'fail-audio'),
            'provider'         => 'ai_models_panel',
            'language'         => 'en',
            'status'           => VoiceTranscription::STATUS_PENDING,
        ]);

        $tx->markFailed('TRANSCRIPT_TOO_SHORT: 2 chars');
        $tx->refresh();
        $this->assertTrue($tx->isFailed());
        $this->assertStringContainsString('TRANSCRIPT_TOO_SHORT', $tx->error_message);

        $tx->delete();
    }

    // ── Test 4: VoiceTranscription scopes ──

    public function test_voice_transcription_scopes(): void
    {
        $base = [
            'interview_id'     => '00000000-0000-0000-0000-000000000099',
            'candidate_id'     => '00000000-0000-0000-0000-000000000002',
            'audio_path'       => 'private/test/scope.webm',
            'audio_mime'       => 'audio/webm',
            'audio_size_bytes' => 100,
            'audio_sha256'     => hash('sha256', 'scope-test'),
            'provider'         => 'ai_models_panel',
            'language'         => 'en',
        ];

        $pending = VoiceTranscription::create(array_merge($base, [
            'question_id' => 'scope-p',
            'slot' => 1,
            'status' => VoiceTranscription::STATUS_PENDING,
            'audio_sha256' => hash('sha256', 'scope-p'),
        ]));
        $done = VoiceTranscription::create(array_merge($base, [
            'question_id' => 'scope-d',
            'slot' => 2,
            'status' => VoiceTranscription::STATUS_DONE,
            'transcript_text' => 'Done transcript',
            'audio_sha256' => hash('sha256', 'scope-d'),
        ]));
        $failed = VoiceTranscription::create(array_merge($base, [
            'question_id' => 'scope-f',
            'slot' => 3,
            'status' => VoiceTranscription::STATUS_FAILED,
            'error_message' => 'Test error',
            'audio_sha256' => hash('sha256', 'scope-f'),
        ]));

        $interviewId = '00000000-0000-0000-0000-000000000099';
        $this->assertEquals(1, VoiceTranscription::where('interview_id', $interviewId)->pending()->count());
        $this->assertEquals(1, VoiceTranscription::where('interview_id', $interviewId)->done()->count());
        $this->assertEquals(1, VoiceTranscription::where('interview_id', $interviewId)->failed()->count());

        // Cleanup
        VoiceTranscription::where('interview_id', $interviewId)->delete();
    }

    // ── Test 5: AiModelsPanelWhisperClient handles successful response ──

    public function test_whisper_client_handles_success(): void
    {
        Storage::fake();
        Storage::put('test-audio.webm', str_repeat('a', 1000));

        Http::fake([
            '*/v1/audio/transcriptions' => Http::response([
                'text'       => 'I have been working as a marine engineer for five years.',
                'language'   => 'en',
            ], 200),
        ]);

        // Override config for test
        config([
            'services.ai_models_panel.base_url' => 'https://test.example.com',
            'services.ai_models_panel.api_key'  => 'test-key',
            'services.ai_models_panel.timeout'  => 10,
            'services.ai_models_panel.verify_ssl' => false,
        ]);

        $client = new AiModelsPanelWhisperClient();
        $result = $client->transcribe('test-audio.webm', 'audio/webm', 'en');

        $this->assertEquals('I have been working as a marine engineer for five years.', $result['text']);
        $this->assertEquals('en', $result['language']);

        Http::assertSentCount(1);
    }

    // ── Test 6: AiModelsPanelWhisperClient throws on non-200 ──

    public function test_whisper_client_throws_on_error(): void
    {
        Storage::fake();
        Storage::put('test-audio.webm', str_repeat('b', 100));

        Http::fake([
            '*/v1/audio/transcriptions' => Http::response('Internal Server Error', 500),
        ]);

        config([
            'services.ai_models_panel.base_url' => 'https://test.example.com',
            'services.ai_models_panel.api_key'  => 'test-key',
            'services.ai_models_panel.timeout'  => 10,
            'services.ai_models_panel.verify_ssl' => false,
        ]);

        $this->expectException(WhisperTranscriptionException::class);
        $this->expectExceptionMessage('HTTP 500');

        $client = new AiModelsPanelWhisperClient();
        $client->transcribe('test-audio.webm', 'audio/webm');
    }

    // ── Test 7: AiModelsPanelWhisperClient throws when missing API key ──

    public function test_whisper_client_throws_when_no_api_key(): void
    {
        config([
            'services.ai_models_panel.api_key' => null,
        ]);

        $this->expectException(WhisperTranscriptionException::class);
        $this->expectExceptionMessage('API key is not configured');

        $client = new AiModelsPanelWhisperClient();
        $client->transcribe('any.webm', 'audio/webm');
    }

    // ── Test 8: TranscribeVoiceAnswerJob marks done on good transcript ──

    public function test_transcribe_job_marks_done_on_good_transcript(): void
    {
        Storage::fake();
        Storage::put('private/test/good.webm', str_repeat('c', 500));

        $tx = VoiceTranscription::create([
            'interview_id'     => '00000000-0000-0000-0000-000000000001',
            'candidate_id'     => '00000000-0000-0000-0000-000000000002',
            'question_id'      => 'eng-s1',
            'slot'             => 23,
            'audio_path'       => 'private/test/good.webm',
            'audio_mime'       => 'audio/webm',
            'audio_size_bytes' => 500,
            'audio_sha256'     => hash('sha256', 'good-audio'),
            'provider'         => 'ai_models_panel',
            'language'         => 'en',
            'status'           => VoiceTranscription::STATUS_PENDING,
        ]);

        $mockClient = $this->mock(WhisperTranscriber::class);
        $mockClient->shouldReceive('transcribe')
            ->once()
            ->andReturn([
                'text'       => 'I managed the engine room operations on bulk carriers for three years.',
                'language'   => 'en',
                'confidence' => 0.93,
                'raw'        => ['text' => 'I managed the engine room operations on bulk carriers for three years.'],
                'provider'   => 'openai',
            ]);

        $job = new TranscribeVoiceAnswerJob($tx->id);
        $job->handle($mockClient);

        $tx->refresh();
        $this->assertEquals(VoiceTranscription::STATUS_DONE, $tx->status);
        $this->assertStringContainsString('engine room', $tx->transcript_text);
        $this->assertEquals(0.93, $tx->confidence);

        $tx->delete();
    }

    // ── Test 9: TranscribeVoiceAnswerJob marks failed on short transcript ──

    public function test_transcribe_job_marks_failed_on_short_transcript(): void
    {
        Storage::fake();
        Storage::put('private/test/short.webm', str_repeat('d', 100));

        $tx = VoiceTranscription::create([
            'interview_id'     => '00000000-0000-0000-0000-000000000001',
            'candidate_id'     => '00000000-0000-0000-0000-000000000002',
            'question_id'      => 'eng-s2',
            'slot'             => 24,
            'audio_path'       => 'private/test/short.webm',
            'audio_mime'       => 'audio/webm',
            'audio_size_bytes' => 100,
            'audio_sha256'     => hash('sha256', 'short-audio'),
            'provider'         => 'ai_models_panel',
            'language'         => 'en',
            'status'           => VoiceTranscription::STATUS_PENDING,
        ]);

        $mockClient = $this->mock(WhisperTranscriber::class);
        $mockClient->shouldReceive('transcribe')
            ->once()
            ->andReturn([
                'text'       => 'Hi',
                'language'   => 'en',
                'confidence' => 0.1,
                'raw'        => ['text' => 'Hi'],
                'provider'   => 'openai',
            ]);

        $job = new TranscribeVoiceAnswerJob($tx->id);
        $job->handle($mockClient);

        $tx->refresh();
        $this->assertEquals(VoiceTranscription::STATUS_FAILED, $tx->status);
        $this->assertStringContainsString('TRANSCRIPT_TOO_SHORT', $tx->error_message);

        $tx->delete();
    }

    // ── Test 10: TranscribeVoiceAnswerJob skips non-pending ──

    public function test_transcribe_job_skips_non_pending(): void
    {
        $tx = VoiceTranscription::create([
            'interview_id'     => '00000000-0000-0000-0000-000000000001',
            'candidate_id'     => '00000000-0000-0000-0000-000000000002',
            'question_id'      => 'eng-s3',
            'slot'             => 25,
            'audio_path'       => 'private/test/done.webm',
            'audio_mime'       => 'audio/webm',
            'audio_size_bytes' => 100,
            'audio_sha256'     => hash('sha256', 'done-already'),
            'provider'         => 'ai_models_panel',
            'language'         => 'en',
            'status'           => VoiceTranscription::STATUS_DONE,
            'transcript_text'  => 'Already done',
        ]);

        $mockClient = $this->mock(WhisperTranscriber::class);
        $mockClient->shouldNotReceive('transcribe');

        $job = new TranscribeVoiceAnswerJob($tx->id);
        $job->handle($mockClient);

        // Status unchanged
        $tx->refresh();
        $this->assertEquals(VoiceTranscription::STATUS_DONE, $tx->status);
        $this->assertEquals('Already done', $tx->transcript_text);

        $tx->delete();
    }

    // ── Test 11: Config keys exist ──

    public function test_config_keys_exist(): void
    {
        $this->assertNotNull(config('services.ai_models_panel'));
        $this->assertNotNull(config('services.ai_models_panel.base_url'));
        $this->assertNotNull(config('services.whisper'));
        $this->assertNotNull(config('services.whisper.model'));
        $this->assertNotNull(config('services.voice'));
        $this->assertNotNull(config('services.voice.max_upload_mb'));
        $this->assertNotNull(config('services.voice.max_duration_seconds'));
    }

    // ── Test 12: Job detects hallucination and fails ──

    public function test_transcribe_job_detects_hallucination(): void
    {
        Storage::fake();
        Storage::put('private/test/halluc.webm', str_repeat('e', 200));

        $tx = VoiceTranscription::create([
            'interview_id'     => '00000000-0000-0000-0000-000000000001',
            'candidate_id'     => '00000000-0000-0000-0000-000000000002',
            'question_id'      => 'eng-s1',
            'slot'             => 23,
            'audio_path'       => 'private/test/halluc.webm',
            'audio_mime'       => 'audio/webm',
            'audio_size_bytes' => 200,
            'audio_sha256'     => hash('sha256', 'halluc-audio'),
            'provider'         => 'ai_models_panel',
            'language'         => 'en',
            'status'           => VoiceTranscription::STATUS_PENDING,
        ]);

        $mockClient = $this->mock(WhisperTranscriber::class);
        $mockClient->shouldReceive('transcribe')
            ->once()
            ->andReturn([
                'text'       => 'Thank you.',
                'language'   => 'en',
                'confidence' => null,
                'raw'        => ['text' => 'Thank you.'],
                'provider'   => 'openai',
            ]);

        $job = new TranscribeVoiceAnswerJob($tx->id);
        $job->handle($mockClient);

        $tx->refresh();
        $this->assertEquals(VoiceTranscription::STATUS_FAILED, $tx->status);
        $this->assertStringContainsString('SILENCE_HALLUCINATION', $tx->error_message);

        $tx->delete();
    }

    // ── Test 13: Unique constraint on interview_id + slot ──

    public function test_unique_constraint_interview_slot(): void
    {
        $interviewId = '00000000-0000-0000-0000-000000000088';

        // Cleanup any leftover from previous test runs
        VoiceTranscription::where('interview_id', $interviewId)->delete();

        $base = [
            'interview_id'     => $interviewId,
            'candidate_id'     => '00000000-0000-0000-0000-000000000002',
            'question_id'      => 'eng-s1',
            'slot'             => 23,
            'audio_path'       => 'private/test/uniq1.webm',
            'audio_mime'       => 'audio/webm',
            'audio_size_bytes' => 100,
            'audio_sha256'     => hash('sha256', 'uniq-1'),
            'provider'         => 'ai_models_panel',
            'language'         => 'en',
            'status'           => VoiceTranscription::STATUS_PENDING,
        ];

        $first = VoiceTranscription::create($base);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        try {
            VoiceTranscription::create(array_merge($base, [
                'audio_sha256' => hash('sha256', 'uniq-2'),
                'audio_path'   => 'private/test/uniq2.webm',
            ]));
        } finally {
            VoiceTranscription::where('interview_id', $interviewId)->delete();
        }
    }
}
