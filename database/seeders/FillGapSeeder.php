<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fills all empty subdomains with positions and interview questions.
 * Safe to run multiple times — skips positions that already exist.
 */
class FillGapSeeder extends Seeder
{
    public function run(): void
    {
        $competencies = DB::table('competencies')->pluck('id', 'code')->toArray();
        $subdomains   = DB::table('job_subdomains')->pluck('id', 'code')->toArray();

        // ── 1. Position Definitions ─────────────────────────────────
        $positions = [
            // IT_INFRA
            ['IT_INFRA_SYSADMIN',    'IT_INFRA',     'Sistem Yöneticisi',                  'System Administrator',          1, 5, 'bachelor'],
            ['IT_INFRA_NETWORK_ENG', 'IT_INFRA',     'Ağ Mühendisi',                       'Network Engineer',              2, 7, 'bachelor'],
            // IT_SECURITY
            ['IT_SEC_ANALYST',       'IT_SECURITY',  'Güvenlik Analisti',                   'Security Analyst',              1, 4, 'bachelor'],
            ['IT_SEC_ENGINEER',      'IT_SECURITY',  'Güvenlik Mühendisi',                  'Security Engineer',             3, 8, 'bachelor'],
            // IT_PRODUCT
            ['IT_PROD_MANAGER',      'IT_PRODUCT',   'Ürün Yöneticisi',                     'Product Manager',               3, 8, 'bachelor'],
            ['IT_PROD_ANALYST',      'IT_PRODUCT',   'Ürün Analisti',                       'Product Analyst',               1, 4, 'bachelor'],
            // HC_MEDICAL
            ['HC_MED_ASSISTANT',     'HC_MEDICAL',   'Tıbbi Sekreter',                      'Medical Secretary',             0, 3, 'associate'],
            ['HC_MED_TECHNICIAN',    'HC_MEDICAL',   'Sağlık Teknikeri',                    'Medical Technician',            1, 5, 'associate'],
            // HC_ADMIN
            ['HC_ADM_OFFICER',       'HC_ADMIN',     'Sağlık İdari Memuru',                 'Healthcare Admin Officer',      0, 3, 'associate'],
            ['HC_ADM_MANAGER',       'HC_ADMIN',     'Sağlık İdari Yöneticisi',             'Healthcare Admin Manager',      3, 8, 'bachelor'],
            // HC_LAB
            ['HC_LAB_TECH',          'HC_LAB',       'Laboratuvar Teknisyeni',              'Lab Technician',                0, 3, 'associate'],
            ['HC_LAB_SPECIALIST',    'HC_LAB',       'Laboratuvar Uzmanı',                  'Lab Specialist',                2, 7, 'bachelor'],
            // FIN_INSURANCE
            ['FIN_INS_AGENT',        'FIN_INSURANCE','Sigorta Danışmanı',                   'Insurance Agent',               0, 4, 'bachelor'],
            ['FIN_INS_CLAIMS',       'FIN_INSURANCE','Hasar Uzmanı',                        'Claims Specialist',             1, 5, 'bachelor'],
            // FIN_INVEST
            ['FIN_INV_ANALYST',      'FIN_INVEST',   'Yatırım Analisti',                    'Investment Analyst',            1, 5, 'bachelor'],
            ['FIN_INV_ADVISOR',      'FIN_INVEST',   'Yatırım Danışmanı',                   'Investment Advisor',            3, 8, 'bachelor'],
            // FIN_AUDIT
            ['FIN_AUD_AUDITOR',      'FIN_AUDIT',    'Denetçi',                             'Auditor',                       1, 5, 'bachelor'],
            ['FIN_AUD_MANAGER',      'FIN_AUDIT',    'Denetim Müdürü',                      'Audit Manager',                 5,12, 'bachelor'],
            // EDU_HIGHER
            ['EDU_HI_LECTURER',      'EDU_HIGHER',   'Öğretim Görevlisi',                   'Lecturer',                      2, 8, 'master'],
            ['EDU_HI_RESEARCHER',    'EDU_HIGHER',   'Araştırma Görevlisi',                 'Research Assistant',             0, 3, 'master'],
            // EDU_SPECIAL
            ['EDU_SP_TEACHER',       'EDU_SPECIAL',  'Özel Eğitim Öğretmeni',               'Special Education Teacher',     1, 5, 'bachelor'],
            ['EDU_SP_THERAPIST',     'EDU_SPECIAL',  'Rehabilitasyon Uzmanı',                'Rehabilitation Therapist',      1, 5, 'bachelor'],
            // EDU_TRAINING
            ['EDU_TR_SPECIALIST',    'EDU_TRAINING', 'Kurumsal Eğitim Uzmanı',              'Corporate Training Specialist', 2, 6, 'bachelor'],
            ['EDU_TR_MANAGER',       'EDU_TRAINING', 'Eğitim Müdürü',                       'Training Manager',              5,10, 'bachelor'],
            // MFG_MAINTENANCE
            ['MFG_MAINT_TECH',       'MFG_MAINTENANCE','Bakım Teknisyeni',                  'Maintenance Technician',        1, 5, 'vocational'],
            ['MFG_MAINT_SUPERVISOR', 'MFG_MAINTENANCE','Bakım Amiri',                       'Maintenance Supervisor',        3, 8, 'associate'],
            // MFG_PLANNING
            ['MFG_PLAN_PLANNER',     'MFG_PLANNING', 'Üretim Planlama Uzmanı',              'Production Planner',            1, 5, 'bachelor'],
            ['MFG_PLAN_MANAGER',     'MFG_PLANNING', 'Üretim Planlama Müdürü',              'Production Planning Manager',   5,10, 'bachelor'],
            // MFG_SAFETY
            ['MFG_SAFE_OFFICER',     'MFG_SAFETY',   'İş Güvenliği Uzmanı',                 'Occupational Safety Officer',   1, 5, 'bachelor'],
            ['MFG_SAFE_MANAGER',     'MFG_SAFETY',   'İş Güvenliği Müdürü',                 'Safety Manager',                5,10, 'bachelor'],
            // LOG_SUPPLY
            ['LOG_SUP_BUYER',        'LOG_SUPPLY',   'Satın Alma Uzmanı',                   'Procurement Specialist',        1, 5, 'bachelor'],
            ['LOG_SUP_MANAGER',      'LOG_SUPPLY',   'Tedarik Zinciri Yöneticisi',          'Supply Chain Manager',          5,10, 'bachelor'],
            // LOG_CUSTOMS
            ['LOG_CUS_SPECIALIST',   'LOG_CUSTOMS',  'Gümrük Müşavir Yardımcısı',           'Customs Specialist',            1, 5, 'bachelor'],
            ['LOG_CUS_BROKER',       'LOG_CUSTOMS',  'Gümrük Müşaviri',                     'Customs Broker',                5,10, 'bachelor'],
            // LOG_FLEET
            ['LOG_FLT_COORDINATOR',  'LOG_FLEET',    'Filo Koordinatörü',                   'Fleet Coordinator',             1, 5, 'associate'],
            ['LOG_FLT_MANAGER',      'LOG_FLEET',    'Filo Yöneticisi',                     'Fleet Manager',                 3, 8, 'bachelor'],
            // CON_ARCH
            ['CON_ARCH_ARCHITECT',   'CON_ARCH',     'Mimar',                               'Architect',                     1, 5, 'bachelor'],
            ['CON_ARCH_SENIOR',      'CON_ARCH',     'Kıdemli Mimar',                       'Senior Architect',              5,12, 'bachelor'],
            // CON_CIVIL
            ['CON_CIV_ENGINEER',     'CON_CIVIL',    'İnşaat Mühendisi',                    'Civil Engineer',                1, 5, 'bachelor'],
            ['CON_CIV_SENIOR',       'CON_CIVIL',    'Kıdemli İnşaat Mühendisi',            'Senior Civil Engineer',         5,12, 'bachelor'],
            // CON_PROPERTY
            ['CON_PROP_MANAGER',     'CON_PROPERTY', 'Mülk Yöneticisi',                     'Property Manager',              2, 8, 'bachelor'],
            ['CON_PROP_SPECIALIST',  'CON_PROPERTY', 'Mülk Değerleme Uzmanı',               'Property Valuation Specialist', 1, 5, 'bachelor'],
            // MKT_BRAND
            ['MKT_BRN_SPECIALIST',   'MKT_BRAND',   'Marka Uzmanı',                         'Brand Specialist',              1, 5, 'bachelor'],
            ['MKT_BRN_MANAGER',      'MKT_BRAND',   'Marka Yöneticisi',                     'Brand Manager',                 3, 8, 'bachelor'],
            // MKT_PR
            ['MKT_PR_SPECIALIST',    'MKT_PR',      'Halkla İlişkiler Uzmanı',              'PR Specialist',                 1, 5, 'bachelor'],
            ['MKT_PR_MANAGER',       'MKT_PR',      'Halkla İlişkiler Müdürü',              'PR Manager',                    5,10, 'bachelor'],
            // MKT_SOCIAL
            ['MKT_SOC_SPECIALIST',   'MKT_SOCIAL',  'Sosyal Medya Uzmanı',                  'Social Media Specialist',       0, 4, 'bachelor'],
            ['MKT_SOC_MANAGER',      'MKT_SOCIAL',  'Sosyal Medya Yöneticisi',              'Social Media Manager',          3, 7, 'bachelor'],
            // HR_PAYROLL
            ['HR_PAY_SPECIALIST',    'HR_PAYROLL',   'Bordro Uzmanı',                        'Payroll Specialist',            1, 5, 'bachelor'],
            ['HR_PAY_MANAGER',       'HR_PAYROLL',   'Bordro Müdürü',                        'Payroll Manager',               5,10, 'bachelor'],
            // HR_RELATIONS
            ['HR_REL_SPECIALIST',    'HR_RELATIONS', 'Çalışan İlişkileri Uzmanı',            'Employee Relations Specialist', 1, 5, 'bachelor'],
            ['HR_REL_MANAGER',       'HR_RELATIONS', 'Çalışan İlişkileri Müdürü',            'Employee Relations Manager',   5,10, 'bachelor'],
            // HR_COMP
            ['HR_CMP_ANALYST',       'HR_COMP',      'Ücret ve Yan Haklar Analisti',         'Compensation Analyst',          1, 5, 'bachelor'],
            ['HR_CMP_MANAGER',       'HR_COMP',      'Ücret ve Yan Haklar Müdürü',           'Compensation Manager',          5,10, 'bachelor'],
            // LEG_CONTRACT
            ['LEG_CON_SPECIALIST',   'LEG_CONTRACT', 'Sözleşme Uzmanı',                     'Contract Specialist',           1, 5, 'bachelor'],
            ['LEG_CON_MANAGER',      'LEG_CONTRACT', 'Sözleşme Yöneticisi',                 'Contract Manager',              5,10, 'bachelor'],
            // LEG_LITIGATION
            ['LEG_LIT_LAWYER',       'LEG_LITIGATION','Dava Avukatı',                        'Litigation Lawyer',             1, 5, 'bachelor'],
            ['LEG_LIT_SENIOR',       'LEG_LITIGATION','Kıdemli Dava Avukatı',                'Senior Litigation Lawyer',      5,12, 'bachelor'],
            // LEG_COMPLIANCE
            ['LEG_CMP_OFFICER',      'LEG_COMPLIANCE','Uyum Sorumlusu',                     'Compliance Officer',            1, 5, 'bachelor'],
            ['LEG_CMP_MANAGER',      'LEG_COMPLIANCE','Uyum Müdürü',                        'Compliance Manager',            5,10, 'bachelor'],
            // LEG_IP
            ['LEG_IP_SPECIALIST',    'LEG_IP',       'Fikri Mülkiyet Uzmanı',                'IP Specialist',                1, 5, 'bachelor'],
            ['LEG_IP_COUNSEL',       'LEG_IP',       'Fikri Mülkiyet Danışmanı',             'IP Counsel',                   5,12, 'bachelor'],
            // CS_SUPPORT
            ['CS_SUP_AGENT',         'CS_SUPPORT',   'Müşteri Destek Temsilcisi',            'Customer Support Agent',        0, 3, 'high_school'],
            ['CS_SUP_SUPERVISOR',    'CS_SUPPORT',   'Müşteri Destek Süpervizörü',           'Customer Support Supervisor',   2, 6, 'associate'],
            // CS_SUCCESS
            ['CS_SUC_SPECIALIST',    'CS_SUCCESS',   'Müşteri Başarısı Uzmanı',              'Customer Success Specialist',   1, 5, 'bachelor'],
            ['CS_SUC_MANAGER',       'CS_SUCCESS',   'Müşteri Başarısı Yöneticisi',          'Customer Success Manager',      3, 8, 'bachelor'],
            // CS_COMPLAINT
            ['CS_COM_SPECIALIST',    'CS_COMPLAINT', 'Şikayet Yönetim Uzmanı',              'Complaint Mgmt Specialist',     1, 5, 'bachelor'],
            ['CS_COM_MANAGER',       'CS_COMPLAINT', 'Şikayet Yönetim Müdürü',              'Complaint Mgmt Manager',        3, 8, 'bachelor'],
            // SEC_EXECUTIVE
            ['SEC_EXE_BODYGUARD',    'SEC_EXECUTIVE','Yakın Koruma',                         'Bodyguard',                     1, 5, 'high_school'],
            ['SEC_EXE_CHIEF',        'SEC_EXECUTIVE','Koruma Amiri',                         'Chief of Protection',           5,10, 'associate'],
            // SEC_CONTROL
            ['SEC_CTR_OPERATOR',     'SEC_CONTROL',  'Kontrol Odası Operatörü',              'Control Room Operator',         0, 3, 'high_school'],
            ['SEC_CTR_SUPERVISOR',   'SEC_CONTROL',  'Kontrol Odası Amiri',                  'Control Room Supervisor',       2, 6, 'associate'],
            // SEC_PATROL
            ['SEC_PAT_GUARD',        'SEC_PATROL',   'Devriye Güvenlik Görevlisi',           'Patrol Guard',                  0, 3, 'high_school'],
            ['SEC_PAT_SUPERVISOR',   'SEC_PATROL',   'Devriye Amiri',                        'Patrol Supervisor',             2, 6, 'associate'],
            // CLN_INDUSTRIAL
            ['CLN_IND_WORKER',       'CLN_INDUSTRIAL','Endüstriyel Temizlik Görevlisi',      'Industrial Cleaner',            0, 2, 'primary'],
            ['CLN_IND_SUPERVISOR',   'CLN_INDUSTRIAL','Endüstriyel Temizlik Amiri',          'Industrial Cleaning Supervisor',2, 6, 'high_school'],
            // CLN_FACILITY
            ['CLN_FAC_TECH',         'CLN_FACILITY', 'Tesis Bakım Teknisyeni',              'Facility Maint. Technician',    1, 5, 'vocational'],
            ['CLN_FAC_MANAGER',      'CLN_FACILITY', 'Tesis Yöneticisi',                    'Facility Manager',              3, 8, 'bachelor'],
            // CLN_LANDSCAPE
            ['CLN_LND_WORKER',       'CLN_LANDSCAPE','Peyzaj İşçisi',                       'Landscape Worker',              0, 2, 'primary'],
            ['CLN_LND_SUPERVISOR',   'CLN_LANDSCAPE','Peyzaj Sorumlusu',                    'Landscape Supervisor',          2, 6, 'associate'],
            // AUTO_PARTS
            ['AUTO_PRT_ASSOCIATE',   'AUTO_PARTS',   'Yedek Parça Satış Danışmanı',          'Parts Sales Associate',        0, 3, 'high_school'],
            ['AUTO_PRT_MANAGER',     'AUTO_PARTS',   'Yedek Parça Yöneticisi',               'Parts Manager',                3, 8, 'associate'],
            // AUTO_BODYSHOP
            ['AUTO_BDY_TECH',        'AUTO_BODYSHOP','Kaporta-Boya Teknisyeni',              'Body Shop Technician',          0, 3, 'vocational'],
            ['AUTO_BDY_MASTER',      'AUTO_BODYSHOP','Kaporta-Boya Ustası',                  'Body Shop Master',              5,12, 'vocational'],
            // AGR_LIVESTOCK
            ['AGR_LIV_WORKER',       'AGR_LIVESTOCK','Hayvancılık İşçisi',                   'Livestock Worker',              0, 2, 'primary'],
            ['AGR_LIV_SUPERVISOR',   'AGR_LIVESTOCK','Çiftlik Sorumlusu',                    'Farm Supervisor',               2, 6, 'associate'],
            // AGR_PROCESSING
            ['AGR_PRC_WORKER',       'AGR_PROCESSING','Gıda İşleme Operatörü',              'Food Processing Operator',      0, 2, 'high_school'],
            ['AGR_PRC_SUPERVISOR',   'AGR_PROCESSING','Gıda İşleme Sorumlusu',              'Food Processing Supervisor',    2, 6, 'associate'],
            // AGR_GREENHOUSE
            ['AGR_GRN_WORKER',       'AGR_GREENHOUSE','Sera İşçisi',                         'Greenhouse Worker',            0, 2, 'primary'],
            ['AGR_GRN_SUPERVISOR',   'AGR_GREENHOUSE','Sera Sorumlusu',                      'Greenhouse Supervisor',        2, 6, 'associate'],
            // BEAUTY_NAILS
            ['BEAUTY_NAIL_TECH',     'BEAUTY_NAILS', 'Tırnak Bakım Uzmanı',                  'Nail Technician',              0, 3, 'vocational'],
            ['BEAUTY_NAIL_SENIOR',   'BEAUTY_NAILS', 'Kıdemli Tırnak Bakım Uzmanı',          'Senior Nail Technician',       3, 8, 'vocational'],
            // BEAUTY_MAKEUP
            ['BEAUTY_MKP_ARTIST',    'BEAUTY_MAKEUP','Makyaj Sanatçısı',                     'Makeup Artist',                0, 3, 'vocational'],
            ['BEAUTY_MKP_SENIOR',    'BEAUTY_MAKEUP','Kıdemli Makyaj Sanatçısı',             'Senior Makeup Artist',         3, 8, 'vocational'],
            // CHILD_ACTIVITY
            ['CHILD_ACT_LEADER',     'CHILD_ACTIVITY','Çocuk Aktivite Lideri',               'Child Activity Leader',        0, 3, 'associate'],
            ['CHILD_ACT_COORDINATOR','CHILD_ACTIVITY','Çocuk Aktivite Koordinatörü',         'Child Activity Coordinator',   2, 6, 'bachelor'],
            // CHILD_TUTOR
            ['CHILD_TUT_TUTOR',      'CHILD_TUTOR',  'Özel Ders Öğretmeni',                 'Private Tutor',                0, 3, 'bachelor'],
            ['CHILD_TUT_SENIOR',     'CHILD_TUTOR',  'Kıdemli Özel Ders Öğretmeni',         'Senior Private Tutor',         3, 8, 'bachelor'],
        ];
        // Format: [code, subdomain_code, name_tr, name_en, exp_min, exp_max, education]

        // ── 2. Create Positions ─────────────────────────────────────
        $newPositionCodes = [];
        foreach ($positions as $i => $p) {
            [$code, $subCode, $nameTr, $nameEn, $expMin, $expMax, $edu] = $p;

            if (DB::table('job_positions')->where('code', $code)->exists()) {
                $this->command->info("Position already exists: {$code}");
                continue;
            }

            $subId = $subdomains[$subCode] ?? null;
            if (!$subId) {
                $this->command->warn("Subdomain not found: {$subCode}");
                continue;
            }

            DB::table('job_positions')->insert([
                'id'                  => Str::uuid()->toString(),
                'subdomain_id'        => $subId,
                'code'                => $code,
                'name_tr'             => $nameTr,
                'name_en'             => $nameEn,
                'experience_min_years'=> $expMin,
                'experience_max_years'=> $expMax,
                'education_level'     => $edu,
                'sort_order'          => $i + 1,
                'is_active'           => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            $newPositionCodes[] = $code;
        }
        $this->command->info('Created ' . count($newPositionCodes) . ' new positions.');

        // ── 3. Load Question Data Files ─────────────────────────────
        $dataFiles = glob(__DIR__ . '/data/questions_gap_*.php');
        $totalQ = 0;

        foreach ($dataFiles as $file) {
            $allQuestions = require $file;
            $fileName = basename($file);

            foreach ($allQuestions as $posCode => $questions) {
                $position = DB::table('job_positions')->where('code', $posCode)->first();
                if (!$position) {
                    $this->command->warn("Position not found: {$posCode}");
                    continue;
                }

                // Skip if questions already exist for this position
                $existing = DB::table('position_questions')
                    ->where('position_id', $position->id)
                    ->where('is_active', true)
                    ->count();
                if ($existing > 0) {
                    $this->command->info("Questions already exist for {$posCode} ({$existing}), skipping.");
                    continue;
                }

                foreach ($questions as $i => $q) {
                    $compId = $competencies[$q[1]] ?? null;
                    if (!$compId) {
                        $this->command->warn("Competency not found: {$q[1]} for {$posCode}");
                        continue;
                    }

                    DB::table('position_questions')->insert([
                        'id'                 => Str::uuid()->toString(),
                        'position_id'        => $position->id,
                        'competency_id'      => $compId,
                        'question_type'      => $q[2],
                        'question_tr'        => $q[0],
                        'question_en'        => '',
                        'expected_indicators'=> json_encode($q[3], JSON_UNESCAPED_UNICODE),
                        'red_flag_indicators'=> json_encode($q[4], JSON_UNESCAPED_UNICODE),
                        'difficulty_level'   => $q[5] ?? 2,
                        'time_limit_seconds' => 120,
                        'sort_order'         => $i + 1,
                        'is_mandatory'       => $i < 4 ? 1 : 0,
                        'is_active'          => 1,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                    $totalQ++;
                }
            }
            $this->command->info("Loaded: {$fileName}");
        }

        $this->command->info("Done! {$totalQ} new questions created.");
    }
}
