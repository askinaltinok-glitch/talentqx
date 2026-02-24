<?php

/**
 * Stability & Risk Engine — Acceptance Test
 *
 * Run: php82 test_stability_risk_engine.php
 *
 * Tests:
 *  1. Feature flag: config('maritime.stability_v1') exists
 *  2. StabilityIndexCalculator: 0 contracts → null
 *  3. StabilityIndexCalculator: 1 contract → null
 *  4. StabilityIndexCalculator: 2+ contracts → computes avg/std/index
 *  5. StabilityIndexCalculator: uniform durations → capped at 10.0
 *  6. RiskScoreCalculator: all-zero inputs → risk_score ≈ 0.0
 *  7. RiskScoreCalculator: high risk inputs → risk_score increases
 *  8. RiskScoreCalculator: returns all 6 factors
 *  9. RiskScoreCalculator: weights sum to 1.0
 * 10. RiskTierResolver: boundary values
 * 11. StabilityRiskEngine: integration test
 * 12. Job exists: ComputeStabilityRiskJob
 * 13. Command exists: trust:stability:compute-pending
 * 14. Presenter: fromTrustProfile()
 * 15. Cleanup
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Stability\StabilityIndexCalculator;
use App\Services\Stability\RiskScoreCalculator;
use App\Services\Stability\RiskTierResolver;
use App\Services\Stability\StabilityRiskEngine;
use App\Jobs\ComputeStabilityRiskJob;
use App\Console\Commands\ComputePendingStabilityRiskCommand;
use App\Presenters\StabilityRiskPresenter;
use App\Models\PoolCandidate;
use App\Models\CandidateContract;
use App\Models\CandidateTrustProfile;
use App\Models\TrustEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

// ── Helpers ──────────────────────────────────────────────────────────────
$pass = 0;
$fail = 0;
$total = 0;

function assert_test(string $msg, bool $condition): void {
    global $pass, $fail, $total;
    $total++;
    if ($condition) { $pass++; echo "  ✓ $msg\n"; }
    else            { $fail++; echo "  ✗ FAIL: $msg\n"; }
}

function assert_eq(string $msg, mixed $expected, mixed $actual): void {
    global $pass, $fail, $total;
    $total++;
    if ($expected == $actual) { $pass++; echo "  ✓ $msg\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (expected=" . json_encode($expected) . " actual=" . json_encode($actual) . ")\n"; }
}

function assert_null(string $msg, mixed $value): void {
    global $pass, $fail, $total;
    $total++;
    if ($value === null) { $pass++; echo "  ✓ $msg\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (expected null, got " . json_encode($value) . ")\n"; }
}

function assert_not_null(string $msg, mixed $value): void {
    global $pass, $fail, $total;
    $total++;
    if ($value !== null) { $pass++; echo "  ✓ $msg\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (expected not null)\n"; }
}

function assert_gt(string $msg, float|int $value, float|int $threshold): void {
    global $pass, $fail, $total;
    $total++;
    if ($value > $threshold) { $pass++; echo "  ✓ $msg (value=$value)\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (value=$value <= threshold=$threshold)\n"; }
}

function assert_gte(string $msg, float|int $value, float|int $threshold): void {
    global $pass, $fail, $total;
    $total++;
    if ($value >= $threshold) { $pass++; echo "  ✓ $msg (value=$value)\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (value=$value < threshold=$threshold)\n"; }
}

function assert_lte(string $msg, float|int $value, float|int $threshold): void {
    global $pass, $fail, $total;
    $total++;
    if ($value <= $threshold) { $pass++; echo "  ✓ $msg (value=$value)\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (value=$value > threshold=$threshold)\n"; }
}

/**
 * Build a mock contract-like object for StabilityIndexCalculator unit tests.
 * Must have `end_date` (non-null) and `durationMonths()` method.
 */
