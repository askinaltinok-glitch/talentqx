<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Billing\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyCreditController extends Controller
{
    public function __construct(private CreditService $creditService) {}

    /**
     * GET /v1/octopus/admin/credits
     * List all companies with credit info.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::octopus();

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('plan')) {
            $query->where('subscription_plan', $request->plan);
        }

        $companies = $query->orderBy('name')->get();

        $statusFilter = $request->status;

        $data = $companies->map(function (Company $c) {
            $credits = $this->creditService->getCreditStatus($c);
            return self::formatCreditItem($c, $credits);
        });

        if ($statusFilter) {
            $data = $data->filter(fn ($c) => $c['status'] === $statusFilter)->values();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => ['total' => $data->count()],
        ]);
    }

    /**
     * PATCH /v1/octopus/admin/credits/{id}
     * Full credit edit for a company.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $company = Company::octopus()->find($id);
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }

        $validated = $request->validate([
            'subscription_plan' => ['sometimes', Rule::in(Company::PLANS)],
            'monthly_credits' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'bonus_credits' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'grace_credits_total' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'reset_usage' => ['sometimes', 'boolean'],
            'subscription_ends_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $this->creditService->updateCompanyCredits(
            $company,
            $validated,
            $request->user()->id
        );

        $company->refresh();
        $credits = $this->creditService->getCreditStatus($company);

        return response()->json([
            'success' => true,
            'message' => 'Credits updated.',
            'data' => self::formatCreditItem($company, $credits),
        ]);
    }

    /**
     * POST /v1/octopus/admin/credits/{id}/bonus
     * Add bonus credits to a company.
     */
    public function addBonus(Request $request, string $id): JsonResponse
    {
        $company = Company::octopus()->find($id);
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:10000'],
            'reason' => ['sometimes', 'string', 'max:255'],
        ]);

        $this->creditService->addBonusCredits(
            $company,
            $validated['amount'],
            $validated['reason'] ?? 'Bonus credit eklendi',
            $request->user()->id
        );

        $company->refresh();
        $credits = $this->creditService->getCreditStatus($company);

        return response()->json([
            'success' => true,
            'message' => 'Bonus credits added.',
            'data' => self::formatCreditItem($company, $credits),
        ]);
    }

    /**
     * POST /v1/octopus/admin/credits/{id}/reset
     * Reset monthly usage for a company.
     */
    public function resetUsage(Request $request, string $id): JsonResponse
    {
        $company = Company::octopus()->find($id);
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }

        $this->creditService->resetPeriod($company, $request->user()->id);

        $company->refresh();
        $credits = $this->creditService->getCreditStatus($company);

        return response()->json([
            'success' => true,
            'message' => 'Monthly usage reset.',
            'data' => self::formatCreditItem($company, $credits),
        ]);
    }

    /**
     * Consistent DTO for all responses.
     */
    private static function formatCreditItem(Company $c, array $credits): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'subscription_plan' => $c->subscription_plan,
            'subscription_ends_at' => $c->subscription_ends_at?->toIso8601String(),
            'grace_period_ends_at' => $c->grace_period_ends_at?->toIso8601String(),
            'monthly_credits' => $c->monthly_credits,
            'bonus_credits' => $c->bonus_credits,
            'credits_used' => $c->credits_used,
            'grace_credits_total' => $credits['grace_total'],
            'grace_credits_used' => $credits['grace_used'],
            'remaining' => $credits['remaining'],
            'status' => $credits['status'],
            'updated_at' => $c->updated_at->toIso8601String(),
        ];
    }
}
