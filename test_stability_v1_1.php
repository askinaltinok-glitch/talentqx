<?php

/**
 * Stability & Risk Engine v1.1 — Acceptance Test
 *
 * Run: php82 test_stability_v1_1.php
 *
 * Tests all 6 phases of the v1.1 upgrade:
 *   Phase 1: StabilityConfig centralized config resolver
 *   Phase 2: Rank-aware short contract evaluation
 *   Phase 3: Promotion window context penalty reduction
 *   Phase 4: Temporal decay weighting
 *   Phase 5: Vessel diversity factor
 *   Phase 6: Fleet profile overrides
 *   Integration: Full engine run with new features
 *   Before/After: Same candidate, v1 defaults vs v1.1 context-aware
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Stability\StabilityConfig;
use App\Services\Stability\StabilityIndexCalculator;
use App\Services\Stability\RiskScoreCalculator;
use App\Services\Stability\RiskTierResolver;
use App\Services\Stability\PromotionContextAnalyzer;
use App\Services\Stability\TemporalDecayCalculator;
use App\Services\Stability\VesselDiversityCalculator;
use App\Services\Stability\StabilityRiskEngine;
use App\Services\Trust\ContractPatternAnalyzer;
use App\Services\Trust\RankProgressionAnalyzer;
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

function assert_lt(string $msg, float|int $value, float|int $threshold): void {
    global $pass, $fail, $total;
    $total++;
    if ($value < $threshold) { $pass++; echo "  ✓ $msg (value=$value)\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (value=$value >= threshold=$threshold)\n"; }
}

function assert_between(string $msg, float $value, float $min, float $max): void {
    global $pass, $fail, $total;
    $total++;
    if ($value >= $min && $value <= $max) { $pass++; echo "  ✓ $msg (value=$value)\n"; }
    else { $fail++; echo "  ✗ FAIL: $msg (value=$value not in [$min, $max])\n"; }
}

/**
 * Build a mock contract-like object for unit tests.
 */
function mock_contract(string $start, ?string $end, string $rank = 'AB', string $vesselType = 'bulk_carrier', string $company = 'Test Co'): object {
    static $idCounter = 0;
    $idCounter++;
    $startDate = Carbon::parse($start);
    $endDate = $end ? Carbon::parse($end) : null;
    return new class($startDate, $endDate, $rank, $vesselType, $company, 'mock-' . $idCounter) {
        public Carbon $start_date;
        public ?Carbon $end_date;
        public string $rank_code;
        public string $vessel_type;
        public string $company_name;
        public string $id;
        public function __construct(Carbon $start, ?Carbon $end, string $rank, string $vesselType, string $company, string $id) {
            $this->start_date = $start;
            $this->end_date = $end;
            $this->rank_code = $rank;
            $this->vessel_type = $vesselType;
            $this->company_name = $company;
            $this->id = $id;
        }
        public function durationMonths(): float {
            $end = $this->end_date ?? Carbon::now();
            return round($this->start_date->diffInDays($end) / 30.44, 1);
        }
    };
}

// ── Setup ────────────────────────────────────────────────────────────────
echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "   STABILITY & RISK ENGINE v1.1 — ACCEPTANCE TEST\n";
echo "   Context-Aware, Configurable, Fleet-Ready\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

DB::beginTransaction();

