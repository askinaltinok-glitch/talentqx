<?php

namespace Tests\Feature;

use App\Models\CompanyCrewMember;
use App\Models\CrewMemberCertificate;
use App\Services\Import\CrewRosterImportService;
use App\Services\Import\ExcelCertificateMapper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CrewRosterImportTest extends TestCase
{
    use DatabaseTransactions;

    private string $companyId = '00000000-0000-0000-0000-000000000001';
    private string $userId    = '00000000-0000-0000-0000-000000000099';

    private function buildTestXlsx(array $dataRows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('application form');

        // Row 2: column headers (1-based Excel columns)
        $headers = [
            1 => 'No',          2 => 'Name Surname',  3 => 'Rank',
            4 => 'Vessel',      5 => 'Flag',           6 => 'Contract Start',
            7 => 'Contract End', 8 => 'Nationality',   9 => 'DOB',
            10 => 'Passport No', 11 => 'Passport Expired Date', 12 => 'Phone',
            13 => 'Seaman Book No', 14 => 'Seaman Book Expired', 15 => 'Medical Certificate',
            16 => 'GMDSS', 17 => 'BST', 18 => 'PSCRB', 19 => 'AFF', 20 => 'ARPA',
            21 => 'SAT', 22 => 'STCW SH', 23 => 'STCW SG', 24 => 'First Aid',
            25 => 'Medical Care', 26 => 'ECDIS', 27 => 'BRM', 28 => 'Leadership',
            29 => 'ISM Code', 30 => 'Ship Handling',
            31 => 'COC 1 Name', 32 => 'COC 1 Expiry',
            33 => 'COC 2 Name', 34 => 'COC 2 Expiry',
            35 => 'COC 3 Name', 36 => 'COC 3 Expiry',
            37 => 'COC 4 Name', 38 => 'COC 4 Expiry',
            39 => 'Endorsement Text',
            40 => 'Panama Flag', 41 => 'Liberia Flag', 42 => 'Tanzania Flag',
            43 => 'Sierra Leone Flag', 44 => 'Palau Flag',
            45 => 'Coverall Size', 46 => 'Shoe Size',
            47 => 'Emergency Contact', 48 => 'Emergency Phone',
        ];

        foreach ($headers as $col => $label) {
            $sheet->getCell([$col, 2])->setValue($label);
        }

        foreach ($dataRows as $rowIdx => $rowData) {
            $excelRow = $rowIdx + 3;
            foreach ($rowData as $col => $value) {
                $sheet->getCell([$col, $excelRow])->setValue($value);
            }
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'crew_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        return $tmpFile;
    }

    public function test_preview_returns_correct_structure(): void
    {
        $row = [
            2 => 'Ali Yildirim',
            3 => 'Captain',
            4 => 'MV Test Vessel',
            5 => 'TR',
            6 => '2026-01-15',
            7 => '2027-01-15',
            8 => 'TR',
            9 => '1985-03-20',
            10 => 'TR12345678',
            11 => '2028-06-15',
            13 => 'SB-001234',
            16 => '2027-05-10',
            17 => '2026-11-20',
        ];

        $xlsxPath = $this->buildTestXlsx([$row]);

        $service = new CrewRosterImportService();
        $preview = $service->preview($xlsxPath, 10);

        $this->assertArrayHasKey('headers', $preview);
        $this->assertArrayHasKey('rows', $preview);
        $this->assertArrayHasKey('total_rows', $preview);
        $this->assertArrayHasKey('warnings', $preview);

        $this->assertEquals(1, $preview['total_rows']);
        $this->assertCount(1, $preview['rows']);
        $this->assertEquals('Ali Yildirim', $preview['rows'][0]['full_name']);
        $this->assertEquals('Captain', $preview['rows'][0]['rank_raw']);
        $this->assertGreaterThan(0, $preview['rows'][0]['certificates_found']);

        @unlink($xlsxPath);
    }

    public function test_upserts_crew_member_by_passport_no(): void
    {
        // Pre-create a crew member with same passport
        CompanyCrewMember::create([
            'company_id'  => $this->companyId,
            'full_name'   => 'Ali Yildirim',
            'passport_no' => 'TR12345678',
            'role_code'   => 'master',
        ]);

        $row = [
            2 => 'Ali Yildirim',
            3 => 'Master',
            4 => 'MV Updated Vessel',
            5 => 'PA',
            10 => 'TR12345678',
            11 => '2028-06-15',
        ];

        $xlsxPath = $this->buildTestXlsx([$row]);

        $service = new CrewRosterImportService();
        $run = $service->import($xlsxPath, $this->userId, $this->companyId, 'test.xlsx');

        $this->assertEquals('completed', $run->status);
        $this->assertEquals(1, $run->updated_count);
        $this->assertEquals(0, $run->created_count);

        // Verify no duplicate
        $this->assertEquals(1, CompanyCrewMember::where('company_id', $this->companyId)->count());

        $member = CompanyCrewMember::where('passport_no', 'TR12345678')->first();
        $this->assertEquals('MV Updated Vessel', $member->vessel_name);
        $this->assertEquals('PA', $member->vessel_country);

        @unlink($xlsxPath);
    }

    public function test_creates_certificates_with_correct_types(): void
    {
        $row = [
            2 => 'Mehmet Demir',
            3 => 'Chief Officer',
            10 => 'TR99887766',
            11 => '2029-01-01',    // PASSPORT
            14 => '2027-03-15',    // SEAMANS_BOOK
            15 => '2027-08-20',    // MEDICAL_FITNESS
            16 => '2028-02-10',    // GMDSS
            17 => '2027-06-01',    // BST
            26 => '2028-11-30',    // ECDIS
        ];

        $xlsxPath = $this->buildTestXlsx([$row]);

        $service = new CrewRosterImportService();
        $run = $service->import($xlsxPath, $this->userId, $this->companyId, 'test.xlsx');

        $this->assertEquals('completed', $run->status);
        $this->assertEquals(1, $run->created_count);

        $member = CompanyCrewMember::where('passport_no', 'TR99887766')->first();
        $this->assertNotNull($member);

        $certs = $member->certificates()->pluck('certificate_type')->toArray();
        $this->assertContains('PASSPORT', $certs);
        $this->assertContains('SEAMANS_BOOK', $certs);
        $this->assertContains('MEDICAL_FITNESS', $certs);
        $this->assertContains('GMDSS', $certs);
        $this->assertContains('BST', $certs);
        $this->assertContains('ECDIS', $certs);

        // Verify expiry date
        $passport = $member->certificates()->where('certificate_type', 'PASSPORT')->first();
        $this->assertEquals('2029-01-01', $passport->expires_at->toDateString());
        $this->assertEquals('uploaded', $passport->expiry_source);

        @unlink($xlsxPath);
    }

    public function test_maps_flag_endorsements_with_issuing_country(): void
    {
        $row = [
            2 => 'Ahmet Kaya',
            3 => 'Second Officer',
            10 => 'TR55443322',
            40 => '2027-09-15',   // Panama flag
            41 => '2028-04-01',   // Liberia flag
        ];

        $xlsxPath = $this->buildTestXlsx([$row]);

        $service = new CrewRosterImportService();
        $run = $service->import($xlsxPath, $this->userId, $this->companyId, 'test.xlsx');

        $this->assertEquals('completed', $run->status);
        $this->assertEquals(1, $run->created_count);

        $member = CompanyCrewMember::where('passport_no', 'TR55443322')->first();
        $this->assertNotNull($member);

        // Panama flag endorsement
        $panama = $member->certificates()
            ->where('certificate_type', 'FLAG_ENDORSEMENT')
            ->where('issuing_country', 'PA')
            ->first();
        $this->assertNotNull($panama, 'Panama flag endorsement should exist');
        $this->assertEquals('2027-09-15', $panama->expires_at->toDateString());

        // Liberia flag endorsement
        $liberia = $member->certificates()
            ->where('certificate_type', 'FLAG_ENDORSEMENT')
            ->where('issuing_country', 'LR')
            ->first();
        $this->assertNotNull($liberia, 'Liberia flag endorsement should exist');
        $this->assertEquals('2028-04-01', $liberia->expires_at->toDateString());

        @unlink($xlsxPath);
    }
}
