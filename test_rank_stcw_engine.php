<?php

/**
 * Rank & STCW Rule Engine — Acceptance Test
 *
 * Run: php82 test_rank_stcw_engine.php
 *
 * Tests:
 *  1. RankHierarchy model: lookup by canonical code
 *  2. RankHierarchy model: lookup by STCW code
 *  3. RankHierarchy: top rank detection
 *  4. RankHierarchy: required days calculation
 *  5. Feature flag gating
 *  6. StcwComplianceChecker: full compliance
 *  7. StcwComplianceChecker: partial compliance (missing certs)
 *  8. StcwComplianceChecker: no certs → zero compliance
 *  9. PromotionGapCalculator: positive gap (eligible)
 * 10. PromotionGapCalculator: negative gap (not eligible)
 * 11. PromotionGapCalculator: top rank
 * 12. TechnicalScoreCalculator: full pipeline
 * 13. TechnicalScoreCalculator: stores in trust profile
 * 14. TechnicalScoreCalculator: recompute overwrites
 * 15. Presenter output
 * 16. Artisan command: dry-run
 */

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CandidateContract;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\RankHierarchy;
use App\Models\SeafarerCertificate;
use App\Models\SeaTimeLog;
use App\Models\StcwRequirement;
use App\Models\TrustEvent;
use App\Presenters\RankStcwPresenter;
use App\Services\RankStcw\PromotionGapCalculator;
use App\Services\RankStcw\StcwComplianceChecker;
use App\Services\RankStcw\TechnicalScoreCalculator;
use App\Services\SeaTime\SeaTimeCalculator;
use App\Services\Trust\RankProgressionAnalyzer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ── Helpers ──────────────────────────────────────────────────────────────
$pass = 0;
$fail = 0;

function assert_true(string $msg, bool $condition): void {
    global $pass, $fail;
    if ($condition) { $pass++; echo "  ✓ $msg\n"; }
    else            { $fail++; echo "  ✗ FAIL: $msg\n"; }
}

function assert_eq(string $msg, mixed $expected, mixed $actual): void {
    global $pass, $fail;
    if ($expected == $actual) { $pass++; echo "  ✓ $msg\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (expected=" . json_encode($expected) . " actual=" . json_encode($actual) . ")\n"; }
}

function assert_gt(string $msg, float|int $value, float|int $threshold): void {
    global $pass, $fail;
    if ($value > $threshold) { $pass++; echo "  ✓ $msg (value=$value)\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (value=$value <= threshold=$threshold)\n"; }
}

function assert_gte(string $msg, float|int $value, float|int $threshold): void {
    global $pass, $fail;
    if ($value >= $threshold) { $pass++; echo "  ✓ $msg (value=$value)\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (value=$value < threshold=$threshold)\n"; }
}

function assert_lte(string $msg, float|int $value, float|int $threshold): void {
    global $pass, $fail;
    if ($value <= $threshold) { $pass++; echo "  ✓ $msg (value=$value)\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (value=$value > threshold=$threshold)\n"; }
}

function assert_null(string $msg, mixed $value): void {
    global $pass, $fail;
    if ($value === null) { $pass++; echo "  ✓ $msg\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (expected null, got " . json_encode($value) . ")\n"; }
}

function assert_not_null(string $msg, mixed $value): void {
    global $pass, $fail;
    if ($value !== null) { $pass++; echo "  ✓ $msg\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (expected not null)\n"; }
}

// ── Setup ────────────────────────────────────────────────────────────────
echo "\n═══════════════════════════════════════════════════\n";
echo "   RANK & STCW RULE ENGINE — ACCEPTANCE TEST\n";
echo "═══════════════════════════════════════════════════\n\n";

DB::beginTransaction();

