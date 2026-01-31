#!/bin/bash
#
# TalentQX Deploy Script (Safe Version)
# Usage: ./deploy.sh [--skip-frontend] [--skip-backend]
#
# IMPORTANT: This script NEVER deletes files from production.
# It only adds/updates files from git source.
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Paths
SRC_DIR="/www/wwwroot/talentqx-src"
PROD_DIR="/www/wwwroot/talentqx.com"
BACKEND_SRC="$SRC_DIR/backend"
FRONTEND_SRC="$SRC_DIR/frontend"
BACKEND_PROD="$PROD_DIR/api"
PHP_BIN="/www/server/php/82/bin/php"
BACKUP_DIR="/www/wwwroot/backups"

# Flags
SKIP_FRONTEND=false
SKIP_BACKEND=false

# Parse arguments
for arg in "$@"; do
    case $arg in
        --skip-frontend) SKIP_FRONTEND=true ;;
        --skip-backend) SKIP_BACKEND=true ;;
    esac
done

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   TalentQX Deploy Script (Safe)${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# 0. Pre-deploy backup
echo -e "${YELLOW}[0/6] Creating backup...${NC}"
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/api_$(date +%Y%m%d_%H%M%S).tar.gz"
tar -czf "$BACKUP_FILE" -C /www/wwwroot talentqx.com/api 2>/dev/null || true
echo -e "Backup: $BACKUP_FILE"
echo -e "${GREEN}OK${NC}"
echo ""

# 1. Git Pull
echo -e "${YELLOW}[1/6] Git pull...${NC}"
cd "$SRC_DIR"
git pull origin main
echo -e "${GREEN}OK${NC}"
echo ""

# 2. Backend Deploy
if [ "$SKIP_BACKEND" = false ]; then
    echo -e "${YELLOW}[2/6] Backend deploy...${NC}"

    # Sync backend files (NO --delete flag!)
    rsync -av \
        --exclude='vendor' \
        --exclude='storage' \
        --exclude='.env' \
        --exclude='bootstrap/cache/*.php' \
        "$BACKEND_SRC/" "$BACKEND_PROD/"

    # Composer install
    cd "$BACKEND_PROD"
    if [ -f "composer.phar" ]; then
        $PHP_BIN composer.phar install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-fileinfo
    fi

    # Laravel cache
    $PHP_BIN artisan config:cache
    $PHP_BIN artisan route:cache
    $PHP_BIN artisan view:cache

    # Run migrations
    $PHP_BIN artisan migrate --force

    echo -e "${GREEN}Backend OK${NC}"
else
    echo -e "${YELLOW}[2/6] Backend skipped${NC}"
fi
echo ""

# 3. Frontend Build
if [ "$SKIP_FRONTEND" = false ]; then
    echo -e "${YELLOW}[3/6] Frontend build...${NC}"
    cd "$FRONTEND_SRC"
    npm ci --silent
    npm run build
    echo -e "${GREEN}Frontend build OK${NC}"
else
    echo -e "${YELLOW}[3/6] Frontend skipped${NC}"
fi
echo ""

# 4. Frontend Deploy
if [ "$SKIP_FRONTEND" = false ]; then
    echo -e "${YELLOW}[4/6] Frontend deploy...${NC}"
    # NO --delete flag!
    rsync -av "$FRONTEND_SRC/dist/" "$PROD_DIR/"
    echo -e "${GREEN}Frontend deploy OK${NC}"
else
    echo -e "${YELLOW}[4/6] Frontend skipped${NC}"
fi
echo ""

# 5. Permissions
echo -e "${YELLOW}[5/6] Fixing permissions...${NC}"
chown -R www:www "$PROD_DIR"
chmod -R 755 "$PROD_DIR"
chmod -R 775 "$BACKEND_PROD/storage"
chmod -R 775 "$BACKEND_PROD/bootstrap/cache"
echo -e "${GREEN}OK${NC}"
echo ""

# 6. Health Check
echo -e "${YELLOW}[6/6] Health check...${NC}"
HEALTH=$(curl -s https://talentqx.com/api/v1/health 2>/dev/null | grep -o '"status":"ok"' || echo "FAILED")
if [ "$HEALTH" = '"status":"ok"' ]; then
    echo -e "${GREEN}API: OK${NC}"
else
    echo -e "${RED}API: FAILED - Check logs!${NC}"
fi
echo ""

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   Deploy Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Site: https://talentqx.com"
echo "Backup: $BACKUP_FILE"
echo ""