function mock_contract(string $start, string $end): object {
    $startDate = Carbon::parse($start);
    $endDate   = Carbon::parse($end);
    return new class($startDate, $endDate) {
        public Carbon $start_date;
        public Carbon $end_date;
        public function __construct(Carbon $start, Carbon $end) {
            $this->start_date = $start;
            $this->end_date   = $end;
        }
        public function durationMonths(): float {
            return round($this->start_date->diffInDays($this->end_date) / 30.44, 1);
        }
    };
}

// ── Setup ────────────────────────────────────────────────────────────────
echo "\n═══════════════════════════════════════════════════\n";
echo "   STABILITY & RISK ENGINE — ACCEPTANCE TEST\n";
echo "═══════════════════════════════════════════════════\n\n";

DB::beginTransaction();

try {

// Enable feature flags
Config::set('maritime.stability_v1', true);
Config::set('maritime.stability_auto_compute', true);

// ══════════════════════════════════════════════════════════════════════════
// TEST 1: Feature flag exists
// ══════════════════════════════════════════════════════════════════════════
echo "── 1. Feature flag: stability_v1 ──\n";

assert_not_null('config maritime.stability_v1 exists', config('maritime.stability_v1'));
assert_test('stability_v1 is truthy when set', (bool) config('maritime.stability_v1'));

// ══════════════════════════════════════════════════════════════════════════
// TEST 2: StabilityIndexCalculator — 0 contracts → null
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 2. StabilityIndexCalculator: 0 contracts ──\n";

$stabilityCalc = new StabilityIndexCalculator();
$result0 = $stabilityCalc->calculate(collect([]));

assert_null('0 contracts → stability_index is null', $result0['stability_index']);
assert_eq('0 contracts → contract_count = 0', 0, $result0['contract_count']);
assert_eq('0 contracts → avg_months = 0', 0, $result0['avg_months']);

// ══════════════════════════════════════════════════════════════════════════
// TEST 3: StabilityIndexCalculator — 1 contract → null
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 3. StabilityIndexCalculator: 1 contract ──\n";

$single = collect([mock_contract('2023-01-01', '2023-07-01')]);
$result1 = $stabilityCalc->calculate($single);

assert_null('1 contract → stability_index is null', $result1['stability_index']);
assert_eq('1 contract → contract_count = 1', 1, $result1['contract_count']);
assert_gt('1 contract → avg_months > 0', $result1['avg_months'], 0);

// ══════════════════════════════════════════════════════════════════════════
// TEST 4: StabilityIndexCalculator — 2+ contracts with varying durations
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 4. StabilityIndexCalculator: varying durations ──\n";

$varying = collect([
    mock_contract('2021-01-01', '2021-04-01'),  // ~3 months
    mock_contract('2021-06-01', '2022-03-01'),  // ~9 months
    mock_contract('2022-05-01', '2022-11-01'),  // ~6 months
]);
$resultV = $stabilityCalc->calculate($varying);

assert_not_null('Varying → stability_index not null', $resultV['stability_index']);
assert_gt('Varying → stability_index > 0', $resultV['stability_index'], 0);
assert_lte('Varying → stability_index <= 10.0', $resultV['stability_index'], 10.0);
assert_eq('Varying → contract_count = 3', 3, $resultV['contract_count']);
assert_gt('Varying → avg_months > 0', $resultV['avg_months'], 0);
assert_gt('Varying → std_months > 0', $resultV['std_months'], 0);

/// Verify formula: stability_index ≈ avg / std (capped at 10) — allow rounding tolerance
$expectedIndex = $resultV['avg_months'] / $resultV['std_months'];
assert_test(
    'Varying → index matches avg/std formula',
    abs($resultV['stability_index'] - min(10.0, $expectedIndex)) < 0.05
);

echo "    avg_months={$resultV['avg_months']}, std_months={$resultV['std_months']}, index={$resultV['stability_index']}\n";

// ══════════════════════════════════════════════════════════════════════════
// TEST 5: StabilityIndexCalculator — uniform durations → capped at 10.0
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 5. StabilityIndexCalculator: uniform durations ──\n";

$uniform = collect([
    mock_contract('2021-01-01', '2021-07-01'),  // ~6 months
    mock_contract('2022-01-01', '2022-07-01'),  // ~6 months
    mock_contract('2023-01-01', '2023-07-01'),  // ~6 months
]);
$resultU = $stabilityCalc->calculate($uniform);

assert_not_null('Uniform → stability_index not null', $resultU['stability_index']);
assert_eq('Uniform → stability_index = 10.0 (capped)', 10.0, $resultU['stability_index']);
assert_lte('Uniform → std_months ≈ 0', $resultU['std_months'], 0.01);

// ══════════════════════════════════════════════════════════════════════════
// TEST 6: RiskScoreCalculator — all-zero inputs → risk_score ≈ 0.0
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 6. RiskScoreCalculator: all-zero inputs ──\n";

$riskCalc = new RiskScoreCalculator();
$riskZero = $riskCalc->calculate(
    shortRatio: 0.0,
    totalGapMonths: 0.0,
    overlapCount: 0,
    rankAnomaly: false,
    recentUniqueCompanies3y: 0,
    stabilityIndex: 10.0, // perfectly stable → stability_inverse = 0
);

assert_lte('All-zero → risk_score ≈ 0.0 (vessel_diversity floor = 0.05)', $riskZero['risk_score'], 0.06);
echo "    risk_score={$riskZero['risk_score']}\n";

// ══════════════════════════════════════════════════════════════════════════
// TEST 7: RiskScoreCalculator — high risk inputs → score increases
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 7. RiskScoreCalculator: high risk inputs ──\n";

$riskHigh = $riskCalc->calculate(
    shortRatio: 0.8,
    totalGapMonths: 24.0,
    overlapCount: 3,
    rankAnomaly: true,
    recentUniqueCompanies3y: 7,
    stabilityIndex: 1.0, // low stability → high risk
);

assert_gt('High-risk → risk_score > zero-risk score', $riskHigh['risk_score'], $riskZero['risk_score']);
assert_gt('High-risk → risk_score > 0.5', $riskHigh['risk_score'], 0.5);
assert_lte('High-risk → risk_score <= 1.0', $riskHigh['risk_score'], 1.0);
echo "    risk_score={$riskHigh['risk_score']}\n";

// ══════════════════════════════════════════════════════════════════════════
// TEST 8: RiskScoreCalculator — all 6 factors returned
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 8. RiskScoreCalculator: all 8 factors (v1.1) ──\n";

$expectedFactors = ['short_ratio', 'gap_months', 'overlap_count', 'rank_anomaly', 'frequent_switch', 'stability_inverse', 'vessel_diversity', 'temporal_recency'];
foreach ($expectedFactors as $factor) {
    assert_test("Factor '$factor' present", isset($riskHigh['factors'][$factor]));
}
assert_eq('Exactly 8 factors returned (v1.1)', 8, count($riskHigh['factors']));

// Verify each factor has the expected structure
foreach ($riskHigh['factors'] as $name => $factor) {
    assert_test("Factor '$name' has 'raw'", array_key_exists('raw', $factor));
    assert_test("Factor '$name' has 'normalized'", array_key_exists('normalized', $factor));
    assert_test("Factor '$name' has 'weight'", array_key_exists('weight', $factor));
    assert_test("Factor '$name' has 'contribution'", array_key_exists('contribution', $factor));
}

// ══════════════════════════════════════════════════════════════════════════
// TEST 9: RiskScoreCalculator — weights sum to 1.0
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 9. RiskScoreCalculator: weights sum to 1.0 ──\n";

$weightSum = 0.0;
foreach ($riskHigh['factors'] as $factor) {
    $weightSum += $factor['weight'];
}
$weightSum = round($weightSum, 4);
assert_eq('Weights sum to 1.0', 1.0, $weightSum);

// ══════════════════════════════════════════════════════════════════════════
// TEST 10: RiskTierResolver — boundary values
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 10. RiskTierResolver: boundary values ──\n";

$tierResolver = new RiskTierResolver();

assert_eq('0.00 → low',      'low',      $tierResolver->resolve(0.00));
assert_eq('0.24 → low',      'low',      $tierResolver->resolve(0.24));
assert_eq('0.25 → medium',   'medium',   $tierResolver->resolve(0.25));
assert_eq('0.49 → medium',   'medium',   $tierResolver->resolve(0.49));
assert_eq('0.50 → high',     'high',     $tierResolver->resolve(0.50));
assert_eq('0.74 → high',     'high',     $tierResolver->resolve(0.74));
assert_eq('0.75 → critical', 'critical', $tierResolver->resolve(0.75));
assert_eq('1.00 → critical', 'critical', $tierResolver->resolve(1.00));

// ══════════════════════════════════════════════════════════════════════════
// TEST 11: StabilityRiskEngine — integration test
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 11. StabilityRiskEngine: integration ──\n";

$testEmail = 'stability_risk_test@test.example';

// Clean up any leftover test data (in case previous run failed without cleanup)
$existing = PoolCandidate::where('email', $testEmail)->first();
if ($existing) {
    CandidateContract::where('pool_candidate_id', $existing->id)->delete();
    TrustEvent::where('pool_candidate_id', $existing->id)->delete();
    CandidateTrustProfile::where('pool_candidate_id', $existing->id)->delete();
    $existing->delete();
}

// Create test candidate
$candidate = PoolCandidate::create([
    'first_name'       => 'Stability',
    'last_name'        => 'RiskTest',
    'email'            => $testEmail,
    'phone'            => '+905559999999',
    'country_code'     => 'TR',
    'preferred_language' => 'en',
    'source_channel'   => 'organic',
    'status'           => 'in_pool',
    'primary_industry' => 'maritime',
    'seafarer'         => true,
]);
assert_not_null('Test candidate created', $candidate);

// Create 5 contracts with varying durations, some short, some long, one overlapping
// Contract 1: short (~2 months) — Alpha Maritime
$c1 = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name'       => 'MV Alpha One',
    'vessel_type'       => 'bulk_carrier',
    'company_name'      => 'Alpha Maritime',
    'rank_code'         => 'AB',
    'start_date'        => Carbon::parse('2021-03-01'),
    'end_date'          => Carbon::parse('2021-05-01'),
]);