try {

// Enable feature flags
Config::set('maritime.rank_stcw_v1', true);
Config::set('maritime.rank_stcw_auto_compute', true);
Config::set('maritime.sea_time_v1', true);

// Create test candidate — Chief Officer with 5 years experience
$candidate = PoolCandidate::create([
    'first_name' => 'RankStcw',
    'last_name' => 'TestCandidate',
    'email' => 'rankstcw-test-' . Str::random(8) . '@test.local',
    'phone' => '+905551234567',
    'country_code' => 'TR',
    'preferred_language' => 'en',
    'source_channel' => 'organic',
    'status' => 'in_pool',
    'primary_industry' => 'maritime',
    'seafarer' => true,
]);

// Create contracts — career progression from 3/O → 2/O → C/O
$contracts = [];

// Contract 1: 3/O on bulk carrier (18 months)
$contracts[] = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Bulk Star',
    'vessel_imo' => '9876543',
    'vessel_type' => 'bulk_carrier',
    'company_name' => 'Star Shipping',
    'rank_code' => '3/O',
    'start_date' => Carbon::parse('2020-01-15'),
    'end_date' => Carbon::parse('2021-07-14'),
]);

// Contract 2: 2/O on tanker (24 months)
$contracts[] = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MT Ocean Pride',
    'vessel_imo' => '9876544',
    'vessel_type' => 'tanker',
    'company_name' => 'Ocean Tankers',
    'rank_code' => '2/O',
    'start_date' => Carbon::parse('2021-08-01'),
    'end_date' => Carbon::parse('2023-08-01'),
]);

// Contract 3: C/O on bulk carrier (12 months, current rank)
$contracts[] = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Iron Maiden',
    'vessel_imo' => '9876545',
    'vessel_type' => 'bulk_carrier',
    'company_name' => 'Iron Fleet',
    'rank_code' => 'C/O',
    'start_date' => Carbon::parse('2023-09-01'),
    'end_date' => Carbon::parse('2024-09-01'),
]);

// Compute sea-time first (needed for TechnicalScore)
$seaTimeCalc = app(SeaTimeCalculator::class);
$seaTimeSummary = $seaTimeCalc->compute($candidate->id);

// Create seafarer certificates — partial compliance for 3/O rank requirements
$baseCerts = ['BST', 'PSSR', 'SAT', 'MEDICAL_FITNESS', 'SEAMANS_BOOK', 'PASSPORT'];
$extraCerts = ['GMDSS', 'ARPA', 'ECDIS']; // missing COC_CHIEF_OFFICER, BRM, AFF, PSCRB for C/O

foreach (array_merge($baseCerts, $extraCerts) as $code) {
    SeafarerCertificate::create([
        'pool_candidate_id' => $candidate->id,
        'certificate_type' => $code,
        'certificate_code' => $code,
        'issuing_authority' => 'Turkey Maritime Admin',
        'issuing_country' => 'TR',
        'issued_at' => Carbon::parse('2019-06-01'),
        'expires_at' => Carbon::parse('2028-06-01'),
        'verification_status' => SeafarerCertificate::STATUS_VERIFIED,
    ]);
}

// Add one expired cert
SeafarerCertificate::create([
    'pool_candidate_id' => $candidate->id,
    'certificate_type' => 'COC_OOW',
    'certificate_code' => 'COC_OOW',
    'issuing_authority' => 'Turkey Maritime Admin',
    'issuing_country' => 'TR',
    'issued_at' => Carbon::parse('2018-01-01'),
    'expires_at' => Carbon::parse('2023-01-01'), // expired
    'verification_status' => SeafarerCertificate::STATUS_VERIFIED,
]);

// ══════════════════════════════════════════════════════════════════════════
// TEST 1: RankHierarchy — Lookup by canonical code
// ══════════════════════════════════════════════════════════════════════════
echo "── 1. RankHierarchy: lookup by canonical code ──\n";

$master = RankHierarchy::findByCanonical('MASTER');
assert_not_null('MASTER found', $master);
assert_eq('MASTER department = deck', 'deck', $master->department);
assert_eq('MASTER level = 8', 8, $master->level);
assert_eq('MASTER stcw_rank_code = master', 'master', $master->stcw_rank_code);

$co = RankHierarchy::findByCanonical('C/O');
assert_not_null('C/O found', $co);
assert_eq('C/O next_rank_code = MASTER', 'MASTER', $co->next_rank_code);
assert_eq('C/O min_sea_months_in_rank = 36', 36, $co->min_sea_months_in_rank);

