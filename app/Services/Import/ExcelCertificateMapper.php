<?php

namespace App\Services\Import;

class ExcelCertificateMapper
{
    /**
     * Maps Excel column index (0-based) to certificate definition.
     *
     * Each entry: [certificate_type, issuing_country (or null), description]
     */
    public const COLUMN_MAP = [
        10 => ['PASSPORT',          null, 'Passport Expiry'],
        13 => ['SEAMANS_BOOK',      null, 'Seamans Book Expiry'],
        14 => ['MEDICAL_FITNESS',   null, 'Medical Certificate'],
        15 => ['GMDSS',             null, 'GMDSS Radio Operator'],
        16 => ['BST',               null, 'Basic Safety Training (A-VI/1)'],
        17 => ['PSCRB',             null, 'Survival Craft & Rescue Boats'],
        18 => ['AFF',               null, 'Advanced Fire Fighting'],
        19 => ['ARPA',              null, 'ARPA / Radar'],
        20 => ['SAT',               null, 'Ship Security Awareness'],
        21 => ['STCW_SH',           null, 'Designated Security Duties'],
        22 => ['STCW_SG',           null, 'Ship Security Officer (SSO)'],
        23 => ['EFA',               null, 'First Aid'],
        24 => ['MEDICAL_CARE',      null, 'Medical Care on Board'],
        25 => ['ECDIS',             null, 'ECDIS'],
        26 => ['BRM',               null, 'BRM'],
        27 => ['STCW_DL',           null, 'Leadership & Teamwork'],
        28 => ['STCW_SP',           null, 'ISM Code'],
        29 => ['STCW_SV',           null, 'Ship Handling & Manoeuvring'],
        // Flag endorsements
        39 => ['FLAG_ENDORSEMENT',  'PA', 'Panama Flag Endorsement'],
        40 => ['FLAG_ENDORSEMENT',  'LR', 'Liberia Flag Endorsement'],
        41 => ['FLAG_ENDORSEMENT',  'TZ', 'Tanzania Flag Endorsement'],
        42 => ['FLAG_ENDORSEMENT',  'SL', 'Sierra Leone Flag Endorsement'],
        43 => ['FLAG_ENDORSEMENT',  'PW', 'Palau Flag Endorsement'],
    ];

    /**
     * COC (Certificate of Competency) columns: 30–37
     * These contain free-text certificate descriptions, not just dates.
     * Even columns = name/code, odd columns = expiry date.
     * We treat pairs: (30,31), (32,33), (34,35), (36,37)
     */
    public const COC_COLUMN_PAIRS = [
        [30, 31],
        [32, 33],
        [34, 35],
        [36, 37],
    ];

    /**
     * Identity / crew-member columns (0-based).
     */
    public const CREW_COLUMNS = [
        'full_name'         => 1,  // Col B: Name Surname
        'rank_raw'          => 2,  // Col C: Rank/Position
        'vessel_name'       => 3,  // Col D: Vessel Name
        'vessel_country'    => 4,  // Col E: Flag/Country
        'contract_start_at' => 5,  // Col F: Contract Start
        'contract_end_at'   => 6,  // Col G: Contract End
        'nationality'       => 7,  // Col H: Nationality
        'date_of_birth'     => 8,  // Col I: Date of Birth
        'passport_no'       => 9,  // Col J: Passport No
        'seamans_book_no'   => 12, // Col M: Seamans Book No
    ];

    /**
     * Col 38 (0-based) = endorsement free-text → stored in meta.
     */
    public const ENDORSEMENT_TEXT_COL = 38;

    /**
     * All certificate type codes that can appear in the DB.
     */
    public const ALL_TYPES = [
        'PASSPORT', 'SEAMANS_BOOK', 'MEDICAL_FITNESS', 'GMDSS', 'BST',
        'PSCRB', 'AFF', 'ARPA', 'SAT', 'STCW_SH', 'STCW_SG', 'EFA',
        'MEDICAL_CARE', 'ECDIS', 'BRM', 'STCW_DL', 'STCW_SP', 'STCW_SV',
        'COC', 'FLAG_ENDORSEMENT',
    ];
}
