#!/usr/bin/env bash

# Yalıhan Bekçi: Pre-Deploy Smoke Test
# Usage: ./scripts/smoke.sh [environment]

set -euo pipefail

ENV=${1:-local}
echo "🚀 Yalıhan Bekçi: Pre-Deploy Smoke Test ($ENV)"
echo "==============================================="
echo ""

FAILED=0
REPORT_FILE="storage/logs/smoke-$(date +%Y%m%d-%H%M%S).log"

# Set base URL based on environment
case $ENV in
    local)
        BASE_URL="http://127.0.0.1:8002"
        ;;
    staging)
        BASE_URL="https://staging.yalihanemlak.com"
        ;;
    production)
        BASE_URL="https://yalihanemlak.com"
        ;;
    *)
        echo "❌ Unknown environment: $ENV"
        exit 1
        ;;
esac

log_step() {
    echo "$1" | tee -a "$REPORT_FILE"
}

log_step "Environment: $ENV"
log_step "Base URL: $BASE_URL"
log_step ""

# Critical smoke tests only
log_step "📋 CRITICAL SMOKE TESTS"
log_step "======================="

log_step ""
log_step "1️⃣ Ilan Publish/Unpublish Authorization"
log_step "----------------------------------------"
php artisan test --filter="IlanYayinDurumuAuthorizationTest" 2>&1 | tee -a "$REPORT_FILE" || FAILED=$((FAILED + 1))

log_step ""
log_step "2️⃣ Features Load Test"
log_step "---------------------"
RESPONSE=$(curl -s "$BASE_URL/api/v1/admin/category/arsa-arazi/frontend-features?yayin_tipi_id=1" -H "Accept: application/json")
echo "$RESPONSE" | tee -a "$REPORT_FILE"

# Check if response is JSON
if echo "$RESPONSE" | jq . >/dev/null 2>&1; then
    log_step "✅ Valid JSON response"

    # Check for HTML in response (should not happen)
    if echo "$RESPONSE" | grep -q "<!DOCTYPE\|<html\|<body"; then
        log_step "❌ CRITICAL: HTML found in JSON response!"
        FAILED=$((FAILED + 1))
    fi
else
    log_step "❌ CRITICAL: Invalid JSON response!"
    FAILED=$((FAILED + 1))
fi

log_step ""
log_step "3️⃣ Map Endpoint JSON Check"
log_step "---------------------------"
RESPONSE=$(curl -s "$BASE_URL/api/v1/location/poi-distances" \
    -X POST \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"latitude": 37.0, "longitude": 27.5}' 2>&1)

echo "$RESPONSE" | tee -a "$REPORT_FILE"

if echo "$RESPONSE" | jq . >/dev/null 2>&1; then
    log_step "✅ Valid JSON response"
else
    log_step "⚠️  Warning: POI endpoint might need auth"
fi

log_step ""
log_step "============================================"
log_step "SMOKE TEST SUMMARY"
log_step "============================================"
if [ $FAILED -eq 0 ]; then
    log_step "✅ ALL SMOKE TESTS PASSED - Safe to deploy!"
    log_step ""
    log_step "Report saved to: $REPORT_FILE"
    exit 0
else
    log_step "❌ $FAILED CRITICAL TEST(S) FAILED - BLOCK DEPLOY!"
    log_step ""
    log_step "Report saved to: $REPORT_FILE"
    log_step ""
    log_step "🚨 DO NOT DEPLOY - Fix critical issues first!"
    exit 1
fi
