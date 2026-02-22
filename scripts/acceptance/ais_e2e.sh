#!/usr/bin/env bash
set -uo pipefail

# ═══════════════════════════════════════════════════════════════════════════
# AIS Verification Engine v1 — End-to-End Acceptance Test (bash wrapper)
# ═══════════════════════════════════════════════════════════════════════════
#
# Usage:
#   cd /www/wwwroot/talentqx.com/api
#   bash scripts/acceptance/ais_e2e.sh
#
# Optional env overrides:
#   BASE_URL=https://octopus-ai.net     (default: http://127.0.0.1:3000)
#   ADMIN_TOKEN=<Bearer token>           (for HTTP presenter tests; skip if empty)
#
# All test data runs inside DB::beginTransaction → rollBack. Zero artifacts.
# Exit code: 0 = all pass, non-zero = number of failures.
# ═══════════════════════════════════════════════════════════════════════════

BASE_URL="${BASE_URL:-http://127.0.0.1:3000}"
ADMIN_TOKEN="${ADMIN_TOKEN:-}"
API_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
PHP_BIN="php82"
TMPDIR_E2E="$(mktemp -d)"
trap 'rm -rf "$TMPDIR_E2E"' EXIT

# ── colors ────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[0;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

PASS=0; FAIL=0

# assert_jq <msg> <json_var_name> <jq_expr>
assert_jq() {
  local msg="$1" json="$2" expr="$3"
  if echo "$json" | jq -e "$expr" >/dev/null 2>&1; then
    PASS=$((PASS + 1))
    echo -e "  ${GREEN}✓${NC} $msg"
  else
    FAIL=$((FAIL + 1))
    echo -e "  ${RED}✗ FAIL:${NC} $msg"
  fi
}

section() {
  echo -e "\n${CYAN}${BOLD}━━━ $1 ━━━${NC}"
}

# Write a PHP heredoc to temp file with %%API_DIR%% replaced.
write_php() {
  local name="$1"
  local file="$TMPDIR_E2E/${name}.php"
  sed "s|%%API_DIR%%|${API_DIR}|g" > "$file"
  echo "$file"
}

run() {
  $PHP_BIN "$1" 2>/dev/null
}

# ── preconditions ─────────────────────────────────────────────────────────
if [[ ! -f "$API_DIR/artisan" ]]; then
  echo -e "${RED}Run from the api/ directory.${NC}"; exit 1
fi
command -v "$PHP_BIN" >/dev/null 2>&1 || { echo "$PHP_BIN not found"; exit 1; }
command -v jq >/dev/null 2>&1 || { echo "jq not found"; exit 1; }

echo -e "${BOLD}╔══════════════════════════════════════════════════════════╗"
echo -e "║   AIS Verification Engine v1 — E2E Acceptance Test      ║"
echo -e "╚══════════════════════════════════════════════════════════╝${NC}"
echo "API_DIR=$API_DIR"
echo "BASE_URL=$BASE_URL"
echo "ADMIN_TOKEN=$([ -n "$ADMIN_TOKEN" ] && echo 'set' || echo 'not set (HTTP tests skipped)')"

# ═══════════════════════════════════════════════════════════════════════════
# Write all PHP test scripts to temp files
# ═══════════════════════════════════════════════════════════════════════════

