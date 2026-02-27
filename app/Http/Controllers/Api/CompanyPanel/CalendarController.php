<?php

namespace App\Http\Controllers\Api\CompanyPanel;

use App\Http\Controllers\Controller;
use App\Models\CompanyPanelAppointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after:start',
        ]);

        $query = CompanyPanelAppointment::with(['lead:id,lead_name', 'salesRep:id,first_name,last_name'])
            ->whereBetween('starts_at', [$request->start, $request->end]);

        // Non-admins only see their own appointments
        if ($request->user()->company_panel_role !== 'super_admin') {
            $query->where('sales_rep_id', $request->user()->id);
        }

        $appointments = $query->orderBy('starts_at')->get();

        return response()->json(['success' => true, 'data' => $appointments]);
    }

    public function availability(Request $request): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $request->validate([
            'date' => 'required|date',
            'sales_rep_id' => 'nullable|uuid',
        ]);

        $repId = $request->input('sales_rep_id', $request->user()->id);

        $booked = CompanyPanelAppointment::where('sales_rep_id', $repId)
            ->whereDate('starts_at', $request->date)
            ->where('status', '!=', 'cancelled')
            ->select('starts_at', 'ends_at')
            ->orderBy('starts_at')
            ->get();

        return response()->json(['success' => true, 'data' => ['booked_slots' => $booked]]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $request->validate([
            'lead_id' => 'nullable|uuid|exists:crm_leads,id',
            'title' => 'required|string|max:255',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
            'customer_timezone' => 'sometimes|string|max:50',
            'notes' => 'nullable|string',
        ]);

        // Check for conflicts
        $conflict = CompanyPanelAppointment::where('sales_rep_id', $request->user()->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($request) {
                $q->whereBetween('starts_at', [$request->starts_at, $request->ends_at])
                    ->orWhereBetween('ends_at', [$request->starts_at, $request->ends_at])
                    ->orWhere(function ($q2) use ($request) {
                        $q2->where('starts_at', '<=', $request->starts_at)
                            ->where('ends_at', '>=', $request->ends_at);
                    });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Bu zaman diliminde çakışan bir randevu var.',
            ], 422);
        }

        $appointment = CompanyPanelAppointment::create([
            'sales_rep_id' => $request->user()->id,
            'lead_id' => $request->lead_id,
            'title' => $request->title,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'customer_timezone' => $request->input('customer_timezone', 'Europe/Istanbul'),
            'notes' => $request->notes,
            'status' => 'scheduled',
        ]);

        return response()->json(['success' => true, 'data' => $appointment], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $appointment = CompanyPanelAppointment::findOrFail($id);

        // Non-admins can only edit their own
        if ($request->user()->company_panel_role !== 'super_admin' && $appointment->sales_rep_id !== $request->user()->id) {
            abort(403, 'Bu randevuyu düzenleme yetkiniz yok.');
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date',
            'status' => 'sometimes|in:scheduled,completed,cancelled',
            'customer_timezone' => 'sometimes|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $appointment->update($request->only('title', 'starts_at', 'ends_at', 'status', 'customer_timezone', 'notes'));

        return response()->json(['success' => true, 'data' => $appointment->fresh()]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $appointment = CompanyPanelAppointment::findOrFail($id);

        if ($request->user()->company_panel_role !== 'super_admin' && $appointment->sales_rep_id !== $request->user()->id) {
            abort(403, 'Bu randevuyu silme yetkiniz yok.');
        }

        $appointment->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Randevu iptal edildi.']);
    }

    private function requireSalesOrAdmin(Request $request): void
    {
        $role = $request->user()->company_panel_role;
        if (!in_array($role, ['super_admin', 'sales_rep'])) {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }
    }
}
