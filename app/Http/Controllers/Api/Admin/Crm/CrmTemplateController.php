<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmEmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmTemplateController extends Controller
{
    /**
     * GET /v1/admin/crm/templates
     */
    public function index(Request $request): JsonResponse
    {
        $query = CrmEmailTemplate::query();

        if ($request->filled('industry')) {
            $query->forIndustry($request->industry);
        }
        if ($request->filled('language')) {
            $query->forLanguage($request->language);
        }
        if ($request->has('active')) {
            $query->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
        }

        $templates = $query->orderBy('key')->orderBy('language')->get();

        return response()->json(['success' => true, 'data' => $templates]);
    }

    /**
     * POST /v1/admin/crm/templates
     */
    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'key' => ['required', 'string', 'max:64'],
            'industry_code' => ['nullable', 'string', 'max:32'],
            'language' => ['required', 'string', 'max:8'],
            'subject' => ['required', 'string', 'max:500'],
            'body_html' => ['required', 'string'],
            'body_text' => ['required', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $template = CrmEmailTemplate::create($v);

        return response()->json(['success' => true, 'data' => $template], 201);
    }

    /**
     * PUT /v1/admin/crm/templates/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $template = CrmEmailTemplate::find($id);
        if (!$template) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Template not found.']], 404);
        }

        $v = $request->validate([
            'subject' => ['sometimes', 'string', 'max:500'],
            'body_html' => ['sometimes', 'string'],
            'body_text' => ['sometimes', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $template->update($v);

        return response()->json(['success' => true, 'data' => $template->fresh()]);
    }

    /**
     * DELETE /v1/admin/crm/templates/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $template = CrmEmailTemplate::find($id);
        if (!$template) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Template not found.']], 404);
        }

        $template->delete();

        return response()->json(['success' => true, 'message' => 'Template deleted.']);
    }

    /**
     * POST /v1/admin/crm/templates/{id}/preview
     */
    public function preview(Request $request, string $id): JsonResponse
    {
        $template = CrmEmailTemplate::find($id);
        if (!$template) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Template not found.']], 404);
        }

        $vars = $request->get('vars', [
            'contact_name' => 'John Doe',
            'company_name' => 'Acme Shipping Co.',
            'lead_name' => 'Acme Shipping Co. — John Doe',
        ]);

        $rendered = $template->render($vars);

        return response()->json(['success' => true, 'data' => $rendered]);
    }
}