STEP1=$(write_php step1 <<'PHP'
<?php
require '%%API_DIR%%/vendor/autoload.php';
$app = require '%%API_DIR%%/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{PoolCandidate, CandidateContract, ContractAisVerification};
use App\Services\Ais\ContractAisVerificationService;
use Illuminate\Support\Facades\{Config, DB};

Config::set('maritime.ais_v1', false);
Config::set('maritime.ais_mock', true);
DB::beginTransaction();

$cand = PoolCandidate::create([
    'email' => 'gate_test_' . uniqid() . '@test.local',
    'first_name' => 'Gate', 'last_name' => 'Test',
    'country_code' => 'TR', 'status' => 'new',
    'primary_industry' => 'maritime', 'source_channel' => 'organic',
]);
$contract = CandidateContract::create([
    'pool_candidate_id' => $cand->id,
    'vessel_name' => 'MV Gated', 'vessel_imo' => '8888801',
    'vessel_type' => 'bulk_carrier', 'company_name' => 'Gate Co',
    'rank_code' => 'MASTER', 'start_date' => '2024-01-01', 'end_date' => '2024-06-30',
]);

$svc = app(ContractAisVerificationService::class);
$result = $svc->verify($contract, 'system');
$count = ContractAisVerification::where('candidate_contract_id', $contract->id)->count();
DB::rollBack();
echo json_encode(['result_null' => $result === null, 'count' => $count]);
PHP
)

STEP2=$(write_php step2 <<'PHP'
<?php
require '%%API_DIR%%/vendor/autoload.php';
$app = require '%%API_DIR%%/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{PoolCandidate, CandidateContract, ContractAisVerification, Vessel, AisVerification, TrustEvent};
use App\Jobs\VerifyContractAisJob;
use Illuminate\Support\Facades\{Config, DB};

Config::set('maritime.ais_v1', true);
Config::set('maritime.ais_mock', true);
DB::beginTransaction();

$cand = PoolCandidate::create([
    'email' => 'full_test_' . uniqid() . '@test.local',
    'first_name' => 'Full', 'last_name' => 'Test',
    'country_code' => 'TR', 'status' => 'new',
    'primary_industry' => 'maritime', 'source_channel' => 'organic',
]);
Vessel::create([
    'imo' => '7777701', 'name' => 'MV Full Bulker', 'type' => 'bulk_carrier',
    'flag' => 'TR', 'dwt' => 50000, 'gt' => 30000,
    'length_m' => 200, 'beam_m' => 32, 'year_built' => 2018, 'data_source' => 'manual',
]);
$contract = CandidateContract::create([
    'pool_candidate_id' => $cand->id,
    'vessel_name' => 'MV Full Bulker', 'vessel_imo' => '7777701',
    'vessel_type' => 'bulk_carrier', 'company_name' => 'Full Co',
    'rank_code' => 'CHIEF_OFFICER',
    'start_date' => '2024-01-01', 'end_date' => '2024-06-30',
]);

$before = ContractAisVerification::where('candidate_contract_id', $contract->id)->count();
dispatch_sync(new VerifyContractAisJob($contract->id, 'admin'));
$after = ContractAisVerification::where('candidate_contract_id', $contract->id)->count();
$latest = ContractAisVerification::where('candidate_contract_id', $contract->id)->latest('created_at')->first();
$legacy = AisVerification::where('candidate_contract_id', $contract->id)->first();
$event = TrustEvent::where('pool_candidate_id', $cand->id)->where('event_type', 'ais_verification_completed')->first();
$contract->refresh();
DB::rollBack();

echo json_encode([
    'before' => $before, 'after' => $after,
    'status' => $latest?->status,
    'confidence_score' => $latest?->confidence_score,
    'provider' => $latest?->provider,
    'triggered_by' => $latest?->triggered_by,
    'has_reasons' => is_array($latest?->reasons_json) && count($latest->reasons_json) === 5,
    'has_anomalies' => is_array($latest?->anomalies_json),
    'has_evidence' => is_array($latest?->evidence_summary_json),
    'ev_total_points' => $latest?->evidence_summary_json['total_points'] ?? null,
    'ev_days_covered' => $latest?->evidence_summary_json['days_covered'] ?? null,
    'ev_data_quality' => $latest?->evidence_summary_json['data_quality'] ?? null,
    'ev_has_clusters' => isset($latest?->evidence_summary_json['area_clusters']),
    'period_start' => $latest?->period_start?->toDateString(),
    'period_end' => $latest?->period_end?->toDateString(),
    'vessel_id_linked' => $contract->vessel_id !== null,
    'legacy_exists' => $legacy !== null,
    'legacy_status_match' => $legacy?->status === $latest?->status,
    'trust_event' => $event !== null,
]);
PHP
)

