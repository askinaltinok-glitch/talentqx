#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/www/wwwroot/talentqx.com/api"
PHP_BIN="/www/server/php/82/bin/php"
LOG_SCHED="/www/wwwlogs/talentqx-scheduler.log"

cd "$APP_DIR"

ok()   { echo -e "✅ $*"; }
warn() { echo -e "⚠️  $*"; }
bad()  { echo -e "❌ $*"; }
hr()   { echo "------------------------------------------------------------"; }

FAIL=0

hr
echo "TalentQX Production Check @ $(date -u '+%Y-%m-%d %H:%M:%S UTC')"
echo "App: $APP_DIR"
echo "PHP: $PHP_BIN"
hr

# 1) PHP version
if "$PHP_BIN" -v >/dev/null 2>&1; then
  PHPV="$("$PHP_BIN" -r 'echo PHP_VERSION;')"
  echo "PHP_VERSION=$PHPV"
  if [[ "$PHPV" =~ ^8\.2\. ]]; then ok "PHP 8.2 detected"; else warn "PHP is not 8.2 (found $PHPV)"; FAIL=1; fi
else
  bad "PHP binary not working: $PHP_BIN"; FAIL=1
fi

hr

# 2) Laravel basics
if [[ -f artisan ]]; then
  LARAVELV="$("$PHP_BIN" artisan --version 2>/dev/null || true)"
  echo "$LARAVELV"
  ok "artisan present"
else
  bad "artisan not found in $APP_DIR"; FAIL=1
fi

hr

# 3) Config summary (via artisan about)
"$PHP_BIN" artisan about 2>/dev/null | grep -E "(Environment|Debug|Queue|Mail)" | head -10 || echo "Could not read config"

hr

# 4) DB + outbox tables + last 5 messages
echo "DB / Outbox checks:"
OUT="$("$PHP_BIN" artisan tinker --execute='
try {
  $hasOutbox = \Schema::hasTable("message_outbox");
  $hasTpl    = \Schema::hasTable("message_templates");
  dump($hasOutbox, $hasTpl);

  if ($hasOutbox) {
    $rows = \DB::table("message_outbox")
      ->select("id","channel","recipient","subject","status","created_at","sent_at","failed_at")
      ->orderByDesc("id")->limit(5)->get();
    dump($rows);
  }
} catch (\Throwable $e) {
  echo "DB_ERROR: ".$e->getMessage().PHP_EOL;
  exit(10);
}
' 2>&1)" || { bad "DB check failed"; echo "$OUT"; FAIL=1; OUT=""; }

if [[ -n "${OUT:-}" ]]; then
  echo "$OUT"
  if echo "$OUT" | grep -q "DB_ERROR:"; then bad "Database error detected"; FAIL=1; fi
  if echo "$OUT" | grep -qE "true //|bool\(true\)"; then ok "Outbox tables exist"; else warn "Outbox tables missing?"; FAIL=1; fi
fi

hr

# 5) Supervisor (optional)
if command -v supervisorctl >/dev/null 2>&1; then
  echo "Supervisor:"
  SUP="$(supervisorctl status 2>&1 || true)"
  echo "$SUP"
  if echo "$SUP" | grep -q "talentqx-queue:talentqx-queue_00.*RUNNING"; then ok "Queue worker RUNNING"; else warn "Queue worker not RUNNING"; FAIL=1; fi
else
  warn "supervisorctl not found (skip)"
fi

hr

# 6) Scheduler cron check
echo "Cron (schedule:run) check:"
CRON="$(crontab -l 2>/dev/null | grep -i "artisan schedule:run" || true)"
if [[ -n "$CRON" ]]; then
  echo "$CRON"
  ok "schedule:run cron present"
else
  warn "No schedule:run cron found"
  FAIL=1
fi

hr

# 7) Scheduler log tail (optional)
if [[ -f "$LOG_SCHED" ]]; then
  echo "Scheduler log tail ($LOG_SCHED):"
  tail -n 20 "$LOG_SCHED" || true
  ok "Scheduler log readable"
else
  warn "Scheduler log not found: $LOG_SCHED (skip)"
fi

hr

if [[ "$FAIL" -eq 0 ]]; then
  ok "PROD CHECK PASSED"
  exit 0
else
  bad "PROD CHECK FAILED (see warnings above)"
  exit 1
fi
