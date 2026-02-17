<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCompany;
use App\Models\CrmDeal;
use App\Models\CrmEmailMessage;
use App\Models\CrmLead;
use App\Models\CrmOutboundQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmAnalyticsController extends Controller
{
    /**
     * Consolidated sales analytics dashboard.
     */
    public function salesDashboard(Request $request): JsonResponse
    {
        $period = $request->input('period', '30d');
        $days = match ($period) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };
        $since = now()->subDays($days);

        // Email metrics
        $emailsSent = CrmOutboundQueue::where('status', 'sent')
            ->where('sent_at', '>=', $since)
            ->count();

        $emailsDelivered = CrmEmailMessage::outbound()
            ->where('sent_at', '>=', $since)
            ->count();

        $repliesReceived = CrmEmailMessage::inbound()
            ->where('received_at', '>=', $since)
            ->count();

        $replyRate = $emailsSent > 0 ? round(($repliesReceived / $emailsSent) * 100, 1) : 0;

        // Deal metrics
        $dealsOpen = CrmDeal::open()->count();
        $dealsWon = CrmDeal::won()->where('won_at', '>=', $since)->count();
        $dealsLost = CrmDeal::lost()->where('lost_at', '>=', $since)->count();
        $pipelineValue = CrmDeal::open()->sum('value');
        $wonValue = CrmDeal::won()->where('won_at', '>=', $since)->sum('value');
        $totalClosed = $dealsWon + $dealsLost;
        $winRate = $totalClosed > 0 ? round(($dealsWon / $totalClosed) * 100, 1) : 0;

        // Demo metrics (deals at demo_scheduled or demo stage)
        $demosBooked = CrmDeal::whereIn('stage', ['demo_scheduled', 'demo'])
            ->where('created_at', '>=', $since)
            ->count();

        // Pipeline by stage
        $byStage = CrmDeal::open()
            ->select('stage', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(value), 0) as total_value'))
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        // Pipeline by industry
        $byIndustry = CrmDeal::open()
            ->select('industry_code', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(value), 0) as total_value'))
            ->groupBy('industry_code')
            ->get()
            ->keyBy('industry_code');

        // Conversion funnel: leads → contacted → demo → pilot → paying
        $totalLeads = CrmLead::where('created_at', '>=', $since)->count();
        $contacted = CrmLead::where('created_at', '>=', $since)
            ->whereNotIn('stage', ['new'])
            ->count();
        $demosStage = CrmDeal::where('created_at', '>=', $since)
            ->whereIn('stage', ['demo_scheduled', 'demo', 'pilot', 'paying', 'proposal', 'closed_won'])
            ->orWhereNotNull('won_at')
            ->count();
        $pilotStage = CrmDeal::where('created_at', '>=', $since)
            ->whereIn('stage', ['pilot', 'paying', 'closed_won'])
            ->count();
        $payingStage = CrmDeal::where('created_at', '>=', $since)
            ->whereIn('stage', ['paying', 'closed_won'])
            ->count();

        // Top sources
        $topSources = CrmLead::where('created_at', '>=', $since)
            ->select('source_channel', DB::raw('COUNT(*) as count'))
            ->groupBy('source_channel')
            ->orderByDesc('count')
            ->get();

        // Emails by source type
        $emailsBySource = CrmOutboundQueue::where('sent_at', '>=', $since)
            ->where('status', 'sent')
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->get()
            ->keyBy('source');

        // Industry breakdown
        $industryBreakdown = CrmLead::where('created_at', '>=', $since)
            ->select('industry_code', DB::raw('COUNT(*) as leads'), DB::raw("SUM(CASE WHEN stage = 'won' THEN 1 ELSE 0 END) as won"))
            ->groupBy('industry_code')
            ->get()
            ->keyBy('industry_code');

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'emails' => [
                    'sent' => $emailsSent,
                    'delivered' => $emailsDelivered,
                    'replied' => $repliesReceived,
                    'reply_rate' => $replyRate,
                    'by_source' => $emailsBySource,
                ],
                'demos' => [
                    'booked' => $demosBooked,
                ],
                'deals' => [
                    'open' => $dealsOpen,
                    'won' => $dealsWon,
                    'lost' => $dealsLost,
                    'pipeline_value' => (float) $pipelineValue,
                    'won_value' => (float) $wonValue,
                    'win_rate' => $winRate,
                ],
                'pipeline' => [
                    'by_stage' => $byStage,
                    'by_industry' => $byIndustry,
                ],
                'conversion_funnel' => [
                    'leads' => $totalLeads,
                    'contacted' => $contacted,
                    'demo' => $demosStage,
                    'pilot' => $pilotStage,
                    'paying' => $payingStage,
                ],
                'top_sources' => $topSources,
                'industry_breakdown' => $industryBreakdown,
            ],
        ]);
    }

    /**
     * Shipping campaign metrics — maritime-specific sales KPIs.
     */
    public function shippingCampaignMetrics(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $since = now()->subDays($days);

        // Companies contacted (maritime leads with at least one outbound email)
        $companiesContacted = CrmLead::where('industry_code', 'maritime')
            ->whereHas('emails', function ($q) use ($since) {
                $q->where('direction', CrmEmailMessage::DIRECTION_OUTBOUND)
                    ->where('created_at', '>=', $since);
            })
            ->distinct()
            ->count('company_id');

        // Emails sent
        $emailsSent = CrmOutboundQueue::where('status', CrmOutboundQueue::STATUS_SENT)
            ->where('sent_at', '>=', $since)
            ->whereHas('lead', function ($q) {
                $q->where('industry_code', 'maritime');
            })
            ->count();

        // Replies received
        $repliesReceived = CrmEmailMessage::where('direction', CrmEmailMessage::DIRECTION_INBOUND)
            ->where('received_at', '>=', $since)
            ->whereHas('lead', function ($q) {
                $q->where('industry_code', 'maritime');
            })
            ->count();

        $replyRate = $emailsSent > 0 ? round(($repliesReceived / $emailsSent) * 100, 1) : 0;

        // Demos booked (maritime deals at demo_scheduled stage)
        $demosBooked = CrmDeal::where('industry_code', 'maritime')
            ->where('created_at', '>=', $since)
            ->whereIn('stage', ['demo_scheduled', 'pilot', 'paying'])
            ->count();

        // Pilots started
        $pilotsStarted = CrmDeal::where('industry_code', 'maritime')
            ->where('created_at', '>=', $since)
            ->whereIn('stage', ['pilot', 'paying'])
            ->count();

        // Paying conversions
        $payingConversions = CrmDeal::where('industry_code', 'maritime')
            ->where('created_at', '>=', $since)
            ->where('stage', 'paying')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'companies_contacted' => $companiesContacted,
                'emails_sent' => $emailsSent,
                'replies_received' => $repliesReceived,
                'reply_rate' => $replyRate,
                'demos_booked' => $demosBooked,
                'pilots_started' => $pilotsStarted,
                'paying_conversions' => $payingConversions,
                'targets' => [
                    'contacted' => 100,
                    'reply_rate' => '20%',
                    'demos' => 10,
                    'pilots' => 3,
                ],
            ],
        ]);
    }
}