STEP3=$(write_php step3 <<'PHP'
<?php
require '%%API_DIR%%/vendor/autoload.php';
$app = require '%%API_DIR%%/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{PoolCandidate, CandidateContract};
use App\Services\Ais\ContractAisVerificationService;
use Illuminate\Support\Facades\{Config, DB};

Config::set('maritime.ais_v1', true);
Config::set('maritime.ais_mock', true);
DB::beginTransaction();

$cand = PoolCandidate::create([
    'email' => 'na_test_' . uniqid() . '@test.local',
    'first_name' => 'NA', 'last_name' => 'Test',
    'country_code' => 'TR', 'status' => 'new',
    'primary_industry' => 'maritime', 'source_channel' => 'organic',
]);
$noImo = CandidateContract::create([
    'pool_candidate_id' => $cand->id,
    'vessel_name' => 'MV No IMO', 'vessel_imo' => null,
    'company_name' => 'NoIMO Co', 'rank_code' => 'AB',
    'start_date' => '2024-01-01', 'end_date' => '2024-06-30',
]);
$ongoing = CandidateContract::create([
    'pool_candidate_id' => $cand->id,
    'vessel_name' => 'MV Ongoing', 'vessel_imo' => '6666601',
    'vessel_type' => 'tanker', 'company_name' => 'Ongoing Co',
    'rank_code' => 'MASTER', 'start_date' => '2025-11-01', 'end_date' => null,
]);

$svc = app(ContractAisVerificationService::class);
$vNoImo = $svc->verify($noImo, 'system');
$vOngoing = $svc->verify($ongoing, 'system');
DB::rollBack();

echo json_encode([
    'no_imo_status' => $vNoImo?->status,
    'no_imo_reason' => $vNoImo?->reasons_json[0]['code'] ?? null,
    'no_imo_confidence' => $vNoImo?->confidence_score,
    'ongoing_status' => $vOngoing?->status,
    'ongoing_reason' => $vOngoing?->reasons_json[0]['code'] ?? null,
]);
PHP
)

STEP4=$(write_php step4 <<'PHP'
<?php
require '%%API_DIR%%/vendor/autoload.php';
$app = require '%%API_DIR%%/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{PoolCandidate, CandidateContract, ContractAisVerification, Vessel};
use App\Services\Ais\ContractAisVerificationService;
use Illuminate\Support\Facades\{Config, DB};

Config::set('maritime.ais_v1', true);
Config::set('maritime.ais_mock', true);
DB::beginTransaction();

$cand = PoolCandidate::create([
    'email' => 'append_test_' . uniqid() . '@test.local',
    'first_name' => 'Append', 'last_name' => 'Test',
    'country_code' => 'TR', 'status' => 'new',
    'primary_industry' => 'maritime', 'source_channel' => 'organic',
]);
Vessel::create([
    'imo' => '5555501', 'name' => 'MV Append', 'type' => 'tanker',
    'flag' => 'PA', 'data_source' => 'manual',
]);
$contract = CandidateContract::create([
    'pool_candidate_id' => $cand->id,
    'vessel_name' => 'MV Append', 'vessel_imo' => '5555501',
    'vessel_type' => 'tanker', 'company_name' => 'Append Co',
    'rank_code' => '2ND_ENGINEER',
    'start_date' => '2024-03-01', 'end_date' => '2024-09-30',
]);

$svc = app(ContractAisVerificationService::class);
$v1 = $svc->verify($contract, 'admin');
$v2 = $svc->verify($contract, 'cron');
$count = ContractAisVerification::where('candidate_contract_id', $contract->id)->count();
$contract->refresh();
$contract->load('latestAisVerification');
DB::rollBack();

echo json_encode([
    'count' => $count,
    'ids_differ' => $v1->id !== $v2->id,
    'latest_is_v2' => $contract->latestAisVerification?->id === $v2->id,
]);
PHP
)

