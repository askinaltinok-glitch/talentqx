<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Services\DemoCandidateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicDemoController extends Controller
{
    public function __construct(
        private DemoCandidateService $demoService,
    ) {}

    /**
     * POST /v1/public/demo/start
     *
     * Start a public demo flow (no auth, IP rate-limited).
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => 'required|in:maritime,general',
            'locale' => 'required|in:en,tr,ru',
            'profile' => 'nullable|integer|min:0|max:4',
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'source_channel' => 'nullable|string|max:50',
        ]);

        // Force demo mode on for this endpoint
        config(['app.demo_mode' => true]);

        $result = $this->demoService->createDemoCandidate($request->input('profile'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Demo creation failed',
            ], 422);
        }

        $candidateId = $result['data']['candidate']['id'];
        $interviewId = $result['data']['interview']['id'];

        // Capture lead if email or company_name provided
        if ($request->filled('email') || $request->filled('company_name')) {
            try {
                CrmLead::create([
                    'lead_name' => $request->input('company_name', $request->input('email', 'Demo visitor')),
                    'industry_code' => $request->input('industry', 'maritime'),
                    'source_channel' => $request->input('source_channel', 'website_demo'),
                    'source_meta' => [
                        'email' => $request->input('email'),
                        'company_name' => $request->input('company_name'),
                        'demo_candidate_id' => $candidateId,
                        'ip' => $request->ip(),
                        'locale' => $request->input('locale'),
                    ],
                    'stage' => CrmLead::STAGE_NEW,
                    'priority' => CrmLead::PRIORITY_MED,
                    'is_demo' => true,
                ]);
            } catch (\Throwable $e) {
                Log::channel('single')->warning('PublicDemoController: CRM lead creation failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $locale = $request->input('locale', 'en');

        return response()->json([
            'success' => true,
            'data' => [
                'demo_id' => $candidateId,
                'candidate_id' => $candidateId,
                'interview_id' => $interviewId,
                'next_url' => "/{$locale}/maritime/apply?demo=1&cid={$candidateId}",
                'candidate' => $result['data']['candidate'],
                'interview' => $result['data']['interview'],
            ],
        ]);
    }
}
