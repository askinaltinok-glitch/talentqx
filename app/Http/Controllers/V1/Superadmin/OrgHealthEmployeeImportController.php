<?php

namespace App\Http\Controllers\V1\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OrgEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrgHealthEmployeeImportController extends Controller
{
    private const TEMPLATE_HEADERS = [
        'employee_code', 'full_name', 'email', 'phone',
        'department_code', 'position_code', 'hire_date', 'status',
    ];

    private const TEMPLATE_ROWS = [
        ['EMP-0001', 'Askin Firat Altinok', 'askinaltinok@gmail.com', '+905xxxxxxxxx', 'EXEC', 'founder', '2015-01-10', 'active'],
        ['EMP-0002', 'Ayse Yilmaz', 'ayse@company.com', '+905xxxxxxxxx', 'SALES', 'sales_exec', '2023-11-01', 'active'],
        ['EMP-0003', 'Mehmet Demir', 'mehmet@company.com', '', 'OPS', 'ops_staff', '2020-05-15', 'inactive'],
    ];

    /**
     * GET /v1/superadmin/orghealth/employees/template.csv
     */
    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::TEMPLATE_HEADERS);
            foreach (self::TEMPLATE_ROWS as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'orghealth_employee_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * GET /v1/superadmin/orghealth/tenants
     * List companies for tenant selector.
     */
    public function tenants(Request $request)
    {
        $companies = Company::query()
            ->select('id', 'name', 'trade_name')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->trade_name ?: $c->name,
            ]);

        return response()->json(['success' => true, 'data' => $companies]);
    }

    /**
     * POST /v1/superadmin/orghealth/employees/import
     */
    public function import(Request $request)
    {
        $request->validate([
            'tenant_id' => ['required', 'uuid', 'exists:companies,id'],
            'file' => ['required', 'file', 'max:5120'], // 5MB max
        ]);

        $tenantId = $request->input('tenant_id');
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
            return response()->json([
                'success' => false,
                'message' => 'File must be CSV or XLSX.',
            ], 422);
        }

        try {
            $rows = $this->parseFile($file->getPathname(), $ext);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'File contains no data rows.',
            ], 422);
        }

        $result = $this->processRows($rows, $tenantId);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Parse CSV or XLSX into array of associative arrays with normalized headers.
     */
    private function parseFile(string $path, string $ext): array
    {
        if ($ext === 'csv') {
            return $this->parseCsv($path);
        }

        return $this->parseSpreadsheet($path);
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('Cannot open file.');
        }

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            throw new \RuntimeException('Empty file.');
        }

        // Normalize headers: lowercase, trim, strip BOM
        $headers = array_map(function ($h) {
            $h = preg_replace('/^\x{FEFF}/u', '', $h); // strip UTF-8 BOM
            return strtolower(trim($h));
        }, $headerRow);

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) === 1 && $line[0] === null) continue; // skip blank lines
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = trim($line[$i] ?? '');
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function parseSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        if (empty($data)) {
            throw new \RuntimeException('Empty spreadsheet.');
        }

        $headerRow = array_shift($data);
        $headers = array_map(fn($h) => strtolower(trim((string) $h)), $headerRow);

        $rows = [];
        foreach ($data as $line) {
            // Skip entirely blank rows
            $nonEmpty = array_filter($line, fn($v) => $v !== null && trim((string) $v) !== '');
            if (empty($nonEmpty)) continue;

            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = trim((string) ($line[$i] ?? ''));
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Process parsed rows and upsert into org_employees.
     */
    private function processRows(array $rows, string $tenantId): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedNoEmail = 0;
        $errors = [];
        $sampleCreated = [];
        $sampleUpdated = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +1 for 0-index, +1 for header row

            // Normalize field access (case-insensitive already from parsing)
            $employeeCode = $row['employee_code'] ?? '';
            $fullName = $row['full_name'] ?? '';
            $email = $row['email'] ?? '';
            $phone = $row['phone'] ?? '';
            $departmentCode = $row['department_code'] ?? '';
            $positionCode = $row['position_code'] ?? '';
            $hireDate = $row['hire_date'] ?? '';
            $status = strtolower($row['status'] ?? '');

            // Validate required fields
            if ($fullName === '') {
                $errors[] = ['row' => $rowNum, 'reason' => 'Missing full_name.'];
                $skipped++;
                continue;
            }

            if ($departmentCode === '') {
                $errors[] = ['row' => $rowNum, 'reason' => 'Missing department_code.'];
                $skipped++;
                continue;
            }

            if (!in_array($status, ['active', 'inactive'])) {
                $errors[] = ['row' => $rowNum, 'reason' => "Invalid status: '$status'. Must be active or inactive."];
                $skipped++;
                continue;
            }

            // Identity check
            if ($employeeCode === '' && $email === '') {
                $errors[] = ['row' => $rowNum, 'reason' => 'Missing identity: employee_code or email required.'];
                $skipped++;
                continue;
            }

            // Validate hire_date format if present
            $hireDateValue = null;
            if ($hireDate !== '') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hireDate)) {
                    $hireDateValue = $hireDate;
                } else {
                    $errors[] = ['row' => $rowNum, 'reason' => "Invalid hire_date format: '$hireDate'. Expected YYYY-MM-DD."];
                    $skipped++;
                    continue;
                }
            }

            // Prepare data
            $data = [
                'full_name' => $fullName,
                'department_code' => $departmentCode,
                'position_code' => $positionCode ?: null,
                'hire_date' => $hireDateValue,
                'status' => $status,
            ];

            if ($email !== '') {
                $data['email'] = $email;
            }
            if ($phone !== '') {
                $data['phone_e164'] = $phone;
            }
            if ($employeeCode !== '') {
                $data['external_employee_ref'] = $employeeCode;
            }

            // Determine upsert key
            $existing = null;
            if ($employeeCode !== '') {
                $existing = OrgEmployee::query()
                    ->where('tenant_id', $tenantId)
                    ->where('external_employee_ref', $employeeCode)
                    ->first();
            }

            if (!$existing && $email !== '') {
                $existing = OrgEmployee::query()
                    ->where('tenant_id', $tenantId)
                    ->where('email', $email)
                    ->first();
            }

            try {
                if ($existing) {
                    $existing->fill($data);
                    $existing->save();
                    $updated++;
                    if (count($sampleUpdated) < 3) {
                        $sampleUpdated[] = ['row' => $rowNum, 'name' => $fullName, 'code' => $employeeCode];
                    }
                } else {
                    $data['tenant_id'] = $tenantId;
                    OrgEmployee::create($data);
                    $created++;
                    if (count($sampleCreated) < 3) {
                        $sampleCreated[] = ['row' => $rowNum, 'name' => $fullName, 'code' => $employeeCode];
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = ['row' => $rowNum, 'reason' => 'DB error: ' . $e->getMessage()];
                $skipped++;
                continue;
            }

            // Track no-email for reporting
            if ($email === '') {
                $skippedNoEmail++;
            }
        }

        return [
            'total_rows' => count($rows),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'skipped_no_email' => $skippedNoEmail,
            'errors' => array_slice($errors, 0, 50), // Cap at 50 to prevent huge responses
            'sample' => [
                'created' => $sampleCreated,
                'updated' => $sampleUpdated,
            ],
        ];
    }
}
