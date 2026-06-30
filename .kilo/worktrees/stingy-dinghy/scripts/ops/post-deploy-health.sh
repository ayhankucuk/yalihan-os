#!/usr/bin/env bash

# SAB Post-Deploy Health Check
# İlk 10 dakikada çalıştırılacak kritik endpoint kontrolü
# Usage: ./scripts/post-deploy-health.sh [local|staging|production]

set -euo pipefail

ENV=${1:-local}
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
REPORT_FILE="storage/logs/post-deploy-health-${TIMESTAMP}.log"
mkdir -p "$(dirname "$REPORT_FILE")"

case $ENV in
    local)      BASE_URL="http://127.0.0.1:8002" ;;
    staging)    BASE_URL="https://staging.yalihanemlak.com" ;;
    production) BASE_URL="https://yalihanemlak.com" ;;
    *)
        echo "❌ Unknown environment: $ENV (local|staging|production)"
        exit 1
        ;;
esac

FAILED=0
TOTAL=0

log() { echo "$1" | tee -a "$REPORT_FILE"; }

check_page() {
    local name="$1"
    local path="$2"
    local expect_code="${3:-200}"
    local url="${BASE_URL}${path}"
    TOTAL=$((TOTAL + 1))

    local http_code
    http_code=$(curl -s -o /dev/null -w "%{http_code}" -L --max-time 10 "$url" 2>/dev/null || echo "000")

    if [ "$http_code" = "000" ]; then
        log "  🚨 UNREACHABLE  $name ($url) — connection failed"
        FAILED=$((FAILED + 1))
        return 1
    fi

    # Accept expected code or 302 (auth redirect is normal for protected pages)
    if [ "$http_code" = "$expect_code" ] || [ "$http_code" = "302" ]; then
        log "  ✅ OK ($http_code)    $name"
        return 0
    fi

    if [ "$http_code" -ge 500 ]; then
        log "  🚨 CRITICAL ($http_code) $name ($url)"
        FAILED=$((FAILED + 1))
        return 1
    fi

    if [ "$http_code" = "403" ]; then
        log "  ⚠️  FORBIDDEN ($http_code) $name — permission issue?"
        FAILED=$((FAILED + 1))
        return 1
    fi

    log "  ⚠️  UNEXPECTED ($http_code) $name ($url)"
    return 0
}

check_api() {
    local name="$1"
    local path="$2"
    local url="${BASE_URL}${path}"
    TOTAL=$((TOTAL + 1))

    local response
    response=$(curl -s -w "\n%{http_code}" -L --max-time 10 "$url" -H "Accept: application/json" 2>/dev/null || echo -e "\n000")
    local http_code
    http_code=$(echo "$response" | tail -1)
    local body
    body=$(echo "$response" | sed '$d')

    if [ "$http_code" = "000" ]; then
        log "  🚨 UNREACHABLE  $name — connection failed"
        FAILED=$((FAILED + 1))
        return 1
    fi

    if [ "$http_code" -ge 500 ]; then
        log "  🚨 CRITICAL ($http_code) $name"
        FAILED=$((FAILED + 1))
        return 1
    fi

    # Check for HTML in JSON response (broken endpoint)
    if echo "$body" | grep -q "<!DOCTYPE\|<html\|<body" 2>/dev/null; then
        log "  🚨 HTML-IN-JSON  $name — endpoint returning HTML!"
        FAILED=$((FAILED + 1))
        return 1
    fi

    log "  ✅ OK ($http_code)    $name"
    return 0
}

log ""
log "═══════════════════════════════════════════════════════"
log "  SAB Post-Deploy Health Check"
log "  Environment: $ENV"
log "  Base URL:    $BASE_URL"
log "  Timestamp:   $(date '+%Y-%m-%d %H:%M:%S')"
log "═══════════════════════════════════════════════════════"
log ""

# ── Phase 1: Critical Pages ──────────────────────────────
log "📋 Phase 1: Kritik Sayfalar"
log "───────────────────────────"
check_page "Login"          "/login"
check_page "Dashboard"      "/admin/dashboard"
check_page "Features"       "/admin/property-hub/features"
check_page "Templates"      "/admin/property-hub/templates"
check_page "Kişiler"        "/admin/kisiler"
check_page "İlan Oluştur"   "/admin/ilanlar/create"
log ""

# ── Phase 2: API Endpoints ───────────────────────────────
log "📋 Phase 2: API Endpoints"
log "───────────────────────────"
check_api "Validation Rules"   "/api/v1/wizard/validation-rules"
check_api "Frontend Features"  "/api/v1/admin/category/daire/frontend-features?yayin_tipi_id=1"
log ""

# ── Phase 3: Artisan Health Commands ─────────────────────
log "📋 Phase 3: Artisan Health"
log "───────────────────────────"

if command -v php &>/dev/null; then
    TOTAL=$((TOTAL + 1))
    if php artisan ups:health --performance 2>&1 | tee -a "$REPORT_FILE" | grep -qi "fail\|error\|critical"; then
        log "  ⚠️  ups:health reported issues"
        FAILED=$((FAILED + 1))
    else
        log "  ✅ ups:health OK"
    fi

    TOTAL=$((TOTAL + 1))
    if php artisan queue:check-worker 2>&1 | tee -a "$REPORT_FILE" | grep -qi "not running\|fail\|error"; then
        log "  ⚠️  queue:check-worker reported issues"
        FAILED=$((FAILED + 1))
    else
        log "  ✅ queue:check-worker OK"
    fi
else
    log "  ⏭️  Skipped artisan checks (php not available)"
fi

log ""

# ── Summary ──────────────────────────────────────────────
log "═══════════════════════════════════════════════════════"
if [ $FAILED -eq 0 ]; then
    log "  ✅ ALL CHECKS PASSED ($TOTAL/$TOTAL)"
    log "  Sistem stabil görünüyor."
else
    log "  ❌ $FAILED/$TOTAL CHECK(S) FAILED"
    log "  Rollback değerlendirmesi gerekebilir!"
    log "  Bkz: docs/runbooks/deploy-24h-survival-plan.md"
fi
log "  Report: $REPORT_FILE"
log "═══════════════════════════════════════════════════════"

exit $FAILED