try {

Config::set('maritime.stability_v1', true);
Config::set('maritime.stability_auto_compute', true);

// ══════════════════════════════════════════════════════════════════════════
// PHASE 1: StabilityConfig — Centralized Config Resolver
// ══════════════════════════════════════════════════════════════════════════
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PHASE 1: StabilityConfig\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "── 1a. Base config values ──\n";
$cfg = new StabilityConfig();

assert_eq('short_contract_months = 6', 6, $cfg->get('short_contract_months'));
assert_eq('gap_months_flag_threshold = 18', 18, $cfg->get('gap_months_flag_threshold'));
assert_eq('frequent_switch_flag_threshold = 6', 6, $cfg->get('frequent_switch_flag_threshold'));
assert_eq('stability_index_min_contracts = 2', 2, $cfg->get('stability_index_min_contracts'));
assert_eq('stability_index_max_cap = 10.0', 10.0, $cfg->get('stability_index_max_cap'));

echo "\n── 1b. Factor weights ──\n";
$weights = $cfg->getFactorWeights();
assert_eq('8 factor weights', 8, count($weights));
$weightSum = array_sum($weights);
assert_between('Weights sum ≈ 1.0', $weightSum, 0.99, 1.01);
assert_test('Has vessel_diversity weight', isset($weights['vessel_diversity']));
assert_test('Has temporal_recency weight', isset($weights['temporal_recency']));

echo "\n── 1c. Risk tier thresholds ──\n";
$tiers = $cfg->getRiskTierThresholds();
assert_eq('3 tier thresholds', 3, count($tiers));
assert_eq('critical = 0.75', 0.75, $tiers['critical']);
assert_eq('high = 0.50', 0.50, $tiers['high']);
assert_eq('medium = 0.25', 0.25, $tiers['medium']);

echo "\n── 1d. Rank-specific short contract months ──\n";
assert_eq('DC (cadet) = 3 months', 3, $cfg->getShortContractMonths('DC'));
assert_eq('AB (rating) = 4 months', 4, $cfg->getShortContractMonths('AB'));
assert_eq('MASTER = 9 months', 9, $cfg->getShortContractMonths('MASTER'));
assert_eq('C/E = 9 months', 9, $cfg->getShortContractMonths('C/E'));
assert_eq('Unknown rank → default 6', 6, $cfg->getShortContractMonths('UNKNOWN'));
assert_eq('Null rank → default 6', 6, $cfg->getShortContractMonths(null));

echo "\n── 1e. Temporal decay config ──\n";
$decay = $cfg->getTemporalDecay();
assert_eq('recent_months = 36', 36, $decay['recent_months']);
assert_eq('old_months = 60', 60, $decay['old_months']);
assert_eq('recent_weight = 1.5', 1.5, $decay['recent_weight']);
assert_eq('old_weight = 0.5', 0.5, $decay['old_weight']);

echo "\n── 1f. Vessel diversity config ──\n";
$vd = $cfg->getVesselDiversity();
assert_eq('min_types_for_bonus = 2', 2, $vd['min_types_for_bonus']);
assert_eq('max_types_for_bonus = 5', 5, $vd['max_types_for_bonus']);
assert_eq('min_tenure_months = 6', 6, $vd['min_tenure_months']);

echo "\n── 1g. getNested() ──\n";
assert_eq('getNested temporal_decay.recent_weight', 1.5, $cfg->getNested('temporal_decay', 'recent_weight'));
assert_eq('getNested vessel_diversity.min_tenure_months', 6, $cfg->getNested('vessel_diversity', 'min_tenure_months'));

// ══════════════════════════════════════════════════════════════════════════
// PHASE 2: Rank-Aware Short Contract Evaluation
// ══════════════════════════════════════════════════════════════════════════
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PHASE 2: Rank-Aware Short Contracts\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$analyzer = new ContractPatternAnalyzer();

echo "── 2a. Cadet (DC) — 3-month threshold ──\n";
// A 4-month contract for a cadet should NOT be short (threshold=3)
$cadetContracts = collect([
    mock_contract('2024-01-01', '2024-05-01', 'DC', 'bulk_carrier', 'Alpha Co'),  // ~4 months
    mock_contract('2024-06-01', '2024-08-15', 'DC', 'bulk_carrier', 'Alpha Co'),  // ~2.5 months (short)
]);
$cadetResult = $analyzer->analyze($cadetContracts, $cfg);
assert_eq('Cadet: 1 short contract (2.5mo < 3mo threshold)', 1, $cadetResult['short_contract_count']);
assert_eq('Cadet: short_ratio = 0.5', 0.5, $cadetResult['short_contract_ratio']);

echo "\n── 2b. MASTER — 9-month threshold ──\n";
// A 7-month contract for MASTER is short (threshold=9), but not for AB (threshold=4)
$masterContracts = collect([
    mock_contract('2023-01-01', '2023-08-01', 'MASTER', 'tanker', 'Beta Co'),   // ~7 months (short for MASTER)
    mock_contract('2023-10-01', '2024-08-01', 'MASTER', 'tanker', 'Beta Co'),   // ~10 months (ok)
]);
$masterResult = $analyzer->analyze($masterContracts, $cfg);
assert_eq('MASTER: 1 short (7mo < 9mo threshold)', 1, $masterResult['short_contract_count']);

echo "\n── 2c. AB — 4-month threshold ──\n";
// Same 7-month duration for AB is NOT short (threshold=4)
$abContracts = collect([
    mock_contract('2023-01-01', '2023-08-01', 'AB', 'tanker', 'Beta Co'),      // ~7 months (ok for AB)
    mock_contract('2023-10-01', '2024-01-01', 'AB', 'tanker', 'Beta Co'),      // ~3 months (short for AB)
]);
$abResult = $analyzer->analyze($abContracts, $cfg);
assert_eq('AB: 1 short (3mo < 4mo threshold)', 1, $abResult['short_contract_count']);

echo "\n── 2d. Mixed ranks ──\n";
$mixedContracts = collect([
    mock_contract('2022-01-01', '2022-04-01', 'DC', 'bulk_carrier', 'Co A'),     // 3mo — NOT short for DC (threshold 3)
    mock_contract('2022-06-01', '2022-08-01', 'AB', 'bulk_carrier', 'Co B'),     // 2mo — short for AB (threshold 4)
    mock_contract('2023-01-01', '2023-09-01', 'MASTER', 'tanker', 'Co C'),       // 8mo — short for MASTER (threshold 9)
    mock_contract('2024-01-01', '2024-12-01', 'MASTER', 'tanker', 'Co C'),       // 11mo — ok for MASTER
]);
$mixedResult = $analyzer->analyze($mixedContracts, $cfg);
assert_eq('Mixed: 2 short contracts (AB 2mo + MASTER 8mo)', 2, $mixedResult['short_contract_count']);

// ══════════════════════════════════════════════════════════════════════════
// PHASE 3: Promotion Window Context
// ══════════════════════════════════════════════════════════════════════════
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PHASE 3: Promotion Window Context\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$promotionAnalyzer = new PromotionContextAnalyzer();

echo "── 3a. Non-existent candidate (fail-open) ──\n";
$promoResult = $promotionAnalyzer->analyze('00000000-0000-0000-0000-000000000000', $cfg);
assert_test('Non-existent → not in promotion window', !$promoResult['in_promotion_window']);
assert_eq('Non-existent → modifier = 1.0', 1.0, $promoResult['modifier']);

echo "\n── 3b. Config values ──\n";
assert_eq('promotion_window_months = 12', 12, (int) $cfg->get('promotion_window_months'));
assert_eq('promotion_penalty_modifier = 0.5', 0.5, (float) $cfg->get('promotion_penalty_modifier'));

echo "\n── 3c. Modifier applied to RiskScoreCalculator ──\n";
$riskCalc = new RiskScoreCalculator();
// Without modifier
$noModifier = $riskCalc->calculate(
    shortRatio: 0.6, totalGapMonths: 0, overlapCount: 0,
    rankAnomaly: false, recentUniqueCompanies3y: 5, stabilityIndex: 5.0,
    promotionModifier: 1.0, cfg: $cfg,
);
// With modifier (0.5 = reduce short_ratio and frequent_switch penalties by 50%)
$withModifier = $riskCalc->calculate(
    shortRatio: 0.6, totalGapMonths: 0, overlapCount: 0,
    rankAnomaly: false, recentUniqueCompanies3y: 5, stabilityIndex: 5.0,
    promotionModifier: 0.5, cfg: $cfg,
);
assert_lt('Promotion modifier reduces risk score', $withModifier['risk_score'], $noModifier['risk_score']);
echo "    Without modifier: {$noModifier['risk_score']}, With modifier: {$withModifier['risk_score']}\n";

// Verify short_ratio contribution reduced
$shortNoMod = $noModifier['factors']['short_ratio']['contribution'];
$shortWithMod = $withModifier['factors']['short_ratio']['contribution'];
assert_lt('short_ratio contribution reduced by modifier', $shortWithMod, $shortNoMod);

// Verify frequent_switch contribution reduced
$switchNoMod = $noModifier['factors']['frequent_switch']['contribution'];
$switchWithMod = $withModifier['factors']['frequent_switch']['contribution'];
assert_lt('frequent_switch contribution reduced by modifier', $switchWithMod, $switchNoMod);

// ══════════════════════════════════════════════════════════════════════════
// PHASE 4: Temporal Decay
// ══════════════════════════════════════════════════════════════════════════
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PHASE 4: Temporal Decay\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$temporalCalc = new TemporalDecayCalculator();

echo "── 4a. Empty contracts ──\n";
$emptyTemporal = $temporalCalc->calculate(collect([]), $cfg);
assert_eq('Empty → temporal_recency_score = 0.0', 0.0, $emptyTemporal['temporal_recency_score']);

echo "\n── 4b. All recent contracts (within 36 months) ──\n";
$recentContracts = collect([
    mock_contract('2024-01-01', '2024-04-01', 'AB'),  // ~3 months (short)
    mock_contract('2024-06-01', '2024-09-01', 'AB'),  // ~3 months (short)
    mock_contract('2025-01-01', '2025-04-01', 'AB'),  // ~3 months (short)
]);
$recentResult = $temporalCalc->calculate($recentContracts, $cfg);
assert_gt('All recent → temporal_recency_score > 0', $recentResult['temporal_recency_score'], 0);
echo "    temporal_recency_score={$recentResult['temporal_recency_score']}\n";
echo "    recent_short_ratio={$recentResult['recent_short_ratio']}\n";

echo "\n── 4c. All old contracts (> 60 months ago) ──\n";
$oldContracts = collect([
    mock_contract('2018-01-01', '2018-04-01', 'AB'),  // ~3 months (short)
    mock_contract('2018-06-01', '2018-09-01', 'AB'),  // ~3 months (short)
    mock_contract('2019-01-01', '2019-04-01', 'AB'),  // ~3 months (short)
]);
$oldResult = $temporalCalc->calculate($oldContracts, $cfg);
echo "    temporal_recency_score={$oldResult['temporal_recency_score']}\n";
echo "    old_short_ratio={$oldResult['old_short_ratio']}\n";

echo "\n── 4d. Weights applied per contract ──\n";
assert_eq('Recent: 3 weights applied', 3, count($recentResult['weights_applied']));
foreach ($recentResult['weights_applied'] as $w) {
    assert_eq("Recent contract bucket = 'recent'", 'recent', $w['bucket']);
    assert_eq('Recent contract weight = 1.5', 1.5, $w['weight']);
}
foreach ($oldResult['weights_applied'] as $w) {
    assert_eq("Old contract bucket = 'old'", 'old', $w['bucket']);
    assert_eq('Old contract weight = 0.5', 0.5, $w['weight']);
}

echo "\n── 4e. Mixed temporal — recent short contracts score higher ──\n";
$mixedTemporal = collect([
    mock_contract('2019-01-01', '2019-04-01', 'AB'),   // old, 3mo (short)
    mock_contract('2021-06-01', '2022-06-01', 'AB'),   // middle, 12mo (not short)
    mock_contract('2025-01-01', '2025-04-01', 'AB'),   // recent, 3mo (short)
]);
$mixedTemporalResult = $temporalCalc->calculate($mixedTemporal, $cfg);
echo "    mixed: temporal_recency_score={$mixedTemporalResult['temporal_recency_score']}\n";
assert_gt('Mixed temporal: score > 0', $mixedTemporalResult['temporal_recency_score'], 0);

// ══════════════════════════════════════════════════════════════════════════
// PHASE 5: Vessel Diversity
// ══════════════════════════════════════════════════════════════════════════
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PHASE 5: Vessel Diversity\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$diversityCalc = new VesselDiversityCalculator();

echo "── 5a. Empty contracts ──\n";
$emptyDiv = $diversityCalc->calculate(collect([]), $cfg);
assert_eq('Empty → diversity_score = 0.0', 0.0, $emptyDiv['vessel_diversity_score']);

echo "\n── 5b. Single vessel type ──\n";
$singleType = collect([
    mock_contract('2023-01-01', '2023-09-01', 'AB', 'bulk_carrier'),
    mock_contract('2024-01-01', '2024-08-01', 'AB', 'bulk_carrier'),
]);
$singleResult = $diversityCalc->calculate($singleType, $cfg);
assert_eq('Single type → diversity_score = 0.0', 0.0, $singleResult['vessel_diversity_score']);
assert_eq('Single type → qualifying_types = 1', 1, $singleResult['qualifying_types']);
assert_eq('Single type → total_types = 1', 1, $singleResult['total_types']);

echo "\n── 5c. Two types with tenure → bonus ──\n";
$twoTypes = collect([
    mock_contract('2023-01-01', '2023-09-01', 'AB', 'bulk_carrier'),    // 8mo bulk
    mock_contract('2024-01-01', '2024-08-01', 'AB', 'tanker'),          // 7mo tanker
]);
$twoResult = $diversityCalc->calculate($twoTypes, $cfg);
assert_gt('Two types with tenure → diversity_score > 0', $twoResult['vessel_diversity_score'], 0);
assert_eq('Two types → qualifying_types = 2', 2, $twoResult['qualifying_types']);
echo "    diversity_score={$twoResult['vessel_diversity_score']}\n";

echo "\n── 5d. Three types with tenure → higher bonus ──\n";
$threeTypes = collect([
    mock_contract('2022-01-01', '2022-09-01', 'AB', 'bulk_carrier'),    // 8mo
    mock_contract('2023-01-01', '2023-09-01', 'AB', 'tanker'),          // 8mo
    mock_contract('2024-01-01', '2024-09-01', 'AB', 'container'),       // 8mo
]);
$threeResult = $diversityCalc->calculate($threeTypes, $cfg);
assert_gt('Three types → higher score than two types', $threeResult['vessel_diversity_score'], $twoResult['vessel_diversity_score']);
assert_eq('Three types → qualifying_types = 3', 3, $threeResult['qualifying_types']);
echo "    diversity_score={$threeResult['vessel_diversity_score']}\n";

echo "\n── 5e. Two types WITHOUT tenure → no bonus ──\n";
$noTenure = collect([
    mock_contract('2023-01-01', '2023-03-01', 'AB', 'bulk_carrier'),    // 2mo (< 6mo min tenure)
    mock_contract('2024-01-01', '2024-03-01', 'AB', 'tanker'),          // 2mo (< 6mo min tenure)
]);
$noTenureResult = $diversityCalc->calculate($noTenure, $cfg);
assert_eq('No tenure → diversity_score = 0.0', 0.0, $noTenureResult['vessel_diversity_score']);
assert_eq('No tenure → qualifying_types = 0', 0, $noTenureResult['qualifying_types']);

echo "\n── 5f. 'other' vessel type is excluded ──\n";
$otherType = collect([
    mock_contract('2023-01-01', '2023-09-01', 'AB', 'other'),           // 8mo but 'other' excluded
    mock_contract('2024-01-01', '2024-09-01', 'AB', 'bulk_carrier'),    // 8mo
]);
$otherResult = $diversityCalc->calculate($otherType, $cfg);
assert_eq("'other' excluded → only 1 qualifying type", 1, $otherResult['qualifying_types']);
assert_eq("'other' excluded → diversity = 0", 0.0, $otherResult['vessel_diversity_score']);

echo "\n── 5g. Max diversity cap ──\n";
$manyTypes = collect([
    mock_contract('2020-01-01', '2020-09-01', 'AB', 'bulk_carrier'),
    mock_contract('2021-01-01', '2021-09-01', 'AB', 'tanker'),
    mock_contract('2022-01-01', '2022-09-01', 'AB', 'container'),
    mock_contract('2023-01-01', '2023-09-01', 'AB', 'lng_lpg'),
    mock_contract('2024-01-01', '2024-09-01', 'AB', 'chemical'),
    mock_contract('2025-01-01', '2025-09-01', 'AB', 'ro_ro'),          // 6th type (> max_types=5)
]);
$manyResult = $diversityCalc->calculate($manyTypes, $cfg);
assert_lte('Max diversity → score <= max_score (1.0)', $manyResult['vessel_diversity_score'], 1.0);
echo "    6 types: diversity_score={$manyResult['vessel_diversity_score']}\n";

echo "\n── 5h. Diversity reduces risk (in RiskScoreCalculator) ──\n";
// No diversity
$noDivRisk = $riskCalc->calculate(
    shortRatio: 0.3, totalGapMonths: 6, overlapCount: 0,
    rankAnomaly: false, recentUniqueCompanies3y: 2, stabilityIndex: 5.0,
    vesselDiversityScore: 0.0, cfg: $cfg,
);
// With diversity
$withDivRisk = $riskCalc->calculate(
    shortRatio: 0.3, totalGapMonths: 6, overlapCount: 0,
    rankAnomaly: false, recentUniqueCompanies3y: 2, stabilityIndex: 5.0,
    vesselDiversityScore: 0.8, cfg: $cfg,
);
assert_lt('Vessel diversity reduces risk score', $withDivRisk['risk_score'], $noDivRisk['risk_score']);
echo "    No diversity: {$noDivRisk['risk_score']}, With diversity: {$withDivRisk['risk_score']}\n";

// ══════════════════════════════════════════════════════════════════════════
// PHASE 6: Fleet Profile Overrides
// ══════════════════════════════════════════════════════════════════════════
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PHASE 6: Fleet Profile Overrides\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "── 6a. Tanker fleet profile ──\n";
$tankerCfg = new StabilityConfig('tanker');
assert_eq('Tanker: short_contract_months = 5', 5, $tankerCfg->get('short_contract_months'));
$tankerWeights = $tankerCfg->getFactorWeights();
assert_eq('Tanker: short_ratio weight = 0.15', 0.15, $tankerWeights['short_ratio']);
assert_eq('Tanker: gap_months weight = 0.20', 0.20, $tankerWeights['gap_months']);
// Non-overridden values fall back to base
assert_eq('Tanker: rank_anomaly weight = 0.12 (base)', 0.12, $tankerWeights['rank_anomaly']);
assert_eq('Tanker fleet type', 'tanker', $tankerCfg->getFleetType());

echo "\n── 6b. Bulk fleet profile ──\n";
$bulkCfg = new StabilityConfig('bulk');
assert_eq('Bulk: short_contract_months = 7', 7, $bulkCfg->get('short_contract_months'));
// Bulk only overrides short_contract_months, no weight changes
$bulkWeights = $bulkCfg->getFactorWeights();
assert_eq('Bulk: short_ratio weight = 0.20 (base)', 0.20, $bulkWeights['short_ratio']);

echo "\n── 6c. River fleet profile ──\n";
$riverCfg = new StabilityConfig('river');
assert_eq('River: short_contract_months = 3', 3, $riverCfg->get('short_contract_months'));
$riverTiers = $riverCfg->getRiskTierThresholds();
assert_eq('River: critical tier = 0.80', 0.80, $riverTiers['critical']);
assert_eq('River: high tier = 0.60', 0.60, $riverTiers['high']);
assert_eq('River: medium tier = 0.30', 0.30, $riverTiers['medium']);

echo "\n── 6d. Unknown fleet → falls back to base ──\n";
$unknownCfg = new StabilityConfig('yacht');
assert_eq('Unknown fleet: short_contract_months = 6 (base)', 6, $unknownCfg->get('short_contract_months'));

echo "\n── 6e. withFleet() creates new instance ──\n";
$base = new StabilityConfig();
$tankerVia = $base->withFleet('tanker');
assert_eq('withFleet returns tanker config', 'tanker', $tankerVia->getFleetType());
assert_eq('Original config unchanged', null, $base->getFleetType());

echo "\n── 6f. River fleet relaxes risk tiers ──\n";
$riverTierResolver = new RiskTierResolver();
// Score 0.55 → 'high' normally, but 'medium' in river (medium boundary = 0.30, high = 0.60)
assert_eq('0.55 → high (base)', 'high', $riverTierResolver->resolve(0.55, $cfg));
assert_eq('0.55 → medium (river)', 'medium', $riverTierResolver->resolve(0.55, $riverCfg));

// ══════════════════════════════════════════════════════════════════════════
// PHASE 7: RiskScoreCalculator — All 8 Factors
// ══════════════════════════════════════════════════════════════════════════
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PHASE 7: RiskScoreCalculator — 8 Factors\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "── 7a. All 8 factors returned ──\n";
$fullRisk = $riskCalc->calculate(
    shortRatio: 0.4, totalGapMonths: 12, overlapCount: 1,
    rankAnomaly: false, recentUniqueCompanies3y: 3, stabilityIndex: 4.0,
    temporalRecencyScore: 0.6, vesselDiversityScore: 0.5,
    promotionModifier: 1.0, cfg: $cfg,
);
$expectedFactors = [
    'short_ratio', 'gap_months', 'overlap_count', 'rank_anomaly',
    'frequent_switch', 'stability_inverse', 'vessel_diversity', 'temporal_recency',
];
foreach ($expectedFactors as $f) {
    assert_test("Factor '$f' present", isset($fullRisk['factors'][$f]));
}
assert_eq('Exactly 8 factors', 8, count($fullRisk['factors']));

echo "\n── 7b. Weight sum = 1.0 ──\n";
$wSum = 0.0;
foreach ($fullRisk['factors'] as $f) {
    $wSum += $f['weight'];
}
assert_between('8-factor weight sum ≈ 1.0', round($wSum, 4), 0.99, 1.01);

echo "\n── 7c. Each factor has correct structure ──\n";
foreach ($fullRisk['factors'] as $name => $factor) {
    assert_test("Factor '$name' has raw", array_key_exists('raw', $factor));
    assert_test("Factor '$name' has normalized", array_key_exists('normalized', $factor));
    assert_test("Factor '$name' has weight", array_key_exists('weight', $factor));
    assert_test("Factor '$name' has contribution", array_key_exists('contribution', $factor));
}

echo "\n── 7d. Temporal recency increases risk ──\n";
$lowTemporal = $riskCalc->calculate(
    shortRatio: 0.3, totalGapMonths: 6, overlapCount: 0,
    rankAnomaly: false, recentUniqueCompanies3y: 2, stabilityIndex: 5.0,
    temporalRecencyScore: 0.0, cfg: $cfg,
);
$highTemporal = $riskCalc->calculate(
    shortRatio: 0.3, totalGapMonths: 6, overlapCount: 0,
    rankAnomaly: false, recentUniqueCompanies3y: 2, stabilityIndex: 5.0,
    temporalRecencyScore: 1.0, cfg: $cfg,
);
assert_lt('Low temporal < high temporal risk', $lowTemporal['risk_score'], $highTemporal['risk_score']);
echo "    Low temporal: {$lowTemporal['risk_score']}, High temporal: {$highTemporal['risk_score']}\n";

// ══════════════════════════════════════════════════════════════════════════
// INTEGRATION: Full Engine Run
// ══════════════════════════════════════════════════════════════════════════
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  INTEGRATION: StabilityRiskEngine v1.1\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$testEmail = 'stability_v1_1_test@test.example';

// Clean up any leftover test data
$existing = PoolCandidate::where('email', $testEmail)->first();
if ($existing) {
    CandidateContract::where('pool_candidate_id', $existing->id)->delete();
    TrustEvent::where('pool_candidate_id', $existing->id)->delete();
    CandidateTrustProfile::where('pool_candidate_id', $existing->id)->delete();
    $existing->delete();
}

// Create test candidate — a 2/O who worked on diverse vessel types
$candidate = PoolCandidate::create([
    'first_name' => 'Stability', 'last_name' => 'V11Test',
    'email' => $testEmail, 'phone' => '+905559999998',
    'country_code' => 'TR', 'preferred_language' => 'en',
    'source_channel' => 'organic', 'status' => 'in_pool',
    'primary_industry' => 'maritime', 'seafarer' => true,
]);

// Create 6 contracts — diverse vessel types, mix of durations
CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Bulk Star', 'vessel_type' => 'bulk_carrier',
    'company_name' => 'Alpha Bulk', 'rank_code' => '3/O',
    'start_date' => Carbon::parse('2020-03-01'),
    'end_date' => Carbon::parse('2020-11-01'),  // 8mo bulk
]);
CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MT Tanker One', 'vessel_type' => 'tanker',
    'company_name' => 'Beta Tankers', 'rank_code' => '3/O',
    'start_date' => Carbon::parse('2021-02-01'),
    'end_date' => Carbon::parse('2021-10-01'),  // 8mo tanker
]);
CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Container Express', 'vessel_type' => 'container',
    'company_name' => 'Gamma Lines', 'rank_code' => '2/O',
    'start_date' => Carbon::parse('2022-01-01'),
    'end_date' => Carbon::parse('2022-09-01'),  // 8mo container (promoted to 2/O)
]);
CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MT Chemical Dawn', 'vessel_type' => 'chemical',
    'company_name' => 'Delta Chem', 'rank_code' => '2/O',
    'start_date' => Carbon::parse('2023-01-01'),
    'end_date' => Carbon::parse('2023-08-01'),  // 7mo chemical
]);
CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MV Bulk Titan', 'vessel_type' => 'bulk_carrier',
    'company_name' => 'Alpha Bulk', 'rank_code' => '2/O',
    'start_date' => Carbon::parse('2024-01-01'),
    'end_date' => Carbon::parse('2024-09-01'),  // 8mo bulk (returned to Alpha)
]);
CandidateContract::create([
    'pool_candidate_id' => $candidate->id,
    'vessel_name' => 'MT Tanker Two', 'vessel_type' => 'tanker',
    'company_name' => 'Beta Tankers', 'rank_code' => '2/O',
    'start_date' => Carbon::parse('2025-01-01'),
    'end_date' => Carbon::parse('2025-08-01'),  // 7mo tanker (returned to Beta)
]);

