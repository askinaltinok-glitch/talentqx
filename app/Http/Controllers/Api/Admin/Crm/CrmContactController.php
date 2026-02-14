<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmContact;
use App\Models\CrmAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmContactController extends Controller
{
    /**
     * POST /v1/admin/crm/contacts
     */
    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'company_id' => ['required', 'uuid', 'exists:crm_companies,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'preferred_language' => ['nullable', 'string', 'max:8'],
            'consent_marketing' => ['nullable', 'boolean'],
        ]);

        $contact = CrmContact::create($v);

        CrmAuditLog::log('contact.created', 'contact', $contact->id, null, $v, $request->user()?->id, $request->ip());

        return response()->json(['success' => true, 'data' => $contact], 201);
    }

    /**
     * PUT /v1/admin/crm/contacts/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $contact = CrmContact::find($id);
        if (!$contact) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Contact not found.']], 404);
        }

        $v = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'preferred_language' => ['nullable', 'string', 'max:8'],
            'consent_marketing' => ['nullable', 'boolean'],
        ]);

        $contact->update($v);

        return response()->json(['success' => true, 'data' => $contact->fresh()]);
    }

    /**
     * DELETE /v1/admin/crm/contacts/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $contact = CrmContact::find($id);
        if (!$contact) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Contact not found.']], 404);
        }

        $contact->delete();

        CrmAuditLog::log('contact.deleted', 'contact', $id);

        return response()->json(['success' => true, 'message' => 'Contact deleted.']);
    }
}
