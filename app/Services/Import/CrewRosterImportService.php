<?php

namespace App\Services\Import;

use App\Models\CompanyCrewMember;
use App\Models\CrewMemberCertificate;
use App\Models\ImportRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CrewRosterImportService
{
    private const HEADER_ROW  = 2;
    private const DATA_START  = 3;
    private const SHEET_NAME  = 'application form';

    /**
     * Preview: parse first N rows without writing to DB.
     *
     * @return array{headers: array, rows: array, total_rows: int, warnings: string[]}
     */
    public function preview(string $filePath, int $limit = 10): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $this->resolveSheet($spreadsheet);

        $headers  = $this->readHeaders($sheet);
        $allRows  = $this->readDataRows($sheet);
        $warnings = $this->detectWarnings($allRows);

        $previewRows = array_slice($allRows, 0, $limit);

        // Map preview rows to friendly format
        $mapped = array_map(function (array $row) {
            $crew = $this->extractCrewFields($row);
            $certs = $this->extractCertificateSummary($row);
            return array_merge($crew, ['certificates_found' => count($certs)]);
        }, $previewRows);

        return [
            'headers'    => $headers,
            'rows'       => $mapped,
            'total_rows' => count($allRows),
            'warnings'   => $warnings,
        ];
    }

    /**
     * Full import: parse entire sheet, match/create crew members, upsert certs.
     */
    public function import(string $filePath, string $userId, string $companyId, string $filename): ImportRun
    {
        $run = ImportRun::create([
            'user_id'    => $userId,
            'company_id' => $companyId,
            'type'       => 'crew_roster',
            'filename'   => $filename,
            'status'     => ImportRun::STATUS_PROCESSING,
        ]);

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet       = $this->resolveSheet($spreadsheet);
            $rows        = $this->readDataRows($sheet);

            $run->total_rows = count($rows);
            $rowIssues       = [];
            $created         = 0;
            $updated         = 0;
            $skipped         = 0;
            $errors          = 0;

            foreach ($rows as $idx => $row) {
                $rowNum = $idx + self::DATA_START; // human-readable row number
                try {
                    $result = $this->processRow($row, $companyId, $run->id);

                    match ($result['action']) {
                        'created' => $created++,
                        'updated' => $updated++,
                        'skipped' => $skipped++,
                        default   => null,
                    };

                    if (!empty($result['warnings'])) {
                        $rowIssues[] = [
                            'row'      => $rowNum,
                            'level'    => 'warning',
                            'messages' => $result['warnings'],
                        ];
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $rowIssues[] = [
                        'row'      => $rowNum,
                        'level'    => 'error',
                        'messages' => [$e->getMessage()],
                    ];
                    Log::warning('CrewRosterImport: row error', [
                        'run_id' => $run->id, 'row' => $rowNum, 'error' => $e->getMessage(),
                    ]);
                }
            }

            $run->update([
                'status'        => ImportRun::STATUS_COMPLETED,
                'created_count' => $created,
                'updated_count' => $updated,
                'skipped_count' => $skipped,
                'error_count'   => $errors,
                'row_issues'    => !empty($rowIssues) ? $rowIssues : null,
                'summary'       => [
                    'total_certificates_upserted' => CrewMemberCertificate::where(
                        'crew_member_id', '>', 0
                    )->count(), // approximate — fine for summary
                ],
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status'     => ImportRun::STATUS_FAILED,
                'row_issues' => [['row' => 0, 'level' => 'error', 'messages' => [$e->getMessage()]]],
            ]);
            Log::error('CrewRosterImport: fatal error', [
                'run_id' => $run->id, 'error' => $e->getMessage(),
            ]);
        }

        return $run->fresh();
    }

    // ───────────────────────── Row processing ─────────────────────────

    /**
     * @return array{action: string, crew_member_id: int|null, warnings: string[]}
     */
    private function processRow(array $row, string $companyId, string $runId): array
    {
        $crew     = $this->extractCrewFields($row);
        $warnings = [];

        // Skip rows with no name
        if (empty(trim($crew['full_name'] ?? ''))) {
            return ['action' => 'skipped', 'crew_member_id' => null, 'warnings' => ['Empty name — row skipped']];
        }

        // Match existing crew member
        $existing = $this->matchCrewMember($crew, $companyId);
        $action   = $existing ? 'updated' : 'created';

        $memberData = [
            'company_id'       => $companyId,
            'full_name'        => trim($crew['full_name']),
            'nationality'      => $this->normalizeCountryCode($crew['nationality'] ?? null),
            'passport_no'      => $this->clean($crew['passport_no'] ?? null),
            'seamans_book_no'  => $this->clean($crew['seamans_book_no'] ?? null),
            'date_of_birth'    => $this->parseDateValue($crew['date_of_birth'] ?? null),
            'vessel_name'      => $this->clean($crew['vessel_name'] ?? null),
            'vessel_country'   => $this->normalizeCountryCode($crew['vessel_country'] ?? null),
            'contract_start_at'=> $this->parseDateValue($crew['contract_start_at'] ?? null),
            'contract_end_at'  => $this->parseDateValue($crew['contract_end_at'] ?? null),
            'rank_raw'         => $this->clean($crew['rank_raw'] ?? null),
            'role_code'        => $this->inferRoleCode($crew['rank_raw'] ?? null),
            'import_run_id'    => $runId,
        ];

        // Endorsement text in meta
        $endorsementText = $this->clean($row[ExcelCertificateMapper::ENDORSEMENT_TEXT_COL] ?? null);
        if ($endorsementText) {
            $existingMeta = $existing?->meta ?? [];
            $memberData['meta'] = array_merge($existingMeta, ['endorsement_text' => $endorsementText]);
        }

        if ($existing) {
            $existing->update($memberData);
            $member = $existing;
        } else {
            $member = CompanyCrewMember::create($memberData);
        }

        // Upsert certificates
        $certWarnings = $this->upsertCertificates($member, $row);
        $warnings     = array_merge($warnings, $certWarnings);

        return ['action' => $action, 'crew_member_id' => $member->id, 'warnings' => $warnings];
    }

    // ───────────────────────── Matching ─────────────────────────

    private function matchCrewMember(array $crew, string $companyId): ?CompanyCrewMember
    {
        $passport = $this->clean($crew['passport_no'] ?? null);
        if ($passport) {
            $match = CompanyCrewMember::where('company_id', $companyId)
                ->where('passport_no', $passport)
                ->first();
            if ($match) return $match;
        }

        $seamans = $this->clean($crew['seamans_book_no'] ?? null);
        $dob     = $this->parseDateValue($crew['date_of_birth'] ?? null);
        if ($seamans && $dob) {
            $match = CompanyCrewMember::where('company_id', $companyId)
                ->where('seamans_book_no', $seamans)
                ->whereDate('date_of_birth', $dob)
                ->first();
            if ($match) return $match;
        }

        $name = trim($crew['full_name'] ?? '');
        if ($name && $dob) {
            $match = CompanyCrewMember::where('company_id', $companyId)
                ->where('full_name', $name)
                ->whereDate('date_of_birth', $dob)
                ->first();
            if ($match) return $match;
        }

        return null;
    }

    // ───────────────────────── Certificate upsert ─────────────────────────

    /**
     * @return string[] warnings
     */
    private function upsertCertificates(CompanyCrewMember $member, array $row): array
    {
        $warnings = [];

        // Standard + flag endorsement certificates
        foreach (ExcelCertificateMapper::COLUMN_MAP as $colIdx => $def) {
            [$certType, $issuingCountry] = $def;
            $rawValue = $row[$colIdx] ?? null;
            $date     = $this->parseDateValue($rawValue);

            if ($date === null && $rawValue === null) {
                continue; // empty cell
            }

            $matchKey = ['crew_member_id' => $member->id, 'certificate_type' => $certType];
            if ($issuingCountry) {
                $matchKey['issuing_country'] = $issuingCountry;
            }

            $values = [
                'expires_at'     => $date,
                'expiry_source'  => 'uploaded',
                'issuing_country'=> $issuingCountry,
            ];

            // For non-flag certs without issuing_country in the unique key,
            // we need to handle the unique constraint properly
            if (!$issuingCountry) {
                $matchKey['issuing_country'] = null;
            }

            CrewMemberCertificate::updateOrCreate($matchKey, $values);

            if ($rawValue !== null && $date === null) {
                $warnings[] = "Column {$colIdx} ({$def[2]}): could not parse date from '{$rawValue}'";
            }
        }

        // COC certificate pairs
        foreach (ExcelCertificateMapper::COC_COLUMN_PAIRS as [$nameCol, $dateCol]) {
            $codeName = $this->clean($row[$nameCol] ?? null);
            $rawDate  = $row[$dateCol] ?? null;
            $date     = $this->parseDateValue($rawDate);

            if (!$codeName && !$date) {
                continue;
            }

            $certCode = $codeName ?: 'COC';

            CrewMemberCertificate::updateOrCreate(
                [
                    'crew_member_id'   => $member->id,
                    'certificate_type' => 'COC',
                    'certificate_code' => $certCode,
                ],
                [
                    'expires_at'       => $date,
                    'expiry_source'    => 'uploaded',
                    'issuing_country'  => null,
                ]
            );
        }

        return $warnings;
    }

    // ───────────────────────── Sheet parsing ─────────────────────────

    private function resolveSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): Worksheet
    {
        // Try exact match first
        $sheet = $spreadsheet->getSheetByName(self::SHEET_NAME);
        if ($sheet) return $sheet;

        // Case-insensitive search
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (mb_strtolower(trim($name)) === self::SHEET_NAME) {
                return $spreadsheet->getSheetByName($name);
            }
        }

        // Fallback: first sheet
        return $spreadsheet->getActiveSheet();
    }

    private function readHeaders(Worksheet $sheet): array
    {
        $headers = [];
        $highCol = $sheet->getHighestColumn();
        $highColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highCol);

        for ($col = 1; $col <= $highColIdx; $col++) {
            $value = $sheet->getCell([$col, self::HEADER_ROW])->getValue();
            $headers[$col - 1] = $value ? trim((string) $value) : null;
        }

        return $headers;
    }

    private function readDataRows(Worksheet $sheet): array
    {
        $rows      = [];
        $highRow   = $sheet->getHighestRow();
        $highCol   = $sheet->getHighestColumn();
        $highColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highCol);

        for ($rowNum = self::DATA_START; $rowNum <= $highRow; $rowNum++) {
            $row     = [];
            $isEmpty = true;

            for ($col = 1; $col <= $highColIdx; $col++) {
                $cell  = $sheet->getCell([$col, $rowNum]);
                $value = $cell->getValue();

                // Preserve raw value (could be Excel serial date or string)
                $row[$col - 1] = $value;
                if ($value !== null && trim((string) $value) !== '') {
                    $isEmpty = false;
                }
            }

            if (!$isEmpty) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    // ───────────────────────── Field extraction ─────────────────────────

    private function extractCrewFields(array $row): array
    {
        $fields = [];
        foreach (ExcelCertificateMapper::CREW_COLUMNS as $field => $colIdx) {
            $fields[$field] = $row[$colIdx] ?? null;
        }
        return $fields;
    }

    private function extractCertificateSummary(array $row): array
    {
        $certs = [];

        foreach (ExcelCertificateMapper::COLUMN_MAP as $colIdx => $def) {
            if (!empty($row[$colIdx])) {
                $certs[] = $def[0];
            }
        }

        foreach (ExcelCertificateMapper::COC_COLUMN_PAIRS as [$nameCol, $dateCol]) {
            if (!empty($row[$nameCol]) || !empty($row[$dateCol])) {
                $certs[] = 'COC';
            }
        }

        return $certs;
    }

    // ───────────────────────── Date parsing ─────────────────────────

    private function parseDateValue(mixed $value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        // Excel serial number (numeric)
        if (is_numeric($value) && (float) $value > 10000 && (float) $value < 100000) {
            try {
                $dateTime = ExcelDate::excelToDateTimeObject((float) $value);
                return $dateTime->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        // String date formats
        if (is_string($value)) {
            $value = trim($value);

            // Try standard formats
            $formats = ['Y-m-d', 'd.m.Y', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd-m-Y'];
            foreach ($formats as $fmt) {
                $dt = \DateTime::createFromFormat($fmt, $value);
                if ($dt && $dt->format($fmt) === $value) {
                    return $dt->format('Y-m-d');
                }
            }

            // strtotime fallback
            $ts = strtotime($value);
            if ($ts !== false && $ts > 0) {
                return date('Y-m-d', $ts);
            }
        }

        return null;
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function clean(?string $value): ?string
    {
        if ($value === null) return null;
        $v = trim((string) $value);
        return $v === '' ? null : $v;
    }

    private function normalizeCountryCode(?string $value): ?string
    {
        if ($value === null) return null;
        $v = strtoupper(trim((string) $value));
        return strlen($v) >= 2 ? substr($v, 0, 2) : null;
    }

    private function inferRoleCode(?string $rankRaw): ?string
    {
        if (!$rankRaw) return null;

        $raw = mb_strtolower(trim($rankRaw));

        $map = [
            'master'           => 'master',
            'captain'          => 'master',
            'chief officer'    => 'chief_officer',
            'chief mate'       => 'chief_officer',
            '2nd officer'      => 'second_officer',
            'second officer'   => 'second_officer',
            '3rd officer'      => 'third_officer',
            'third officer'    => 'third_officer',
            'chief engineer'   => 'chief_engineer',
            '2nd engineer'     => 'second_engineer',
            'second engineer'  => 'second_engineer',
            '3rd engineer'     => 'third_engineer',
            'third engineer'   => 'third_engineer',
            'bosun'            => 'bosun',
            'boatswain'        => 'bosun',
            'ab'               => 'able_seaman',
            'able seaman'      => 'able_seaman',
            'os'               => 'ordinary_seaman',
            'ordinary seaman'  => 'ordinary_seaman',
            'cook'             => 'cook',
            'chief cook'       => 'cook',
            'steward'          => 'steward',
            'messman'          => 'messman',
            'oiler'            => 'oiler',
            'wiper'            => 'wiper',
            'fitter'           => 'fitter',
            'electrician'      => 'electrician',
            'eto'              => 'electro_technical_officer',
            'pumpman'          => 'pumpman',
            'cadet'            => 'cadet',
            'deck cadet'       => 'deck_cadet',
            'engine cadet'     => 'engine_cadet',
        ];

        foreach ($map as $keyword => $code) {
            if (str_contains($raw, $keyword)) {
                return $code;
            }
        }

        return null;
    }

    private function detectWarnings(array $rows): array
    {
        $warnings = [];

        $noName = 0;
        $noPassport = 0;
        foreach ($rows as $row) {
            $name     = trim((string) ($row[ExcelCertificateMapper::CREW_COLUMNS['full_name']] ?? ''));
            $passport = trim((string) ($row[ExcelCertificateMapper::CREW_COLUMNS['passport_no']] ?? ''));
            if ($name === '') $noName++;
            if ($passport === '') $noPassport++;
        }

        if ($noName > 0) {
            $warnings[] = "{$noName} row(s) have no name and will be skipped.";
        }
        if ($noPassport > 0) {
            $warnings[] = "{$noPassport} row(s) have no passport number — matching will fall back to name+DOB.";
        }

        return $warnings;
    }
}