echo "    Created candidate {$candidate->id} with 6 contracts (4 vessel types)\n";

// Run engine
$engine = app(StabilityRiskEngine::class);
$result = $engine->compute($candidate->id);

assert_not_null('Engine v1.1 compute() returned a result', $result);

echo "\n── Integration: Engine version ──\n";
assert_eq('Engine version = 1.1', '1.1', $result['engine_version']);
assert_null('Fleet type = null (default)', $result['fleet_type']);

echo "\n── Integration: Standard fields ──\n";
assert_not_null('stability_index present', $result['stability_index']);
assert_gt('stability_index > 0', $result['stability_index'], 0);
assert_between('risk_score in [0, 1]', $result['risk_score'], 0.0, 1.0);
$validTiers = ['low', 'medium', 'high', 'critical'];
assert_test('risk_tier is valid', in_array($result['risk_tier'], $validTiers, true));

echo "\n── Integration: New v1.1 fields ──\n";
// Promotion context
assert_test('promotion_context present', isset($result['promotion_context']));
assert_test('promotion_context.in_promotion_window is bool', is_bool($result['promotion_context']['in_promotion_window']));
assert_test('promotion_context.modifier is float', is_float($result['promotion_context']['modifier']));

// Temporal decay
assert_test('temporal_decay present', isset($result['temporal_decay']));
assert_between('temporal_recency_score in [0, 1]', $result['temporal_decay']['temporal_recency_score'], 0.0, 1.0);