// Contract 2: long (~9 months) — Beta Shipping
$c2 = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name'       => 'MV Beta Star',
    'vessel_type'       => 'tanker',
    'company_name'      => 'Beta Shipping',
    'rank_code'         => 'AB',
    'start_date'        => Carbon::parse('2021-08-01'),
    'end_date'          => Carbon::parse('2022-05-01'),
]);

// Contract 3: medium (~6 months) — Gamma Lines
$c3 = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name'       => 'MV Gamma Wave',
    'vessel_type'       => 'container',
    'company_name'      => 'Gamma Lines',
    'rank_code'         => 'OS',
    'start_date'        => Carbon::parse('2022-07-01'),
    'end_date'          => Carbon::parse('2023-01-01'),
]);

// Contract 4: overlapping with C3, short (~2.5 months) — Delta Corp
$c4 = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name'       => 'MV Delta Force',
    'vessel_type'       => 'bulk_carrier',
    'company_name'      => 'Delta Corp',
    'rank_code'         => 'OS',
    'start_date'        => Carbon::parse('2022-12-01'),
    'end_date'          => Carbon::parse('2023-02-15'),
]);

// Contract 5: long (~8 months) — Epsilon Fleet
$c5 = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name'       => 'MV Epsilon Dawn',
    'vessel_type'       => 'tanker',
    'company_name'      => 'Epsilon Fleet',
    'rank_code'         => 'OS',
    'start_date'        => Carbon::parse('2023-06-01'),
    'end_date'          => Carbon::parse('2024-02-01'),
]);

