# TalentQX Production Runbook

Last updated: 2026-02-22

---

## 1. System Overview

| Component | Detail |
|-----------|--------|
| **Backend** | Laravel 11, PHP 8.2 (`php82`), MySQL 8 |
| **Frontend** | Next.js 16 (App Router), React 19, TypeScript, Tailwind v4 |
| **Reverse Proxy** | nginx (managed via BT Panel) |
| **Process Manager** | pm2 (process name: `talentqx-frontend`) |
| **SSL** | Let's Encrypt, auto-renewed via BT Panel |
| **Server** | CPX62 -- 32 GB RAM, 6 GB swap |
| **Queue** | Laravel queue workers via supervisor, backed by `jobs` / `failed_jobs` tables |
| **Scheduler** | Laravel scheduler via cron (`php82 artisan schedule:run`) |

### Domains

| Domain | Purpose | Backend |
|--------|---------|---------|
| `talentqx.com` | Static marketing pages + Laravel API (`/api/*`) | nginx serves static; proxies `/api` to PHP-FPM |
| `app.talentqx.com` | Next.js application | nginx reverse proxy to `localhost:3000` |
| `octopus-ai.net` | Next.js frontend (alternate domain) | nginx reverse proxy to `localhost:3000` |

### Key Paths

```
/www/wwwroot/talentqx.com/api          # Laravel root
/www/wwwroot/talentqx-frontend          # Next.js root
/www/server/panel/vhost/nginx/          # nginx vhost configs (BT Panel)
/www/server/panel/vhost/rewrite/        # nginx rewrite rules
/www/server/panel/vhost/nginx/snippets/security-headers.conf  # shared security headers
```

### PHP Version Warning

**Always use `php82`, never `php`.** The system default is PHP 7.2 which is incompatible with Laravel 11.

```bash
# Correct
php82 artisan migrate --force

# WRONG -- will use PHP 7.2 and fail
php artisan migrate --force
```

---

## 2. Rebuild / Recovery Procedure

Use this after a server crash, OOM kill, or full rebuild.

### 2.1 Swap Setup

If swap is missing or undersized (check with `free -h`):

```bash
fallocate -l 6G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

### 2.2 MySQL Socket Fix

If MySQL fails to start and the socket file is missing:

```bash
# Check socket path expected by PHP
php82 -i | grep mysql.default_socket

# Typical fix: ensure /tmp/mysql.sock exists or symlink it
ls -la /tmp/mysql.sock
ls -la /var/run/mysqld/mysqld.sock

# If the socket is at a different path, symlink:
ln -sf /var/run/mysqld/mysqld.sock /tmp/mysql.sock

# Restart MySQL
systemctl restart mysqld
```

### 2.3 Laravel Cache Rebuild

```bash
cd /www/wwwroot/talentqx.com/api
php82 artisan config:cache
php82 artisan route:cache
php82 artisan view:cache
php82 artisan event:cache
```

### 2.4 Queue Worker Restart

```bash
supervisorctl restart all
# or target specific workers:
supervisorctl status
supervisorctl restart laravel-worker:*
```

### 2.5 PM2 Restart (Frontend)

```bash
cd /www/wwwroot/talentqx-frontend
pm2 restart talentqx-frontend
pm2 save
```

### 2.6 nginx Reload

```bash
nginx -t && nginx -s reload
```

### 2.7 Full Recovery Sequence (copy-paste)

```bash
# 1. MySQL
systemctl restart mysqld

# 2. Laravel
cd /www/wwwroot/talentqx.com/api
php82 composer.phar install --no-dev --optimize-autoloader
php82 artisan migrate --force
php82 artisan config:cache
php82 artisan route:cache
php82 artisan view:cache
php82 artisan event:cache

# 3. Queue workers
supervisorctl restart all

# 4. Frontend
cd /www/wwwroot/talentqx-frontend
npm ci
npm run build
pm2 restart talentqx-frontend
pm2 save

# 5. nginx
nginx -t && nginx -s reload
```

---

## 3. Deploy Procedure

### 3.1 Backend Deploy

```bash
cd /www/wwwroot/talentqx.com/api