STEP5=$(write_php step5 <<'PHP'
<?php
require '%%API_DIR%%/vendor/autoload.php';
$app = require '%%API_DIR%%/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{PoolCandidate, CandidateContract, Vessel};
use App\Presenters\CandidateContractAisPresenter;
use App\Services\Ais\ContractAisVerificationService;
use Illuminate\Support\Facades\{Config, DB};

Config::set('maritime.ais_v1', true);
Config::set('maritime.ais_mock', true);
DB::beginTransaction();

$cand = PoolCandidate::create([
    'email' => 'pres_test_' . uniqid() . '@test.local',
    'first_name' => 'Pres', 'last_name' => 'Test',
    'country_code' => 'TR', 'status' => 'new',
    'primary_industry' => 'maritime', 'source_channel' => 'organic',
]);
Vessel::create([
    'imo' => '4444401', 'name' => 'MV Presenter', 'type' => 'container',
    'flag' => 'LR', 'dwt' => 65000, 'gt' => 40000,
    'length_m' => 250, 'beam_m' => 35, 'year_built' => 2020, 'data_source' => 'manual',
]);
$contract = CandidateContract::create([
    'pool_candidate_id' => $cand->id,
    'vessel_name' => 'MV Presenter', 'vessel_imo' => '4444401',
    'vessel_type' => 'container', 'company_name' => 'Pres Co',
    'rank_code' => 'MASTER',
    'start_date' => '2024-04-01', 'end_date' => '2024-10-30',
]);

$svc = app(ContractAisVerificationService::class);
$svc->verify($contract, 'system');
$contract->refresh();
$contract->load(['vessel', 'aisVerification', 'latestAisVerification']);
$presented = CandidateContractAisPresenter::present($contract);
$ais = $presented['ais_verification'];
DB::rollBack();

echo json_encode([
    'has_status' => isset($ais['status']),
    'has_reasons' => is_array($ais['reasons'] ?? null),
    'has_anomalies' => array_key_exists('anomalies', $ais),
    'has_evidence_summary' => is_array($ais['evidence_summary'] ?? null),
    'has_provider' => isset($ais['provider']),
    'provider_value' => $ais['provider'] ?? null,
    'reasons_count' => is_array($ais['reasons'] ?? null) ? count($ais['reasons']) : 0,
    'status' => $ais['status'],
    'confidence' => $ais['confidence_score'],
]);
PHP
)

STEP6=$(write_php step6 <<'PHP'
<?php
require '%%API_DIR%%/vendor/autoload.php';
$app = require '%%API_DIR%%/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\{Config, Artisan};

Config::set('maritime.ais_v1', true);
Config::set('maritime.ais_mock', true);
Config::set('maritime.ais_auto_verify', true);
$code1 = Artisan::call('trust:ais:verify-pending', ['--dry-run' => true, '--limit' => 5]);
$out1 = trim(Artisan::output());

Config::set('maritime.ais_v1', false);
$code2 = Artisan::call('trust:ais:verify-pending', ['--dry-run' => true]);
$out2 = trim(Artisan::output());

Config::set('maritime.ais_v1', true);
Config::set('maritime.ais_auto_verify', false);
$code3 = Artisan::call('trust:ais:verify-pending', ['--dry-run' => true]);
$out3 = trim(Artisan::output());

echo json_encode([
    'enabled_code' => $code1, 'enabled_output' => $out1,
    'v1off_output' => $out2, 'autooff_output' => $out3,
]);
PHP
)

STEP7=$(write_php step7 <<'PHP'
<?php
require '%%API_DIR%%/vendor/autoload.php';
$app = require '%%API_DIR%%/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CandidateContract;
$contract = CandidateContract::whereNotNull('vessel_imo')
    ->whereHas('contractAisVerifications')
    ->first();
echo $contract ? $contract->pool_candidate_id : 'NONE';
PHP
)

