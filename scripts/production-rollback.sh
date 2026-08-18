#!/usr/bin/env bash
# YALIHAN-OS Production Deployment Rollback Script
#
# Usage:
#   bash scripts/production-rollback.sh
#
# What it does:
#   1. Stops new containers (yalihanai-nginx, yalihanai-app)
#   2. Removes new images
#   3. Legacy container (yalihanai-app :8002) remains untouched
#   4. api.yalihanemlak.com.tr still points to legacy until switched
#
# Prerequisite: Run from /opt/yalihan-os-production/releases/<timestamp>

set -euo pipefail

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOGFILE="/var/log/rollback-${TIMESTAMP}.log"

log() { echo "[$(date '+%H:%M:%S')] $1" | tee -a "$LOGFILE"; }

log "=== YALIHAN Production Rollback ==="
log "Timestamp: ${TIMESTAMP}"
log ""

# ── 1. Stop new containers ─────────────────────────────────────────────────
log "1. Stopping new containers..."
docker compose -f docker-compose.production.yml stop yalihanai-nginx yalihanai-app 2>/dev/null || true
docker stop yalihanai-nginx yalihanai-app 2>/dev/null || true

# ── 2. Remove containers ────────────────────────────────────────────────
log "2. Removing containers..."
docker rm -f yalihanai-nginx yalihanai-app 2>/dev/null || true

# ── 3. Prune new images ────────────────────────────────────────────────
log "3. Pruning new images..."
docker image prune -f --filter "label=maintainer=yalihan" 2>/dev/null || true

# ── 4. Confirm legacy is still running ─────────────────────────────────
log "4. Legacy containers still running:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | tee -a "$LOGFILE" || true

# ── 5. Health confirm legacy ───────────────────────────────────────────
log "5. Legacy health check:"
LEGACY_HEALTH=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8002/api/v1/health 2>/dev/null || echo "000")
log "Legacy /api/v1/health: HTTP ${LEGACY_HEALTH}"
if [[ "${LEGACY_HEALTH}" == "200" ]]; then
    log "✅ Legacy container healthy — rollback complete"
else
    log "⚠️  WARNING: Legacy health check FAILED"
fi

log ""
log "=== Rollback complete ==="
log "Log: ${LOGFILE}"
log "Legacy :8002: $(docker ps --filter name=yalihanai-app --format '{{.Status}}' 2>/dev/null || 'STOPPED')"
