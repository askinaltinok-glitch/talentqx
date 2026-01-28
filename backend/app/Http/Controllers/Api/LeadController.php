<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    /**
     * List all leads with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Lead::with(['assignedUser', 'activities' => fn($q) => $q->latest()->limit(3)])
            ->withCount('activities');

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('is_hot')) {
            $query->where('is_hot', $request->boolean('is_hot'));
        }

        if ($request->has('company_type')) {
            $query->where('company_type', $request->company_type);
        }

        if ($request->has('needs_follow_up') && $request->boolean('needs_follow_up')) {
            $query->needsFollowUp();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $leads = $query->get();

        return response()->json([
            'success' => true,
            'data' => $leads,
        ]);
    }

    /**
     * Get pipeline stats
     */
    public function pipelineStats(): JsonResponse
    {
        $stats = Lead::select('status', DB::raw('count(*) as count'), DB::raw('sum(estimated_value) as total_value'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalLeads = Lead::count();
        $hotLeads = Lead::where('is_hot', true)->count();
        $needsFollowUp = Lead::needsFollowUp()->count();
        $wonThisMonth = Lead::where('status', 'won')
            ->whereMonth('won_at', now()->month)
            ->whereYear('won_at', now()->year)
            ->count();
        $conversionRate = $totalLeads > 0
            ? round(($stats->get('won')?->count ?? 0) / $totalLeads * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'by_status' => $stats,
                'total_leads' => $totalLeads,
                'hot_leads' => $hotLeads,
                'needs_follow_up' => $needsFollowUp,
                'won_this_month' => $wonThisMonth,
                'conversion_rate' => $conversionRate,
            ],
        ]);
    }

    /**
     * Store new lead (from demo form)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_type' => 'nullable|in:single,chain,franchise',
            'company_size' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:50',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['source'] = $validated['source'] ?? 'website';

        $lead = Lead::create($validated);

        // Create default checklist items
        LeadChecklistItem::createDefaultForLead($lead);

        // Calculate initial score
        $lead->calculateScore();

        return response()->json([
            'success' => true,
            'data' => $lead->load(['activities', 'checklistItems']),
            'message' => 'Lead oluşturuldu',
        ], 201);
    }

    /**
     * Get single lead with all details
     */
    public function show(Lead $lead): JsonResponse
    {
        $lead->load([
            'assignedUser',
            'activities.user',
            'checklistItems' => fn($q) => $q->orderBy('stage')->orderBy('created_at'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $lead,
        ]);
    }

    /**
     * Update lead
     */
    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'contact_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_type' => 'nullable|in:single,chain,franchise',
            'company_size' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|uuid|exists:users,id',
            'estimated_value' => 'nullable|numeric|min:0',
            'next_follow_up_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $lead->update($validated);
        $lead->calculateScore();

        return response()->json([
            'success' => true,
            'data' => $lead->fresh(['assignedUser', 'activities', 'checklistItems']),
            'message' => 'Lead güncellendi',
        ]);
    }

    /**
     * Update lead status
     */
    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', Lead::STATUSES),
            'lost_reason' => 'required_if:status,lost|nullable|string|max:500',
        ]);

        $lead->updateStatus($validated['status'], $validated['lost_reason'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $lead->fresh(['activities']),
            'message' => 'Durum güncellendi',
        ]);
    }

    /**
     * Add activity to lead
     */
    public function addActivity(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:note,call,email,meeting,demo,task',
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meeting_link' => 'nullable|url|max:500',
            'scheduled_at' => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'due_at' => 'nullable|date',
        ]);

        $validated['user_id'] = auth()->id();

        $activity = $lead->activities()->create($validated);

        // Update demo scheduled if it's a demo activity
        if ($validated['type'] === 'demo' && isset($validated['scheduled_at'])) {
            $lead->update(['demo_scheduled_at' => $validated['scheduled_at']]);
        }

        return response()->json([
            'success' => true,
            'data' => $activity->load('user'),
            'message' => 'Aktivite eklendi',
        ], 201);
    }

    /**
     * Update activity
     */
    public function updateActivity(Request $request, Lead $lead, LeadActivity $activity): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meeting_link' => 'nullable|url|max:500',
            'scheduled_at' => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'outcome' => 'nullable|in:completed,no_show,rescheduled,cancelled',
            'is_completed' => 'nullable|boolean',
        ]);

        $activity->update($validated);

        // If demo completed, update lead
        if ($activity->type === 'demo' && ($validated['outcome'] ?? null) === 'completed') {
            $lead->update(['demo_completed_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'data' => $activity->fresh('user'),
            'message' => 'Aktivite güncellendi',
        ]);
    }

    /**
     * Toggle checklist item
     */
    public function toggleChecklist(Request $request, Lead $lead, LeadChecklistItem $item): JsonResponse
    {
        if ($item->is_completed) {
            $item->update([
                'is_completed' => false,
                'completed_at' => null,
                'completed_by' => null,
            ]);
        } else {
            $item->markCompleted(auth()->id());
        }

        return response()->json([
            'success' => true,
            'data' => $item,
            'message' => 'Checklist güncellendi',
        ]);
    }

    /**
     * Get checklist progress
     */
    public function checklistProgress(Lead $lead): JsonResponse
    {
        $items = $lead->checklistItems;
        $byStage = $items->groupBy('stage')->map(function ($stageItems) {
            return [
                'total' => $stageItems->count(),
                'completed' => $stageItems->where('is_completed', true)->count(),
                'percentage' => $stageItems->count() > 0
                    ? round($stageItems->where('is_completed', true)->count() / $stageItems->count() * 100)
                    : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'by_stage' => $byStage,
                'total' => $items->count(),
                'completed' => $items->where('is_completed', true)->count(),
                'overall_percentage' => $items->count() > 0
                    ? round($items->where('is_completed', true)->count() / $items->count() * 100)
                    : 0,
            ],
        ]);
    }

    /**
     * Delete lead
     */
    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead silindi',
        ]);
    }

    /**
     * Get follow-up stats and due leads
     */
    public function followUpStats(): JsonResponse
    {
        $now = now();
        $startOfToday = $now->copy()->startOfDay();
        $endOfToday = $now->copy()->endOfDay();

        // Overdue: next_follow_up_at < start of today
        $overdue = Lead::whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', $startOfToday)
            ->whereNotIn('status', [Lead::STATUS_WON, Lead::STATUS_LOST])
            ->count();

        // Today: next_follow_up_at is today
        $today = Lead::whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [$startOfToday, $endOfToday])
            ->whereNotIn('status', [Lead::STATUS_WON, Lead::STATUS_LOST])
            ->count();

        // Upcoming: next_follow_up_at > end of today (next 7 days)
        $upcoming = Lead::whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '>', $endOfToday)
            ->where('next_follow_up_at', '<=', $now->copy()->addDays(7)->endOfDay())
            ->whereNotIn('status', [Lead::STATUS_WON, Lead::STATUS_LOST])
            ->count();

        // Total due (overdue + today)
        $totalDue = $overdue + $today;

        // Get due leads list (overdue + today, max 10)
        $dueLeads = Lead::select(['id', 'company_name', 'contact_name', 'email', 'status', 'next_follow_up_at'])
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', $endOfToday)
            ->whereNotIn('status', [Lead::STATUS_WON, Lead::STATUS_LOST])
            ->orderBy('next_follow_up_at', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'overdue' => $overdue,
                'today' => $today,
                'upcoming' => $upcoming,
                'total_due' => $totalDue,
                'due_leads' => $dueLeads,
            ],
        ]);
    }
}
