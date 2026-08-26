#!/bin/bash
# =============================================================================
# YALIHAN OS — Production Deploy Script
# =============================================================================
# Usage: ./scripts/deploy.sh
#
# This script handles the full production deployment:
#   1. Pull latest code from git
#   2. Build frontend assets (npm)
#   3. Build Docker images
#   4. Recreate containers
#   5. Clear Laravel caches
#   6. Verify deployment
# =============================================================================

set -e

APP_DIR="/opt/yalihan2026/current"
BRANCH="integration/era-v-phase2a-e01"
COMPOSE_FILE="docker-compose.production.yml"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# -----------------------------------------------------------------------------
# Step 1: Pull latest code
# -----------------------------------------------------------------------------
log_info "Step 1/6: Pulling latest code..."
cd "$APP_DIR"
git fetch origin
git checkout -B "$BRANCH" "origin/$BRANCH"
log_info "Code updated to: $(git log -1 --oneline)"

# -----------------------------------------------------------------------------
# Step 2: Build frontend assets
# -----------------------------------------------------------------------------
log_info "Step 2/6: Building frontend assets..."
npm ci --silent 2>/dev/null || npm install --silent 2>/dev/null
npm run build
log_info "Assets built successfully"

# -----------------------------------------------------------------------------
# Step 3: Rebuild Docker images
# -----------------------------------------------------------------------------
log_info "Step 3/6: Rebuilding Docker images..."
docker compose -f "$COMPOSE_FILE" build --no-cache yalihanai-app-v2 yalihanai-nginx-v2

# -----------------------------------------------------------------------------
# Step 4: Recreate containers
# -----------------------------------------------------------------------------
log_info "Step 4/6: Recreating containers..."
docker compose -f "$COMPOSE_FILE" up -d --force-recreate yalihanai-app-v2 yalihanai-nginx-v2 yalihanai-queue-v2

# Wait for app to be healthy
log_info "Waiting for app container to be healthy..."
for i in {1..30}; do
    STATUS=$(docker inspect --format='{{.State.Health.Status}}' yalihanai-app-v2 2>/dev/null || echo "not_found")
    if [ "$STATUS" = "healthy" ]; then
        log_info "App container is healthy"
        break
    fi
    if [ $i -eq 30 ]; then
        log_error "App container did not become healthy in time"
        exit 1
    fi
    sleep 2
done

# -----------------------------------------------------------------------------
# Step 5: Clear Laravel caches
# -----------------------------------------------------------------------------
log_info "Step 5/6: Clearing Laravel caches..."
docker exec yalihanai-app-v2 php artisan view:clear
docker exec yalihanai-app-v2 php artisan cache:clear
docker exec yalihanai-app-v2 php artisan config:clear
log_info "Caches cleared"

# -----------------------------------------------------------------------------
# Step 6: Verify deployment
# -----------------------------------------------------------------------------
log_info "Step 6/6: Verifying deployment..."
CONTAINER_STATUS=$(docker ps --format '{{.Names}}: {{.Status}}' | grep -E "yalihanai-app-v2|yalihanai-nginx-v2|yalihanai-queue-v2")
echo "$CONTAINER_STATUS"

# Test HTTP response
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://yalihanemlak.com.tr/ --max-time 10 || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    log_info "✅ Deployment verified! Site returns HTTP 200"
else
    log_warn "⚠️ Site returned HTTP $HTTP_CODE"
fi

log_info "Deployment complete!"
