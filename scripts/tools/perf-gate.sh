#!/bin/bash
# Performance Regression Gate - CI Enforcement
# Part of L5 Self-Protecting System
#
# Purpose: Block PR merge if performance degrades beyond acceptable thresholds
# Usage: ./scripts/perf-gate.sh
# Exit Code: 0 = PASS, 1 = FAIL (blocks PR)

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

BASELINE_FILE="reports/performance-baseline.json"
FAIL=0

echo "🛡️  Performance Regression Gate"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Check if baseline exists
if [ ! -f "$BASELINE_FILE" ]; then
    echo -e "${YELLOW}⚠️  Warning: No baseline found. Generating...${NC}"
    ./scripts/perf-baseline-wizard.sh --save
    echo ""
    echo -e "${BLUE}ℹ️  Baseline created. Skipping regression check (first run).${NC}"
    exit 0
fi

# Load baseline
echo "📊 Loading baseline..."
if command -v jq &> /dev/null; then
    BASELINE_P95=$(cat "$BASELINE_FILE" | jq -r '.wizard_context_api.p95')
    THRESHOLD=$(cat "$BASELINE_FILE" | jq -r '.thresholds.wizard_context_p95')
else
    # Fallback if jq not available
    BASELINE_P95=$(grep -oP '"p95":\s*\K\d+' "$BASELINE_FILE" | head -1)
    THRESHOLD=400
fi

echo "  Baseline p95: ${BASELINE_P95} ms"
echo "  Threshold:    ${THRESHOLD} ms"
echo ""

# Measure current performance
echo "🔍 Measuring current performance..."
CURRENT_OUTPUT=$(./scripts/perf-baseline-wizard.sh)
CURRENT_P95=$(echo "$CURRENT_OUTPUT" | tail -1 | grep -oP 'wizard_context_p95":\s*\K\d+')
CURRENT_AVG=$(echo "$CURRENT_OUTPUT" | tail -1 | grep -oP 'wizard_context_avg":\s*\K\d+')

echo "  Current p95:  ${CURRENT_P95} ms"
echo "  Current avg:  ${CURRENT_AVG} ms"
echo ""

# Calculate regression percentage
if [ "$BASELINE_P95" -gt 0 ]; then
    REGRESSION_PCT=$(awk "BEGIN {print int(($CURRENT_P95 - $BASELINE_P95) * 100 / $BASELINE_P95)}")
else
    REGRESSION_PCT=0
fi

# Check 1: Absolute threshold
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "CHECK 1: Absolute Threshold"
if [ "$CURRENT_P95" -gt "$THRESHOLD" ]; then
    echo -e "${RED}❌ FAIL: p95 (${CURRENT_P95}ms) exceeds threshold (${THRESHOLD}ms)${NC}"
    FAIL=1
else
    echo -e "${GREEN}✅ PASS: p95 within absolute threshold${NC}"
fi
echo ""

# Check 2: Regression vs baseline (allow 20% degradation)
echo "CHECK 2: Regression vs Baseline"
ALLOWED_REGRESSION=20
if [ "$REGRESSION_PCT" -gt "$ALLOWED_REGRESSION" ]; then
    echo -e "${RED}❌ FAIL: Performance degraded by ${REGRESSION_PCT}% (max: ${ALLOWED_REGRESSION}%)${NC}"
    echo "   Baseline: ${BASELINE_P95}ms → Current: ${CURRENT_P95}ms"
    FAIL=1
elif [ "$REGRESSION_PCT" -gt 10 ]; then
    echo -e "${YELLOW}⚠️  WARNING: Performance degraded by ${REGRESSION_PCT}%${NC}"
    echo "   Baseline: ${BASELINE_P95}ms → Current: ${CURRENT_P95}ms"
    echo -e "${GREEN}✅ PASS: Within tolerance (${ALLOWED_REGRESSION}%)${NC}"
else
    echo -e "${GREEN}✅ PASS: Performance stable (${REGRESSION_PCT}% change)${NC}"
fi
echo ""

# Check 3: Error rate (from telemetry if available)
echo "CHECK 3: Error Rate"
ERROR_LOG="storage/logs/telemetry-$(date +%Y-%m-%d).log"
if [ -f "$ERROR_LOG" ]; then
    TOTAL_EVENTS=$(grep -c "frontend_event" "$ERROR_LOG" || echo "0")
    ERROR_EVENTS=$(grep -c '"success":false' "$ERROR_LOG" || echo "0")

    if [ "$TOTAL_EVENTS" -gt 0 ]; then
        ERROR_RATE=$(awk "BEGIN {print ($ERROR_EVENTS * 100 / $TOTAL_EVENTS)}")

        if (( $(echo "$ERROR_RATE > 2.0" | bc -l) )); then
            echo -e "${RED}❌ FAIL: Error rate ${ERROR_RATE}% exceeds 2%${NC}"
            FAIL=1
        else
            echo -e "${GREEN}✅ PASS: Error rate ${ERROR_RATE}% within limits${NC}"
        fi
    else
        echo -e "${BLUE}ℹ️  SKIP: No telemetry events yet${NC}"
    fi
else
    echo -e "${BLUE}ℹ️  SKIP: No telemetry log found${NC}"
fi
echo ""

# Final result
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $FAIL -eq 1 ]; then
    echo -e "${RED}❌ PERFORMANCE GATE: FAILED${NC}"
    echo ""
    echo "Performance regression detected. PR merge blocked."
    echo ""
    echo "Actions:"
    echo "  1. Review recent changes for performance impact"
    echo "  2. Run: php artisan horizon:snapshot (check queue performance)"
    echo "  3. Run: php artisan telescope:prune (check slow queries)"
    echo "  4. If intentional, update baseline: ./scripts/perf-baseline-wizard.sh --save"
    echo ""
    exit 1
else
    echo -e "${GREEN}✅ PERFORMANCE GATE: PASSED${NC}"
    echo ""
    echo "All performance checks passed. Ready for merge."
    echo ""
    exit 0
fi
