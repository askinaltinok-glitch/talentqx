<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Http\Controllers\Controller;
use App\Models\OrgQuestionnaire;
use Illuminate\Http\Request;

class PulseQuestionnaireController extends Controller
{
    public function active(Request $request)
    {
        $lang = $request->get('lang') ?: $request->header('Accept-Language', 'en');
        $lang = str_starts_with($lang, 'tr') ? 'tr' : 'en';

        $tenantId = $request->user()->company_id;

        $q = OrgQuestionnaire::query()
            ->where('code', 'pulse')
            ->where('status', 'active')
            ->where(function ($qq) use ($tenantId) {
                $qq->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->orderByRaw('tenant_id is null')
            ->with('questions')
            ->firstOrFail();

        return response()->json([
            'id' => $q->id,
            'code' => $q->code,
            'version' => $q->version,
            'title' => $q->title[$lang] ?? $q->title['en'] ?? 'Pulse Survey',
            'description' => $q->description[$lang] ?? $q->description['en'] ?? null,
            'scoring_schema' => $q->scoring_schema,
            'questions' => $q->questions->map(fn($item) => [
                'id' => $item->id,
                'dimension' => $item->dimension,
                'is_reverse' => (bool) $item->is_reverse,
                'sort_order' => $item->sort_order,
                'text' => $item->text[$lang] ?? $item->text['en'],
            ])->values(),
        ]);
    }
}