# Pull latest
git pull origin main

# Dependencies
php82 composer.phar install --no-dev --optimize-autoloader

# Migrations
php82 artisan migrate --force

# Clear and rebuild caches
php82 artisan config:cache
php82 artisan route:cache
php82 artisan view:cache
php82 artisan event:cache

# Restart queue workers to pick up new code
php82 artisan queue:restart
```

### 3.2 Frontend Deploy

```bash
cd /www/wwwroot/talentqx-frontend

# Pull latest
git pull origin main

# Dependencies
npm ci

# Build
npm run build

# Restart
pm2 restart talentqx-frontend
pm2 save
```

### 3.3 nginx Changes

After editing any nginx config:

```bash
nginx -t && nginx -s reload
```

**Important**: Location-level `add_header` directives override ALL server-level headers. Always include the shared security headers snippet in every location block:

```nginx
location /api {
    include /www/server/panel/vhost/nginx/snippets/security-headers.conf;
    # ... other directives
}
```

---

## 4. Cron Standards

### Scheduler Entry

The Laravel scheduler must use `php82` explicitly:

```cron
* * * * * cd /www/wwwroot/talentqx.com/api && php82 artisan schedule:run >> /dev/null 2>&1
```

**Never use bare `php`** in any cron entry. Always `php82`.

### Verifying Cron

```bash
crontab -l
# Look for the schedule:run entry with php82

# Check scheduler output manually:
cd /www/wwwroot/talentqx.com/api
php82 artisan schedule:list
```

### Invite Scheduler Observability

The maritime invite scheduler logs runs to the `maritime_invite_runs` table. Query it to verify the scheduler is firing:

```bash
cd /www/wwwroot/talentqx.com/api
php82 artisan tinker --execute="echo \App\Models\MaritimeInviteRun::latest()->first();"
```

Or directly via MySQL:

```sql
SELECT * FROM maritime_invite_runs ORDER BY created_at DESC LIMIT 10;
```

---

## 5. Queue Troubleshooting

### Check Worker Status

```bash
supervisorctl status
```

### Restart Workers

```bash
# Graceful restart (finishes current job, then restarts)
cd /www/wwwroot/talentqx.com/api
php82 artisan queue:restart

# Hard restart via supervisor
supervisorctl restart laravel-worker:*
```

### Check Failed Jobs

```bash
cd /www/wwwroot/talentqx.com/api
php82 artisan queue:failed
```

### Retry Failed Jobs

```bash
# Retry all
php82 artisan queue:retry all

# Retry specific job
php82 artisan queue:retry <job-id>

# Flush all failed jobs (destructive)
php82 artisan queue:flush
```

### Check Pending Jobs

```sql
SELECT queue, COUNT(*) as pending, MIN(created_at) as oldest
FROM jobs
GROUP BY queue;
```

### Queue Stuck / Not Processing

1. Check supervisor is running: `supervisorctl status`
2. Check worker logs: `tail -f /www/wwwroot/talentqx.com/api/storage/logs/worker.log`
3. Check for zombie processes: `ps aux | grep queue:work`
4. Kill and restart: `supervisorctl stop all && supervisorctl start all`

---

## 6. Token Security Model

### Sanctum Token Architecture

- Authentication uses Laravel Sanctum personal access tokens.
- Tokens are stored in the `personal_access_tokens` table.
- The `token` column contains a **SHA-256 hash** of the plaintext token. Plaintext is never stored.
- Token format returned to client: `{id}|{plaintext}` (e.g., `177|4j7rq...`). The `{id}` is the row ID, used to look up the hash for comparison.

### Token Lifecycle

- Tokens are created on successful login/authentication.
- Admin tokens are stored client-side in `localStorage` as `octopus_admin_token`.
- API client at `/www/wwwroot/talentqx-frontend/src/lib/octo-admin-api.ts` attaches the token as `Authorization: Bearer {token}`.

### Middleware Stack (Admin)

```
auth:sanctum -> platform.octopus_admin
```

All admin API routes require both a valid Sanctum token and the `octopus_admin` platform guard.

### Rate Limiting

Laravel's built-in rate limiting applies to authentication endpoints. Check `RouteServiceProvider` or `bootstrap/app.php` for throttle configuration.

### Revoking Tokens

```bash
cd /www/wwwroot/talentqx.com/api

