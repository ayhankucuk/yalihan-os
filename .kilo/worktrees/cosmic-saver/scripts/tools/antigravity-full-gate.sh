#!/bin/bash

# Antigravity AI OS — Full Quality Gate Pipeline
# Path: scripts/tools/antigravity-full-gate.sh
# Purpose: Runs ALL Antigravity guard tools in the correct sequence.
#          This is the master command that combines all checks.
# Usage:
#   ./scripts/tools/antigravity-full-gate.sh           # Full gate
#   ./scripts/tools/antigravity-full-gate.sh --quick    # Skip artisan commands (fast mode)

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'
BOLD='\033[1m'

QUICK_MODE=false
if [ "$1" = "--quick" ]; then
    QUICK_MODE=true
fi

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PASS=0
FAIL=0
WARN=0
TOTAL=0

run_gate() {
    local GATE_NAME="$1"
    local GATE_CMD="$2"
    
    ((TOTAL++))
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  Gate ${TOTAL}: ${BOLD}${GATE_NAME}${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    
    eval "$GATE_CMD" 2>&1
    local EXIT_CODE=$?
    
    if [ $EXIT_CODE -eq 0 ]; then
        echo -e "${GREEN}  ✅ PASSED: ${GATE_NAME}${NC}"
        ((PASS++))
    else
        echo -e "${RED}  ❌ FAILED: ${GATE_NAME}${NC}"
        ((FAIL++))
    fi
    
    return $EXIT_CODE
}

echo -e "${BLUE}${BOLD}"
echo "  ╔══════════════════════════════════════════════════════════╗"
echo "  ║        🚀 ANTIGRAVITY FULL QUALITY GATE PIPELINE        ║"
echo "  ║    Yalıhan AI OS — Technical Constitution Enforcer      ║"
echo "  ╚══════════════════════════════════════════════════════════╝"
echo -e "${NC}"

START_TIME=$(date +%s)

# Gate 1: Antigravity Preflight (10 Golden Rules on modified files)
run_gate "Preflight Guard (10 Golden Rules)" "${SCRIPT_DIR}/antigravity-preflight.sh"

# Gate 2: Layout Validator
run_gate "Layout Validator (Correct @extends)" "${SCRIPT_DIR}/antigravity-layout-check.sh"

# Gate 3: Route Duplicate Check
run_gate "Route Duplication Guard" "${SCRIPT_DIR}/antigravity-route-check.sh --duplicates"

if [ "$QUICK_MODE" = false ]; then
    # Gate 4: SAB Integrity Scan (artisan)
    run_gate "SAB Integrity Scan" "php artisan sab:integrity-scan"
    
    # Gate 5: Bekçi Health Check (artisan)
    run_gate "Bekçi Health Check" "php artisan bekci:health"
fi

END_TIME=$(date +%s)
ELAPSED=$((END_TIME - START_TIME))

echo ""
echo -e "${BLUE}${BOLD}"
echo "  ╔══════════════════════════════════════════════════════════╗"
echo "  ║                  📊 PIPELINE SUMMARY                    ║"
echo "  ╚══════════════════════════════════════════════════════════╝"
echo -e "${NC}"
echo -e "  Total Gates:  ${BOLD}${TOTAL}${NC}"
echo -e "  Passed:       ${GREEN}${BOLD}${PASS}${NC}"
echo -e "  Failed:       ${RED}${BOLD}${FAIL}${NC}"
echo -e "  Duration:     ${ELAPSED}s"
echo ""

if [ $FAIL -gt 0 ]; then
    echo -e "${RED}${BOLD}  ╔══════════════════════════════════════════════════════════╗"
    echo -e "  ║  🔴 QUALITY GATE FAILED — DO NOT PROCEED WITH CHANGES   ║"
    echo -e "  ╚══════════════════════════════════════════════════════════╝${NC}"
    exit 1
else
    echo -e "${GREEN}${BOLD}  ╔══════════════════════════════════════════════════════════╗"
    echo -e "  ║  🟢 ALL QUALITY GATES PASSED — SAFE TO PROCEED          ║"
    echo -e "  ╚══════════════════════════════════════════════════════════╝${NC}"
    exit 0
fi
