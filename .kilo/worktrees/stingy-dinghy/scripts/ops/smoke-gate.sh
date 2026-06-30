#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ Yalıhan Bekçi: Smoke Gate (Regression Guard)
# ═══════════════════════════════════════════════════════════════════════════
#
# PRODUCTION-READY GUARD:
# - Wizard Step 1 Regression Test
# - Features Non-Empty Test
# - Wizard AI Integration Test
# - Context7 Integrity Scan
# - Bekçi Wizard Contract
#
# Exit Codes:
#   0 = All checks passed
#   1 = One or more checks failed
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail
IFS=$'\n\t'

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
LOG_DIR="storage/logs"
LOG_FILE="${LOG_DIR}/smoke-gate-${TIMESTAMP}.log"

mkdir -p "${LOG_DIR}"

log_step() {
    echo ""
    echo "═══════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
    echo "📋 ${1}" | tee -a "${LOG_FILE}"
    echo "═══════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
    echo "" | tee -a "${LOG_FILE}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a "${LOG_FILE}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}" | tee -a "${LOG_FILE}"
}

log_info() {
    echo -e "${BLUE}🧪 $1${NC}" | tee -a "${LOG_FILE}"
}

FAILED_CHECKS=0
TOTAL_CHECKS=5

run_check() {
    local check_name="$1"
    local command="$2"
    local description="$3"

    log_step "${check_name}"
    log_info "${description}"

    echo "" | tee -a "${LOG_FILE}"
    echo "Command: ${command}" | tee -a "${LOG_FILE}"
    echo "" | tee -a "${LOG_FILE}"

    if eval "${command}" >> "${LOG_FILE}" 2>&1; then
        local exit_code=$?
        log_success "${check_name} PASSED (exit: ${exit_code})"
        echo "" | tee -a "${LOG_FILE}"
        return 0
    else
        local exit_code=$?
        log_error "${check_name} FAILED (exit: ${exit_code})"
        echo "" | tee -a "${LOG_FILE}"
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
        return 1
    fi
}

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║  🛡️  YALIHAN BEKÇİ: SMOKE GATE (REGRESSION GUARD)          ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

echo "Starting smoke gate checks..." | tee -a "${LOG_FILE}"
echo "Log file: ${LOG_FILE}"
echo ""

WIZARD_EXIT=1
FEATURES_EXIT=1
AI_EXIT=1
CTX7_EXIT=1
BEKCI_EXIT=1

run_check \
    "1/5 Wizard Step 1 Regression" \
    "php artisan test --filter=WizardStep1TemplateDataTest" \
    "Ensuring Wizard Step 1 template loading works correctly"
WIZARD_EXIT=$?

run_check \
    "2/5 Features Non-Empty Guard" \
    "php artisan test --filter=FeaturesNonEmptyTest" \
    "Ensuring features endpoints return non-empty data"
FEATURES_EXIT=$?

run_check \
    "3/5 Wizard AI Integration" \
    "php artisan test --filter=WizardAiIntegrationTest" \
    "Ensuring Wizard AI endpoints (suggest, analyze_images) work"
AI_EXIT=$?

run_check \
    "4/5 SAB Governance Drift Scan" \
    "php artisan gov:drift:scan" \
    "Ensuring SAB governance compliance (0 drift allowed)"
CTX7_EXIT=$?

run_check \
    "5/5 Bekçi Wizard Contract" \
    "php artisan bekci:wizard-contract" \
    "Ensuring Wizard contract compliance"
BEKCI_EXIT=$?

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "SUMMARY"
echo "════════════════════════════════════════════════════════════════"
echo ""

echo "Exit Codes:"
echo "  WIZARD_EXIT:    ${WIZARD_EXIT}"
echo "  FEATURES_EXIT:  ${FEATURES_EXIT}"
echo "  AI_EXIT:        ${AI_EXIT}"
echo "  CTX7_EXIT:      ${CTX7_EXIT}"
echo "  BEKCI_EXIT:     ${BEKCI_EXIT}"
echo ""

if [ ${FAILED_CHECKS} -eq 0 ]; then
    log_success "All ${TOTAL_CHECKS} smoke gate checks PASSED!"
    echo ""
    echo "Report saved to: ${LOG_FILE}"
    echo ""
    exit 0
else
    log_error "${FAILED_CHECKS} of ${TOTAL_CHECKS} smoke gate checks FAILED!"
    echo ""
    echo "Report saved to: ${LOG_FILE}"
    echo ""
    echo "🔍 Review failing checks in log file above ⬆️"
    echo "📝 Fix suggestions:"
    echo "  - Check test output in log file"
    echo "  - Review Context7 violations"
    echo "  - Review Bekçi contract violations"
    echo ""
    exit 1
fi