// Vessel diversity
assert_test('vessel_diversity present', isset($result['vessel_diversity']));
assert_between('vessel_diversity_score in [0, 1]', $result['vessel_diversity']['vessel_diversity_score'], 0.0, 1.0);
assert_gt('qualifying_types >= 2 (4 vessel types)', $result['vessel_diversity']['qualifying_types'], 1);
echo "    vessel_diversity_score={$result['vessel_diversity']['vessel_diversity_score']}\n";
echo "    qualifying_types={$result['vessel_diversity']['qualifying_types']}\n";
echo "    total_types={$result['vessel_diversity']['total_types']}\n";

// Risk factors — should have 8
assert_eq('8 risk factors', 8, count($result['risk_factors']));
assert_test('risk_factors has vessel_diversity', isset($result['risk_factors']['vessel_diversity']));
assert_test('risk_factors has temporal_recency', isset($result['risk_factors']['temporal_recency']));

echo "\n── Integration: Contract summary ──\n";
assert_eq('total_contracts = 6', 6, $result['contract_summary']['total_contracts']);
echo "    short_contract_count={$result['contract_summary']['short_contract_count']}\n";
echo "    avg_duration_months={$result['contract_summary']['avg_duration_months']}\n";
echo "    company_repeat_ratio={$result['contract_summary']['company_repeat_ratio']}\n";

