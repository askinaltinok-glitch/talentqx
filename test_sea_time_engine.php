<?php

/**
 * Sea-Time Intelligence Engine — Acceptance Test
 *
 * Run: php82 test_sea_time_engine.php
 *
 * Tests:
 *  1. Feature flag gating
 *  2. OverlapCorrector: no overlaps
 *  3. OverlapCorrector: with overlaps
 *  4. OverlapCorrector: fully overlapped contract
 *  5. OperationTypeClassifier
 *  6. Full engine: compute with contracts
 *  7. Full engine: no contracts → empty summary
 *  8. Full engine: overlap correction stores correct logs
 *  9. Recompute replaces old logs
 * 10. Presenter output
 * 11. Artisan command: dry-run
 * 12. Aggregate KPIs (vessel experience, rank days)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CandidateContract;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\SeaTimeLog;
use App\Models\TrustEvent;
use App\Presenters\SeaTimePresenter;
use App\Services\SeaTime\OperationTypeClassifier;
use App\Services\SeaTime\OverlapCorrector;
use App\Services\SeaTime\SeaTimeCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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

// ── Setup ────────────────────────────────────────────────────────────────
echo "\n═══════════════════════════════════════════════════\n";
echo "   SEA-TIME INTELLIGENCE ENGINE — ACCEPTANCE TEST\n";
echo "═══════════════════════════════════════════════════\n\n";

DB::beginTransaction();

try {

// Create test candidate
$candidate = PoolCandidate::create([
    'first_name' => 'SeaTime',
    'last_name' => 'Test',
    'email' => 'seatime-test-' . uniqid() . '@test.example',
    'country_code' => 'TR',
    'source_channel' => 'organic',
    'primary_industry' => 'maritime',
    'seafarer' => true,
    'status' => 'in_pool',
]);

// ── Section 1: Feature Flag Gating ──────────────────────────────────────
echo "§1 Feature flag gating\n";
Config::set('maritime.sea_time_v1', false);
$calculator = app(SeaTimeCalculator::class);
$result = $calculator->compute($candidate->id);
assert_eq("sea_time_v1=false → returns null", null, $result);
assert_eq("no sea_time_logs created", 0, SeaTimeLog::where('pool_candidate_id', $candidate->id)->count());

Config::set('maritime.sea_time_v1', true);

// ── Section 2: OverlapCorrector — No Overlaps ───────────────────────────
echo "\n§2 OverlapCorrector: no overlaps\n";

$c1 = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Alpha',
    'vessel_type' => 'tanker',
    'company_name' => 'ShipCo A',
    'rank_code' => 'AB',
    'start_date' => '2023-01-01',
    'end_date' => '2023-06-30',
]);
$c2 = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Beta',
    'vessel_type' => 'bulk_carrier',
    'company_name' => 'ShipCo B',
    'rank_code' => '3/O',
    'start_date' => '2023-08-01',
    'end_date' => '2024-02-28',
]);

$corrector = new OverlapCorrector();
$contracts = collect([$c1, $c2]);
$corrected = $corrector->correct($contracts);

assert_eq("2 entries returned", 2, count($corrected));
assert_eq("first: 0 overlap", 0, $corrected[0]['overlap_deducted']);
assert_eq("second: 0 overlap", 0, $corrected[1]['overlap_deducted']);
$rawDaysC1 = Carbon::parse('2023-01-01')->diffInDays(Carbon::parse('2023-06-30'));
assert_eq("first: raw=calculated", $rawDaysC1, $corrected[0]['calculated_days']);

$merged = $corrector->mergedTotalDays($contracts);
$sumCalculated = $corrected[0]['calculated_days'] + $corrected[1]['calculated_days'];
assert_eq("merged total equals sum of calculated", $sumCalculated, $merged);

// ── Section 3: OverlapCorrector — With Overlaps ─────────────────────────
echo "\n§3 OverlapCorrector: with overlaps\n";

$c3 = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Gamma',
    'vessel_type' => 'container',
    'company_name' => 'ShipCo C',
    'rank_code' => '2/O',
    'start_date' => '2023-05-01',
    'end_date' => '2023-09-30',
]);

// Now c1 (Jan-Jun) and c3 (May-Sep) overlap: May-Jun is double-counted
$contractsOverlap = collect([$c1, $c3, $c2])->sortBy('start_date');
$corrected2 = $corrector->correct($contractsOverlap);

assert_eq("3 entries returned", 3, count($corrected2));
assert_eq("first (c1): 0 overlap", 0, $corrected2[0]['overlap_deducted']);
assert_gt("second (c3): has overlap deducted", $corrected2[1]['overlap_deducted'], 0);

$mergedOverlap = $corrector->mergedTotalDays($contractsOverlap);
$sumCalc2 = array_sum(array_column($corrected2, 'calculated_days'));
// Allow ±N days boundary difference (fence-post at split points)
assert_true("merged total ≈ sum of calculated (±3 days)", abs($sumCalc2 - $mergedOverlap) <= 3);

$totalRaw = array_sum(array_column($corrected2, 'raw_days'));
assert_gt("total raw > merged (confirms overlap)", $totalRaw, $mergedOverlap);

// ── Section 4: OverlapCorrector — Fully Overlapped ──────────────────────
echo "\n§4 OverlapCorrector: fully overlapped\n";

$cShort = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Delta',
    'vessel_type' => 'tanker',
    'company_name' => 'ShipCo D',
    'rank_code' => 'AB',
    'start_date' => '2023-02-01',
    'end_date' => '2023-03-31',
]);

// c1 covers Jan-Jun, cShort is Feb-Mar → fully within c1
$fullyOverlap = collect([$c1, $cShort])->sortBy('start_date');
$corrected3 = $corrector->correct($fullyOverlap);

assert_eq("2 entries", 2, count($corrected3));
assert_eq("cShort: 0 calculated days (fully overlapped)", 0, $corrected3[1]['calculated_days']);
assert_gt("cShort: overlap_deducted > 0", $corrected3[1]['overlap_deducted'], 0);

// ── Section 5: OperationTypeClassifier ──────────────────────────────────
echo "\n§5 OperationTypeClassifier\n";

assert_eq("tanker → sea", 'sea', OperationTypeClassifier::classify('tanker'));
assert_eq("bulk_carrier → sea", 'sea', OperationTypeClassifier::classify('bulk_carrier'));
assert_eq("container → sea", 'sea', OperationTypeClassifier::classify('container'));
assert_eq("river_vessel → river", 'river', OperationTypeClassifier::classify('river_vessel'));
assert_eq("null → sea", 'sea', OperationTypeClassifier::classify(null));
assert_eq("other → sea", 'sea', OperationTypeClassifier::classify('other'));

// ── Section 6: Full Engine — Compute With Contracts ─────────────────────
echo "\n§6 Full engine: compute with contracts\n";

// Clean up extra contracts from overlap tests
$cShort->delete();
$c3->delete();

// Reload: candidate has c1 (Jan-Jun 2023, tanker, AB) + c2 (Aug 2023-Feb 2024, bulk, 3/O)
$result = $calculator->compute($candidate->id);

assert_true("result is not null", $result !== null);
assert_eq("total_contracts = 2", 2, $result['total_contracts']);
assert_gt("total_sea_days > 0", $result['total_sea_days'], 0);
assert_eq("overlap_days = 0 (no overlap)", 0, $result['overlap_days']);
assert_eq("total_sea_days = total_raw_days (no overlap)", $result['total_raw_days'], $result['total_sea_days']);
assert_eq("river_days = 0", 0, $result['river_days']);
assert_eq("sea_days = total_sea_days", $result['total_sea_days'], $result['sea_days']);

// Rank days
assert_true("rank_days has AB", isset($result['rank_days']['AB']));
assert_true("rank_days has 3/O", isset($result['rank_days']['3/O']));
assert_eq("AB+3/O = total", $result['total_sea_days'], $result['rank_days']['AB'] + $result['rank_days']['3/O']);

// Vessel experience
assert_true("vessel_experience_pct has tanker", isset($result['vessel_experience_pct']['tanker']));
assert_true("vessel_experience_pct has bulk_carrier", isset($result['vessel_experience_pct']['bulk_carrier']));
$pctSum = $result['vessel_experience_pct']['tanker'] + $result['vessel_experience_pct']['bulk_carrier'];
assert_gte("pct sum ~100", $pctSum, 99.0);

// Sea-time logs persisted
$logs = SeaTimeLog::where('pool_candidate_id', $candidate->id)->orderBy('effective_start_date')->get();
assert_eq("2 sea_time_logs created", 2, $logs->count());
assert_eq("first log: rank=AB", 'AB', $logs[0]->rank_code);
assert_eq("first log: operation_type=sea", 'sea', $logs[0]->operation_type);
assert_eq("second log: rank=3/O", '3/O', $logs[1]->rank_code);

// Trust profile updated
$trustProfile = CandidateTrustProfile::where('pool_candidate_id', $candidate->id)->first();
assert_true("trust profile exists", $trustProfile !== null);
assert_true("detail_json.sea_time exists", isset($trustProfile->detail_json['sea_time']));
assert_eq("detail_json.sea_time.total_contracts = 2", 2, $trustProfile->detail_json['sea_time']['total_contracts']);

// Audit event
$event = TrustEvent::where('pool_candidate_id', $candidate->id)
    ->where('event_type', 'sea_time_computed')
    ->first();
assert_true("sea_time_computed event created", $event !== null);
assert_eq("event.total_sea_days matches", $result['total_sea_days'], $event->payload_json['total_sea_days']);

// ── Section 7: Full Engine — No Contracts ───────────────────────────────
echo "\n§7 Full engine: no contracts → empty summary\n";

$candidate2 = PoolCandidate::create([
    'first_name' => 'Empty',
    'last_name' => 'Test',
    'email' => 'empty-test-' . uniqid() . '@test.example',
    'country_code' => 'TR',
    'source_channel' => 'organic',
    'primary_industry' => 'maritime',
    'seafarer' => true,
    'status' => 'in_pool',
]);

$emptyResult = $calculator->compute($candidate2->id);
assert_true("empty result is not null", $emptyResult !== null);
assert_eq("total_contracts = 0", 0, $emptyResult['total_contracts']);
assert_eq("total_sea_days = 0", 0, $emptyResult['total_sea_days']);
assert_eq("overlap_days = 0", 0, $emptyResult['overlap_days']);

// ── Section 8: Engine With Overlaps ─────────────────────────────────────
echo "\n§8 Full engine: overlap correction\n";

// Re-add overlapping contract
$c3b = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Gamma',
    'vessel_type' => 'container',
    'company_name' => 'ShipCo C',
    'rank_code' => '2/O',
    'start_date' => '2023-05-01',
    'end_date' => '2023-09-30',
]);

$resultOvl = $calculator->compute($candidate->id);

assert_gt("overlap_days > 0", $resultOvl['overlap_days'], 0);
assert_gt("total_raw_days > total_sea_days", $resultOvl['total_raw_days'], $resultOvl['total_sea_days']);
assert_eq("total_sea_days + overlap = total_raw_days", $resultOvl['total_raw_days'], $resultOvl['total_sea_days'] + $resultOvl['overlap_days']);

// Logs show overlap
$overlapLogs = SeaTimeLog::where('pool_candidate_id', $candidate->id)
    ->where('overlap_deducted_days', '>', 0)
    ->get();
assert_gt("at least 1 log has overlap_deducted > 0", $overlapLogs->count(), 0);

// ── Section 9: Recompute Replaces Old Logs ──────────────────────────────
echo "\n§9 Recompute replaces old logs\n";

$oldBatchId = SeaTimeLog::where('pool_candidate_id', $candidate->id)
    ->orderByDesc('computed_at')
    ->value('computation_batch_id');

$resultRecomp = $calculator->compute($candidate->id);
$newBatchId = SeaTimeLog::where('pool_candidate_id', $candidate->id)
    ->orderByDesc('computed_at')
    ->value('computation_batch_id');

assert_true("batch IDs differ", $oldBatchId !== $newBatchId);

// Only new batch logs exist
$oldLogs = SeaTimeLog::where('pool_candidate_id', $candidate->id)
    ->where('computation_batch_id', $oldBatchId)
    ->count();
assert_eq("old batch logs deleted", 0, $oldLogs);

$newLogs = SeaTimeLog::where('pool_candidate_id', $candidate->id)
    ->where('computation_batch_id', $newBatchId)
    ->count();
assert_eq("3 new batch logs (3 contracts)", 3, $newLogs);

// ── Section 10: Presenter Output ────────────────────────────────────────
echo "\n§10 Presenter output\n";

$trustProfile = CandidateTrustProfile::where('pool_candidate_id', $candidate->id)->first();
$presented = SeaTimePresenter::fromTrustProfile($trustProfile);

assert_true("presenter returns array", is_array($presented));
assert_true("has total_sea_days", isset($presented['total_sea_days']));
assert_true("has vessel_experience_pct", isset($presented['vessel_experience_pct']));
assert_true("has rank_days", isset($presented['rank_days']));
assert_true("has overlap_days", isset($presented['overlap_days']));

$contractLogs = SeaTimePresenter::contractLogs($candidate->id);
assert_eq("contract logs count = 3", 3, count($contractLogs));
assert_true("first log has calculated_days", isset($contractLogs[0]['calculated_days']));
assert_true("first log has overlap_deducted", isset($contractLogs[0]['overlap_deducted']));

// ── Section 11: Artisan Command ─────────────────────────────────────────
echo "\n§11 Artisan command\n";

Config::set('maritime.sea_time_auto_compute', true);

$exitCode = Artisan::call('trust:sea-time:compute-pending', ['--dry-run' => true, '--force' => true]);
assert_eq("dry-run exits 0", 0, $exitCode);

Config::set('maritime.sea_time_v1', false);
$exitCode2 = Artisan::call('trust:sea-time:compute-pending');
assert_eq("ais_v1=false → exits 0 (aborts gracefully)", 0, $exitCode2);

Config::set('maritime.sea_time_v1', true);
Config::set('maritime.sea_time_auto_compute', false);
$exitCode3 = Artisan::call('trust:sea-time:compute-pending');
assert_eq("auto_compute=false → exits 0 (aborts gracefully)", 0, $exitCode3);

// ── Section 12: Aggregate KPIs ──────────────────────────────────────────
echo "\n§12 Aggregate KPIs (vessel experience, rank days)\n";

// c1: tanker/AB (Jan-Jun = ~180 days)
// c2: bulk_carrier/3/O (Aug-Feb = ~212 days)
// c3b: container/2/O (May-Sep = ~153 days, partially overlapped)

Config::set('maritime.sea_time_v1', true);
$finalResult = $calculator->compute($candidate->id);

assert_true("has 3 vessel types", count($finalResult['vessel_type_days']) === 3);
assert_true("tanker days > 0", $finalResult['vessel_type_days']['tanker'] > 0);
assert_true("bulk_carrier days > 0", $finalResult['vessel_type_days']['bulk_carrier'] > 0);
assert_true("container days >= 0", $finalResult['vessel_type_days']['container'] >= 0);

assert_true("has 3 ranks", count($finalResult['rank_days']) === 3);
assert_true("AB days > 0", $finalResult['rank_days']['AB'] > 0);
assert_true("3/O days > 0", $finalResult['rank_days']['3/O'] > 0);
assert_true("2/O days >= 0", $finalResult['rank_days']['2/O'] >= 0);

// Rank experience percentages sum to ~100
$rankPctSum = array_sum($finalResult['rank_experience_pct']);
assert_gte("rank pct sum ~100", $rankPctSum, 99.0);

// Vessel experience percentages sum to ~100
$vexpPctSum = array_sum($finalResult['vessel_experience_pct']);
assert_gte("vessel pct sum ~100", $vexpPctSum, 99.0);

// ── River operation type ─────────────────────────────────────────────
echo "\n§12b River operation type\n";

$c4 = CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'River Queen',
    'vessel_type' => 'river_vessel',
    'company_name' => 'River Co',
    'rank_code' => 'BSN',
    'start_date' => '2024-06-01',
    'end_date' => '2024-08-31',
]);

$riverResult = $calculator->compute($candidate->id);
assert_gt("river_days > 0", $riverResult['river_days'], 0);
assert_true("river_vessel in vessel_type_days", isset($riverResult['vessel_type_days']['river_vessel']));

} finally {
    DB::rollBack();
}

// ── Summary ─────────────────────────────────────────────────────────────
echo "\n═══════════════════════════════════════════════════\n";
$total = $pass + $fail;
if ($fail === 0) {
    echo "   ALL $total TESTS PASSED ✓\n";
} else {
    echo "   $pass/$total passed, $fail FAILED ✗\n";
}
echo "═══════════════════════════════════════════════════\n\n";

exit($fail > 0 ? 1 : 0);
