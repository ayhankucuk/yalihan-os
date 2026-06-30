#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# CI-GATE-04: Controller Size Guard
# ═══════════════════════════════════════════════════════════════════════════
# Detects oversized controllers (method count + line count).
# WARN: >15 methods or >800 lines
# FAIL: >25 methods or >1200 lines
# Compares against baseline for growth detection.
# ═══════════════════════════════════════════════════════════════════════════
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
BASELINE_FILE="${PROJECT_ROOT}/scripts/guards/baselines/baseline-controller-size.txt"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

cd "${PROJECT_ROOT}"

CONTROLLER_DIR="app/Http/Controllers"

echo "═══════════════════════════════════════════════════════════════"
echo "🔍 CI-GATE-04: Controller Size Guard"
echo "═══════════════════════════════════════════════════════════════"
echo ""

FAIL_COUNT=0
WARN_COUNT=0
CURRENT_FILE=$(mktemp)
trap "rm -f '${CURRENT_FILE}'" EXIT

# ─── Scan all controllers ───
find "${CONTROLLER_DIR}" -name "*.php" -type f | sort | while IFS= read -r controller; do
    # Count lines
    line_count=$(wc -l < "${controller}" | tr -d ' ')

    # Count public methods (excluding constructor)
    total_public=$(grep -cE '^\s+public\s+function\s+' "${controller}" 2>/dev/null) || true
    constructor_count=$(grep -cE '^\s+public\s+function\s+__construct' "${controller}" 2>/dev/null) || true
    [[ -z "${total_public}" ]] && total_public=0
    [[ -z "${constructor_count}" ]] && constructor_count=0
    method_count=$((total_public - constructor_count))

    # Determine severity
    severity="OK"
    if [[ ${method_count} -gt 25 ]] || [[ ${line_count} -gt 1200 ]]; then
        severity="FAIL"
    elif [[ ${method_count} -gt 15 ]] || [[ ${line_count} -gt 800 ]]; then
        severity="WARN"
    fi

    echo "${controller}|${method_count}|${line_count}|${severity}" >> "${CURRENT_FILE}"
done

# ─── Report ───
FAIL_ENTRIES=$(grep '|FAIL$' "${CURRENT_FILE}" 2>/dev/null || true)
WARN_ENTRIES=$(grep '|WARN$' "${CURRENT_FILE}" 2>/dev/null || true)

if [[ -n "${FAIL_ENTRIES}" ]]; then
    FAIL_COUNT=$(echo "${FAIL_ENTRIES}" | wc -l | tr -d ' ')
else
    FAIL_COUNT=0
fi
if [[ -n "${WARN_ENTRIES}" ]]; then
    WARN_COUNT=$(echo "${WARN_ENTRIES}" | wc -l | tr -d ' ')
else
    WARN_COUNT=0
fi

if [[ -n "${WARN_ENTRIES}" ]]; then
    echo -e "${YELLOW}⚠️  WARN controllers (>15 methods or >800 lines):${NC}"
    echo "${WARN_ENTRIES}" | while IFS='|' read -r path methods lines sev; do
        echo "  ⚠️  ${path} — ${methods} methods, ${lines} lines"
    done
    echo ""
fi

if [[ -n "${FAIL_ENTRIES}" ]]; then
    echo -e "${RED}❌ FAIL controllers (>25 methods or >1200 lines):${NC}"
    echo "${FAIL_ENTRIES}" | while IFS='|' read -r path methods lines sev; do
        echo "  ⛔ ${path} — ${methods} methods, ${lines} lines"
    done
    echo ""
fi

# ─── Generate baseline if requested ───
if [[ "${1:-}" == "--generate-baseline" ]]; then
    mkdir -p "$(dirname "${BASELINE_FILE}")"
    cp "${CURRENT_FILE}" "${BASELINE_FILE}"
    TOTAL=$(wc -l < "${CURRENT_FILE}" | tr -d ' ')
    echo -e "${GREEN}✅ Baseline generated: ${TOTAL} controllers profiled${NC}"
    exit 0
fi

# ─── Baseline comparison for growth detection ───
if [[ ! -f "${BASELINE_FILE}" ]]; then
    echo -e "${YELLOW}⚠️  No baseline found at ${BASELINE_FILE}${NC}"
    echo "   Run with --generate-baseline to create initial baseline"
else
    # Check for growth: any controller that grew past a threshold since baseline
    GROWTH_VIOLATIONS=0
    while IFS='|' read -r path methods lines sev; do
        baseline_entry=$(grep "^${path}|" "${BASELINE_FILE}" 2>/dev/null || true)
        if [[ -n "${baseline_entry}" ]]; then
            baseline_methods=$(echo "${baseline_entry}" | cut -d'|' -f2)
            if [[ ${methods} -gt ${baseline_methods} ]]; then
                if [[ ${methods} -gt 25 ]] && [[ ${baseline_methods} -le 25 ]]; then
                    echo -e "${RED}  ⛔ GROWTH VIOLATION: ${path} grew from ${baseline_methods} → ${methods} methods (crossed FAIL threshold)${NC}"
                    GROWTH_VIOLATIONS=$((GROWTH_VIOLATIONS + 1))
                fi
            fi
        else
            # New controller — check if it starts above threshold
            if [[ ${methods} -gt 25 ]]; then
                echo -e "${RED}  ⛔ NEW OVERSIZED: ${path} — ${methods} methods (above FAIL threshold)${NC}"
                GROWTH_VIOLATIONS=$((GROWTH_VIOLATIONS + 1))
            fi
        fi
    done < "${CURRENT_FILE}"

    if [[ ${GROWTH_VIOLATIONS} -gt 0 ]]; then
        FAIL_COUNT=$((FAIL_COUNT + GROWTH_VIOLATIONS))
    fi
fi

# ─── Summary ───
TOTAL=$(wc -l < "${CURRENT_FILE}" | tr -d ' ')
echo "📊 Total controllers scanned: ${TOTAL}"
echo "📊 WARN: ${WARN_COUNT} | FAIL: ${FAIL_COUNT}"

if [[ ${FAIL_COUNT} -gt 0 ]]; then
    echo ""
    echo -e "${RED}❌ FAIL: ${FAIL_COUNT} controller(s) exceed size limits${NC}"
    echo "Fix: Decompose large controllers using Action classes or sub-controllers"
    exit 1
fi

if [[ ${WARN_COUNT} -gt 0 ]]; then
    echo ""
    echo -e "${YELLOW}⚠️  WARN: ${WARN_COUNT} controller(s) approaching size limits${NC}"
fi

echo -e "${GREEN}✅ PASS — No controller size violations${NC}"
exit 0
