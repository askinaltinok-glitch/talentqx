<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterviewReport;
use App\Models\InterviewSession;
use App\Models\ReportAuditLog;
use App\Services\Report\InterviewReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private InterviewReportService $reportService
    ) {}

    /**
     * List reports for the authenticated user's company
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->company_id;

        $query = InterviewReport::query()
            ->orderBy('created_at', 'desc');

        // Scope to tenant if not platform admin
        if ($tenantId && !$user->is_platform_admin) {
            $query->where('tenant_id', $tenantId);
        }

        $perPage = min((int) $request->input('per_page', 20), 50);
        $reports = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reports->map(fn($r) => [
                'id' => $r->id,
                'session_id' => $r->session_id,
                'status' => $r->status,
                'locale' => $r->locale,
                'file_size' => $r->file_size,
                'generated_at' => $r->generated_at,
                'expires_at' => $r->expires_at,
                'created_at' => $r->created_at,
            ]),
            'meta' => [
                'total' => $reports->total(),
                'per_page' => $reports->perPage(),
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
            ],
        ]);
    }

    /**
     * Generate a new report
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|uuid|exists:interview_sessions,id',
            'locale' => 'nullable|in:tr,en',
            'branding' => 'nullable|array',
            'branding.logo_url' => 'nullable|url',
            'branding.primary_color' => 'nullable|string',
            'branding.company_name' => 'nullable|string',
        ]);

        $session = InterviewSession::with('analysis')->findOrFail($validated['session_id']);

        if (!$session->analysis) {
            return response()->json([
                'error' => 'Analysis not found. Please wait for analysis to complete.',
            ], 400);
        }

        try {
            $report = $this->reportService->generate(
                $validated['session_id'],
                $request->user()?->tenant_id,
                $validated['locale'] ?? 'tr',
                $validated['branding'] ?? null
            );

            return response()->json([
                'report_id' => $report->id,
                'status' => $report->status,
                'generated_at' => $report->generated_at,
                'expires_at' => $report->expires_at,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Report generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get report status
     */
    public function status(string $reportId): JsonResponse
    {
        $report = InterviewReport::findOrFail($reportId);

        return response()->json([
            'report_id' => $report->id,
            'status' => $report->status,
            'file_size' => $report->file_size,
            'generated_at' => $report->generated_at,
            'expires_at' => $report->expires_at,
            'error_message' => $report->error_message,
        ]);
    }

    /**
     * Download report
     */
    public function download(string $reportId): StreamedResponse|JsonResponse
    {
        $report = InterviewReport::findOrFail($reportId);

        if ($report->status !== InterviewReport::STATUS_COMPLETED) {
            return response()->json([
                'error' => 'Report is not ready for download.',
            ], 400);
        }

        if (!$report->fileExists()) {
            return response()->json([
                'error' => 'Report file not found.',
            ], 404);
        }

        if ($report->isExpired()) {
            return response()->json([
                'error' => 'Report has expired.',
            ], 410);
        }

        // Log download
        ReportAuditLog::log($report->id, ReportAuditLog::ACTION_DOWNLOADED);

        $stream = $this->reportService->getFileStream($report);
        $filename = "interview-report-{$report->id}.pdf";

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => $report->file_size,
        ]);
    }

    /**
     * List reports for a session
     */
    public function listForSession(string $sessionId): JsonResponse
    {
        $reports = InterviewReport::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'reports' => $reports->map(fn($r) => [
                'id' => $r->id,
                'status' => $r->status,
                'locale' => $r->locale,
                'file_size' => $r->file_size,
                'generated_at' => $r->generated_at,
                'expires_at' => $r->expires_at,
            ]),
        ]);
    }

    /**
     * Delete a report
     */
    public function delete(string $reportId): JsonResponse
    {
        $report = InterviewReport::findOrFail($reportId);

        $this->reportService->delete($report);

        return response()->json([
            'status' => 'deleted',
        ]);
    }
}