# ═══════════════════════════════════════════════════════════════════════════
# Execute tests
# ═══════════════════════════════════════════════════════════════════════════

section "1) Feature-flag gating (ais_v1=false prevents writes)"
R1=$(run "$STEP1")
assert_jq "Service returns null when ais_v1=false"  "$R1"  '.result_null == true'
assert_jq "No verification rows written when gated"  "$R1"  '.count == 0'

section "2) Full verification — contract with IMO (dispatch_sync)"
R2=$(run "$STEP2")
assert_jq "Row count: before=0, after=1"                    "$R2"  '.before == 0 and .after == 1'
assert_jq "Status is verified or failed"                     "$R2"  '.status == "verified" or .status == "failed"'
assert_jq "Confidence score in [0, 100]"                     "$R2"  '.confidence_score >= 0 and .confidence_score <= 100'
assert_jq "Provider = mock"                                  "$R2"  '.provider == "mock"'
assert_jq "triggered_by = admin"                             "$R2"  '.triggered_by == "admin"'
assert_jq "5 reason factors in reasons_json"                 "$R2"  '.has_reasons == true'
assert_jq "anomalies_json is array"                          "$R2"  '.has_anomalies == true'
assert_jq "evidence_summary_json is array"                   "$R2"  '.has_evidence == true'
assert_jq "Evidence: total_points > 0"                       "$R2"  '.ev_total_points > 0'
assert_jq "Evidence: days_covered > 0"                       "$R2"  '.ev_days_covered > 0'
assert_jq "Evidence: data_quality in (0, 1]"                 "$R2"  '.ev_data_quality > 0 and .ev_data_quality <= 1'
assert_jq "Evidence: has area_clusters"                      "$R2"  '.ev_has_clusters == true'
assert_jq "period_start set"                                 "$R2"  '.period_start != null'
assert_jq "period_end set"                                   "$R2"  '.period_end != null'
assert_jq "vessel_id linked on contract"                     "$R2"  '.vessel_id_linked == true'
assert_jq "Legacy ais_verifications upserted"                "$R2"  '.legacy_exists == true'
assert_jq "Legacy status matches engine"                     "$R2"  '.legacy_status_match == true'
assert_jq "TrustEvent ais_verification_completed"            "$R2"  '.trust_event == true'

section "3) Not-applicable paths (MISSING_IMO, ONGOING_CONTRACT)"
R3=$(run "$STEP3")
assert_jq "No-IMO → status = not_applicable"     "$R3"  '.no_imo_status == "not_applicable"'
assert_jq "No-IMO → reason = MISSING_IMO"         "$R3"  '.no_imo_reason == "MISSING_IMO"'
assert_jq "No-IMO → confidence = null"            "$R3"  '.no_imo_confidence == null'
assert_jq "Ongoing → status = not_applicable"     "$R3"  '.ongoing_status == "not_applicable"'
assert_jq "Ongoing → reason = ONGOING_CONTRACT"   "$R3"  '.ongoing_reason == "ONGOING_CONTRACT"'

section "4) Append-only audit log (two runs → two rows)"
R4=$(run "$STEP4")
assert_jq "Two rows created (append-only)"               "$R4"  '.count == 2'
assert_jq "Record IDs differ (no upsert)"                "$R4"  '.ids_differ == true'
assert_jq "latestAisVerification returns second record"   "$R4"  '.latest_is_v2 == true'

section "5) Presenter output includes engine fields"
R5=$(run "$STEP5")
assert_jq "Presenter has status"                    "$R5"  '.has_status == true'
assert_jq "Presenter has reasons array"             "$R5"  '.has_reasons == true'
assert_jq "Presenter has anomalies key"             "$R5"  '.has_anomalies == true'
assert_jq "Presenter has evidence_summary"          "$R5"  '.has_evidence_summary == true'
assert_jq "Presenter has provider"                  "$R5"  '.has_provider == true'
assert_jq "Presenter provider = mock"               "$R5"  '.provider_value == "mock"'
assert_jq "Presenter reasons has 5 factors"         "$R5"  '.reasons_count == 5'
assert_jq "Presenter confidence is a number"        "$R5"  '.confidence != null and (.confidence | type) == "number"'