# Revoke all tokens for a user
php82 artisan tinker --execute="\$user = \App\Models\User::find(1); \$user->tokens()->delete();"

# Revoke a specific token by ID
php82 artisan tinker --execute="\Laravel\Sanctum\PersonalAccessToken::find(177)->delete();"
```

---

## 7. Incident Playbook

### 7.1 "Class not found" or Autoloader Errors

```bash
cd /www/wwwroot/talentqx.com/api
php82 composer.phar dump-autoload -o
php82 artisan config:cache
```

### 7.2 502 Bad Gateway on API

1. Check PHP-FPM: `systemctl status php-fpm-82` (or check BT Panel)
2. Check nginx error log: `tail -50 /www/wwwroot/talentqx.com/api/storage/logs/nginx-error.log` or `/www/server/panel/vhost/nginx/` logs
3. Restart PHP-FPM: `systemctl restart php-fpm-82`

### 7.3 502/504 on app.talentqx.com

1. Check PM2: `pm2 status`
2. Check if port 3000 is listening: `ss -tlnp | grep 3000`
3. Check Next.js logs: `pm2 logs talentqx-frontend --lines 100`
4. Restart: `pm2 restart talentqx-frontend`

### 7.4 OOM Kill / Server Unresponsive

1. Check: `dmesg | grep -i oom` and `journalctl -k | grep -i oom`
2. Check memory: `free -h`
3. Identify hogs: `ps aux --sort=-%mem | head -20`
4. Ensure swap exists: `swapon --show`
5. Restart biggest offenders or increase swap (see Section 2.1)

### 7.5 MySQL Down / Socket Missing

```bash
systemctl status mysqld
systemctl restart mysqld
# If socket missing, see Section 2.2
```

### 7.6 SSL Certificate Expired

BT Panel handles auto-renewal. If it fails:

1. Check BT Panel > Website > SSL
2. Force renew via panel, or:

```bash
# Check expiry
echo | openssl s_client -servername talentqx.com -connect talentqx.com:443 2>/dev/null | openssl x509 -noout -dates
```

### 7.7 Circular Dependency Error (PoolCandidateService / FormInterviewService)

This was fixed using lazy resolution (`App::make()`). If it reappears, check that `FormInterviewService` is resolved via `App::make()` inside `PoolCandidateService` methods, not injected via constructor.

Namespace: `App\Services\Interview\FormInterviewService` (not `App\Services\FormInterview\`).

### 7.8 Security Headers Missing

If security headers disappear from responses, check that every nginx `location` block includes:

```nginx
include /www/server/panel/vhost/nginx/snippets/security-headers.conf;
```

Location-level `add_header` overrides all server-level headers. This is an nginx behavior, not a bug.

---

## 8. Log Hygiene & Monitoring

### Log Paths

| Log | Path |
|-----|------|
| Laravel application | `/www/wwwroot/talentqx.com/api/storage/logs/laravel.log` |
| Laravel worker | `/www/wwwroot/talentqx.com/api/storage/logs/worker.log` |
| PM2 / Next.js | `~/.pm2/logs/talentqx-frontend-*.log` |
| nginx access | BT Panel managed, typically `/www/wwwlogs/` |
| nginx error | BT Panel managed, typically `/www/wwwlogs/` |
| MySQL | `/var/log/mysql/` or BT Panel managed |
| Supervisor | `/var/log/supervisor/` |

### Log Rotation

- **Laravel logs**: logrotate configured. Verify with `cat /etc/logrotate.d/laravel` (or similar).
- **PM2 logs**: `pm2-logrotate` module installed. Check config: `pm2 conf pm2-logrotate`.
- **nginx / MySQL**: managed by BT Panel's built-in rotation.

### What to Check Daily

```bash
# Laravel errors in the last hour
tail -500 /www/wwwroot/talentqx.com/api/storage/logs/laravel.log | grep -c "ERROR"