echo "\n── Integration: Trust profile persisted ──\n";
$tp = CandidateTrustProfile::where('pool_candidate_id', $candidate->id)->first();
assert_not_null('Trust profile exists', $tp);
assert_eq('Persisted stability_index', $result['stability_index'], $tp->stability_index);
assert_eq('Persisted risk_score', $result['risk_score'], $tp->risk_score);
assert_eq('Persisted risk_tier', $result['risk_tier'], $tp->risk_tier);
$dj = $tp->detail_json;
assert_test('detail_json has stability_risk', isset($dj['stability_risk']));
assert_eq('detail_json engine_version = 1.1', '1.1', $dj['stability_risk']['engine_version']);

echo "\n── Integration: Audit event ──\n";
$event = TrustEvent::where('pool_candidate_id', $candidate->id)
    ->where('event_type', 'stability_risk_computed')
    ->latest()->first();
assert_not_null('Audit event created', $event);
assert_eq('Event has engine_version', '1.1', $event->payload_json['engine_version']);
assert_test('Event has vessel_diversity_score', isset($event->payload_json['vessel_diversity_score']));
assert_test('Event has temporal_recency_score', isset($event->payload_json['temporal_recency_score']));
assert_test('Event has promotion_modifier', isset($event->payload_json['promotion_modifier']));

