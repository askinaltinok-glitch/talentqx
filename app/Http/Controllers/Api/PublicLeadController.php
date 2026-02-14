<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmCompany;
use App\Models\CrmContact;
use App\Models\CrmLead;
use App\Models\CrmActivity;
use App\Models\CrmAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicLeadController extends Controller
{
    /**
     * POST /v1/public/leads
     * Website intake — create or match company + contact + lead.
     */
    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'industry_code' => ['nullable', 'string', 'max:32'],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'message' => ['nullable', 'string', 'max:5000'],
            'source_meta' => ['nullable', 'array'],
            'preferred_language' => ['nullable', 'string', 'max:8'],
            'consents' => ['nullable', 'array'],
        ]);

        $industryCode = $v['industry_code'] ?? 'general';
        $domain = CrmCompany::extractDomain(null);

        // Try to extract domain from email
        $emailDomain = null;
        if ($v['email']) {
            $parts = explode('@', $v['email']);
            if (count($parts) === 2) {
                $emailDomain = strtolower($parts[1]);
                // Skip free email providers
                $freeProviders = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'mail.ru', 'yandex.ru', 'yandex.com'];
                if (in_array($emailDomain, $freeProviders)) {
                    $emailDomain = null;
                }
            }
        }

        DB::beginTransaction();

        try {
            // 1) Find or create company by domain
            $company = null;
            if ($emailDomain) {
                $company = CrmCompany::findByDomain($emailDomain);
            }

            if (!$company) {
                $company = CrmCompany::create([
                    'industry_code' => $industryCode,
                    'name' => $v['company_name'],
                    'country_code' => $v['country_code'] ?? 'XX',
                    'domain' => $emailDomain,
                    'status' => CrmCompany::STATUS_NEW,
                    'data_sources' => [['type' => 'website_form', 'date' => now()->toIso8601String()]],
                ]);
            }

            // 2) Find or create contact
            $contact = CrmContact::where('email', $v['email'])->first();

            if (!$contact) {
                $hasMarketing = is_array($v['consents'] ?? null) && in_array('marketing', $v['consents']);
                $contact = CrmContact::create([
                    'company_id' => $company->id,
                    'full_name' => $v['contact_name'],
                    'email' => $v['email'],
                    'phone' => $v['phone'] ?? null,
                    'preferred_language' => $v['preferred_language'] ?? 'en',
                    'consent_marketing' => $hasMarketing,
                    'consent_meta' => [
                        'consents' => $v['consents'] ?? [],
                        'ip' => $request->ip(),
                        'date' => now()->toIso8601String(),
                    ],
                ]);
            }

            // 3) Create lead
            $leadName = "{$company->name} — {$contact->full_name}";
            $lead = CrmLead::create([
                'industry_code' => $industryCode,
                'source_channel' => CrmLead::SOURCE_WEBSITE_FORM,
                'source_meta' => $v['source_meta'] ?? null,
                'company_id' => $company->id,
                'contact_id' => $contact->id,
                'lead_name' => $leadName,
                'stage' => CrmLead::STAGE_NEW,
                'last_activity_at' => now(),
            ]);

            // 4) System activity
            $lead->addActivity(CrmActivity::TYPE_SYSTEM, [
                'action' => 'website_form_submitted',
                'message' => $v['message'] ?? null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // If message, add as note too
            if (!empty($v['message'])) {
                $lead->addActivity(CrmActivity::TYPE_NOTE, [
                    'body' => $v['message'],
                    'source' => 'website_form',
                ]);
            }

            CrmAuditLog::log('lead.public_created', 'lead', $lead->id, null, [
                'company_name' => $v['company_name'],
                'email' => $v['email'],
                'industry' => $industryCode,
            ], null, $request->ip());

            DB::commit();

            Log::info('Public lead created', [
                'lead_id' => $lead->id,
                'company' => $company->name,
                'email' => $v['email'],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'lead_id' => $lead->id,
                    'message' => 'Thank you! We will be in touch shortly.',
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Public lead creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => ['code' => 'server_error', 'message' => 'Failed to submit. Please try again.'],
            ], 500);
        }
    }
}