$ce = RankHierarchy::findByCanonical('C/E');
assert_not_null('C/E found', $ce);
assert_eq('C/E department = engine', 'engine', $ce->department);

// ══════════════════════════════════════════════════════════════════════════
// TEST 2: RankHierarchy — Lookup by STCW code
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 2. RankHierarchy: lookup by STCW code ──\n";

$chiefOfficer = RankHierarchy::findByStcw('chief_officer');
assert_not_null('chief_officer found', $chiefOfficer);
assert_eq('chief_officer canonical = C/O', 'C/O', $chiefOfficer->canonical_code);

$deckCadet = RankHierarchy::findByStcw('deck_cadet');
assert_not_null('deck_cadet found', $deckCadet);
assert_eq('deck_cadet canonical = DC', 'DC', $deckCadet->canonical_code);

// ══════════════════════════════════════════════════════════════════════════
// TEST 3: RankHierarchy — Top rank detection
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 3. RankHierarchy: top rank detection ──\n";

assert_true('MASTER is top rank', $master->isTopRank());
assert_true('C/O is NOT top rank', !$co->isTopRank());
assert_true('C/E is top rank', $ce->isTopRank());

$electro = RankHierarchy::findByCanonical('ELECTRO');
assert_true('ELECTRO is top rank', $electro ? $electro->isTopRank() : false);

$chSteward = RankHierarchy::findByCanonical('CH.STEWARD');
assert_true('CH.STEWARD is top rank', $chSteward ? $chSteward->isTopRank() : false);

// ══════════════════════════════════════════════════════════════════════════
// TEST 4: RankHierarchy — Required days calculation
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 4. RankHierarchy: required days calculation ──\n";

$requiredDays = $co->requiredDaysInRank();
assert_gt('C/O required days > 1000', $requiredDays, 1000); // 36 months * 30.44 ≈ 1096
assert_lte('C/O required days <= 1100', $requiredDays, 1100);

$masterDays = $master->requiredDaysInRank();
assert_eq('MASTER required days = 0 (top rank)', 0, $masterDays);

// ══════════════════════════════════════════════════════════════════════════
// TEST 5: Feature flag gating
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 5. Feature flag gating ──\n";

Config::set('maritime.rank_stcw_v1', false);
$calculator = app(TechnicalScoreCalculator::class);
$result = $calculator->compute($candidate->id);
assert_null('With flag OFF, compute returns null', $result);

Config::set('maritime.rank_stcw_v1', true);

// ══════════════════════════════════════════════════════════════════════════
// TEST 6: StcwComplianceChecker — Partial compliance
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 6. StcwComplianceChecker: partial compliance ──\n";

$complianceChecker = app(StcwComplianceChecker::class);
$compliance = $complianceChecker->check($candidate->id);

assert_not_null('Compliance result not null', $compliance);
assert_eq('Rank code = C/O', 'C/O', $compliance['rank_code']);
assert_gt('Compliance ratio > 0', $compliance['compliance_ratio'], 0);
assert_true('Compliance ratio < 1.0 (partial)', $compliance['compliance_ratio'] < 1.0);
assert_gt('Total required > 0', $compliance['total_required'], 0);
assert_gt('Total held > 0', $compliance['total_held'], 0);
assert_true('Has missing certs', count($compliance['missing_certs']) > 0);

echo "    Compliance: {$compliance['total_held']}/{$compliance['total_required']} = " . round($compliance['compliance_ratio'] * 100) . "%\n";
echo "    Missing: " . implode(', ', array_column($compliance['missing_certs'], 'code')) . "\n";

// ══════════════════════════════════════════════════════════════════════════
// TEST 7: StcwComplianceChecker — No certs candidate
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 7. StcwComplianceChecker: no certs candidate ──\n";