echo "    Created candidate {$candidate->id} with 5 contracts\n";

// Run engine
$engine = app(StabilityRiskEngine::class);
$result = $engine->compute($candidate->id);

assert_not_null('Engine compute() returned a result', $result);

// Assert all expected keys present
$expectedKeys = [
    'stability_index', 'stability', 'risk_score', 'risk_tier',
    'risk_factors', 'contract_summary', 'rank_anomalies', 'flags', 'computed_at',
];
foreach ($expectedKeys as $key) {
    assert_test("Result has key '$key'", array_key_exists($key, $result));
}

// Assert stability_index > 0 (we have 5 contracts with varying durations)
assert_gt('stability_index > 0', $result['stability_index'], 0);

// Assert risk_score between 0.0 and 1.0
assert_gte('risk_score >= 0.0', $result['risk_score'], 0.0);
assert_lte('risk_score <= 1.0', $result['risk_score'], 1.0);

// Assert risk_tier is one of the valid values
$validTiers = ['low', 'medium', 'high', 'critical'];
assert_test('risk_tier is valid', in_array($result['risk_tier'], $validTiers, true));

// Assert contract_summary.total_contracts = 5
assert_eq('contract_summary.total_contracts = 5', 5, $result['contract_summary']['total_contracts']);

// Assert short_contract_count > 0 (contracts 1 and 4 are < 6 months)
assert_gt('contract_summary.short_contract_count > 0', $result['contract_summary']['short_contract_count'], 0);

