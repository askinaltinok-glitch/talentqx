<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\SeafarerCertificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SeafarerCertificate::with('candidate:id,first_name,last_name')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('verification_status', $request->input('status'));
        }
        if ($request->filled('type')) {
            $query->where('certificate_type', $request->input('type'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $certs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $certs->map(fn($c) => [
                'id' => $c->id,
                'certificate_type' => $c->certificate_type,
                'certificate_code' => $c->certificate_code,
                'issuing_authority' => $c->issuing_authority,
                'issuing_country' => $c->issuing_country,
                'issued_at' => $c->issued_at?->toDateString(),
                'expires_at' => $c->expires_at?->toDateString(),
                'verification_status' => $c->verification_status,
                'is_expired' => $c->isExpired(),
                'candidate' => $c->candidate ? [
                    'id' => $c->candidate->id,
                    'first_name' => $c->candidate->first_name,
                    'last_name' => $c->candidate->last_name,
                ] : null,
                'created_at' => $c->created_at->toIso8601String(),
            ]),
            'pagination' => [
                'current_page' => $certs->currentPage(),
                'per_page' => $certs->perPage(),
                'total' => $certs->total(),
                'last_page' => $certs->lastPage(),
            ],
        ]);
    }
}