echo "\n    ┌────────────────────────────────────────────────┐\n";
echo "    │  stability_index = {$result['stability_index']}\n";
echo "    │  risk_score      = {$result['risk_score']}\n";
echo "    │  risk_tier       = {$result['risk_tier']}\n";
echo "    │  vessel_diversity= {$result['vessel_diversity']['vessel_diversity_score']}\n";
echo "    │  temporal_recency= {$result['temporal_decay']['temporal_recency_score']}\n";
echo "    │  promo_modifier  = {$result['promotion_context']['modifier']}\n";
echo "    └────────────────────────────────────────────────┘\n";

// ══════════════════════════════════════════════════════════════════════════
// BEFORE / AFTER: Same inputs, v1 defaults vs v1.1 context-aware
// ══════════════════════════════════════════════════════════════════════════
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  BEFORE / AFTER: v1 Defaults vs v1.1 Context-Aware\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Simulate v1 behavior: no temporal, no diversity, no promotion modifier, 6 factors
$v1Risk = $riskCalc->calculate(
    shortRatio: $result['contract_summary']['short_contract_ratio'],
    totalGapMonths: $result['contract_summary']['total_gap_months'],
    overlapCount: $result['contract_summary']['overlap_count'],
    rankAnomaly: !empty($result['rank_anomalies']),
    recentUniqueCompanies3y: $result['contract_summary']['recent_unique_companies_3y'],
    stabilityIndex: $result['stability_index'],
    temporalRecencyScore: 0.0,      // v1: no temporal decay
    vesselDiversityScore: 0.0,      // v1: no vessel diversity
    promotionModifier: 1.0,         // v1: no promotion context
    cfg: $cfg,
);

