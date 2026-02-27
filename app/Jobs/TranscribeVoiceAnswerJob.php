<?php

namespace App\Jobs;

use App\Exceptions\WhisperTranscriptionException;
use App\Models\FormInterview;
use App\Models\VoiceTranscription;
use App\Services\Maritime\EnglishSpeakingScorer;
use App\Services\Maritime\QuestionBankAssembler;
use App\Services\Voice\WhisperTranscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TranscribeVoiceAnswerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 180, 600];

    public function __construct(
        private string $voiceTranscriptionId,
        private string $dbConnection = 'mysql',
    ) {
        $this->onQueue('voice');
        // Capture the active DB connection at dispatch time
        $this->dbConnection = DB::getDefaultConnection();
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    public function handle(WhisperTranscriber $whisperClient): void
    {
        // Restore the DB connection that was active when this job was dispatched
        DB::setDefaultConnection($this->dbConnection);

        $transcription = VoiceTranscription::find($this->voiceTranscriptionId);

        if (!$transcription) {
            Log::warning('TranscribeVoiceAnswerJob: transcription not found', [
                'id' => $this->voiceTranscriptionId,
            ]);
            return;
        }

        if (!$transcription->isPending()) {
            Log::info('TranscribeVoiceAnswerJob: skipping — not pending', [
                'id'     => $transcription->id,
                'status' => $transcription->status,
            ]);
            return;
        }

        try {
            $result = $whisperClient->transcribe(
                $transcription->audio_path,
                $transcription->audio_mime,
                $transcription->language,
            );

            $transcript = trim($result['text'] ?? '');

            // Guard: empty/too-short transcript
            if (mb_strlen($transcript) < 5) {
                $transcription->markFailed('NEEDS_RERECORD:TRANSCRIPT_TOO_SHORT: ' . mb_strlen($transcript) . ' chars');
                Log::info('TranscribeVoiceAnswerJob: transcript too short', [
                    'id'     => $transcription->id,
                    'length' => mb_strlen($transcript),
                ]);
                return;
            }

            // Guard: Whisper silence hallucination (common phantom phrases on silence/noise)
            if ($this->isHallucination($transcript, $transcription->language)) {
                $transcription->markFailed('NEEDS_RERECORD:SILENCE_HALLUCINATION: "' . mb_substr($transcript, 0, 60) . '"');
                Log::info('TranscribeVoiceAnswerJob: hallucination detected', [
                    'id'         => $transcription->id,
                    'transcript' => $transcript,
                    'language'   => $transcription->language,
                ]);
                return;
            }

            // Mark done
            $transcription->markDone(
                $transcript,
                $result['confidence'] ?? null,
                $result['raw'] ?? null,
                config('services.whisper.model'),
                $result['provider'] ?? null,
            );

            Log::info('TranscribeVoiceAnswerJob: done', [
                'id'               => $transcription->id,
                'transcript_chars' => mb_strlen($transcript),
            ]);

            // Write transcript into interview answer slot
            $this->upsertInterviewAnswer($transcription, $transcript);

            // If English Gate question → trigger scoring
            $this->maybeScoreEnglishGate($transcription, $transcript);

        } catch (WhisperTranscriptionException $e) {
            $message = $e->getMessage();

            // Silence/short audio errors are not retryable — mark as needs rerecord
            if (str_contains($message, 'silence') || str_contains($message, 'too small') || str_contains($message, 'too short')) {
                $transcription->markFailed('NEEDS_RERECORD:' . $message);
                Log::info('TranscribeVoiceAnswerJob: audio quality issue (not retrying)', [
                    'id'    => $transcription->id,
                    'error' => $message,
                ]);
                return; // Don't re-throw — no retry needed
            }

            Log::error('TranscribeVoiceAnswerJob: Whisper error', [
                'id'    => $transcription->id,
                'error' => $message,
            ]);
            $transcription->markFailed($message);
            throw $e; // re-throw for queue retry
        } catch (\Throwable $e) {
            Log::error('TranscribeVoiceAnswerJob: unexpected error', [
                'id'    => $transcription->id,
                'error' => $e->getMessage(),
            ]);
            $transcription->markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Known Whisper hallucination phrases generated from silence, noise, or very short audio.
     * Organized by language.
     */
    private const HALLUCINATION_PATTERNS_EN = [
        'thank you',
        'thanks for watching',
        'thanks for listening',
        'please subscribe',
        'like and subscribe',
        'see you next time',
        'bye bye',
        'you',
        'the end',
        'subtitle',
        'subtitles',
        'music',
        'applause',
        'laughter',
        'silence',
    ];

    private const HALLUCINATION_PATTERNS_TR = [
        'altyazi',
        'altyazı',
        'alt yazı',
        'ses',
        'muzik',
        'müzik',
        'alkis',
        'alkış',
        'sessizlik',
        'tesekkurler',
        'teşekkürler',
        'izlediginiz icin tesekkurler',
        'izlediğiniz için teşekkürler',
        'abone olun',
        'abone ol',
        'bizi takip edin',
        'iyi seyirler',
        'hosgeldiniz',
        'hoşgeldiniz',
        'hoş geldiniz',
    ];

    private function isHallucination(string $transcript, ?string $language = null): bool
    {
        $normalized = mb_strtolower(trim(preg_replace('/[.\s,!?]+/', ' ', $transcript)));
        $normalized = trim($normalized);

        // Check language-specific patterns
        $patterns = self::HALLUCINATION_PATTERNS_EN;
        if ($language === 'tr') {
            $patterns = array_merge($patterns, self::HALLUCINATION_PATTERNS_TR);
        } else {
            // For any language, include Turkish patterns since most interviews are Turkish
            $patterns = array_merge($patterns, self::HALLUCINATION_PATTERNS_TR);
        }

        foreach ($patterns as $pattern) {
            if ($normalized === $pattern) {
                return true;
            }
        }

        // Repetition detection: "Ses 1235885533" or similar numeric hallucinations
        if (preg_match('/^(ses|alt\s*yaz[ıi]|subtitle)\s*[\d\s.]+$/iu', $normalized)) {
            return true;
        }

        // Pure numbers / timestamps hallucination
        if (preg_match('/^[\d\s.:,]+$/', $normalized) && mb_strlen($normalized) <= 30) {
            return true;
        }

        // Very short + single phrase → suspicious
        if (mb_strlen($normalized) <= 20 && str_word_count($normalized) <= 3) {
            // Allow if it looks like a real answer (contains domain-specific words)
            $realWords = [
                // Maritime
                'engine', 'ship', 'vessel', 'sea', 'crew', 'safety', 'bridge', 'cargo', 'port', 'navigation',
                'gemi', 'deniz', 'mürettebat', 'güvenlik', 'köprü', 'kargo', 'liman', 'makine',
                // General work
                'work', 'job', 'team', 'manage', 'project', 'customer', 'service',
                'iş', 'takım', 'müşteri', 'proje', 'hizmet', 'yönet', 'ekip',
                // Technical
                'system', 'data', 'process', 'quality', 'plan',
                'sistem', 'veri', 'süreç', 'kalite',
            ];
            foreach ($realWords as $word) {
                if (str_contains($normalized, $word)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Write the transcript into the interview's answer table so it
     * counts toward completion (same as text answers).
     */
    private function upsertInterviewAnswer(VoiceTranscription $tx, string $transcript): void
    {
        $interview = FormInterview::find($tx->interview_id);
        if (!$interview) {
            return;
        }

        $interview->answers()->updateOrCreate(
            ['slot' => $tx->slot],
            [
                'competency'  => $tx->question_id,
                'answer_text' => $transcript,
            ],
        );
    }

    /**
     * If the question belongs to the English Gate block (slots 23-25),
     * trigger GPT rubric scoring → CEFR → LanguageAssessment.
     *
     * We only score when ALL english gate answers for this interview are done.
     */
    private function maybeScoreEnglishGate(VoiceTranscription $tx, string $transcript): void
    {
        // English gate questions are slots 23, 24, 25
        if ($tx->slot < 23 || $tx->slot > 25) {
            return;
        }

        if (!config('maritime.english_gate.enabled')) {
            return;
        }

        $interview = FormInterview::find($tx->interview_id);
        if (!$interview) {
            return;
        }

        // Check if all 3 english gate slots have done transcriptions
        $doneCount = VoiceTranscription::where('interview_id', $tx->interview_id)
            ->whereBetween('slot', [23, 25])
            ->where('status', VoiceTranscription::STATUS_DONE)
            ->count();

        if ($doneCount < 3) {
            Log::info('TranscribeVoiceAnswerJob: english gate not complete yet', [
                'interview_id' => $tx->interview_id,
                'done_count'   => $doneCount,
            ]);
            return;
        }

        try {
            // Gather all 3 transcripts
            $transcriptions = VoiceTranscription::where('interview_id', $tx->interview_id)
                ->whereBetween('slot', [23, 25])
                ->where('status', VoiceTranscription::STATUS_DONE)
                ->orderBy('slot')
                ->get();

            // Pre-check: each transcript must have minimum substance
            $minTranscriptLen = 20;
            $tooShort = $transcriptions->filter(fn($t) => mb_strlen(trim($t->transcript_text)) < $minTranscriptLen);
            if ($tooShort->isNotEmpty()) {
                $interview->updateQuietly([
                    'meta' => array_merge($interview->meta ?? [], [
                        'english_gate_voice'         => true,
                        'english_gate_scored'        => false,
                        'english_gate_pass'          => false,
                        'english_gate_needs_retake'  => true,
                        'english_gate_error'         => 'INSUFFICIENT_CONTENT: slots ' . $tooShort->pluck('slot')->implode(',') . ' below ' . $minTranscriptLen . ' chars',
                    ]),
                ]);

                Log::info('TranscribeVoiceAnswerJob: english gate insufficient content', [
                    'interview_id' => $tx->interview_id,
                    'short_slots'  => $tooShort->pluck('slot')->toArray(),
                ]);
                return;
            }

            // Build question bank prompt map
            $bankRole = $interview->meta['question_bank_role'] ?? 'oiler';
            $assembler = app(QuestionBankAssembler::class);
            $bank = $assembler->forRole($bankRole, 'en');
            $prompts = $bank['blocks']['english_gate']['prompts'] ?? [];
            $promptMap = collect($prompts)->keyBy('id')->toArray();

            $transcriptPayload = $transcriptions->map(fn($t) => [
                'prompt_id'   => $t->question_id,
                'prompt_text' => $promptMap[$t->question_id]['prompt'] ?? '',
                'transcript'  => $t->transcript_text,
            ])->values()->toArray();

            // GPT rubric scoring
            $scorer = app(EnglishSpeakingScorer::class);
            $rubricScores = $scorer->scoreTranscripts($transcriptPayload);

            // CEFR scoring + LanguageAssessment store
            $candidateId = $interview->pool_candidate_id;
            $roleCode = $interview->meta['question_bank_role'] ?? 'oiler';
            $cefrResult = $scorer->scoreAndStore($candidateId, $roleCode, $rubricScores);

            // Hardened pass gate: confidence must be >= 0.55
            $minConfidence = 0.55;
            $rawPass = $cefrResult['pass'] ?? false;
            $confidence = $cefrResult['confidence'] ?? 0;
            $hardenedPass = $rawPass && $confidence >= $minConfidence;
            $needsRetake = !$hardenedPass;

            // Store in interview meta
            $interview->updateQuietly([
                'meta' => array_merge($interview->meta ?? [], [
                    'english_gate_voice'         => true,
                    'english_gate_scored'        => true,
                    'english_gate_cefr'          => $cefrResult['estimated_level'] ?? null,
                    'english_gate_pass'          => $hardenedPass,
                    'english_gate_needs_retake'  => $needsRetake,
                    'english_gate_confidence'    => $confidence,
                    'english_gate_raw_pass'      => $rawPass,
                ]),
            ]);

            Log::info('TranscribeVoiceAnswerJob: english gate scored', [
                'interview_id' => $tx->interview_id,
                'cefr_level'   => $cefrResult['estimated_level'] ?? null,
                'pass'         => $hardenedPass,
                'raw_pass'     => $rawPass,
                'confidence'   => $confidence,
                'needs_retake' => $needsRetake,
            ]);

        } catch (\Throwable $e) {
            // Fail-open: scoring failure doesn't block transcript
            Log::warning('TranscribeVoiceAnswerJob: english gate scoring failed (fail-open)', [
                'interview_id' => $tx->interview_id,
                'error'        => $e->getMessage(),
            ]);

            $interview->updateQuietly([
                'meta' => array_merge($interview->meta ?? [], [
                    'english_gate_voice'  => true,
                    'english_gate_scored' => false,
                    'english_gate_error'  => $e->getMessage(),
                ]),
            ]);
        }
    }
}
