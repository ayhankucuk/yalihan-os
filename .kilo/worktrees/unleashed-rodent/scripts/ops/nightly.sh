#!/usr/bin/env bash

# Yalıhan Bekçi: Nightly Full Check
# Usage: ./scripts/nightly.sh

set -euo pipefail

echo "🌙 Yalıhan Bekçi: Nightly Full Check Starting..."
echo "================================================"
echo ""

REPORT_FILE="storage/logs/nightly-$(date +%Y%m%d).log"
PREV_REPORT="storage/logs/nightly-$(date -v-1d +%Y%m%d 2>/dev/null || date -d "yesterday" +%Y%m%d 2>/dev/null || echo "none").log"
FAILED=0

DB_HOST="${NIGHTLY_DB_HOST:-127.0.0.1}"
DB_PORT="${NIGHTLY_DB_PORT:-3306}"
DB_NAME="${NIGHTLY_DB_NAME:-yalihanai}"
DB_USER="${NIGHTLY_DB_USER:-root}"
DB_PASS="${NIGHTLY_DB_PASS:-}"

# Create report header
cat > "$REPORT_FILE" <<EOF
YALIHAN BEKÇİ NIGHTLY FULL REPORT
Generated: $(date)
Environment: $(php artisan env)

============================================
EOF

log_step() {
    echo "$1" | tee -a "$REPORT_FILE"
}

run_step() {
    local title="$1"
    shift

    if "$@" 2>&1 | tee -a "$REPORT_FILE"; then
        log_step "✅ ${title}"
    else
        log_step "❌ ${title}"
        FAILED=$((FAILED + 1))
    fi
}

mysql_exec() {
    local sql="$1"
    MYSQL_PWD="$DB_PASS" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" -e "$sql"
}

log_step ""
log_step "📋 STEP 1: Full Test Suite"
log_step "==========================="
run_step "Full Test Suite FAILED" php artisan test

log_step ""
log_step "📋 STEP 2: SAB Governance Drift Scan"
log_step "======================================"
run_step "SAB Governance Drift Scan FAILED" php artisan gov:drift:scan

log_step ""
log_step "📋 STEP 3: Bekçi Wizard Contract Audit"
log_step "======================================="
run_step "Bekçi Wizard Contract Audit FAILED" php artisan bekci:wizard-contract

log_step ""
log_step "📋 STEP 4: DB Schema Smoke Tests"
log_step "================================="
log_step "DB Target: ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"

log_step "Checking yayin_tipi_sablonlari schema..."
if ! mysql_exec "SHOW COLUMNS FROM yayin_tipi_sablonlari;" 2>&1 | tee -a "$REPORT_FILE"; then
    log_step "❌ Table check failed"
    FAILED=$((FAILED + 1))
fi

log_step ""
log_step "Checking ilanlar.yayin_durumu column..."
if ! mysql_exec "SHOW COLUMNS FROM ilanlar LIKE 'yayin_durumu';" 2>&1 | tee -a "$REPORT_FILE"; then
    log_step "❌ Column check failed"
    FAILED=$((FAILED + 1))
fi

log_step ""
log_step "Checking yayin_tipleri data..."
if ! mysql_exec "SELECT COUNT(*) as total, COUNT(CASE WHEN aktiflik_durumu=1 THEN 1 END) as active FROM yayin_tipleri;" 2>&1 | tee -a "$REPORT_FILE"; then
    log_step "❌ Data check failed"
    FAILED=$((FAILED + 1))
fi

log_step ""
log_step "📋 STEP 5: API Endpoint Smoke Tests"
log_step "===================================="

log_step "Testing frontend-features endpoint..."
if ! curl -s "http://127.0.0.1:8002/api/v1/admin/category/arsa-arazi/frontend-features?yayin_tipi_id=1" \
    -H "Accept: application/json" 2>&1 | tee -a "$REPORT_FILE" | head -20; then
    log_step "❌ Endpoint test failed"
    FAILED=$((FAILED + 1))
fi

log_step ""
log_step "Testing validation-rules endpoint..."
if ! curl -s "http://127.0.0.1:8002/api/v1/wizard/validation-rules" \
    -H "Accept: application/json" 2>&1 | tee -a "$REPORT_FILE" | head -20; then
    log_step "❌ Endpoint test failed"
    FAILED=$((FAILED + 1))
fi

log_step ""
log_step "============================================"
log_step "DIFF FROM PREVIOUS NIGHT"
log_step "============================================"
if [ -f "$PREV_REPORT" ]; then
    log_step "Comparing with: $PREV_REPORT"
    diff "$PREV_REPORT" "$REPORT_FILE" | head -50 | tee -a "$REPORT_FILE" || log_step "No significant changes"
else
    log_step "No previous report found for comparison"
fi

log_step ""
log_step "Report saved to: $REPORT_FILE"
log_step "Nightly check completed at $(date)"

if [ "$FAILED" -gt 0 ]; then
    log_step "❌ Nightly completed with $FAILED failure(s)"
    exit 1
fi

log_step "✅ Nightly completed with 0 failures"
exit 0
