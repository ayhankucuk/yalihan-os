#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════
# 🏛️ Yalıhan Architecture Gate
# ═══════════════════════════════════════════════════════════════════════════
# CI-GATE-01: Single entrypoint for all architectural drift checks.
# Calls individual check scripts, collects results, exits 1 on FAIL.
#
# Usage:
#   ./scripts/architecture-gate.sh                  # Normal run
#   ./scripts/architecture-gate.sh --generate-baseline  # Generate all baselines
#
# Exit Codes:
#   0 = All checks passed (may have WARNs)
#   1 = One or more checks FAILED
# ═══════════════════════════════════════════════════════════════════════════
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

cd "${PROJECT_ROOT}"

# ─── Generate baseline mode ───
if [[ "${1:-}" == "--generate-baseline" ]]; then
    echo ""
    echo -e "${BOLD}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}🏛️  ARCHITECTURE GATE — Baseline Generation${NC}"
    echo -e "${BOLD}═══════════════════════════════════════════════════════════════${NC}"
    echo ""

    mkdir -p scripts/guards/baselines

    echo "Generating baselines..."
    echo ""

    bash "${SCRIPT_DIR}/check-service-locator.sh" --generate-baseline || true
    echo ""
    bash "${SCRIPT_DIR}/check-hardcoded-endpoints.sh" --generate-baseline || true
    echo ""
    bash "${SCRIPT_DIR}/check-controller-size.sh" --generate-baseline || true
    echo ""
    bash "${SCRIPT_DIR}/check-cross-domain-drift.sh" --generate-baseline || true
    echo ""
    bash "${SCRIPT_DIR}/check-deprecated-surface.sh" --generate-baseline || true

    echo ""
    echo -e "${GREEN}✅ All baselines generated in scripts/guards/baselines/${NC}"
    echo ""
    ls -la scripts/guards/baselines/baseline-*.txt 2>/dev/null || echo "No baseline files found"
    exit 0
fi

# ─── Normal run ───
echo ""
echo -e "${BOLD}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BOLD}🏛️  YALIHAN ARCHITECTURE GATE${NC}"
echo -e "${BOLD}═══════════════════════════════════════════════════════════════${NC}"
echo -e "Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

TOTAL_CHECKS=0
FAILED_CHECKS=0
WARNED_CHECKS=0
PASSED_CHECKS=0

RESULTS=()

run_check() {
    local name="$1"
    local script="$2"
    local mode="${3:-fail}"  # fail or warn

    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))

    echo ""
    if bash "${SCRIPT_DIR}/${script}" 2>&1; then
        RESULTS+=("PASS|${name}")
        PASSED_CHECKS=$((PASSED_CHECKS + 1))
    else
        exit_code=$?
        if [[ "${mode}" == "warn" ]]; then
            RESULTS+=("WARN|${name}")
            WARNED_CHECKS=$((WARNED_CHECKS + 1))
        else
            RESULTS+=("FAIL|${name}")
            FAILED_CHECKS=$((FAILED_CHECKS + 1))
        fi
    fi
    echo ""
    echo "───────────────────────────────────────────────────────────────"
}

# ─── Run all checks ───
run_check "Service Locator"        "check-service-locator.sh"     "fail"
run_check "Hardcoded Endpoints"    "check-hardcoded-endpoints.sh" "fail"
run_check "Controller Size"        "check-controller-size.sh"     "fail"
run_check "Cross Domain Drift"     "check-cross-domain-drift.sh"  "warn"   # Phase 1: warn only
run_check "Deprecated Surface"     "check-deprecated-surface.sh"  "fail"

# ─── Summary ───
echo ""
echo -e "${BOLD}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BOLD}📊 ARCHITECTURE GATE SUMMARY${NC}"
echo -e "${BOLD}═══════════════════════════════════════════════════════════════${NC}"
echo ""

for result in "${RESULTS[@]}"; do
    status=$(echo "${result}" | cut -d'|' -f1)
    name=$(echo "${result}" | cut -d'|' -f2)
    case "${status}" in
        PASS) echo -e "  ${GREEN}✅ PASS${NC}  ${name}" ;;
        WARN) echo -e "  ${YELLOW}⚠️  WARN${NC}  ${name}" ;;
        FAIL) echo -e "  ${RED}❌ FAIL${NC}  ${name}" ;;
    esac
done

echo ""
echo "───────────────────────────────────────────────────────────────"
echo -e "  Total: ${TOTAL_CHECKS} | Pass: ${PASSED_CHECKS} | Warn: ${WARNED_CHECKS} | Fail: ${FAILED_CHECKS}"
echo "───────────────────────────────────────────────────────────────"
echo ""

if [[ ${FAILED_CHECKS} -gt 0 ]]; then
    echo -e "${RED}${BOLD}RESULT: ❌ FAIL${NC}"
    echo ""
    echo "Fix the FAIL items above before proceeding."
    exit 1
fi

if [[ ${WARNED_CHECKS} -gt 0 ]]; then
    echo -e "${YELLOW}${BOLD}RESULT: ⚠️  PASS (with warnings)${NC}"
    echo ""
    exit 0
fi

echo -e "${GREEN}${BOLD}RESULT: ✅ PASS${NC}"
echo ""
exit 0