$noCertCandidate = PoolCandidate::create([
    'first_name' => 'NoCert',
    'last_name' => 'Test',
    'email' => 'nocert-' . Str::random(8) . '@test.local',
    'country_code' => 'TR',
    'source_channel' => 'organic',
    'status' => 'in_pool',
    'primary_industry' => 'maritime',
    'seafarer' => true,
]);

CandidateContract::create([
    'pool_candidate_id' => $noCertCandidate->id,
    'vessel_name' => 'MV Empty',
    'vessel_type' => 'bulk_carrier',
    'company_name' => 'Empty Corp',
    'rank_code' => 'AB',
    'start_date' => Carbon::parse('2023-01-01'),
    'end_date' => Carbon::parse('2024-01-01'),
]);

$noCertCompliance = $complianceChecker->check($noCertCandidate->id);
assert_not_null('No-cert compliance result not null', $noCertCompliance);
assert_eq('No-cert compliance ratio = 0', 0.0, $noCertCompliance['compliance_ratio']);
assert_eq('No-cert rank = AB', 'AB', $noCertCompliance['rank_code']);
assert_gt('No-cert has missing certs', count($noCertCompliance['missing_certs']), 0);

// ══════════════════════════════════════════════════════════════════════════
// TEST 8: StcwComplianceChecker — Full compliance
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 8. StcwComplianceChecker: full compliance ──\n";

$fullCompCandidate = PoolCandidate::create([
    'first_name' => 'FullComp',
    'last_name' => 'Test',
    'email' => 'fullcomp-' . Str::random(8) . '@test.local',
    'country_code' => 'TR',
    'source_channel' => 'organic',
    'status' => 'in_pool',
    'primary_industry' => 'maritime',
    'seafarer' => true,
]);

CandidateContract::create([
    'pool_candidate_id' => $fullCompCandidate->id,
    'vessel_name' => 'MV Full',
    'vessel_type' => 'bulk_carrier',
    'company_name' => 'Full Corp',
    'rank_code' => 'OS',
    'start_date' => Carbon::parse('2023-01-01'),
    'end_date' => Carbon::parse('2024-01-01'),
]);

// OS only needs base STCW certs
foreach ($baseCerts as $code) {
    SeafarerCertificate::create([
        'pool_candidate_id' => $fullCompCandidate->id,
        'certificate_type' => $code,
        'certificate_code' => $code,
        'issuing_authority' => 'Turkey Maritime Admin',
        'issuing_country' => 'TR',
        'issued_at' => Carbon::parse('2022-01-01'),
        'expires_at' => Carbon::parse('2028-01-01'),
        'verification_status' => SeafarerCertificate::STATUS_VERIFIED,
    ]);
}

$fullCompliance = $complianceChecker->check($fullCompCandidate->id);
assert_not_null('Full compliance result not null', $fullCompliance);
assert_eq('Full compliance ratio = 1.0', 1.0, $fullCompliance['compliance_ratio']);
assert_eq('Full compliance missing = 0', 0, count($fullCompliance['missing_certs']));
assert_eq('Full compliance rank = OS', 'OS', $fullCompliance['rank_code']);

// ══════════════════════════════════════════════════════════════════════════
// TEST 9: PromotionGapCalculator — Positive gap (eligible)
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 9. PromotionGapCalculator: positive gap (eligible) ──\n";

// Create a candidate who has served 24 months as 2/O (requirement: 18 months)
$eligibleCandidate = PoolCandidate::create([
    'first_name' => 'Eligible',
    'last_name' => 'Test',
    'email' => 'eligible-' . Str::random(8) . '@test.local',
    'country_code' => 'TR',
    'source_channel' => 'organic',
    'status' => 'in_pool',
    'primary_industry' => 'maritime',
    'seafarer' => true,
]);

CandidateContract::create([
    'pool_candidate_id' => $eligibleCandidate->id,
    'vessel_name' => 'MV Promotable',
    'vessel_type' => 'bulk_carrier',
    'company_name' => 'Promo Corp',
    'rank_code' => '2/O',
    'start_date' => Carbon::parse('2022-01-01'),
    'end_date' => Carbon::parse('2024-01-01'), // 24 months
]);

