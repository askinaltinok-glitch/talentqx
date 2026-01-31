<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterviewSession;
use App\Models\InterviewAnswer;
use App\Models\InterviewQuestion;
use App\Models\PrivacyConsent;
use App\Jobs\AnalyzeInterviewSessionJob;
use App\Services\Interview\ContextQuestionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InterviewSessionController extends Controller
{
    /**
     * Start a new interview session
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_key' => 'required|string',
            'context_key' => 'nullable|string',
            'locale' => 'required|in:tr,en',
            'candidate_id' => 'nullable|string',
            'consent' => 'required|array',
            'consent.privacy_accepted' => 'required|boolean|accepted',
            'consent.recording_accepted' => 'required|boolean|accepted',
        ]);

        // Create session
        $session = InterviewSession::create([
            'role_key' => $validated['role_key'],
            'context_key' => $validated['context_key'] ?? null,
            'locale' => $validated['locale'],
            'candidate_id' => $validated['candidate_id'] ?? null,
            'status' => InterviewSession::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'metadata' => [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ],
        ]);

        // Record consent
        PrivacyConsent::create([
            'interview_session_id' => $session->id,
            'consent_type' => 'interview',
            'privacy_accepted' => true,
            'recording_accepted' => true,
            'accepted_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Get questions
        $questionService = app(ContextQuestionService::class);
        $questions = $questionService->getQuestionsForSession($session);

        return response()->json([
            'session_id' => $session->id,
            'questions' => $questions->map(fn($q) => [
                'id' => $q->id,
                'key' => $q->question_key,
                'text' => $q->question_text,
                'dimension' => $q->dimension,
            ]),
            'total_questions' => $questions->count(),
        ]);
    }

    /**
     * Submit an answer
     */
    public function submitAnswer(Request $request, string $sessionId): JsonResponse
    {
        $session = InterviewSession::findOrFail($sessionId);

        if ($session->status !== InterviewSession::STATUS_IN_PROGRESS) {
            return response()->json(['error' => 'Session is not active'], 400);
        }

        $validated = $request->validate([
            'question_id' => 'required|integer',
            'question_key' => 'required|string',
            'audio' => 'nullable|file|mimes:webm,mp3,wav,m4a|max:10240',
            'text' => 'nullable|string',
            'duration' => 'nullable|integer',
        ]);

        $audioPath = null;
        if ($request->hasFile('audio')) {
            $audioPath = $request->file('audio')->store(
                "interviews/{$session->id}",
                'private'
            );
        }

        $answer = InterviewAnswer::create([
            'session_id' => $session->id,
            'question_id' => $validated['question_id'],
            'question_key' => $validated['question_key'],
            'audio_path' => $audioPath,
            'raw_text' => $validated['text'] ?? null,
            'duration_seconds' => $validated['duration'] ?? null,
        ]);

        return response()->json([
            'answer_id' => $answer->id,
            'status' => 'saved',
        ]);
    }

    /**
     * Complete the interview
     */
    public function complete(string $sessionId): JsonResponse
    {
        $session = InterviewSession::with('answers')->findOrFail($sessionId);

        if ($session->status !== InterviewSession::STATUS_IN_PROGRESS) {
            return response()->json(['error' => 'Session is not active'], 400);
        }

        $session->markAsCompleted();

        // Dispatch analysis job
        AnalyzeInterviewSessionJob::dispatch($session->id);

        return response()->json([
            'status' => 'completed',
            'message' => 'Interview completed. Analysis will be processed.',
            'answers_count' => $session->answers->count(),
        ]);
    }

    /**
     * Get session status
     */
    public function status(string $sessionId): JsonResponse
    {
        $session = InterviewSession::with(['answers', 'analysis'])->findOrFail($sessionId);

        return response()->json([
            'session_id' => $session->id,
            'status' => $session->status,
            'answers_count' => $session->answers->count(),
            'has_analysis' => $session->analysis !== null,
            'analysis_score' => $session->analysis?->overall_score,
            'started_at' => $session->started_at,
            'completed_at' => $session->completed_at,
        ]);
    }

    /**
     * Get questions for a role/context
     */
    public function questions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_key' => 'required|string',
            'context_key' => 'nullable|string',
            'locale' => 'required|in:tr,en',
        ]);

        $questions = InterviewQuestion::getForRoleAndContext(
            $validated['role_key'],
            $validated['context_key'] ?? null,
            $validated['locale']
        );

        return response()->json([
            'questions' => $questions->map(fn($q) => [
                'id' => $q->id,
                'key' => $q->question_key,
                'text' => $q->question_text,
                'dimension' => $q->dimension,
            ]),
            'total' => $questions->count(),
        ]);
    }
}