// v1.1 is what the engine already computed
$v11Risk = $result['risk_score'];
$v1Score = $v1Risk['risk_score'];

echo "    v1  risk_score (no context)  = {$v1Score}\n";
echo "    v1.1 risk_score (with context) = {$v11Risk}\n";
echo "    Difference: " . round(abs($v1Score - $v11Risk), 4) . "\n\n";

// For this candidate with diverse vessels and good career progression,
// v1.1 should give a better (lower or equal) risk score
echo "── Before/After assertions ──\n";
assert_test('v1.1 considers vessel diversity (positive modifier)', $result['vessel_diversity']['vessel_diversity_score'] > 0);
assert_test('v1.1 includes temporal context', isset($result['temporal_decay']['temporal_recency_score']));
assert_test('v1.1 includes promotion context', isset($result['promotion_context']['in_promotion_window']));

// Since this candidate has diverse vessel experience, v1.1 should give ≤ v1 risk
if ($result['vessel_diversity']['vessel_diversity_score'] > 0) {
    assert_lte('v1.1 risk ≤ v1 risk (diversity bonus helps)', $v11Risk, $v1Score + 0.01);
}

// ══════════════════════════════════════════════════════════════════════════
// CLEANUP
// ══════════════════════════════════════════════════════════════════════════
echo "\n── Cleanup ──\n";
$cleanup = PoolCandidate::where('email', $testEmail)->first();
if ($cleanup) {
    CandidateContract::where('pool_candidate_id', $cleanup->id)->delete();
    CandidateTrustProfile::where('pool_candidate_id', $cleanup->id)->delete();
    TrustEvent::where('pool_candidate_id', $cleanup->id)->delete();
    $cleanup->delete();
    echo "    Cleaned up test data\n";
}