// Assert overlap_count > 0 (contract 4 overlaps with contract 3)
assert_gt('contract_summary.overlap_count > 0', $result['contract_summary']['overlap_count'], 0);

echo "    stability_index={$result['stability_index']}, risk_score={$result['risk_score']}, risk_tier={$result['risk_tier']}\n";
echo "    short_contract_count={$result['contract_summary']['short_contract_count']}, overlap_count={$result['contract_summary']['overlap_count']}\n";

// Verify trust profile was persisted
$trustProfile = CandidateTrustProfile::where('pool_candidate_id', $candidate->id)->first();
assert_not_null('Trust profile exists', $trustProfile);
assert_eq('Persisted stability_index matches', $result['stability_index'], $trustProfile->stability_index);
assert_eq('Persisted risk_score matches', $result['risk_score'], $trustProfile->risk_score);
assert_eq('Persisted risk_tier matches', $result['risk_tier'], $trustProfile->risk_tier);

// Verify detail_json['stability_risk'] stored
$detailJson = $trustProfile->detail_json;
assert_test('detail_json has stability_risk key', isset($detailJson['stability_risk']));
assert_eq(
    'detail_json stability_risk.risk_score matches',
    $result['risk_score'],
    $detailJson['stability_risk']['risk_score']
);

// Verify audit event was created
$event = TrustEvent::where('pool_candidate_id', $candidate->id)
    ->where('event_type', 'stability_risk_computed')
    ->latest()
    ->first();
assert_not_null('Audit event stability_risk_computed created', $event);
assert_eq('Event payload risk_tier matches', $result['risk_tier'], $event->payload_json['risk_tier']);

// ══════════════════════════════════════════════════════════════════════════
// TEST 12: Job exists — ComputeStabilityRiskJob
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 12. Job exists: ComputeStabilityRiskJob ──\n";

assert_test('ComputeStabilityRiskJob class exists', class_exists(ComputeStabilityRiskJob::class));

$job = new ComputeStabilityRiskJob($candidate->id);
assert_test('Job implements ShouldQueue', $job instanceof \Illuminate\Contracts\Queue\ShouldQueue);

// ══════════════════════════════════════════════════════════════════════════
// TEST 13: Command exists — trust:stability:compute-pending
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 13. Command exists: trust:stability:compute-pending ──\n";

assert_test('ComputePendingStabilityRiskCommand class exists', class_exists(ComputePendingStabilityRiskCommand::class));