// Compute sea-time first
$seaTimeCalc->compute($eligibleCandidate->id);

$gapCalc = app(PromotionGapCalculator::class);
$gap = $gapCalc->calculate($eligibleCandidate->id);

assert_not_null('Eligible gap result not null', $gap);
assert_eq('Current rank = 2/O', '2/O', $gap['current_rank']);
assert_eq('Next rank = C/O', 'C/O', $gap['next_rank']);
assert_true('Actual rank days > 0', $gap['actual_rank_days'] > 0);
assert_gt('Required rank days > 0', $gap['required_rank_days'], 0);
assert_not_null('Promotion gap days not null', $gap['promotion_gap_days']);
assert_gt('Promotion gap is positive (eligible)', $gap['promotion_gap_days'], 0);
assert_true('Is eligible', $gap['is_eligible']);
assert_true('Is NOT top rank', !$gap['is_top_rank']);

echo "    2/O: actual={$gap['actual_rank_days']}d, required={$gap['required_rank_days']}d, gap={$gap['promotion_gap_days']}d\n";

// ══════════════════════════════════════════════════════════════════════════
// TEST 10: PromotionGapCalculator — Negative gap (not eligible)
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 10. PromotionGapCalculator: negative gap (not eligible) ──\n";

// Create a candidate who has served only 3 months as C/O (requirement: 36 months)
$notEligible = PoolCandidate::create([
    'first_name' => 'NotEligible',
    'last_name' => 'Test',
    'email' => 'noteligible-' . Str::random(8) . '@test.local',
    'country_code' => 'TR',
    'source_channel' => 'organic',
    'status' => 'in_pool',
    'primary_industry' => 'maritime',
    'seafarer' => true,
]);

CandidateContract::create([
    'pool_candidate_id' => $notEligible->id,
    'vessel_name' => 'MV Short',
    'vessel_type' => 'bulk_carrier',
    'company_name' => 'Short Corp',
    'rank_code' => 'C/O',
    'start_date' => Carbon::parse('2024-06-01'),
    'end_date' => Carbon::parse('2024-09-01'), // only 3 months
]);

$seaTimeCalc->compute($notEligible->id);
$negGap = $gapCalc->calculate($notEligible->id);

assert_not_null('Not-eligible gap result not null', $negGap);
assert_eq('Current rank = C/O', 'C/O', $negGap['current_rank']);
assert_not_null('Promotion gap days not null', $negGap['promotion_gap_days']);
assert_true('Promotion gap is negative', $negGap['promotion_gap_days'] < 0);
assert_true('Is NOT eligible', !$negGap['is_eligible']);

echo "    C/O: actual={$negGap['actual_rank_days']}d, required={$negGap['required_rank_days']}d, gap={$negGap['promotion_gap_days']}d\n";

// ══════════════════════════════════════════════════════════════════════════
// TEST 11: PromotionGapCalculator — Top rank
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 11. PromotionGapCalculator: top rank ──\n";

$topRankCandidate = PoolCandidate::create([
    'first_name' => 'TopRank',
    'last_name' => 'Master',
    'email' => 'toprank-' . Str::random(8) . '@test.local',
    'country_code' => 'TR',
    'source_channel' => 'organic',
    'status' => 'in_pool',
    'primary_industry' => 'maritime',
    'seafarer' => true,
]);

CandidateContract::create([
    'pool_candidate_id' => $topRankCandidate->id,
    'vessel_name' => 'MV Captain',
    'vessel_type' => 'bulk_carrier',
    'company_name' => 'Captain Corp',
    'rank_code' => 'MASTER',
    'start_date' => Carbon::parse('2020-01-01'),
    'end_date' => Carbon::parse('2024-01-01'),
]);

$seaTimeCalc->compute($topRankCandidate->id);
$topGap = $gapCalc->calculate($topRankCandidate->id);

assert_not_null('Top rank gap result not null', $topGap);
assert_eq('Current rank = MASTER', 'MASTER', $topGap['current_rank']);
assert_true('Is top rank', $topGap['is_top_rank']);
assert_null('Next rank is null', $topGap['next_rank']);
assert_null('Promotion gap days is null for top rank', $topGap['promotion_gap_days']);

