#!/bin/bash
#
# TalentQX Deploy Script
# Usage: ./deploy.sh [--skip-frontend] [--skip-backend]
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Paths
SRC_DIR="/www/wwwroot/talentqx-src"
PROD_DIR="/www/wwwroot/talentqx.com"
BACKEND_SRC="$SRC_DIR/backend"
FRONTEND_SRC="$SRC_DIR/frontend"
BACKEND_PROD="$PROD_DIR/api"
PHP_BIN="/www/server/php/82/bin/php"

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
echo -e "${GREEN}   TalentQX Deploy Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# 1. Git Pull
echo -e "${YELLOW}[1/5] Git pull...${NC}"
cd "$SRC_DIR"
git pull origin main
echo -e "${GREEN}OK${NC}"
echo ""

# 2. Backend Deploy
if [ "$SKIP_BACKEND" = false ]; then
    echo -e "${YELLOW}[2/5] Backend deploy...${NC}"

    # Sync backend files (exclude vendor, storage, .env)
    rsync -av --delete \
        --exclude='vendor' \
        --exclude='storage/app' \
        --exclude='storage/logs' \
        --exclude='storage/framework/cache' \
        --exclude='storage/framework/sessions' \
        --exclude='storage/framework/views' \
        --exclude='.env' \
        --exclude='node_modules' \
        "$BACKEND_SRC/" "$BACKEND_PROD/"

    # Composer install (production)
    cd "$BACKEND_PROD"
    $PHP_BIN composer.phar install --no-dev --optimize-autoloader --no-interaction

    # Laravel optimizations
    $PHP_BIN artisan config:cache
    $PHP_BIN artisan route:cache
    $PHP_BIN artisan view:cache

    # Run migrations (if any)
    $PHP_BIN artisan migrate --force

    echo -e "${GREEN}Backend OK${NC}"
else
    echo -e "${YELLOW}[2/5] Backend skipped${NC}"
fi
echo ""

# 3. Frontend Build
if [ "$SKIP_FRONTEND" = false ]; then
    echo -e "${YELLOW}[3/5] Frontend build...${NC}"
    cd "$FRONTEND_SRC"

    # Install dependencies
    npm ci --silent

    # Build
    npm run build

    echo -e "${GREEN}Frontend build OK${NC}"
else
    echo -e "${YELLOW}[3/5] Frontend skipped${NC}"
fi
echo ""

# 4. Frontend Deploy
if [ "$SKIP_FRONTEND" = false ]; then
    echo -e "${YELLOW}[4/5] Frontend deploy...${NC}"

    # Copy dist to production
    cp -r "$FRONTEND_SRC/dist/"* "$PROD_DIR/"

    echo -e "${GREEN}Frontend deploy OK${NC}"
else
    echo -e "${YELLOW}[4/5] Frontend skipped${NC}"
fi
echo ""

# 5. Cleanup & Permissions
echo -e "${YELLOW}[5/5] Cleanup & permissions...${NC}"

# Set permissions
chown -R www:www "$PROD_DIR"
chmod -R 755 "$PROD_DIR"
chmod -R 775 "$BACKEND_PROD/storage"
chmod -R 775 "$BACKEND_PROD/bootstrap/cache"

# Clear opcache (optional - restart php-fpm)
# systemctl restart php82-fpm

echo -e "${GREEN}OK${NC}"
echo ""

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   Deploy Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Site: https://talentqx.com"
echo ""