// ══════════════════════════════════════════════════════════════════════════
// SUMMARY
// ══════════════════════════════════════════════════════════════════════════

} finally {
    DB::rollBack();
}

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "   RESULTS: $pass passed, $fail failed, $total total\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// ── v1.1 Upgrade Summary ──
echo "┌─────────────────────────────────────────────────────────────────┐\n";
echo "│                    v1.1 UPGRADE SUMMARY                        │\n";
echo "├─────────────────────────────────────────────────────────────────┤\n";
echo "│                                                                 │\n";
echo "│  Hardcoded values REMOVED from engine code:                     │\n";
echo "│    • short_contract_months = 6        → config                  │\n";
echo "│    • short_ratio_flag > 0.6           → config                  │\n";
echo "│    • gap_flag > 18 months             → config                  │\n";
echo "│    • frequent_switch > 6              → config                  │\n";
echo "│    • recent_companies_window 3y       → config                  │\n";
echo "│    • factor weights (6 fixed)         → config (8 configurable) │\n";
echo "│    • gap_norm_cap = 36                → config                  │\n";
echo "│    • overlap_norm_cap = 5             → config                  │\n";
echo "│    • switch_norm_cap = 8              → config                  │\n";
echo "│    • SI pivot = 5.0                   → config                  │\n";
echo "│    • SI neutral = 0.5                 → config                  │\n";
echo "│    • SI min_contracts = 2             → config                  │\n";
echo "│    • SI std_threshold = 0.001         → config                  │\n";
echo "│    • SI max_cap = 10.0                → config                  │\n";
echo "│    • tier: critical = 0.75            → config                  │\n";
echo "│    • tier: high = 0.50                → config                  │\n";
echo "│    • tier: medium = 0.25              → config                  │\n";
echo "│    • unrealistic_promo months = 6     → config                  │\n";
echo "│    • unrealistic_promo levels = 2     → config                  │\n";
echo "│  ─────────────────────────────────────────                      │\n";
echo "│  Total hardcoded values removed:  19                            │\n";
echo "│                                                                 │\n";
echo "│  New configurable parameters:                                   │\n";
echo "│    • 23 rank-specific thresholds (short_contract_months_by_rank)│\n";
echo "│    • 8 factor weights (was 6)                                   │\n";
echo "│    • 5 temporal decay params                                    │\n";
echo "│    • 4 vessel diversity params                                  │\n";
echo "│    • 2 promotion window params                                  │\n";
echo "│    • 4 fleet profiles (tanker, bulk, container, river)          │\n";
echo "│  ─────────────────────────────────────────                      │\n";
echo "│  Total configurable parameters:  46+                            │\n";
echo "│                                                                 │\n";
echo "│  New engine components:                                         │\n";
echo "│    • StabilityConfig          (centralized config resolver)     │\n";
echo "│    • PromotionContextAnalyzer (Phase 3)                         │\n";
echo "│    • TemporalDecayCalculator  (Phase 4)                         │\n";
echo "│    • VesselDiversityCalculator(Phase 5)                         │\n";
echo "│  ─────────────────────────────────────────                      │\n";
echo "│  New service classes:  4                                        │\n";
echo "│                                                                 │\n";
echo "│  Modified files:                                                │\n";
echo "│    • config/maritime.php       (stability config block)         │\n";
echo "│    • ContractPatternAnalyzer   (rank-aware + config)            │\n";
echo "│    • RiskScoreCalculator       (8 factors + modifiers)          │\n";
echo "│    • RiskTierResolver          (configurable tiers)             │\n";
echo "│    • StabilityIndexCalculator  (configurable params)            │\n";
echo "│    • RankProgressionAnalyzer   (configurable anomaly detection) │\n";
echo "│    • StabilityRiskEngine       (orchestrates all new features)  │\n";
echo "│  ─────────────────────────────────────────                      │\n";
echo "│  Modified files:  7                                             │\n";
echo "│                                                                 │\n";
printf("│  Tests in this file:  %-3d                                      │\n", $total);
echo "│                                                                 │\n";
echo "└─────────────────────────────────────────────────────────────────┘\n\n";

exit($fail > 0 ? 1 : 0);
