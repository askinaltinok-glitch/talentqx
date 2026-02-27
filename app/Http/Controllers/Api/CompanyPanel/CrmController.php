<?php

namespace App\Http\Controllers\Api\CompanyPanel;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\LeadChecklistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $query = CrmLead::with(['company', 'contact'])
            ->select('id', 'lead_name', 'stage', 'priority', 'company_id', 'contact_id', 'source_channel', 'last_activity_at', 'next_follow_up_at', 'created_at');

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $leads = $query->orderByDesc('last_activity_at')->paginate(25);

        return response()->json(['success' => true, 'data' => $leads]);
    }

    public function pipeline(Request $request): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $pipeline = [];
        foreach (CrmLead::STAGES as $stage) {
            $pipeline[$stage] = CrmLead::with(['company:id,name', 'contact:id,full_name,title'])
                ->where('stage', $stage)
                ->select('id', 'lead_name', 'stage', 'priority', 'company_id', 'contact_id', 'last_activity_at', 'next_follow_up_at')
                ->orderByDesc('last_activity_at')
                ->limit(50)
                ->get();
        }

        return response()->json(['success' => true, 'data' => $pipeline]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $request->validate([
            'lead_name' => 'required|string|max:255',
            'company_id' => 'nullable|uuid|exists:crm_companies,id',
            'contact_id' => 'nullable|uuid|exists:crm_contacts,id',
            'stage' => 'sometimes|in:' . implode(',', CrmLead::STAGES),
            'priority' => 'sometimes|in:' . implode(',', CrmLead::PRIORITIES),
            'source_channel' => 'sometimes|in:' . implode(',', CrmLead::SOURCE_CHANNELS),
            'notes' => 'nullable|string',
        ]);

        $lead = CrmLead::create(array_merge(
            $request->only('lead_name', 'company_id', 'contact_id', 'stage', 'priority', 'source_channel', 'notes'),
            ['stage' => $request->input('stage', 'new'), 'last_activity_at' => now()]
        ));

        // Create default checklist items
        $this->createDefaultChecklist($lead);

        return response()->json(['success' => true, 'data' => $lead->load(['company', 'contact'])], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $lead = CrmLead::with(['company', 'contact', 'activities', 'deals'])
            ->findOrFail($id);

        $checklist = LeadChecklistItem::where('lead_id', $id)
            ->orderBy('stage')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'lead' => $lead,
                'checklist' => $checklist,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $lead = CrmLead::findOrFail($id);

        $request->validate([
            'lead_name' => 'sometimes|string|max:255',
            'stage' => 'sometimes|in:' . implode(',', CrmLead::STAGES),
            'priority' => 'sometimes|in:' . implode(',', CrmLead::PRIORITIES),
            'notes' => 'nullable|string',
            'next_follow_up_at' => 'nullable|date',
        ]);

        $lead->update($request->only('lead_name', 'stage', 'priority', 'notes', 'next_follow_up_at'));
        $lead->touchActivity();

        return response()->json(['success' => true, 'data' => $lead->fresh()]);
    }

    public function addActivity(Request $request, string $id): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $lead = CrmLead::findOrFail($id);

        $request->validate([
            'type' => 'required|string|max:50',
            'payload' => 'nullable|array',
        ]);

        $activity = $lead->addActivity(
            $request->type,
            $request->input('payload', []),
            $request->user()->id
        );

        return response()->json(['success' => true, 'data' => $activity], 201);
    }

    public function toggleChecklist(Request $request, string $id, string $itemId): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $item = LeadChecklistItem::where('lead_id', $id)->findOrFail($itemId);

        if ($item->is_completed) {
            $item->update(['is_completed' => false, 'completed_at' => null, 'completed_by' => null]);
        } else {
            $item->markCompleted($request->user()->id);
        }

        return response()->json(['success' => true, 'data' => $item->fresh()]);
    }

    private function createDefaultChecklist(CrmLead $lead): void
    {
        foreach (LeadChecklistItem::DEFAULT_CHECKLIST as $stage => $items) {
            foreach ($items as $item) {
                LeadChecklistItem::create([
                    'lead_id' => $lead->id,
                    'stage' => $stage,
                    'item' => $item,
                ]);
            }
        }
    }

    private function requireSalesOrAdmin(Request $request): void
    {
        $role = $request->user()->company_panel_role;
        if (!in_array($role, ['super_admin', 'sales_rep'])) {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }
    }
}