# Failed queue jobs
cd /www/wwwroot/talentqx.com/api && php82 artisan queue:failed --limit=5

# PM2 status
pm2 status

# Disk usage
df -h /

# Memory
free -h
```

### What to Check After Deploy

```bash
# Tail Laravel log for errors
tail -f /www/wwwroot/talentqx.com/api/storage/logs/laravel.log

# Tail PM2 log for frontend errors
pm2 logs talentqx-frontend --lines 50

# Confirm API responds
curl -s -o /dev/null -w "%{http_code}" https://talentqx.com/api/health

# Confirm frontend responds
curl -s -o /dev/null -w "%{http_code}" https://app.talentqx.com
```

---

## 9. Performance Notes

### Memory Tuning

- Server: 32 GB RAM + 6 GB swap. Generous, but watch for leaks.
- PHP-FPM `pm.max_children`: sized for available memory. Each PHP-FPM worker uses ~40-80 MB. Check via BT Panel PHP settings.
- Node.js (Next.js): default heap. If OOM, set `NODE_OPTIONS=--max-old-space-size=4096` in PM2 ecosystem config.

### N+1 Query Prevention

- Use `->with()` eager loading on all Eloquent queries that traverse relationships.
- Enable query logging in development: `DB::enableQueryLog()`.
- Use Laravel Debugbar or Telescope locally to spot N+1s.

### Caching Strategy

- Config, routes, views, events are cached in production (see deploy steps).
- Application-level caching uses the `file` driver by default. For heavier loads, switch to Redis.
- Clear application cache when data seems stale:

```bash
cd /www/wwwroot/talentqx.com/api
php82 artisan cache:clear
```

### Database

- Ensure indexes exist on all foreign keys and commonly queried columns.
- Check slow queries: `SHOW FULL PROCESSLIST;` or enable slow query log in MySQL config.

---

## 10. Useful Commands (Quick Reference)

```bash
# --- Laravel ---
cd /www/wwwroot/talentqx.com/api
php82 artisan migrate --force              # Run pending migrations
php82 artisan migrate:status               # Show migration status
php82 artisan config:cache                 # Cache config
php82 artisan route:cache                  # Cache routes
php82 artisan cache:clear                  # Clear app cache
php82 artisan queue:restart                # Graceful queue restart
php82 artisan queue:failed                 # List failed jobs
php82 artisan queue:retry all              # Retry all failed jobs
php82 artisan schedule:list                # Show scheduled tasks
php82 artisan tinker                       # Interactive REPL

# --- Frontend ---
cd /www/wwwroot/talentqx-frontend
npm run build                              # Production build
pm2 restart talentqx-frontend              # Restart frontend
pm2 logs talentqx-frontend --lines 100     # View logs
pm2 status                                 # Process status
pm2 save                                   # Save process list

# --- nginx ---
nginx -t                                   # Test config
nginx -s reload                            # Reload config

# --- Supervisor (Queue Workers) ---
supervisorctl status                       # Worker status
supervisorctl restart all                  # Restart all workers
supervisorctl tail -f laravel-worker:*     # Tail worker logs

# --- System ---
free -h                                    # Memory usage
df -h /                                    # Disk usage
htop                                       # Interactive process viewer
ss -tlnp                                   # Listening ports
dmesg | grep -i oom                        # OOM kill history
journalctl -u mysqld --since "1 hour ago"  # Recent MySQL logs

# --- MySQL ---
mysql -u root -p
SHOW FULL PROCESSLIST;                     # Active queries
SELECT * FROM jobs LIMIT 10;               # Pending queue jobs
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
SELECT * FROM maritime_invite_runs ORDER BY created_at DESC LIMIT 10;

# --- SSL Check ---
echo | openssl s_client -servername talentqx.com -connect talentqx.com:443 2>/dev/null | openssl x509 -noout -dates
```

---

*End of runbook.*