// ══════════════════════════════════════════════════════════════════════════
// TEST 12: TechnicalScoreCalculator — Full pipeline
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 12. TechnicalScoreCalculator: full pipeline ──\n";

$calculator = app(TechnicalScoreCalculator::class);
$result = $calculator->compute($candidate->id);

assert_not_null('Technical score result not null', $result);
assert_true('technical_score in [0, 1]', $result['technical_score'] >= 0 && $result['technical_score'] <= 1);
assert_true('rank_days_weight in [0, 1]', $result['rank_days_weight'] >= 0 && $result['rank_days_weight'] <= 1);
assert_true('vessel_match_weight in [0, 1]', $result['vessel_match_weight'] >= 0 && $result['vessel_match_weight'] <= 1);
assert_true('certification_weight in [0, 1]', $result['certification_weight'] >= 0 && $result['certification_weight'] <= 1);
assert_not_null('Has promotion_gap', $result['promotion_gap']);
assert_not_null('Has stcw_compliance', $result['stcw_compliance']);
assert_not_null('Has vessel_match', $result['vessel_match']);
assert_not_null('Has computed_at', $result['computed_at']);

echo "    TechnicalScore = {$result['technical_score']}\n";
echo "    RankDaysWeight (×0.4) = {$result['rank_days_weight']}\n";
echo "    VesselMatchWeight (×0.3) = {$result['vessel_match_weight']}\n";
echo "    CertificationWeight (×0.3) = {$result['certification_weight']}\n";

// Verify the formula: score = (rank * 0.4) + (vessel * 0.3) + (cert * 0.3)
$expectedScore = round(
    ($result['rank_days_weight'] * 0.4) + ($result['vessel_match_weight'] * 0.3) + ($result['certification_weight'] * 0.3),
    4
);
assert_eq('Score matches formula', $expectedScore, $result['technical_score']);

// ══════════════════════════════════════════════════════════════════════════
// TEST 13: TechnicalScoreCalculator — Stores in trust profile
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 13. TechnicalScoreCalculator: stores in trust profile ──\n";

$trustProfile = CandidateTrustProfile::where('pool_candidate_id', $candidate->id)->first();
assert_not_null('Trust profile exists', $trustProfile);

$detailJson = $trustProfile->detail_json;
assert_true('detail_json has rank_stcw key', isset($detailJson['rank_stcw']));
assert_eq(
    'Stored technical_score matches returned',
    $result['technical_score'],
    $detailJson['rank_stcw']['technical_score']
);

// Check audit event was created
$event = TrustEvent::where('pool_candidate_id', $candidate->id)
    ->where('event_type', 'rank_stcw_computed')
    ->latest()
    ->first();
assert_not_null('Audit event created', $event);
assert_eq('Event has technical_score', $result['technical_score'], $event->payload_json['technical_score']);

// ══════════════════════════════════════════════════════════════════════════
// TEST 14: TechnicalScoreCalculator — Recompute overwrites
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 14. TechnicalScoreCalculator: recompute overwrites ──\n";

$result2 = $calculator->compute($candidate->id);
assert_not_null('Recompute result not null', $result2);
assert_eq('Recompute score same', $result['technical_score'], $result2['technical_score']);

// Verify only one rank_stcw block in detail_json (overwritten, not appended)
$trustProfile2 = CandidateTrustProfile::where('pool_candidate_id', $candidate->id)->first();
assert_true('detail_json still has rank_stcw', isset($trustProfile2->detail_json['rank_stcw']));
assert_eq(
    'Score is overwritten (same value)',
    $result2['technical_score'],
    $trustProfile2->detail_json['rank_stcw']['technical_score']
);

// ══════════════════════════════════════════════════════════════════════════
// TEST 15: Presenter output
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 15. RankStcwPresenter output ──\n";

