<?php

namespace Database\Seeders;

use App\Models\CertificateType;
use Illuminate\Database\Seeder;

/**
 * Seeds certificate_types table with real IMO/STCW maritime certificate structure.
 */
class CertificateTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // ========== STCW CORE (Basic Safety Training) ==========
            ['name' => 'Basic Safety Training (BST)', 'code' => 'BST', 'category' => 'STCW', 'is_mandatory' => true, 'sort_order' => 1,
             'description' => 'STCW A-VI/1: Personal survival, fire prevention, elementary first aid, personal safety.'],
            ['name' => 'Proficiency in Survival Craft (PST/PSCRB)', 'code' => 'PST', 'category' => 'STCW', 'is_mandatory' => true, 'sort_order' => 2,
             'description' => 'STCW A-VI/2: Proficiency in survival craft and rescue boats.'],
            ['name' => 'Fire Prevention & Fire Fighting (FPFF)', 'code' => 'FPFF', 'category' => 'STCW', 'is_mandatory' => true, 'sort_order' => 3,
             'description' => 'STCW A-VI/1-2: Advanced fire fighting techniques.'],
            ['name' => 'Elementary First Aid (EFA)', 'code' => 'EFA', 'category' => 'STCW', 'is_mandatory' => true, 'sort_order' => 4,
             'description' => 'STCW A-VI/1-3: Elementary first aid at sea.'],
            ['name' => 'Personal Safety & Social Responsibilities (PSSR)', 'code' => 'PSSR', 'category' => 'STCW', 'is_mandatory' => true, 'sort_order' => 5,
             'description' => 'STCW A-VI/1-4: Personal safety and social responsibilities onboard.'],
            ['name' => 'Advanced Fire Fighting (AFF)', 'code' => 'AFF', 'category' => 'STCW', 'is_mandatory' => false, 'sort_order' => 6,
             'description' => 'STCW A-VI/3: Advanced fire fighting for senior officers.'],
            ['name' => 'Proficiency in Survival Craft & Rescue Boats (PSCRB)', 'code' => 'PSCRB', 'category' => 'STCW', 'is_mandatory' => false, 'sort_order' => 7,
             'description' => 'STCW A-VI/2-1: Proficiency in survival craft and rescue boats.'],
            ['name' => 'Security Awareness Training (SAT)', 'code' => 'SAT', 'category' => 'STCW', 'is_mandatory' => true, 'sort_order' => 8,
             'description' => 'STCW A-VI/6: Security awareness for all seafarers (ISPS).'],

            // ========== OFFICER CERTIFICATES ==========
            ['name' => 'Certificate of Competency - OOW Deck', 'code' => 'COC_OOW', 'category' => 'OFFICER', 'is_mandatory' => false, 'sort_order' => 10,
             'description' => 'STCW II/1: Officer in Charge of a Navigational Watch.'],
            ['name' => 'Certificate of Competency - Chief Officer', 'code' => 'COC_CHIEF_OFFICER', 'category' => 'OFFICER', 'is_mandatory' => false, 'sort_order' => 11,
             'description' => 'STCW II/2: Chief Mate unlimited.'],
            ['name' => 'Certificate of Competency - Master', 'code' => 'COC_MASTER', 'category' => 'OFFICER', 'is_mandatory' => false, 'sort_order' => 12,
             'description' => 'STCW II/2: Master unlimited.'],
            ['name' => 'GMDSS General Operator Certificate', 'code' => 'GMDSS', 'category' => 'OFFICER', 'is_mandatory' => false, 'sort_order' => 13,
             'description' => 'STCW IV/2: Global Maritime Distress and Safety System.'],
            ['name' => 'ARPA / Radar Navigation', 'code' => 'ARPA', 'category' => 'OFFICER', 'is_mandatory' => false, 'sort_order' => 14,
             'description' => 'STCW A-II/1: Automatic Radar Plotting Aid.'],
            ['name' => 'Bridge Resource Management (BRM)', 'code' => 'BRM', 'category' => 'OFFICER', 'is_mandatory' => false, 'sort_order' => 15,
             'description' => 'STCW A-II: Bridge team management and leadership.'],

            // ========== ENGINE CERTIFICATES ==========
            ['name' => 'Certificate of Competency - Engineer OOW', 'code' => 'COC_ENGINEER', 'category' => 'ENGINE', 'is_mandatory' => false, 'sort_order' => 20,
             'description' => 'STCW III/1: Officer in Charge of an Engineering Watch.'],
            ['name' => 'Certificate of Competency - Second Engineer', 'code' => 'COC_2ND_ENGINEER', 'category' => 'ENGINE', 'is_mandatory' => false, 'sort_order' => 21,
             'description' => 'STCW III/2: Second engineer officer.'],
            ['name' => 'Certificate of Competency - Chief Engineer', 'code' => 'COC_CHIEF_ENGINEER', 'category' => 'ENGINE', 'is_mandatory' => false, 'sort_order' => 22,
             'description' => 'STCW III/2: Chief engineer officer unlimited.'],
            ['name' => 'Engine Resource Management (ERM)', 'code' => 'ERM', 'category' => 'ENGINE', 'is_mandatory' => false, 'sort_order' => 23,
             'description' => 'STCW A-III: Engine room resource management.'],

            // ========== SPECIAL CERTIFICATES ==========
            ['name' => 'Tanker Familiarization', 'code' => 'TANKER_FAM', 'category' => 'SPECIAL', 'is_mandatory' => false, 'sort_order' => 30,
             'description' => 'STCW A-V/1: Basic tanker cargo operations.'],
            ['name' => 'Oil Tanker Specialized', 'code' => 'TANKER_OIL', 'category' => 'SPECIAL', 'is_mandatory' => false, 'sort_order' => 31,
             'description' => 'STCW A-V/1: Oil tanker specialized training.'],
            ['name' => 'Chemical Tanker Specialized', 'code' => 'TANKER_CHEM', 'category' => 'SPECIAL', 'is_mandatory' => false, 'sort_order' => 32,
             'description' => 'STCW A-V/1: Chemical tanker specialized training.'],
            ['name' => 'LNG/LPG Tanker Specialized', 'code' => 'TANKER_GAS', 'category' => 'SPECIAL', 'is_mandatory' => false, 'sort_order' => 33,
             'description' => 'STCW A-V/1: Liquefied gas tanker specialized training.'],
            ['name' => 'ECDIS Type-Specific', 'code' => 'ECDIS', 'category' => 'SPECIAL', 'is_mandatory' => false, 'sort_order' => 34,
             'description' => 'Electronic Chart Display and Information System type-specific training.'],
            ['name' => 'DP Basic', 'code' => 'DP_BASIC', 'category' => 'SPECIAL', 'is_mandatory' => false, 'sort_order' => 35,
             'description' => 'Dynamic Positioning basic (Nautical Institute).'],
            ['name' => 'DP Advanced', 'code' => 'DP_ADVANCED', 'category' => 'SPECIAL', 'is_mandatory' => false, 'sort_order' => 36,
             'description' => 'Dynamic Positioning advanced (Nautical Institute).'],
            ['name' => 'High Voltage Safety', 'code' => 'HV_SAFETY', 'category' => 'SPECIAL', 'is_mandatory' => false, 'sort_order' => 37,
             'description' => 'High voltage electrical safety training for electricians and engineers.'],
            ['name' => 'Hazardous Materials (HAZMAT)', 'code' => 'HAZMAT', 'category' => 'SPECIAL', 'is_mandatory' => false, 'sort_order' => 38,
             'description' => 'Handling of hazardous materials and dangerous goods.'],

            // ========== MEDICAL ==========
            ['name' => 'Medical Fitness Certificate', 'code' => 'MEDICAL_FITNESS', 'category' => 'MEDICAL', 'is_mandatory' => true, 'sort_order' => 40,
             'description' => 'ILO/MLC 2006 Reg 1.2: Medical certificate for seafarers. Valid 2 years.'],
            ['name' => 'Ship Medical Care', 'code' => 'MEDICAL_CARE', 'category' => 'MEDICAL', 'is_mandatory' => false, 'sort_order' => 41,
             'description' => 'STCW A-VI/4-2: Medical care aboard ship (for designated medical officers).'],

            // ========== FLAG / ENDORSEMENT ==========
            ['name' => 'Flag State Endorsement', 'code' => 'FLAG_ENDORSEMENT', 'category' => 'FLAG', 'is_mandatory' => false, 'sort_order' => 50,
             'description' => 'Flag state endorsement/recognition of COC.'],
            ['name' => "Seaman's Book / CDC", 'code' => 'SEAMANS_BOOK', 'category' => 'FLAG', 'is_mandatory' => true, 'sort_order' => 51,
             'description' => "Seaman's discharge book / Continuous Discharge Certificate."],
            ['name' => 'Passport', 'code' => 'PASSPORT', 'category' => 'FLAG', 'is_mandatory' => true, 'sort_order' => 52,
             'description' => 'Valid passport for international travel.'],

            // ========== MLC (Maritime Labour Convention) ==========
            ['name' => 'MLC Medical Fitness', 'code' => 'MLC_MEDICAL', 'category' => 'MLC', 'is_mandatory' => true, 'sort_order' => 60,
             'description' => 'MLC 2006 Reg 1.2: Medical certificate per MLC standards.'],
            ['name' => 'Insurance / P&I Coverage', 'code' => 'MLC_INSURANCE', 'category' => 'MLC', 'is_mandatory' => false, 'sort_order' => 61,
             'description' => 'MLC 2006 Reg 4.2: Financial security / P&I club coverage.'],
            ['name' => 'Employment Agreement', 'code' => 'MLC_EMPLOYMENT', 'category' => 'MLC', 'is_mandatory' => false, 'sort_order' => 62,
             'description' => 'MLC 2006 Reg 2.1: Seafarer employment agreement on file.'],
        ];

        foreach ($types as $type) {
            CertificateType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        $this->command->info('Seeded ' . count($types) . ' certificate types.');
    }
}
