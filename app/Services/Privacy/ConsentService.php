<?php

namespace App\Services\Privacy;

use App\Models\PrivacyConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConsentService
{
    private RegimeResolver $regimeResolver;

    public function __construct(RegimeResolver $regimeResolver)
    {
        $this->regimeResolver = $regimeResolver;
    }

    /**
     * Record a new consent
     *
     * @param array $payload Consent data
     * @param Request|null $request HTTP request for IP/UA extraction
     * @return PrivacyConsent
     */
    public function record(array $payload, ?Request $request = null): PrivacyConsent
    {
        // Resolve regime server-side (don't trust client)
        $regimeData = $this->regimeResolver->resolveFromRequest($request ?? request());

        $consent = PrivacyConsent::create([
            'subject_type' => $payload['subject_type'] ?? 'visitor',
            'subject_id' => $payload['subject_id'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'full_name' => $payload['full_name'] ?? null,
            'consent_type' => $payload['consent_type'] ?? 'privacy_notice',
            'regime' => $regimeData['regime'],
            'policy_version' => $payload['policy_version'] ?? $regimeData['policy_version'],
            'locale' => $regimeData['locale'],
            'country' => $regimeData['country'],
            'ip_address' => $this->getClientIp($request),
            'user_agent' => $request?->userAgent() ?? request()->userAgent(),
            'source' => $payload['source'] ?? 'website',
            'form_type' => $payload['form_type'] ?? null,
            'accepted_at' => now(),
        ]);

        Log::info('Privacy consent recorded', [
            'consent_id' => $consent->id,
            'regime' => $consent->regime,
            'source' => $consent->source,
            'email' => $this->maskEmail($consent->email),
        ]);

        return $consent;
    }

    /**
     * Record consent from a form submission
     */
    public function recordFromForm(array $formData, string $source, string $formType): PrivacyConsent
    {
        return $this->record([
            'subject_type' => 'visitor',
            'email' => $formData['email'] ?? null,
            'phone' => $formData['phone'] ?? null,
            'full_name' => $formData['full_name'] ?? $formData['name'] ?? null,
            'consent_type' => 'privacy_notice',
            'policy_version' => $formData['policy_version'] ?? null,
            'source' => $source,
            'form_type' => $formType,
        ]);
    }

    /**
     * Record consent for a lead
     */
    public function recordForLead(string $leadId, array $leadData): PrivacyConsent
    {
        return $this->record([
            'subject_type' => 'lead',
            'subject_id' => $leadId,
            'email' => $leadData['email'] ?? null,
            'phone' => $leadData['phone'] ?? null,
            'full_name' => $leadData['company_name'] ?? $leadData['contact_name'] ?? null,
            'consent_type' => 'privacy_notice',
            'source' => 'lead_form',
            'form_type' => $leadData['form_type'] ?? 'demo',
        ]);
    }

    /**
     * Record consent for an employee (assessment)
     */
    public function recordForEmployee(string $employeeId, array $employeeData): PrivacyConsent
    {
        return $this->record([
            'subject_type' => 'employee',
            'subject_id' => $employeeId,
            'email' => $employeeData['email'] ?? null,
            'full_name' => $employeeData['full_name'] ?? null,
            'consent_type' => 'data_processing',
            'source' => 'employee_assessment',
            'form_type' => 'assessment',
        ]);
    }

    /**
     * Check if a subject has active consent
     */
    public function hasActiveConsent(string $subjectType, string $subjectId, string $consentType = 'privacy_notice'): bool
    {
        return PrivacyConsent::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('consent_type', $consentType)
            ->active()
            ->exists();
    }

    /**
     * Get all consents for a subject
     */
    public function getConsentsForSubject(string $subjectType, string $subjectId): \Illuminate\Database\Eloquent\Collection
    {
        return PrivacyConsent::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('accepted_at')
            ->get();
    }

    /**
     * Withdraw consent
     */
    public function withdrawConsent(string $consentId): bool
    {
        $consent = PrivacyConsent::find($consentId);
        if (!$consent) {
            return false;
        }

        $consent->withdraw();

        Log::info('Privacy consent withdrawn', [
            'consent_id' => $consent->id,
            'regime' => $consent->regime,
        ]);

        return true;
    }

    /**
     * Get consent statistics
     */
    public function getStats(): array
    {
        return [
            'total' => PrivacyConsent::count(),
            'active' => PrivacyConsent::active()->count(),
            'by_regime' => PrivacyConsent::select('regime')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('regime')
                ->pluck('count', 'regime')
                ->toArray(),
            'by_source' => PrivacyConsent::select('source')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray(),
            'today' => PrivacyConsent::whereDate('accepted_at', today())->count(),
        ];
    }

    /**
     * Get client IP address
     */
    private function getClientIp(?Request $request = null): ?string
    {
        $request = $request ?? request();

        // Check Cloudflare header first
        if ($request->hasHeader('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        // Check X-Forwarded-For
        if ($request->hasHeader('X-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Forwarded-For'));
            return trim($ips[0]);
        }

        return $request->ip();
    }

    /**
     * Mask email for logging
     */
    private function maskEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }

        $local = $parts[0];
        $domain = $parts[1];

        $maskedLocal = strlen($local) > 2
            ? substr($local, 0, 2) . str_repeat('*', strlen($local) - 2)
            : str_repeat('*', strlen($local));

        return $maskedLocal . '@' . $domain;
    }
}
