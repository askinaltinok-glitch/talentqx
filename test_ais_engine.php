<?php
/**
 * AIS Verification Engine v1 — Acceptance Test
 *
 * Bootstraps Laravel, creates throwaway data, runs every code path,
 * asserts results, then cleans up.  Exit 0 = all pass, 1 = failure.
 *
 * Usage:  php82 test_ais_engine.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AisVerification;
use App\Models\CandidateContract;
use App\Models\ContractAisVerification;
use App\Models\PoolCandidate;
use App\Models\TrustEvent;
use App\Models\Vessel;
use App\Services\Ais\AisProviderInterface;
use App\Services\Ais\ConfidenceScorer;
use App\Services\Ais\ContractAisVerificationService;
use App\Services\Ais\Dto\TrackResultDto;
use App\Services\Ais\Dto\VesselStaticDto;
use App\Services\Ais\MockAisProvider;
use App\Services\Ais\VesselTypeNormalizer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

// ── helpers ──────────────────────────────────────────────────────
$passed = 0;
$failed = 0;
$cleanup = [];

function ok(bool $cond, string $label): void
{
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  ✓ {$label}\n";
    } else {
        $failed++;
        echo "  ✗ FAIL: {$label}\n";
    }
}

function section(string $title): void
{
    echo "\n━━━ {$title} ━━━\n";
}

function cleanup(): void
{
    global $cleanup;
    foreach (array_reverse($cleanup) as $fn) {
        try { $fn(); } catch (\Throwable $e) {
            echo "  ⚠ cleanup error: {$e->getMessage()}\n";
        }
    }
}

// ── force feature flags ON for the test ──────────────────────────
Config::set('maritime.ais_v1', true);
Config::set('maritime.ais_mock', true);
Config::set('maritime.ais_auto_verify', true);

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  AIS Verification Engine v1 — Acceptance Test       ║\n";
echo "╚══════════════════════════════════════════════════════╝\n";

// ═══════════════════════════════════════════════════════════════
// TEST 0: Provider binding & DI
// ═══════════════════════════════════════════════════════════════
section('0. Provider Binding');

$provider = app(AisProviderInterface::class);
ok($provider instanceof MockAisProvider, 'DI resolves MockAisProvider when ais_mock=true');

// ═══════════════════════════════════════════════════════════════
// TEST 1: VesselTypeNormalizer
// ═══════════════════════════════════════════════════════════════
section('1. VesselTypeNormalizer');

ok(VesselTypeNormalizer::normalize('Bulk Carrier') === CandidateContract::VESSEL_BULK_CARRIER, 'normalize("Bulk Carrier") → bulk_carrier');
ok(VesselTypeNormalizer::normalize('crude oil tanker') === CandidateContract::VESSEL_TANKER, 'normalize("crude oil tanker") → tanker');
ok(VesselTypeNormalizer::normalize('Container Ship') === CandidateContract::VESSEL_CONTAINER, 'normalize("Container Ship") → container');
ok(VesselTypeNormalizer::normalize('LNG') === CandidateContract::VESSEL_LNG_LPG, 'normalize("LNG") → lng_lpg');
ok(VesselTypeNormalizer::normalize('some unknown vessel') === CandidateContract::VESSEL_OTHER, 'normalize(unknown) → other');
ok(VesselTypeNormalizer::normalize(null) === CandidateContract::VESSEL_OTHER, 'normalize(null) → other');
ok(VesselTypeNormalizer::isCloseMatch(CandidateContract::VESSEL_TANKER, CandidateContract::VESSEL_CHEMICAL), 'tanker ↔ chemical is close match');
ok(!VesselTypeNormalizer::isCloseMatch(CandidateContract::VESSEL_TANKER, CandidateContract::VESSEL_CONTAINER), 'tanker ↔ container is NOT close match');

// ═══════════════════════════════════════════════════════════════
// TEST 2: MockAisProvider — deterministic output
// ═══════════════════════════════════════════════════════════════
section('2. MockAisProvider — determinism');

$mock = new MockAisProvider();
$from = \Carbon\Carbon::parse('2025-01-01');
$to   = \Carbon\Carbon::parse('2025-06-30');

$t1 = $mock->fetchTrackPoints('9876543', $from, $to);
$t2 = $mock->fetchTrackPoints('9876543', $from, $to);

ok($t1->totalPoints === $t2->totalPoints, 'Same IMO+dates → same totalPoints');
ok($t1->daysCovered === $t2->daysCovered, 'Same IMO+dates → same daysCovered');
ok($t1->dataQuality === $t2->dataQuality, 'Same IMO+dates → same dataQuality');
ok($t1->areaClusters === $t2->areaClusters, 'Same IMO+dates → same areaClusters');
ok($t1->totalPoints > 0, 'totalPoints > 0');
ok($t1->daysCovered > 0, 'daysCovered > 0');
ok($t1->dataQuality >= 0.6 && $t1->dataQuality <= 1.0, 'dataQuality in [0.6, 1.0]');
ok(count($t1->areaClusters) === 1, 'Exactly 1 area cluster');
ok($t1->firstSeen !== null && $t1->lastSeen !== null, 'firstSeen & lastSeen are set');

// ═══════════════════════════════════════════════════════════════
// TEST 3: Create test data
// ═══════════════════════════════════════════════════════════════
section('3. Create test data');

DB::beginTransaction();
$cleanup[] = fn() => DB::rollBack();

// Create a test pool candidate
$candidate = PoolCandidate::create([
    'email' => 'ais_test_' . uniqid() . '@test.local',
    'first_name' => 'AIS',
    'last_name' => 'TestRunner',
    'country_code' => 'TR',
    'status' => 'new',
    'primary_industry' => 'maritime',
    'source_channel' => 'organic',
]);
ok($candidate->exists, 'PoolCandidate created');

// Contract A: complete with IMO (should verify)
$contractA = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Test Bulker',
    'vessel_imo' => '9999901',
    'vessel_type' => CandidateContract::VESSEL_BULK_CARRIER,
    'company_name' => 'Test Shipping Co',
    'rank_code' => 'CHIEF_OFFICER',
    'start_date' => '2024-01-01',
    'end_date' => '2024-06-30',
    'trading_area' => 'Mediterranean',
]);
ok($contractA->exists, 'Contract A (with IMO, completed) created');

// Create a vessel so MockAisProvider can find it
$vesselA = Vessel::create([
    'imo' => '9999901',
    'name' => 'MV Test Bulker',
    'type' => 'bulk_carrier',
    'flag' => 'TR',
    'dwt' => 45000,
    'gt' => 28000,
    'length_m' => 190.0,
    'beam_m' => 32.0,
    'year_built' => 2015,
    'data_source' => 'manual',
]);
ok($vesselA->exists, 'Vessel A created for IMO 9999901');

// Contract B: no IMO (should → not_applicable MISSING_IMO)
$contractB = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'Unknown Vessel',
    'vessel_imo' => null,
    'vessel_type' => null,
    'company_name' => 'Unknown Shipping',
    'rank_code' => 'AB',
    'start_date' => '2023-01-01',
    'end_date' => '2023-06-30',
]);
ok($contractB->exists, 'Contract B (no IMO) created');

// Contract C: ongoing — no end_date (should → not_applicable ONGOING_CONTRACT)
$contractC = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Ongoing',
    'vessel_imo' => '9999902',
    'vessel_type' => CandidateContract::VESSEL_TANKER,
    'company_name' => 'Ongoing Shipping',
    'rank_code' => 'MASTER',
    'start_date' => '2025-11-01',
    'end_date' => null,
]);
ok($contractC->exists, 'Contract C (ongoing, no end_date) created');

// ═══════════════════════════════════════════════════════════════
// TEST 4: ConfidenceScorer standalone
// ═══════════════════════════════════════════════════════════════
section('4. ConfidenceScorer');

$scorer = new ConfidenceScorer();

// Build a realistic track for contract A
$trackA = new TrackResultDto(
    totalPoints: 540,
    daysCovered: 160,
    dataQuality: 0.88,
    areaClusters: [['name' => 'Mediterranean Sea', 'points' => 540, 'pctTime' => 1.0]],
    firstSeen: \Carbon\Carbon::parse('2024-01-02'),
    lastSeen: \Carbon\Carbon::parse('2024-06-28'),
);

$vesselA->update(['vessel_type_normalized' => CandidateContract::VESSEL_BULK_CARRIER]);

$result = $scorer->score($trackA, $contractA, $vesselA);

ok($result->score > 0 && $result->score <= 1.0, "Score in (0, 1.0]: {$result->score}");
ok(in_array($result->status, ['verified', 'failed']), "Status is verified or failed: {$result->status}");
ok(count($result->reasons) === 5, 'Exactly 5 reason factors');
ok(is_array($result->anomalies), 'Anomalies is array');

// With high quality data + matching type + good coverage → should be verified
ok($result->status === 'verified', 'High-quality data scores as verified');
ok($result->score >= 0.60, "Score >= 0.60 threshold: {$result->score}");

// Check each reason code exists
$codes = array_column($result->reasons, 'code');
ok(in_array('DATA_QUALITY', $codes), 'Has DATA_QUALITY reason');
ok(in_array('DAYS_COVERAGE', $codes), 'Has DAYS_COVERAGE reason');
ok(in_array('VESSEL_TYPE_MATCH', $codes), 'Has VESSEL_TYPE_MATCH reason');
ok(in_array('PERIOD_OVERLAP', $codes), 'Has PERIOD_OVERLAP reason');
ok(in_array('STATIC_DATA', $codes), 'Has STATIC_DATA reason');

// Test low-quality scenario → anomalies
$trackBad = new TrackResultDto(
    totalPoints: 10,
    daysCovered: 5,
    dataQuality: 0.03,
    areaClusters: [['name' => 'Unknown', 'points' => 10, 'pctTime' => 1.0]],
    firstSeen: \Carbon\Carbon::parse('2024-03-15'),
    lastSeen: \Carbon\Carbon::parse('2024-03-20'),
);

$resultBad = $scorer->score($trackBad, $contractA, $vesselA);
ok($resultBad->status === 'failed', 'Low-quality data scores as failed');
ok($resultBad->score < 0.60, "Score < 0.60: {$resultBad->score}");

$anomalyTypes = array_column($resultBad->anomalies, 'type');
ok(in_array('LOW_COVERAGE', $anomalyTypes), 'LOW_COVERAGE anomaly detected');

// ═══════════════════════════════════════════════════════════════
// TEST 5: Full engine — Contract WITH IMO (completed)
// ═══════════════════════════════════════════════════════════════
section('5. Engine: contract with IMO (completed)');

$service = app(ContractAisVerificationService::class);

$verA = $service->verify($contractA, 'admin', null);

ok($verA !== null, 'Verification record returned (not null)');
ok($verA instanceof ContractAisVerification, 'Is ContractAisVerification instance');
ok(in_array($verA->status, ['verified', 'failed']), "Status: {$verA->status}");
ok($verA->confidence_score !== null, "Confidence score set: {$verA->confidence_score}");
ok($verA->confidence_score >= 0 && $verA->confidence_score <= 100, "Score in [0,100]: {$verA->confidence_score}");
ok(is_array($verA->reasons_json) && count($verA->reasons_json) === 5, '5 reasons in JSON');
ok(is_array($verA->anomalies_json), 'Anomalies JSON is array');
ok(is_array($verA->evidence_summary_json), 'Evidence summary JSON is array');
ok(isset($verA->evidence_summary_json['total_points']), 'Evidence has total_points');
ok(isset($verA->evidence_summary_json['days_covered']), 'Evidence has days_covered');
ok(isset($verA->evidence_summary_json['data_quality']), 'Evidence has data_quality');
ok(isset($verA->evidence_summary_json['area_clusters']), 'Evidence has area_clusters');
ok($verA->period_start !== null, 'period_start set');
ok($verA->period_end !== null, 'period_end set');
ok($verA->provider === 'mock', "Provider = mock: {$verA->provider}");
ok($verA->triggered_by === 'admin', "triggered_by = admin: {$verA->triggered_by}");
ok($verA->candidate_contract_id === $contractA->id, 'Linked to correct contract');
ok($verA->vessel_id !== null, 'vessel_id set');

// Verify vessel_id was linked on contract
$contractA->refresh();
ok($contractA->vessel_id !== null, 'Contract vessel_id was linked');

// Verify legacy ais_verifications was also upserted
$legacyAis = AisVerification::where('candidate_contract_id', $contractA->id)->first();
ok($legacyAis !== null, 'Legacy ais_verifications record created');
ok($legacyAis->status === $verA->status, 'Legacy status matches engine status');

// Verify trust event was created
$event = TrustEvent::where('pool_candidate_id', $candidate->id)
    ->where('event_type', 'ais_verification_completed')
    ->latest('created_at')
    ->first();
ok($event !== null, 'TrustEvent ais_verification_completed created');
ok($event->payload_json['contract_id'] === $contractA->id, 'Event payload has correct contract_id');

// ═══════════════════════════════════════════════════════════════
// TEST 6: Full engine — Contract WITHOUT IMO
// ═══════════════════════════════════════════════════════════════
section('6. Engine: contract without IMO');

$verB = $service->verify($contractB, 'system', null);

ok($verB !== null, 'Verification record returned');
ok($verB->status === ContractAisVerification::STATUS_NOT_APPLICABLE, "Status = not_applicable: {$verB->status}");
ok(is_array($verB->reasons_json), 'Reasons JSON present');
ok($verB->reasons_json[0]['code'] === 'MISSING_IMO', 'Reason code = MISSING_IMO');
ok($verB->confidence_score === null, 'No confidence score');
ok($verB->evidence_summary_json === null, 'No evidence summary');

// ═══════════════════════════════════════════════════════════════
// TEST 7: Full engine — Ongoing contract (no end_date)
// ═══════════════════════════════════════════════════════════════
section('7. Engine: ongoing contract (no end_date)');

$verC = $service->verify($contractC, 'system', null);

ok($verC !== null, 'Verification record returned');
ok($verC->status === ContractAisVerification::STATUS_NOT_APPLICABLE, "Status = not_applicable: {$verC->status}");
ok($verC->reasons_json[0]['code'] === 'ONGOING_CONTRACT', 'Reason code = ONGOING_CONTRACT');

// ═══════════════════════════════════════════════════════════════
// TEST 8: Append-only — re-run creates second record
// ═══════════════════════════════════════════════════════════════
section('8. Append-only: second run creates new record');

$verA2 = $service->verify($contractA, 'cron', null);

ok($verA2 !== null, 'Second verification returned');
ok($verA2->id !== $verA->id, 'Different record ID (append-only)');

$totalForA = ContractAisVerification::where('candidate_contract_id', $contractA->id)->count();
ok($totalForA === 2, "2 records for contract A: {$totalForA}");

// latestAisVerification should return the newest
$contractA->refresh();
$contractA->load('latestAisVerification');
ok($contractA->latestAisVerification->id === $verA2->id, 'latestAisVerification returns newest record');

// ═══════════════════════════════════════════════════════════════
// TEST 9: Feature flag OFF → returns null
// ═══════════════════════════════════════════════════════════════
section('9. Feature flag OFF → null');

Config::set('maritime.ais_v1', false);
$verOff = $service->verify($contractA, 'system', null);
ok($verOff === null, 'Returns null when ais_v1=false');
Config::set('maritime.ais_v1', true);

// ═══════════════════════════════════════════════════════════════
// TEST 10: MockAisProvider.fetchVesselStatic
// ═══════════════════════════════════════════════════════════════
section('10. MockAisProvider.fetchVesselStatic');

$staticA = $mock->fetchVesselStatic('9999901');
ok($staticA !== null, 'Returns DTO for existing vessel');
ok($staticA instanceof VesselStaticDto, 'Is VesselStaticDto');
ok($staticA->imo === '9999901', 'DTO has correct IMO');
ok($staticA->name === 'MV Test Bulker', 'DTO has correct name');
ok($staticA->flag === 'TR', 'DTO has correct flag');

$staticNone = $mock->fetchVesselStatic('0000000');
ok($staticNone === null, 'Returns null for non-existent IMO');

// ═══════════════════════════════════════════════════════════════
// TEST 11: Presenter output
// ═══════════════════════════════════════════════════════════════
section('11. Presenter output');

use App\Presenters\CandidateContractAisPresenter;

$contractA->load(['vessel', 'aisVerification', 'latestAisVerification']);
$presented = CandidateContractAisPresenter::present($contractA);

ok(isset($presented['ais_verification']), 'Presenter has ais_verification block');
$aisBlock = $presented['ais_verification'];
ok(in_array($aisBlock['status'], ['verified', 'failed', 'pending', 'not_applicable']), "Presenter status valid: {$aisBlock['status']}");
ok(array_key_exists('reasons', $aisBlock), 'Presenter has reasons key');
ok(array_key_exists('anomalies', $aisBlock), 'Presenter has anomalies key');
ok(array_key_exists('evidence_summary', $aisBlock), 'Presenter has evidence_summary key');
ok(array_key_exists('provider', $aisBlock), 'Presenter has provider key');

// Since latestAisVerification is loaded, new engine data should be present
ok(is_array($aisBlock['reasons']), 'Presenter reasons is array (engine data)');
ok($aisBlock['provider'] === 'mock', 'Presenter provider = mock');

// Contract B presenter (no IMO)
$contractB->load(['vessel', 'aisVerification', 'latestAisVerification']);
$presentedB = CandidateContractAisPresenter::present($contractB);
ok($presentedB['ais_verification']['status'] === 'not_applicable', 'Contract B presenter → not_applicable');
ok($presentedB['ais_verification']['reasons'] === null, 'Contract B presenter → reasons null (no IMO path)');

// Aggregate KPIs
$allContracts = CandidateContract::where('pool_candidate_id', $candidate->id)
    ->with(['aisVerification'])
    ->get();
$kpis = CandidateContractAisPresenter::aggregateKpis($allContracts);
ok($kpis['total_contracts'] === 3, "Total contracts: {$kpis['total_contracts']}");
ok($kpis['with_imo'] === 2, "With IMO: {$kpis['with_imo']}");

// ═══════════════════════════════════════════════════════════════
// TEST 12: Artisan command (dry-run)
// ═══════════════════════════════════════════════════════════════
section('12. Artisan command (dry-run)');

$exitCode = Illuminate\Support\Facades\Artisan::call('trust:ais:verify-pending', ['--dry-run' => true]);
$output = Illuminate\Support\Facades\Artisan::output();

ok($exitCode === 0, "Exit code = 0: {$exitCode}");
ok(str_contains($output, 'DRY RUN') || str_contains($output, 'No contracts need'), 'Output mentions DRY RUN or no contracts');

// ═══════════════════════════════════════════════════════════════
// DONE
// ═══════════════════════════════════════════════════════════════

echo "\n══════════════════════════════════════════════════════\n";
if ($failed === 0) {
    echo "  ALL {$passed} TESTS PASSED ✓\n";
} else {
    echo "  {$passed} passed, {$failed} FAILED ✗\n";
}
echo "══════════════════════════════════════════════════════\n\n";

// Cleanup: rollback the transaction
cleanup();

exit($failed > 0 ? 1 : 0);
