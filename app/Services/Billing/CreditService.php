<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\CreditUsageLog;
use App\Models\Interview;
use App\Models\User;
use App\Notifications\LowCreditNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class CreditService
{
    /**
     * Credit allocations per subscription plan.
     */
    private const PLAN_CREDITS = [
        'free' => 5,
        'pilot' => 10,
        'mini' => 25,
        'midi' => 100,
        'starter' => 50,
        'pro' => 200,
        'enterprise' => 1000,
    ];

    private const DEFAULT_GRACE_TOTAL = 5;

    /**
     * Get monthly credit allocation for a plan.
     */
    public function getCreditsForPlan(string $plan): int
    {
        return self::PLAN_CREDITS[$plan] ?? self::PLAN_CREDITS['free'];
    }

    /**
     * Get remaining credits for a company (monthly + bonus - used).
     */
    public function getRemainingCredits(Company $company): int
    {
        $totalCredits = $company->monthly_credits + $company->bonus_credits;
        return max(0, $totalCredits - $company->credits_used);
    }

    /**
     * Get grace credit settings from company settings JSON.
     */
    public function getGraceInfo(Company $company): array
    {
        $settings = $company->settings ?? [];
        return [
            'total' => $settings['grace_credits_total'] ?? self::DEFAULT_GRACE_TOTAL,
            'used' => $settings['grace_credits_used'] ?? 0,
        ];
    }

    /**
     * Check if company can use a credit (including grace).
     *
     * Logic:
     * - remaining > 0 → OK
     * - remaining <= 0 AND grace_used < grace_total → OK (grace mode)
     * - else → BLOCK
     */
    public function canUseCredit(Company $company): bool
    {
        if ($this->getRemainingCredits($company) > 0) {
            return true;
        }

        // Check grace credits
        $grace = $this->getGraceInfo($company);
        return $grace['used'] < $grace['total'];
    }

    /**
     * Deduct a credit when interview is completed.
     *
     * - remaining > 0: normal credits_used++
     * - remaining <= 0: grace_credits_used++ (and log)
     */
    public function deductCredit(Company $company, Interview $interview, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($company, $interview, $userId) {
            $company = Company::lockForUpdate()->find($company->id);

            $remaining = $this->getRemainingCredits($company);
            $balanceBefore = $remaining;
            $isGrace = false;

            if ($remaining > 0) {
                // Normal deduction
                $company->increment('credits_used');
            } else {
                // Grace deduction
                $grace = $this->getGraceInfo($company);
                if ($grace['used'] >= $grace['total']) {
                    Log::warning('Credit deduct attempted but exhausted', [
                        'company_id' => $company->id,
                        'interview_id' => $interview->id,
                    ]);
                    return false;
                }

                $settings = $company->settings ?? [];
                $settings['grace_credits_used'] = ($settings['grace_credits_used'] ?? 0) + 1;
                $company->update(['settings' => $settings]);
                $isGrace = true;
            }

            $balanceAfter = $this->getRemainingCredits($company);

            CreditUsageLog::create([
                'company_id' => $company->id,
                'interview_id' => $interview->id,
                'action' => CreditUsageLog::ACTION_DEDUCT,
                'amount' => 1,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => $isGrace
                    ? "Grace credit kullanıldı: {$interview->id}"
                    : "Mülakat tamamlandı: {$interview->id}",
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            Log::info('Credit deducted', [
                'company_id' => $company->id,
                'interview_id' => $interview->id,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'is_grace' => $isGrace,
            ]);

            // Check if credits dropped below 10% threshold
            $this->checkLowCreditWarning($company);

            return true;
        });
    }

    /**
     * Check if company credits are below 10% and send warning.
     */
    public function checkLowCreditWarning(Company $company): void
    {
        $remaining = $this->getRemainingCredits($company);
        $total = $company->monthly_credits + $company->bonus_credits;

        if ($total <= 0) {
            return;
        }

        $percentage = $remaining / $total;

        if ($percentage > 0.10) {
            return;
        }

        // Check if we already sent a warning today
        $settings = $company->settings ?? [];
        $lastWarning = $settings['last_low_credit_warning_at'] ?? null;

        if ($lastWarning && Carbon::parse($lastWarning)->isToday()) {
            return; // Already warned today
        }

        // Update timestamp
        $settings['last_low_credit_warning_at'] = now()->toIso8601String();
        $company->update(['settings' => $settings]);

        // Send notification to company admin users
        $adminUsers = User::where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('role', 'admin')
                  ->orWhere('role', 'owner');
            })
            ->get();

        foreach ($adminUsers as $user) {
            try {
                $user->notify(new LowCreditNotification($company, $remaining, $total));
            } catch (\Throwable $e) {
                Log::warning('Failed to send low credit notification', [
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Low credit warning sent', [
            'company_id' => $company->id,
            'remaining' => $remaining,
            'total' => $total,
            'percentage' => round($percentage * 100, 1),
        ]);
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

            // Reset usage and grace
            $settings = $company->settings ?? [];
            $settings['grace_credits_used'] = 0;

            $company->update([
                'credits_used' => 0,
                'credits_period_start' => now()->startOfMonth(),
                'settings' => $settings,
            ]);

            $balanceAfter = $this->getRemainingCredits($company);

            CreditUsageLog::create([
                'company_id' => $company->id,
                'interview_id' => null,
                'action' => CreditUsageLog::ACTION_RESET,
                'amount' => $previousUsed,
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
     * Full credit update (admin edit drawer).
     */
    public function updateCompanyCredits(
        Company $company,
        array $data,
        ?string $userId = null
    ): void {
        DB::transaction(function () use ($company, $data, $userId) {
            $company = Company::lockForUpdate()->find($company->id);

            $balanceBefore = $this->getRemainingCredits($company);
            $updates = [];

            if (isset($data['subscription_plan'])) {
                $updates['subscription_plan'] = $data['subscription_plan'];
            }
            if (isset($data['monthly_credits'])) {
                $updates['monthly_credits'] = $data['monthly_credits'];
            }
            if (isset($data['bonus_credits'])) {
                $updates['bonus_credits'] = $data['bonus_credits'];
            }
            if (isset($data['subscription_ends_at'])) {
                $updates['subscription_ends_at'] = $data['subscription_ends_at'];
            }

            // Grace credits total
            if (isset($data['grace_credits_total'])) {
                $settings = $company->settings ?? [];
                $settings['grace_credits_total'] = $data['grace_credits_total'];
                $updates['settings'] = $settings;
            }

            // Reset usage
            if (!empty($data['reset_usage'])) {
                $updates['credits_used'] = 0;
                $updates['credits_period_start'] = now()->startOfMonth();
                $settings = $updates['settings'] ?? ($company->settings ?? []);
                $settings['grace_credits_used'] = 0;
                $updates['settings'] = $settings;
            }

            if (!empty($updates)) {
                $company->update($updates);
            }

            $balanceAfter = $this->getRemainingCredits($company);

            CreditUsageLog::create([
                'company_id' => $company->id,
                'interview_id' => null,
                'action' => CreditUsageLog::ACTION_ADD,
                'amount' => abs($balanceAfter - $balanceBefore),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => 'Admin kredi düzenlemesi',
                'created_by' => $userId,
                'created_at' => now(),
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
     * Get detailed credit status for a company (includes grace).
     */
    public function getCreditStatus(Company $company): array
    {
        $remaining = $this->getRemainingCredits($company);
        $totalCredits = $company->monthly_credits + $company->bonus_credits;
        $grace = $this->getGraceInfo($company);

        $resetDate = now()->startOfMonth()->addMonth();

        // Compute status
        if ($remaining > 0) {
            $status = 'active';
        } elseif ($grace['used'] < $grace['total']) {
            $status = 'grace';
        } else {
            $status = 'exhausted';
        }

        return [
            'plan' => $company->subscription_plan,
            'plan_limit' => $company->monthly_credits,
            'used' => $company->credits_used,
            'bonus' => $company->bonus_credits,
            'remaining' => $remaining,
            'total' => $totalCredits,
            'grace_total' => $grace['total'],
            'grace_used' => $grace['used'],
            'status' => $status,
            'can_use' => $this->canUseCredit($company),
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
            ->with(['interview:id,candidate_id', 'interview.candidate:id,first_name,last_name', 'creator:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                $creatorName = null;
                if ($log->creator) {
                    $creatorName = trim($log->creator->first_name . ' ' . $log->creator->last_name);
                }
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'amount' => $log->amount,
                    'balance_before' => $log->balance_before,
                    'balance_after' => $log->balance_after,
                    'reason' => $log->reason,
                    'interview_id' => $log->interview_id,
                    'candidate_name' => $log->interview?->candidate?->full_name,
                    'created_by' => $creatorName,
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }
}