// Verify the command is registered via artisan
$exitCode = \Illuminate\Support\Facades\Artisan::call('trust:stability:compute-pending', [
    '--dry-run' => true,
    '--limit' => 10,
    '--force' => true,
]);
assert_eq('Artisan dry-run exit code = 0', 0, $exitCode);

$output = \Illuminate\Support\Facades\Artisan::output();
assert_test('Dry-run output contains DRY RUN or No candidates', str_contains($output, 'DRY RUN') || str_contains($output, 'No candidates'));

// Test with feature flag off
Config::set('maritime.stability_v1', false);
$exitCode2 = \Illuminate\Support\Facades\Artisan::call('trust:stability:compute-pending', [
    '--limit' => 10,
]);
assert_eq('Artisan with flag off: exit code = 0', 0, $exitCode2);
$output2 = \Illuminate\Support\Facades\Artisan::output();
assert_test('Output mentions disabled when flag off', str_contains($output2, 'disabled'));
Config::set('maritime.stability_v1', true);

// ══════════════════════════════════════════════════════════════════════════
// TEST 14: Presenter — fromTrustProfile()
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 14. StabilityRiskPresenter: fromTrustProfile() ──\n";

$presented = StabilityRiskPresenter::fromTrustProfile($trustProfile);
assert_not_null('Presenter output not null', $presented);
assert_eq('Presented stability_index matches', $result['stability_index'], $presented['stability_index']);
assert_eq('Presented risk_score matches', $result['risk_score'], $presented['risk_score']);
assert_eq('Presented risk_tier matches', $result['risk_tier'], $presented['risk_tier']);
assert_not_null('Presented has risk_factors', $presented['risk_factors']);
assert_not_null('Presented has contract_summary', $presented['contract_summary']);
assert_not_null('Presented has computed_at', $presented['computed_at']);

$presentedKeys = ['stability_index', 'stability', 'risk_score', 'risk_tier', 'risk_factors', 'contract_summary', 'rank_anomalies', 'flags', 'computed_at'];
foreach ($presentedKeys as $key) {
    assert_test("Presented has key '$key'", array_key_exists($key, $presented));
}

// Null trust profile returns null
$nullPresented = StabilityRiskPresenter::fromTrustProfile(null);
assert_null('Null trust profile → null', $nullPresented);

// Trust profile without stability_risk data returns null
$emptyProfile = new CandidateTrustProfile();
$emptyProfile->detail_json = [];
$emptyPresented = StabilityRiskPresenter::fromTrustProfile($emptyProfile);
assert_null('Empty detail_json → null', $emptyPresented);

// ══════════════════════════════════════════════════════════════════════════
// TEST 15: Cleanup
// ══════════════════════════════════════════════════════════════════════════
echo "\n── 15. Cleanup ──\n";

$cleanup = PoolCandidate::where('email', $testEmail)->first();
if ($cleanup) {
    $deletedContracts = CandidateContract::where('pool_candidate_id', $cleanup->id)->delete();
    echo "    Deleted $deletedContracts contracts\n";

    $deletedProfile = CandidateTrustProfile::where('pool_candidate_id', $cleanup->id)->delete();
    echo "    Deleted $deletedProfile trust profile(s)\n";

    $deletedEvents = TrustEvent::where('pool_candidate_id', $cleanup->id)->delete();
    echo "    Deleted $deletedEvents trust event(s)\n";

    $cleanup->delete();
    echo "    Deleted candidate {$cleanup->id}\n";
}

// Verify cleanup
$verifyCleanup = PoolCandidate::where('email', $testEmail)->first();
assert_null('Candidate fully cleaned up', $verifyCleanup);

// ══════════════════════════════════════════════════════════════════════════
// SUMMARY
// ══════════════════════════════════════════════════════════════════════════

} finally {
    DB::rollBack();
}

echo "\n═══════════════════════════════════════════════════\n";
echo "   RESULTS: $pass passed, $fail failed, $total total\n";
echo "═══════════════════════════════════════════════════\n\n";

exit($fail > 0 ? 1 : 0);
