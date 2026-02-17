<?php

namespace App\Http\Controllers\Api\Admin;

use App\Config\MaritimeRole;
use App\Http\Controllers\Controller;
use App\Models\CompanyCrewMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyCrewImportController extends Controller
{
    /**
     * Column synonyms → canonical field
     */
    private const COLUMN_MAP = [
        // full name
        'full_name' => 'full_name',
        'fullname' => 'full_name',
        'name' => 'full_name',
        'seafarer_name' => 'full_name',
        'crew_name' => 'full_name',
        'employee_name' => 'full_name',

        // email
        'email' => 'email',
        'e_mail' => 'email',
        'mail' => 'email',

        // phone
        'phone' => 'phone',
        'mobile' => 'phone',
        'tel' => 'phone',
        'telephone' => 'phone',
        'gsm' => 'phone',

        // role / rank
        'role' => 'role',
        'rank' => 'role',
        'position' => 'role',
        'job_title' => 'role',

        // optional extras (kept in meta)
        'nationality' => 'nationality',
        'country' => 'nationality',
        'passport' => 'passport',
        'passport_no' => 'passport',
        'vessel' => 'vessel',
        'ship' => 'vessel',
    ];

    /**
     * Very tolerant role normalization for maritime.
     * Broader than MaritimeRole::ROLE_ALIASES — covers free-text Excel variations.
     */
    private const ROLE_ALIASES = [
        // Deck
        'captain' => 'captain',
        'master' => 'captain',
        'master mariner' => 'captain',

        'chief officer' => 'chief_officer',
        '1st officer' => 'chief_officer',
        'first officer' => 'chief_officer',

        '2nd officer' => 'second_officer',
        'second officer' => 'second_officer',

        '3rd officer' => 'third_officer',
        'third officer' => 'third_officer',

        'bosun' => 'bosun',
        'boatswain' => 'bosun',

        'ab' => 'able_seaman',
        'able seaman' => 'able_seaman',
        'able-bodied seaman' => 'able_seaman',
        'able bodied seaman' => 'able_seaman',

        'os' => 'ordinary_seaman',
        'ordinary seaman' => 'ordinary_seaman',

        // Engine
        'chief engineer' => 'chief_engineer',
        '1st engineer' => 'chief_engineer',
        'first engineer' => 'chief_engineer',

        '2nd engineer' => 'second_engineer',
        'second engineer' => 'second_engineer',

        '3rd engineer' => 'third_engineer',
        'third engineer' => 'third_engineer',

        'electrician' => 'electrician',
        'eto' => 'electrician',

        'motorman' => 'motorman',
        'oiler' => 'oiler',

        // Galley
        'cook' => 'cook',
        'chef' => 'cook',
        'steward' => 'steward',
        'messman' => 'messman',

        // Cadets
        'deck cadet' => 'deck_cadet',
        'engine cadet' => 'engine_cadet',
    ];

    public function preview(Request $request, string $companyId)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $limit = (int)($request->input('limit', 25));

        [$rows, $result] = $this->parseCsv($request->file('file')->getRealPath(), $limit);

        return response()->json([
            'ok' => true,
            'mapped_headers' => $result['mapped_headers'],
            'ignored_headers' => $result['ignored_headers'],
            'has_certificate_columns' => $result['has_certificate_columns'],
            'preview' => $rows,
            'summary' => [
                'total_previewed' => count($rows),
                'valid' => $result['valid'],
                'errors' => $result['errors_count'],
                'warnings' => $result['warnings_count'],
            ],
            'row_issues' => $result['row_issues'],
        ]);
    }

    public function import(Request $request, string $companyId)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
            'mode' => ['nullable', 'in:append,replace'],
        ]);

        $mode = $request->input('mode', 'append');

        // Parse full file (no preview limit)
        [$rows, $result] = $this->parseCsv($request->file('file')->getRealPath(), null);

        // If replace: delete existing crew for company first (safe: only this company)
        if ($mode === 'replace') {
            CompanyCrewMember::query()->where('company_id', $companyId)->delete();
        }

        $created = 0;
        $skipped = 0;

        foreach ($rows as $r) {
            // Hard skip if no usable identity
            if (empty($r['full_name']) && empty($r['email']) && empty($r['phone'])) {
                $skipped++;
                continue;
            }

            // Upsert identity preference:
            // 1) email, else 2) phone, else 3) full_name (weak)
            $query = CompanyCrewMember::query()->where('company_id', $companyId);

            if (!empty($r['email'])) {
                $query->where('email', $r['email']);
            } elseif (!empty($r['phone'])) {
                $query->where('phone', $r['phone']);
            } else {
                $query->where('full_name', $r['full_name']);
            }

            $existing = $query->first();

            $payload = [
                'company_id' => $companyId,
                'full_name' => $r['full_name'] ?? null,
                'email' => $r['email'] ?? null,
                'phone' => $r['phone'] ?? null,
                'role_code' => $r['role_code'] ?? null,
                'department' => $r['department'] ?? null,
                'meta' => $r['meta'] ?? [],
            ];

            if ($existing) {
                $existing->fill($payload)->save();
            } else {
                CompanyCrewMember::create($payload);
                $created++;
            }
        }

        return response()->json([
            'ok' => true,
            'mode' => $mode,
            'created' => $created,
            'skipped' => $skipped,
            'summary' => [
                'rows' => count($rows),
                'valid' => $result['valid'],
                'errors' => $result['errors_count'],
                'warnings' => $result['warnings_count'],
                'has_certificate_columns' => $result['has_certificate_columns'],
            ],
            'row_issues' => $result['row_issues'],
        ]);
    }

    /**
     * Parse CSV tolerantly:
     * - auto-map known header synonyms
     * - ignore unknown columns
     * - unknown role => warning, do not fail row
     * - missing certificate columns => certificate_status=unknown (warning)
     */
    private function parseCsv(string $path, ?int $limit = null): array
    {
        $fh = fopen($path, 'rb');
        if (!$fh) {
            abort(422, 'Unable to read CSV file.');
        }

        // Read header row
        $rawHeader = fgetcsv($fh);
        if (!$rawHeader || count($rawHeader) < 1) {
            fclose($fh);
            abort(422, 'CSV header row is missing.');
        }

        $normalizedHeader = array_map([$this, 'normalizeHeader'], $rawHeader);

        // Map headers → canonical keys (or null if ignored)
        $mapped = [];
        $ignored = [];
        $certificateColumns = [];

        foreach ($normalizedHeader as $idx => $h) {
            if ($h === '') {
                $mapped[$idx] = null;
                continue;
            }

            // detect certificate-ish columns
            if (str_contains($h, 'cert') || str_contains($h, 'stcw') || str_contains($h, 'certificate')) {
                $certificateColumns[] = $idx;
            }

            $canonical = self::COLUMN_MAP[$h] ?? null;
            if ($canonical) {
                $mapped[$idx] = $canonical;
            } else {
                $mapped[$idx] = null;
                $ignored[] = $rawHeader[$idx] ?? $h;
            }
        }

        $rows = [];
        $rowIssues = [];
        $valid = 0;
        $errorsCount = 0;
        $warningsCount = 0;

        $rowNum = 0;
        while (($data = fgetcsv($fh)) !== false) {
            $rowNum++;
            if ($limit !== null && $rowNum > $limit) {
                break;
            }

            $row = [
                'full_name' => null,
                'email' => null,
                'phone' => null,
                'role_raw' => null,
                'role_code' => null,
                'department' => null,
                'meta' => [],
            ];

            // Build canonical row object + keep extras in meta
            foreach ($data as $idx => $value) {
                $value = is_string($value) ? trim($value) : $value;

                $key = $mapped[$idx] ?? null;
                if (!$key) {
                    continue; // ignore unknown column
                }

                if (in_array($key, ['nationality', 'passport', 'vessel'], true)) {
                    if ($value !== '' && $value !== null) {
                        $row['meta'][$key] = $value;
                    }
                    continue;
                }

                if ($key === 'role') {
                    $row['role_raw'] = (string)$value;
                    continue;
                }

                if ($value === '') {
                    continue;
                }

                $row[$key] = (string)$value;
            }

            // Role normalization (warning only if unknown)
            if (!empty($row['role_raw'])) {
                [$roleCode, $dept, $roleWarn] = $this->normalizeRoleAndDept($row['role_raw']);
                $row['role_code'] = $roleCode;
                $row['department'] = $dept;
                if ($roleWarn) {
                    $rowIssues[$rowNum]['warnings'][] = $roleWarn;
                    $warningsCount++;
                }
            } else {
                // role missing is OK for import (company may upload partial list)
                $rowIssues[$rowNum]['warnings'][] = 'Role/rank is missing; row imported without role_code.';
                $warningsCount++;
            }

            // Certificate tolerance
            $hasCertCols = count($certificateColumns) > 0;
            if (!$hasCertCols) {
                $row['meta']['certificate_status'] = 'unknown';
            } else {
                $row['meta']['certificate_status'] = 'provided_or_partial';
            }

            // Soft validation: never fail entire file
            $v = Validator::make($row, [
                'full_name' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email:rfc', 'max:255'],
                'phone' => ['nullable', 'string', 'max:64'],
                'role_code' => ['nullable', 'string', 'max:64'],
                'department' => ['nullable', 'in:' . implode(',', MaritimeRole::DEPARTMENTS)],
                'meta' => ['array'],
            ]);

            if ($v->fails()) {
                $rowIssues[$rowNum]['errors'] = $v->errors()->all();
                $errorsCount += count($v->errors()->all());
            } else {
                $valid++;
            }

            $rows[] = $row;
        }

        fclose($fh);

        return [
            $rows,
            [
                'mapped_headers' => $this->mappedHeadersSummary($rawHeader, $mapped),
                'ignored_headers' => array_values(array_unique(array_filter($ignored))),
                'has_certificate_columns' => count($certificateColumns) > 0,
                'valid' => $valid,
                'errors_count' => $errorsCount,
                'warnings_count' => $warningsCount,
                'row_issues' => $rowIssues,
            ],
        ];
    }

    private function normalizeHeader(string $h): string
    {
        $h = trim($h);
        $h = mb_strtolower($h);
        $h = str_replace(['-', '.', '/', '\\'], '_', $h);
        $h = preg_replace('/\s+/', '_', $h);
        $h = preg_replace('/_+/', '_', $h);
        return trim($h, '_');
    }

    private function normalizeRoleAndDept(string $roleRaw): array
    {
        $key = mb_strtolower(trim($roleRaw));
        $key = preg_replace('/\s+/', ' ', $key);
        $key = str_replace(['-', '_'], ' ', $key);
        $key = trim($key);

        $roleCode = self::ROLE_ALIASES[$key] ?? null;

        // Also try some basic cleanup (e.g., "2/O", "2O", "2nd off")
        if (!$roleCode) {
            $key2 = str_replace(['/', '.'], ' ', $key);
            $key2 = preg_replace('/\s+/', ' ', $key2);
            $roleCode = self::ROLE_ALIASES[$key2] ?? null;
        }

        // Fallback: try MaritimeRole::normalize for underscore-style codes
        if (!$roleCode) {
            $roleCode = MaritimeRole::normalize($roleRaw);
        }

        $dept = $roleCode ? MaritimeRole::departmentFor($roleCode) : null;

        if (!$roleCode) {
            return [null, null, "Unknown role/rank '{$roleRaw}' — row will be imported but flagged."];
        }

        return [$roleCode, $dept, null];
    }

    private function mappedHeadersSummary(array $rawHeader, array $mapped): array
    {
        $out = [];
        foreach ($mapped as $idx => $canonical) {
            if ($canonical) {
                $out[] = [
                    'column' => $rawHeader[$idx] ?? (string)$idx,
                    'maps_to' => $canonical,
                ];
            }
        }
        return $out;
    }
}
