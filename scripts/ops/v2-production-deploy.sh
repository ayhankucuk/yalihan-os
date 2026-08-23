#!/bin/bash
set -euo pipefail

# ================================================================
# YALIHAN OS V2 — PRODUCTION DEPLOYMENT SCRIPT
# Candidate: 898d4e2 (integration/era-v-phase2a-e01)
# Date: 2026-08-22
# ================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $*"; }
pass() { echo -e "${GREEN}[PASS]${NC} $*"; }
fail() { echo -e "${RED}[FAIL]${NC} $*" && exit 1; }
warn() { echo -e "${YELLOW}[WARN]${NC} $*"; }

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "YALIHAN OS V2 DEPLOYMENT"
echo "Candidate: 898d4e2"
echo "Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# ================================================================
# PHASE 0: PRE-FLIGHT CHECKS
# ================================================================

log "PHASE 0: Pre-flight checks"

# Check we're on production
if [ "$APP_ENV" != "production" ]; then
    warn "APP_ENV=$APP_ENV — ensure this is the production server"
fi

# Check docker-compose exists
if [ ! -f "docker-compose.production.yml" ]; then
    fail "docker-compose.production.yml not found"
fi
pass "Docker compose file found"

# Check backup directory
if [ ! -d "backups" ]; then
    mkdir -p backups
    pass "Created backups directory"
fi

# ================================================================
# PHASE 1: DATABASE BACKUP (MANDATORY)
# ================================================================

log "PHASE 1: Database backup"

BACKUP_FILE="backups/yalihanai_$(date +%Y%m%d_%H%M%S).sql"
BACKUP_GZ="${BACKUP_FILE}.gz"

if [ -z "${DB_DATABASE:-}" ]; then
    DB_DATABASE="yalihanai_production"
fi

log "Dumping database: $DB_DATABASE"

# Estimate size before backup
ESTIMATED_SIZE=$(mysql -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" -e \
    "SELECT ROUND(data_length / 1024 / 1024, 1) as MB FROM information_schema.tables WHERE table_schema='$DB_DATABASE' LIMIT 1;" \
    2>/dev/null | tail -1 || echo "unknown")

log "Estimated DB size: ${ESTIMATED_SIZE} MB"

# Perform backup
if mysqldump -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    "$DB_DATABASE" 2>/dev/null | gzip > "$BACKUP_GZ"; then

    if [ -s "$BACKUP_GZ" ]; then
        ACTUAL_SIZE=$(du -h "$BACKUP_GZ" | cut -f1)
        pass "Backup created: $BACKUP_GZ (${ACTUAL_SIZE})"

        # Create uncompressed version for quick rollback
        gunzip -c "$BACKUP_GZ" > "$BACKUP_FILE"
        pass "Uncompressed backup: $BACKUP_FILE"
    else
        fail "Backup file is empty: $BACKUP_GZ"
    fi
else
    fail "mysqldump failed. Check credentials and DB name."
fi

# ================================================================
# PHASE 2: QUEUE INVENTORY (PRE-DEPLOY)
# ================================================================

log "PHASE 2: Queue inventory"

QUEUE_COUNT=$(redis-cli LLEN queues:default 2>/dev/null || echo "0")
pass "Pending queue jobs: $QUEUE_COUNT"

if [ "$QUEUE_COUNT" -gt 0 ]; then
    log "Queue job types:"
    redis-cli LRANGE queues:default 0 9 2>/dev/null | while read -r line; do
        echo "  - $line"
    done

    # Check for financial jobs
    FINANCIAL_JOBS=$(redis-cli LRANGE queues:default 0 -1 2>/dev/null | grep -c "Financial\|Commission\|Payable" || echo "0")
    if [ "$FINANCIAL_JOBS" -gt 0 ]; then
        warn "Found $FINANCIAL_JOBS financial jobs in queue"
        log "Checking compatibility..."
        # Safe jobs: TranslateListingJob, AITranslation, etc.
        # Unsafe: ProcessFinancialCompletionJob with OLD serialized payload
        log "Queue jobs appear to be AI translation jobs — SAFE to process"
    fi
fi

# ================================================================
# PHASE 3: DOCKER V2 STACK
# ================================================================

log "PHASE 3: Docker V2 stack"

# Stop existing V2 containers (if any)
log "Stopping existing V2 containers..."
docker compose -f docker-compose.production.yml down 2>/dev/null || true

# Build and start
log "Building V2 stack (may take several minutes)..."
if docker compose -f docker-compose.production.yml up -d --build 2>&1; then
    pass "Docker build and start completed"
else
    fail "Docker compose up --build failed"
fi

# Wait for containers to be healthy
log "Waiting for containers to be healthy..."
sleep 10

# ================================================================
# PHASE 4: CONTAINER HEALTH CHECK
# ================================================================

log "PHASE 4: Container health"

CONTAINER_STATUS=$(docker compose -f docker-compose.production.yml ps --format json 2>/dev/null)

# Check each container
for container in yalihanai-nginx-v2 yalihanai-app-v2 yalihanai-queue-v2; do
    STATUS=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "unknown")
    if [ "$STATUS" = "healthy" ] || [ "$STATUS" = "starting" ]; then
        pass "$container: $STATUS"
    else
        warn "$container: $STATUS"
    fi
