<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\ImportRun;
use App\Services\Import\CrewRosterImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CrewRosterImportController extends Controller
{
    public function __construct(
        private readonly CrewRosterImportService $importService,
    ) {}

    /**
     * POST /v1/octopus/admin/imports/crew-roster/preview
     */
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file'       => 'required|file|mimes:xlsx,xls|max:20480',
            'company_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $filePath = $request->file('file')->getRealPath();
        $limit    = min((int) $request->input('limit', 10), 50);

        try {
            $preview = $this->importService->preview($filePath, $limit);

            return response()->json([
                'success' => true,
                'data'    => $preview,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /v1/octopus/admin/imports/crew-roster
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file'       => 'required|file|mimes:xlsx,xls|max:20480',
            'company_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $file      = $request->file('file');
        $filePath  = $file->getRealPath();
        $userId    = $request->user()->id;
        $companyId = $request->input('company_id');
        $filename  = $file->getClientOriginalName();

        try {
            $run = $this->importService->import($filePath, $userId, $companyId, $filename);

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'            => $run->id,
                    'status'        => $run->status,
                    'filename'      => $run->filename,
                    'total_rows'    => $run->total_rows,
                    'created_count' => $run->created_count,
                    'updated_count' => $run->updated_count,
                    'skipped_count' => $run->skipped_count,
                    'error_count'   => $run->error_count,
                    'row_issues'    => $run->row_issues,
                    'summary'       => $run->summary,
                    'created_at'    => $run->created_at?->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /v1/octopus/admin/imports/crew-roster/history
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $query = ImportRun::where('type', 'crew_roster')
            ->orderByDesc('created_at');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        $runs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => collect($runs->items())->map(fn (ImportRun $r) => [
                'id'            => $r->id,
                'company_id'    => $r->company_id,
                'filename'      => $r->filename,
                'status'        => $r->status,
                'total_rows'    => $r->total_rows,
                'created_count' => $r->created_count,
                'updated_count' => $r->updated_count,
                'skipped_count' => $r->skipped_count,
                'error_count'   => $r->error_count,
                'created_at'    => $r->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $runs->currentPage(),
                'per_page'     => $runs->perPage(),
                'total'        => $runs->total(),
                'last_page'    => $runs->lastPage(),
            ],
        ]);
    }
}
