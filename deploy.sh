#!/bin/bash
# CPX62 Deploy / Post-Rebuild Script
# Usage: bash /www/wwwroot/talentqx.com/api/deploy.sh
#
# Covers: MySQL socket, Composer, Laravel cache, migrations,
#         queue workers, all PHP-FPM versions, nginx, PM2

set -e

API_DIR="/www/wwwroot/talentqx.com/api"
FRONTEND_DIR="/www/wwwroot/talentqx-frontend"
PHP="/www/server/php/82/bin/php"

echo "=== CPX62 Deploy ==="
echo "$(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# 1. MySQL socket symlink (needed by all PHP versions)
echo "[1/8] MySQL socket symlink..."
ln -sf /run/mysqld/mysqld.sock /tmp/mysql.sock
echo "  OK"

# 2. Composer
echo "[2/8] Composer install..."
cd "$API_DIR"
$PHP /usr/bin/composer install --no-dev --optimize-autoloader --quiet
echo "  OK"

# 3. Laravel cache
echo "[3/8] Laravel optimize..."
$PHP artisan optimize:clear --quiet
$PHP artisan config:cache --quiet
$PHP artisan route:cache --quiet
$PHP artisan view:cache --quiet
echo "  OK"

# 4. Migrations
echo "[4/8] Migrations..."
$PHP artisan migrate --force --quiet
echo "  OK"

# 5. Queue restart
echo "[5/8] Queue restart..."
$PHP artisan queue:restart --quiet
supervisorctl restart all
echo "  OK"

# 6. All PHP-FPM versions restart
echo "[6/8] PHP-FPM restart (all versions)..."
for ver in 73 74 80 81 82 83 84; do
    systemctl restart php-fpm-$ver 2>/dev/null && echo "  php-fpm-$ver restarted" || true
done
echo "  OK"

# 7. nginx reload (not restart — safer)
echo "[7/8] nginx reload..."
nginx -t -q && nginx -s reload
echo "  OK"

# 8. PM2
echo "[8/8] PM2 restart..."
pm2 restart all
pm2 save --force
echo "  OK"

echo ""
echo "=== Deploy complete ==="
echo ""
echo "--- Supervisor ---"
supervisorctl status
echo ""
echo "--- PM2 ---"
pm2 list
echo ""
echo "--- Memory ---"
free -h