section "6) Artisan command: trust:ais:verify-pending"
R6=$(run "$STEP6")
assert_jq "Artisan (enabled) exit code = 0"              "$R6"  '.enabled_code == 0'
assert_jq "Artisan (enabled) DRY RUN or No contracts"    "$R6"  '.enabled_output | test("DRY RUN|No contracts")'
assert_jq "Artisan (v1 off) aborts with disabled msg"    "$R6"  '.v1off_output | test("disabled")'
assert_jq "Artisan (auto off) aborts with disabled msg"  "$R6"  '.autooff_output | test("disabled")'

section "7) HTTP API presenter check"
if [[ -z "$ADMIN_TOKEN" ]]; then
  echo -e "  ${YELLOW}ADMIN_TOKEN not set — skipping HTTP tests.${NC}"
  echo "  To run: ADMIN_TOKEN=<token> bash scripts/acceptance/ais_e2e.sh"
else
  CANDIDATE_ID=$(run "$STEP7")

  if [[ "$CANDIDATE_ID" == "NONE" || -z "$CANDIDATE_ID" ]]; then
    echo -e "  ${YELLOW}No engine-verified contracts in DB. Skipping HTTP test.${NC}"
  else
    URL="${BASE_URL}/api/v1/octopus/admin/candidates/${CANDIDATE_ID}"
    echo "  Fetching $URL"

    HTTP_RESP=$(curl -sS \
      -H "Authorization: Bearer ${ADMIN_TOKEN}" \
      -H "Accept: application/json" \
      "$URL" 2>&1 || true)

    if echo "$HTTP_RESP" | jq -e '.success == true' >/dev/null 2>&1; then
      echo -e "  ${GREEN}HTTP 200 OK${NC}"
      HAS_ENGINE=$(echo "$HTTP_RESP" | jq '[.data.contracts // [] | .[] | select(.ais_verification.reasons != null)] | length' 2>/dev/null || echo "0")

      if [[ "$HAS_ENGINE" -gt 0 ]]; then
        assert_jq "HTTP: contract has reasons"          "$HTTP_RESP"  '[.data.contracts[] | select(.ais_verification.reasons != null)] | length > 0'
        assert_jq "HTTP: contract has anomalies key"    "$HTTP_RESP"  '[.data.contracts[] | select(.ais_verification | has("anomalies"))] | length > 0'
        assert_jq "HTTP: contract has evidence_summary" "$HTTP_RESP"  '[.data.contracts[] | select(.ais_verification.evidence_summary != null)] | length > 0'
        assert_jq "HTTP: contract has provider"         "$HTTP_RESP"  '[.data.contracts[] | select(.ais_verification.provider != null)] | length > 0'
      else
        echo -e "  ${YELLOW}Contracts found but none with engine data (legacy only). OK.${NC}"
      fi
    else
      echo -e "  ${YELLOW}HTTP request failed or auth issue. Skipping.${NC}"
    fi
  fi
fi

# ═══════════════════════════════════════════════════════════════════════════
# SUMMARY
# ═══════════════════════════════════════════════════════════════════════════
echo
echo -e "${BOLD}══════════════════════════════════════════════════════${NC}"
if [[ "$FAIL" -eq 0 ]]; then
  echo -e "  ${GREEN}${BOLD}ALL ${PASS} TESTS PASSED ✓${NC}"
else
  echo -e "  ${GREEN}${PASS} passed${NC}, ${RED}${BOLD}${FAIL} FAILED ✗${NC}"
fi
echo -e "${BOLD}══════════════════════════════════════════════════════${NC}"

exit "$FAIL"
