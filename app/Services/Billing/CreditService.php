<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\CreditUsageLog;
use App\Models\Interview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditService
{
    /**
     * Credit allocations per subscription plan.
     */
    private const PLAN_CREDITS = [
        'free' => 5,
        'starter' => 50,
        'pro' => 200,
        'enterprise' => 1000,
    ];

    /**
     * Get monthly credit allocation for a plan.
     */
    public function getCreditsForPlan(string $plan): int
    {
        return self::PLAN_CREDITS[$plan] ?? self::PLAN_CREDITS['free'];
    }

    /**
     * Get remaining credits for a company.
     */
    public function getRemainingCredits(Company $company): int
    {
        $totalCredits = $company->monthly_credits + $company->bonus_credits;
        return max(0, $totalCredits - $company->credits_used);
    }

    /**
     * Check if company can use a credit.
     */
    public function canUseCredit(Company $company): bool
    {
        return $this->getRemainingCredits($company) > 0;
    }

    /**
     * Deduct a credit when interview is completed.
     */
    public function deductCredit(Company $company, Interview $interview, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($company, $interview, $userId) {
            // Lock company row for update
            $company = Company::lockForUpdate()->find($company->id);

            $balanceBefore = $this->getRemainingCredits($company);

            // Even if no credits, we still increment usage (for tracking)
            $company->increment('credits_used');

            $balanceAfter = $this->getRemainingCredits($company);

            // Log the usage
            CreditUsageLog::create([
                'company_id' => $company->id,
                'interview_id' => $interview->id,
                'action' => CreditUsageLog::ACTION_DEDUCT,
                'amount' => 1,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => "Mülakat tamamlandı: {$interview->id}",
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            Log::info('Credit deducted', [
                'company_id' => $company->id,
                'interview_id' => $interview->id,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            return true;
        });
    }

    /**
     * Add bonus credits to a company (admin action).
     */
    public function addBonusCredits(Company $company, int $amount, string $reason, ?string $userId = null): void
    {
        DB::transaction(function () use ($company, $amount, $reason, $userId) {
            $company = Company::lockForUpdate()->find($company->id);

            $balanceBefore = $this->getRemainingCredits($company);

            $company->increment('bonus_credits', $amount);

            $balanceAfter = $this->getRemainingCredits($company);

            CreditUsageLog::create([
                'company_id' => $company->id,
                'interview_id' => null,
                'action' => CreditUsageLog::ACTION_BONUS,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => $reason,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            Log::info('Bonus credits added', [
                'company_id' => $company->id,
                'amount' => $amount,
                'reason' => $reason,
                'admin_id' => $userId,
            ]);
        });
    }

    /**
     * Reset period for a company (monthly reset).
     */
    public function resetPeriod(Company $company, ?string $userId = null): void
    {
        DB::transaction(function () use ($company, $userId) {
            $company = Company::lockForUpdate()->find($company->id);

            $balanceBefore = $this->getRemainingCredits($company);
            $previousUsed = $company->credits_used;

            // Reset usage to zero
            $company->update([
                'credits_used' => 0,
                'credits_period_start' => now()->startOfMonth(),
            ]);

            $balanceAfter = $this->getRemainingCredits($company);

            CreditUsageLog::create([
                'company_id' => $company->id,
                'interview_id' => null,
                'action' => CreditUsageLog::ACTION_RESET,
                'amount' => $previousUsed, // How much was reset
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => 'Aylık dönem sıfırlaması',
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            Log::info('Credit period reset', [
                'company_id' => $company->id,
                'previous_used' => $previousUsed,
                'new_balance' => $balanceAfter,
            ]);
        });
    }

    /**
     * Update monthly credits based on plan change.
     */
    public function updatePlanCredits(Company $company, string $newPlan, ?string $userId = null): void
    {
        $newCredits = $this->getCreditsForPlan($newPlan);

        DB::transaction(function () use ($company, $newCredits, $userId) {
            $company = Company::lockForUpdate()->find($company->id);

            $balanceBefore = $this->getRemainingCredits($company);
            $oldCredits = $company->monthly_credits;

            $company->update([
                'monthly_credits' => $newCredits,
            ]);

            $balanceAfter = $this->getRemainingCredits($company);

            $action = $newCredits > $oldCredits ? CreditUsageLog::ACTION_ADD : CreditUsageLog::ACTION_DEDUCT;

            CreditUsageLog::create([
                'company_id' => $company->id,
                'interview_id' => null,
                'action' => $action,
                'amount' => abs($newCredits - $oldCredits),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => "Plan değişikliği: aylık kontür {$oldCredits} -> {$newCredits}",
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Get detailed credit status for a company.
     */
    public function getCreditStatus(Company $company): array
    {
        $remaining = $this->getRemainingCredits($company);
        $totalCredits = $company->monthly_credits + $company->bonus_credits;

        // Calculate reset date (first day of next month)
        $resetDate = now()->startOfMonth()->addMonth();

        return [
            'plan_limit' => $company->monthly_credits,
            'used' => $company->credits_used,
            'bonus' => $company->bonus_credits,
            'remaining' => $remaining,
            'total' => $totalCredits,
            'reset_date' => $resetDate->toDateString(),
            'period_start' => $company->credits_period_start?->toDateString(),
            'usage_percent' => $totalCredits > 0 ? round(($company->credits_used / $totalCredits) * 100, 1) : 0,
            'has_credits' => $remaining > 0,
        ];
    }

    /**
     * Get credit usage history for a company.
     */
    public function getUsageHistory(Company $company, int $limit = 50): array
    {
        return CreditUsageLog::where('company_id', $company->id)
            ->with(['interview:id,candidate_id', 'interview.candidate:id,first_name,last_name', 'creator:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'amount' => $log->amount,
                    'balance_before' => $log->balance_before,
                    'balance_after' => $log->balance_after,
                    'reason' => $log->reason,
                    'interview_id' => $log->interview_id,
                    'candidate_name' => $log->interview?->candidate?->full_name,
                    'created_by' => $log->creator?->name,
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }
}