$presented = RankStcwPresenter::fromTrustProfile($trustProfile2);
assert_not_null('Presenter output not null', $presented);
assert_eq('Presented technical_score matches', $result2['technical_score'], $presented['technical_score']);
assert_not_null('Presented has promotion_gap', $presented['promotion_gap']);
assert_not_null('Presented has stcw_compliance', $presented['stcw_compliance']);
assert_not_null('Presented has vessel_match', $presented['vessel_match']);
assert_eq('Presented promotion_gap.current_rank = C/O', 'C/O', $presented['promotion_gap']['current_rank']);

// Null trust profile returns null
$nullPresented = RankStcwPresenter::fromTrustProfile(null);
assert_null('Null trust profile → null', $nullPresented);

// ══════════════════════════════════════════════════════════════════════════
// TEST 16: Artisan command — dry-run
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 16. Artisan command: dry-run ──\n";

// Clear rank_stcw data from the no-cert candidate's trust profile
// (it doesn't have one, so this should appear in pending list)
$seaTimeCalc->compute($noCertCandidate->id);

$exitCode = \Illuminate\Support\Facades\Artisan::call('trust:rank-stcw:compute-pending', [
    '--dry-run' => true,
    '--limit' => 50,
    '--force' => true,
]);
assert_eq('Artisan dry-run exit code = 0', 0, $exitCode);

$output = \Illuminate\Support\Facades\Artisan::output();
assert_true('Output contains DRY RUN', str_contains($output, 'DRY RUN'));

// Test with feature flag off
Config::set('maritime.rank_stcw_v1', false);
$exitCode2 = \Illuminate\Support\Facades\Artisan::call('trust:rank-stcw:compute-pending', [
    '--limit' => 10,
]);
assert_eq('Artisan with flag off: exit code = 0', 0, $exitCode2);
$output2 = \Illuminate\Support\Facades\Artisan::output();
assert_true('Output contains disabled message', str_contains($output2, 'disabled'));
Config::set('maritime.rank_stcw_v1', true);

// ══════════════════════════════════════════════════════════════════════════
// TEST 17: TechnicalScoreCalculator — Candidate with no contracts
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 17. TechnicalScoreCalculator: candidate with no contracts ──\n";

$emptyCandidate = PoolCandidate::create([
    'first_name' => 'Empty',
    'last_name' => 'Test',
    'email' => 'empty-' . Str::random(8) . '@test.local',
    'country_code' => 'TR',
    'source_channel' => 'organic',
    'status' => 'in_pool',
    'primary_industry' => 'maritime',
    'seafarer' => true,
]);

$emptyResult = $calculator->compute($emptyCandidate->id);
// Should still return a result (with low score) since there's no contract → no rank → compliance checker returns null
assert_not_null('Empty candidate result not null', $emptyResult);
assert_gte('Empty candidate score >= 0', $emptyResult['technical_score'], 0);
assert_lte('Empty candidate score <= 1', $emptyResult['technical_score'], 1);

// ══════════════════════════════════════════════════════════════════════════
// TEST 18: Score variation — different candidates produce different scores
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 18. Score variation ──\n";

$fullCompResult = $calculator->compute($fullCompCandidate->id);
$noCertResult = $calculator->compute($noCertCandidate->id);

assert_not_null('Full-comp result not null', $fullCompResult);
assert_not_null('No-cert result not null', $noCertResult);

// Full compliance candidate should have higher cert weight
assert_true(
    'Full-compliance cert weight >= no-cert weight',
    $fullCompResult['certification_weight'] >= $noCertResult['certification_weight']
);

echo "    Full-comp score: {$fullCompResult['technical_score']} (cert: {$fullCompResult['certification_weight']})\n";
echo "    No-cert score: {$noCertResult['technical_score']} (cert: {$noCertResult['certification_weight']})\n";

// ══════════════════════════════════════════════════════════════════════════
// SUMMARY
// ══════════════════════════════════════════════════════════════════════════

} finally {
    DB::rollBack();
}

echo "\n═══════════════════════════════════════════════════\n";
echo "   RESULTS: $pass passed, $fail failed\n";
echo "═══════════════════════════════════════════════════\n\n";

exit($fail > 0 ? 1 : 0);
