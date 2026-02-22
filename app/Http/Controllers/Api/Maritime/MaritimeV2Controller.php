<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\FormInterview;
use App\Services\Maritime\MaritimeIdentityInterviewService;
use App\Services\Maritime\ProfileDrivenResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Maritime V2 API Controller
 *
 * Public endpoints for the 2-phase maritime assessment pipeline.
 * All endpoints gated behind `maritime.resolver_v2` feature flag.
 *
 * Flow:
 * 1. POST /maritime/v2/apply          → register candidate + start Phase-1
 * 2. POST /maritime/v2/phase1/answers  → batch-submit Phase-1 answers
 * 3. POST /maritime/v2/phase1/complete → finalize Phase-1, detect class
 * 4. POST /maritime/v2/phase2/start    → resolve class, select scenarios, create Phase-2
 * 5. POST /maritime/v2/phase2/answers  → submit Phase-2 scenario answer
 * 6. POST /maritime/v2/phase2/complete → score + deployment packet
 */
class MaritimeV2Controller extends Controller
{
    public function __construct(
        private MaritimeIdentityInterviewService $identityService,
        private ProfileDrivenResolver $resolver,
    ) {}

    /**
     * POST /maritime/v2/apply
     *
     * Start a Phase-1 identity interview for a candidate.
     */
    public function apply(Request $request): JsonResponse
    {
        if (!config('maritime.resolver_v2')) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'candidate_id' => ['required', 'uuid'],
            'language' => ['nullable', 'string', 'in:en,tr,ru,az'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $language = $request->input('language', 'en');
        $candidateId = $request->input('candidate_id');

        $interview = $this->identityService->startPhase1(
            $candidateId,
            $language,
            $request->only(['source_channel', 'source_meta']),
        );

        // Set resolver fields
        $interview->update([
            'phase' => 1,
            'resolver_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'interview_id' => $interview->id,
                'phase' => 1,
                'status' => $interview->status,
                'questions' => $this->identityService->getTemplateQuestions($language),
            ],
        ], 201);
    }

    /**
     * POST /maritime/v2/phase1/answers
     *
     * Submit Phase-1 identity answers (batch upsert).
     */
    public function phase1Answers(Request $request): JsonResponse
    {
        if (!config('maritime.resolver_v2')) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'interview_id' => ['required', 'uuid'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.slot' => ['required', 'integer', 'min:1'],
            'answers.*.answer_text' => ['required', 'string', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $interview = FormInterview::find($request->input('interview_id'));

        if (!$interview || $interview->interview_phase !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Phase-1 interview not found.',
            ], 404);
        }

        $this->identityService->submitPhase1Answers(
            $interview,
            $request->input('answers'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Answers submitted.',
        ]);
    }

    /**
     * POST /maritime/v2/phase1/complete
     *
     * Complete Phase-1: extract profile, detect command class.
     */
    public function phase1Complete(Request $request): JsonResponse
    {
        if (!config('maritime.resolver_v2')) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'interview_id' => ['required', 'uuid'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $interview = FormInterview::find($request->input('interview_id'));

        if (!$interview || $interview->interview_phase !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Phase-1 interview not found.',
            ], 404);
        }

        $result = $this->identityService->completePhase1($interview);

        if (!($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'incomplete_identity',
                'missing_fields' => $result['missing_fields'] ?? [],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'interview_id' => $interview->id,
                'command_class' => $result['detection']['command_class'],
                'confidence' => $result['detection']['confidence'],
                'alternative_classes' => $result['detection']['alternative_classes'] ?? [],
            ],
        ]);
    }

    /**
     * POST /maritime/v2/phase2/start
     *
     * Start Phase-2: resolve class, select 8 scenarios, create Phase-2 interview.
     */
    public function phase2Start(Request $request): JsonResponse
    {
        if (!config('maritime.resolver_v2')) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'phase1_interview_id' => ['required', 'uuid'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $phase1 = FormInterview::find($request->input('phase1_interview_id'));

        if (!$phase1 || $phase1->interview_phase !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Phase-1 interview not found.',
            ], 404);
        }

        try {
            $result = $this->resolver->startPhase2($phase1);
        } catch (\InvalidArgumentException $e) {
            $data = json_decode($e->getMessage(), true);
            if ($data && isset($data['error'])) {
                return response()->json($data, 422);
            }
            return response()->json([
                'success' => false,
                'error' => 'invalid_request',
                'message' => $e->getMessage(),
            ], 422);
        }

        if (!($result['success'] ?? false)) {
            $statusCode = ($result['error'] ?? '') === 'needs_review' ? 409 : 422;
            return response()->json($result, $statusCode);
        }

        $phase2 = $result['phase2_interview'];
        $resolverResult = $result['resolver_result'];

        return response()->json([
            'success' => true,
            'data' => [
                'phase2_interview_id' => $phase2->id,
                'command_class' => $resolverResult['command_class'],
                'confidence' => $resolverResult['confidence'],
                'scenarios' => $resolverResult['scenarios'],
                'needs_review' => $resolverResult['needs_review'],
                'secondary_class' => $resolverResult['secondary_class'],
            ],
        ], 201);
    }

    /**
     * POST /maritime/v2/phase2/answers
     *
     * Submit a Phase-2 scenario response.
     */
    public function phase2Answers(Request $request): JsonResponse
    {
        if (!config('maritime.resolver_v2')) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'interview_id' => ['required', 'uuid'],
            'slot' => ['required', 'integer', 'min:1', 'max:8'],
            'answer_text' => ['required', 'string', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $interview = FormInterview::find($request->input('interview_id'));

        if (!$interview || $interview->interview_phase !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Phase-2 interview not found.',
            ], 404);
        }

        try {
            $response = $this->resolver->submitPhase2Answer(
                $interview,
                $request->input('slot'),
                $request->input('answer_text'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'response_id' => $response->id,
                'slot' => $response->slot,
            ],
        ]);
    }

    /**
     * POST /maritime/v2/phase2/complete
     *
     * Complete Phase-2: score capabilities, build deployment packet.
     */
    public function phase2Complete(Request $request): JsonResponse
    {
        if (!config('maritime.resolver_v2')) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'interview_id' => ['required', 'uuid'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $interview = FormInterview::find($request->input('interview_id'));

        if (!$interview || $interview->interview_phase !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Phase-2 interview not found.',
            ], 404);
        }

        try {
            $result = $this->resolver->completePhase2($interview);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $interview = $result['interview'];
        $capScore = $result['capability_score'];
        $packet = $result['deployment_packet'];

        return response()->json([
            'success' => true,
            'data' => [
                'interview_id' => $interview->id,
                'status' => $interview->status,
                'command_class' => $interview->command_class_detected,
                'crl' => $capScore->crl,
                'deployment_packet' => $packet,
                'capability_profile' => $interview->capability_profile_json,
            ],
        ]);
    }
}