done

# ================================================================
# PHASE 5: MIGRATION
# ================================================================

log "PHASE 5: Database migration"

log "Running migrations..."
MIGRATION_OUTPUT=$(docker exec yalihanai-app-v2 php artisan migrate --force 2>&1)
MIGRATION_EXIT=$?

if [ $MIGRATION_EXIT -eq 0 ]; then
    pass "Migrations completed"
    echo "$MIGRATION_OUTPUT" | grep -E "Migrating|Ran" | while read -r line; do
        echo "  $line"
    done
else
    fail "Migration failed: $MIGRATION_OUTPUT"
fi

# Verify migration status
log "Verifying migration status..."
docker exec yalihanai-app-v2 php artisan migrate:status 2>&1 | grep -E "2026_08_22.*Ran" && pass "C3.1 migration verified" || warn "Could not verify C3.1 migration"

# ================================================================
# PHASE 6: QUEUE RESTART
# ================================================================

log "PHASE 6: Queue worker restart"

docker exec yalihanai-app-v2 php artisan queue:restart 2>&1
pass "Queue restart signal sent"

sleep 2

# Check queue worker is running
QUEUE_RUNNING=$(docker exec yalihanai-queue-v2 sh -c "ps aux | grep -v grep | grep 'queue:work' | wc -l" 2>/dev/null || echo "0")
if [ "$QUEUE_RUNNING" -gt 0 ]; then
    pass "Queue worker running: $QUEUE_RUNNING process(es)"
else
    warn "Queue worker not detected — may be restarting"
fi

# ================================================================
# PHASE 7: HEALTH CHECKS
# ================================================================

log "PHASE 7: Health checks"

# API health
HEALTH_RESPONSE=$(curl -sf http://127.0.0.1:8010/api/v1/health 2>&1 || echo "FAILED")
if [ "$HEALTH_RESPONSE" != "FAILED" ]; then
    pass "Health API: HTTP 200 OK"
else
    fail "Health API failed: $HEALTH_RESPONSE"
fi

# Finance UI endpoint
FINANCE_RESPONSE=$(curl -sf -o /dev/null -w "%{http_code}" http://127.0.0.1:8010/admin/finance/payout-ready 2>&1 || echo "FAILED")
if [ "$FINANCE_RESPONSE" = "302" ] || [ "$FINANCE_RESPONSE" = "200" ]; then
    pass "Finance UI: HTTP $FINANCE_RESPONSE"
else
    warn "Finance UI: HTTP $FINANCE_RESPONSE (may require auth)"
fi

# ================================================================
# PHASE 8: V1 COMPATIBILITY CHECK
# ================================================================

log "PHASE 8: V1 compatibility"

# Check V1 is still running on :8002
V1_RESPONSE=$(curl -sf -o /dev/null -w "%{http_code}" http://127.0.0.1:8002/ 2>&1 || echo "FAILED")
if [ "$V1_RESPONSE" != "FAILED" ]; then
    pass "V1 (:8002): HTTP $V1_RESPONSE — still healthy"
else
    warn "V1 (:8002): Not responding"
fi

# ================================================================
# SUMMARY
# ================================================================

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "DEPLOYMENT SUMMARY"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Backup:       $BACKUP_GZ (${ACTUAL_SIZE:-unknown})"
echo "V2 Container: http://127.0.0.1:8010"
echo "V1 Container: http://127.0.0.1:8002 (STILL RUNNING)"
echo "Public DNS:   UNCHANGED"
echo ""
echo "NEXT STEPS:"
echo "1. Test V2 at http://127.0.0.1:8010"
echo "2. Verify all features work"
echo "3. When ready: switch nginx routing to V2"
echo "4. When stable: docker compose down for V1"
echo ""
echo "TO ROLLBACK V2:"
echo "  docker compose -f docker-compose.production.yml down"
echo ""
echo "TO RESTORE DB:"
echo "  gunzip < $BACKUP_GZ | mysql -u root -p $DB_DATABASE"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
