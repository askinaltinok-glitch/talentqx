<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmDeal;
use App\Models\CrmDealStageHistory;
use App\Models\CrmLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmDealController extends Controller
{
    /**
     * List deals with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CrmDeal::with(['lead:id,lead_name,stage', 'company:id,name,domain']);

        if ($request->filled('industry')) {
            $query->industry($request->input('industry'));
        }
        if ($request->filled('stage')) {
            $query->stage($request->input('stage'));
        }
        if ($request->filled('status')) {
            match ($request->input('status')) {
                'open' => $query->open(),
                'won' => $query->won(),
                'lost' => $query->lost(),
                default => null,
            };
        }
        if ($request->filled('q')) {
            $query->search($request->input('q'));
        }

        $deals = $query->orderByDesc('updated_at')
            ->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $deals->items(),
            'meta' => [
                'total' => $deals->total(),
                'page' => $deals->currentPage(),
                'per_page' => $deals->perPage(),
                'last_page' => $deals->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new deal.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'lead_id' => 'required|uuid|exists:crm_leads,id',
            'deal_name' => 'required|string|max:255',
            'industry_code' => 'required|string|max:32',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'expected_close_at' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
            'company_id' => 'nullable|uuid|exists:crm_companies,id',
            'contact_id' => 'nullable|uuid|exists:crm_contacts,id',
        ]);

        $industry = $request->input('industry_code', 'general');
        $initialStage = CrmDeal::initialStage($industry);

        $deal = CrmDeal::create([
            'lead_id' => $request->input('lead_id'),
            'company_id' => $request->input('company_id'),
            'contact_id' => $request->input('contact_id'),
            'industry_code' => $industry,
            'deal_name' => $request->input('deal_name'),
            'stage' => $initialStage,
            'value' => $request->input('value'),
            'currency' => $request->input('currency', 'USD'),
            'probability' => CrmDeal::STAGE_PROBABILITIES[$initialStage] ?? 10,
            'expected_close_at' => $request->input('expected_close_at'),
            'notes' => $request->input('notes'),
        ]);

        // Record initial stage
        CrmDealStageHistory::create([
            'deal_id' => $deal->id,
            'from_stage' => null,
            'to_stage' => $initialStage,
            'created_at' => now(),
        ]);

        // Log activity on lead
        $lead = CrmLead::find($request->input('lead_id'));
        if ($lead) {
            $lead->addActivity('system', [
                'action' => 'deal_created',
                'deal_id' => $deal->id,
                'deal_name' => $deal->deal_name,
                'stage' => $initialStage,
                'value' => $deal->value,
            ]);
        }

        CrmAuditLog::log('deal.created', 'deal', $deal->id, null, $deal->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Deal created',
            'data' => $deal->load(['lead:id,lead_name', 'company:id,name']),
        ], 201);
    }

    /**
     * Show deal detail with stage history.
     */
    public function show(string $id): JsonResponse
    {
        $deal = CrmDeal::with([
            'lead:id,lead_name,stage,industry_code,priority',
            'company:id,name,domain,industry_code,country_code',
            'contact:id,full_name,email,title',
            'stageHistory',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $deal,
            'pipeline_stages' => CrmDeal::stagesFor($deal->industry_code),
        ]);
    }

    /**
     * Update deal fields.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $deal = CrmDeal::findOrFail($id);

        $request->validate([
            'deal_name' => 'sometimes|string|max:255',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'expected_close_at' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
            'probability' => 'nullable|integer|min:0|max:100',
        ]);

        $old = $deal->toArray();
        $deal->update($request->only([
            'deal_name', 'value', 'currency', 'expected_close_at', 'notes', 'probability',
        ]));

        CrmAuditLog::log('deal.updated', 'deal', $deal->id, $old, $deal->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Deal updated',
            'data' => $deal,
        ]);
    }

    /**
     * Advance deal to the next pipeline stage.
     */
    public function advanceStage(Request $request, string $id): JsonResponse
    {
        $deal = CrmDeal::findOrFail($id);

        if (!$deal->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Deal is already closed'], 422);
        }

        $deal->advanceStage($request->user()?->id);

        // Log activity
        if ($deal->lead) {
            $deal->lead->addActivity('system', [
                'action' => 'deal_stage_advanced',
                'deal_id' => $deal->id,
                'stage' => $deal->stage,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Deal moved to {$deal->stage}",
            'data' => $deal->load('stageHistory'),
        ]);
    }

    /**
     * Mark deal as won.
     */
    public function win(Request $request, string $id): JsonResponse
    {
        $deal = CrmDeal::findOrFail($id);

        if (!$deal->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Deal is already closed'], 422);
        }

        $deal->win($request->user()?->id);

        if ($deal->lead) {
            $deal->lead->addActivity('system', [
                'action' => 'deal_won',
                'deal_id' => $deal->id,
                'value' => $deal->value,
            ]);
        }

        CrmAuditLog::log('deal.won', 'deal', $deal->id, null, [
            'value' => $deal->value,
            'currency' => $deal->currency,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deal won!',
            'data' => $deal,
        ]);
    }

    /**
     * Mark deal as lost.
     */
    public function lose(Request $request, string $id): JsonResponse
    {
        $deal = CrmDeal::findOrFail($id);

        if (!$deal->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Deal is already closed'], 422);
        }

        $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        $deal->lose($request->input('reason'), $request->user()?->id);

        if ($deal->lead) {
            $deal->lead->addActivity('system', [
                'action' => 'deal_lost',
                'deal_id' => $deal->id,
                'reason' => $request->input('reason'),
            ]);
        }

        CrmAuditLog::log('deal.lost', 'deal', $deal->id, null, [
            'reason' => $request->input('reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deal marked as lost',
            'data' => $deal,
        ]);
    }

    /**
     * Pipeline stats for deals dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        $industry = $request->input('industry');
        $period = $request->input('period', '30d');
        $days = match ($period) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };
        $since = now()->subDays($days);

        $baseQuery = CrmDeal::query();
        if ($industry) {
            $baseQuery->where('industry_code', $industry);
        }

        $open = (clone $baseQuery)->open()->count();
        $won = (clone $baseQuery)->won()->where('won_at', '>=', $since)->count();
        $lost = (clone $baseQuery)->lost()->where('lost_at', '>=', $since)->count();
        $pipelineValue = (clone $baseQuery)->open()->sum('value');
        $wonValue = (clone $baseQuery)->won()->where('won_at', '>=', $since)->sum('value');

        $total = $won + $lost;
        $winRate = $total > 0 ? round(($won / $total) * 100, 1) : 0;

        // By stage
        $byStage = (clone $baseQuery)->open()
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total_value')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        // By industry
        $byIndustry = CrmDeal::open()
            ->selectRaw('industry_code, COUNT(*) as count, SUM(value) as total_value')
            ->groupBy('industry_code')
            ->get()
            ->keyBy('industry_code');

        // Avg deal age
        $avgDealAge = (clone $baseQuery)->open()
            ->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')
            ->value('avg_days') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'open' => $open,
                'won' => $won,
                'lost' => $lost,
                'win_rate' => $winRate,
                'pipeline_value' => (float) $pipelineValue,
                'won_value' => (float) $wonValue,
                'avg_deal_age_days' => round((float) $avgDealAge, 1),
                'by_stage' => $byStage,
                'by_industry' => $byIndustry,
            ],
        ]);
    }

    /**
     * Pipeline kanban data — deals grouped by stage.
     */
    public function pipeline(Request $request): JsonResponse
    {
        $industry = $request->input('industry', 'maritime');
        $stages = CrmDeal::stagesFor($industry);

        $deals = CrmDeal::with(['lead:id,lead_name', 'company:id,name'])
            ->where('industry_code', $industry)
            ->open()
            ->orderByDesc('updated_at')
            ->get();

        $pipeline = [];
        foreach ($stages as $stage) {
            $pipeline[$stage] = $deals->where('stage', $stage)->values();
        }

        // Also include lost deals
        $lostDeals = CrmDeal::with(['lead:id,lead_name', 'company:id,name'])
            ->where('industry_code', $industry)
            ->lost()
            ->orderByDesc('lost_at')
            ->limit(10)
            ->get();
        $pipeline['lost'] = $lostDeals;

        return response()->json([
            'success' => true,
            'data' => $pipeline,
            'stages' => $stages,
            'industry' => $industry,
        ]);
    }
}
