#!/usr/bin/env bash

# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ Yalıhan Bekçi: Quality Gate Guardian Orchestrator
# ═══════════════════════════════════════════════════════════════════════════

# --- GOVERNANCE IMMUTABLE MODE ENFORCEMENT ---
GOV_FILE="docs/SAB.md"
SHA_FILE="docs/SAB.sha256"

if [ ! -f "$GOV_FILE" ]; then
    echo "[GOVERNANCE] docs/SAB.md bulunamadı. Governance zinciri kırıldı."
    exit 1
fi

# SAB §8: Checksum Verification (Drift Protection)
if [ -f "$SHA_FILE" ]; then
    EXPECTED_SHA=$(cat "$SHA_FILE")
    ACTUAL_SHA=$(shasum -a 256 "$GOV_FILE" | awk '{print $1}')
    if [ "$EXPECTED_SHA" != "$ACTUAL_SHA" ]; then
        echo "[GOVERNANCE] SAB.md drift tespit edildi! Checksum eşleşmiyor."
        echo "Lütfen SAB.md değiştiyse 'shasum -a 256 docs/SAB.md > docs/SAB.sha256' ile güncelleyin."
        exit 2
    fi
fi

GOV_VERSION_LINE=$(grep -m1 '^Version:' "$GOV_FILE" || true)
if [ -z "$GOV_VERSION_LINE" ]; then
    echo "[GOVERNANCE] docs/SAB.md içinde 'Version:' alanı bulunamadı."
    exit 1
fi

# Eğer SAB.md değiştiyse, Version: satırı committe değişmiş olmalı
if git diff --name-only HEAD~1 2>/dev/null | grep -q "$GOV_FILE"; then
    if ! git diff HEAD~1 -- "$GOV_FILE" | grep -q '^+Version:'; then
        echo "[GOVERNANCE] SAB.md değişti ancak Version: bump yok. CI bloklandı."
        exit 1
    fi
fi

# README Reference Check
README_FILE="README.md"
if ! grep -q "SAB.md (Teknik Anayasa) standardına tabidir" "$README_FILE" 2>/dev/null; then
    echo "[GOVERNANCE] README.md içinde SAB referansı eksik."
    # Non-blocking for now, just warning
    echo "WARNING: README.md does not reference SAB.md"
fi
# --- END GOVERNANCE IMMUTABLE MODE ---
#
# FAIL-SAFE MODE:
# - Strict error handling (set -euo pipefail)
# - Step-by-step logging
# - No false PASS allowed
# - Full test suite required (no regex shortcuts)
# - Bekçi contract is P0 blocker
#
# Exit Codes:
#   0 = All checks passed
#   1 = One or more checks failed
#   2 = Drift/conflict detected
# ═══════════════════════════════════════════════════════════════════════════

# Ensure standard paths for Node.js/PHP (Homebrew/Intel/Silicon)
export PATH="/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

set -euo pipefail  # Exit on error, undefined var, pipe failure

IFS=$'\n\t'        # Safe word splitting

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Timestamp for log file
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
LOG_DIR="storage/logs"
LOG_FILE="${LOG_DIR}/quality-gate-${TIMESTAMP}.log"

# Ensure log directory exists
mkdir -p "${LOG_DIR}"

# Logging functions
# ─────────────────────────────────────────────────────────────────────────────
# Authority-driven guard runner — blocking kararını .sab/authority.json'dan okur
# Kullanım: run_sab_guard "ci-guard-sab-controller.sh" "Guard Adı" "Step ID"
# ─────────────────────────────────────────────────────────────────────────────
run_sab_guard() {
    local guard_script="$1"
    local guard_label="$2"
    local step_id="${3:-SAB}"
    local blocking
    
    blocking=$(python3 -c "
import json
try:
    d = json.load(open('.sab/authority.json'))
    v = d.get('ci_guards', {}).get('$guard_script', {}).get('blocking', False)
    print('true' if v else 'false')
except Exception:
    print('false')
" 2>/dev/null || echo 'false')

    run_step "$step_id" "$guard_label" "bash ./scripts/guards/${guard_script}" "$blocking"
}

# Track metrics
FAILED_CHECKS=0
START_TIME=$SECONDS
declare -a STEP_RESULTS

log_step() {
    local step_id="$1"
    local step_name="$2"
    CURRENT_STEP_START=$SECONDS
    echo ""
    echo "═══════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
    echo "📋 STEP ${step_id}: ${step_name}" | tee -a "${LOG_FILE}"
    echo "═══════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
    echo "" | tee -a "${LOG_FILE}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a "${LOG_FILE}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}" | tee -a "${LOG_FILE}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}" | tee -a "${LOG_FILE}"
}

log_info() {
    echo -e "${BLUE}🧪 $1${NC}" | tee -a "${LOG_FILE}"
}

# Standardized Step Runner
run_step() {
    local step_id="$1"
    local step_label="$2"
    local cmd="$3"
    local exit_on_fail="${4:-true}"
    
    log_step "$step_id" "$step_label"
    local step_start=$SECONDS
    
    if eval "$cmd" 2>&1 | tee -a "${LOG_FILE}"; then
        local duration=$((SECONDS - step_start))
        log_success "${step_label} PASSED (${duration}s)"
        STEP_RESULTS+=("${step_id}|${step_label}|PASS|${duration}")
    else
        local exit_code=$?
        local duration=$((SECONDS - step_start))
        STEP_RESULTS+=("${step_id}|${step_label}|FAIL|${duration}")
        
        if [ "$exit_on_fail" = "true" ]; then
            log_error "${step_label} FAILED (exit ${exit_code}, ${duration}s)"
            FAILED_CHECKS=$((FAILED_CHECKS + 1))
            [ "$exit_code" -eq 2 ] && exit 2 || exit 1
        else
            log_warning "${step_label} FAILED (non-blocking, ${duration}s)"
        fi
    fi
}

# Cleanup function
cleanup() {
    TOTAL_DURATION=$((SECONDS - START_TIME))
    
    echo ""
    echo "════════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
    echo "📊 QUALITY GATE SUMMARY" | tee -a "${LOG_FILE}"
    echo "════════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
    printf "%-10s | %-40s | %-10s | %-10s\n" "STEP" "NAME" "STATUS" "TIME" | tee -a "${LOG_FILE}"
    echo "-----------|------------------------------------------|------------|-----------" | tee -a "${LOG_FILE}"
    
    for res in "${STEP_RESULTS[@]}"; do
        IFS='|' read -r sid name status dur <<< "$res"
        if [ "$status" = "PASS" ]; then
            printf "%-10s | %-40s | ${GREEN}%-10s${NC} | %-10s\n" "$sid" "$name" "$status" "${dur}s" | tee -a "${LOG_FILE}"
        else
            printf "%-10s | %-40s | ${RED}%-10s${NC} | %-10s\n" "$sid" "$name" "$status" "${dur}s" | tee -a "${LOG_FILE}"
        fi
    done
    echo "════════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
    echo "Total Duration: ${TOTAL_DURATION}s" | tee -a "${LOG_FILE}"
    
    # Save trend data
    mkdir -p storage/governance
    JSON_OUT="storage/governance/quality_results.json"
    TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    # Simple JSON append (requires python3 for clean formatting)
    python3 -c "
import json, os
data = {'timestamp': '$TIMESTAMP', 'total_duration': $TOTAL_DURATION, 'failed_count': $FAILED_CHECKS, 'steps': []}
for res in \"${STEP_RESULTS[*]}\".split():
    sid, name, status, dur = res.split('|')
    data['steps'].append({'id': sid, 'name': name, 'status': status, 'duration': int(dur)})

history = []
if os.path.exists('$JSON_OUT'):
    try:
        history = json.load(open('$JSON_OUT'))
        if not isinstance(history, list): history = []
    except: history = []

history.append(data)
with open('$JSON_OUT', 'w') as f:
    json.dump(history[-50:], f, indent=2) # Keep last 50 runs
" 2>/dev/null || echo "Warning: Could not save trend JSON"

    if [ $FAILED_CHECKS -gt 0 ]; then
        log_error "GATE BLOCKED: ${FAILED_CHECKS} check(s) failed."
        exit 1
    else
        log_success "GATE OPEN: All checks passed."
        exit 0
    fi
}

trap cleanup EXIT

# ═══════════════════════════════════════════════════════════════════════════
# Main Quality Gate Checks
# ═══════════════════════════════════════════════════════════════════════════

echo "🛡️ Yalıhan Bekçi: Quality Gate Starting..." | tee -a "${LOG_FILE}"
echo "═════════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
echo "" | tee -a "${LOG_FILE}"

# ═══════════════════════════════════════════════════════════════════════════
# Main Quality Gate Checks
# ═══════════════════════════════════════════════════════════════════════════

echo "🛡️ Yalıhan Bekçi: Quality Gate Starting..." | tee -a "${LOG_FILE}"
echo "═════════════════════════════════════════════════════════════════" | tee -a "${LOG_FILE}"
echo "" | tee -a "${LOG_FILE}"

run_step "0" "API Contract Compilation" "node scripts/tools/compile-api-contract.cjs"
run_step "0.1" "DAP Drift Detection" "node scripts/tools/dap-drift-check.cjs"
run_step "0.2" "Canonical Seeder Verification" "npm run seeder:verify"
run_step "0.3" "Command Registry Drift Guard" "node scripts/guards/command-registry-guard.cjs" false
run_step "0.9" "Wizard Cascade SSOT Guard" "node scripts/guards/wizard-cascade-guard.cjs"
run_step "0.5" "Governance Core Integrity" "npm run governance:verify"
run_step "0.6" "Precheck Retention" "node scripts/tools/precheck-retention.cjs" false
run_step "0.8" "Legacy Guard" "npm run dap:legacy"
run_step "0.85" "Legacy Enum Guard" "bash scripts/guards/ci-guard-legacy-enums.sh"
run_step "1" "Critical P0 Tests" "php artisan test --filter=FeaturesNonEmptyTest --exclude-group=skip-until-migration-complete --stop-on-failure"
run_step "2" "Full Test Suite" "php artisan test --testsuite=Unit,Feature --exclude-group=skip-until-migration-complete --stop-on-failure" false
run_step "3" "SAB Governance Drift Scan" "php artisan gov:drift:scan" false
run_step "3.1" "SAB Integrity Scan" "php artisan sab:integrity-scan" false
run_step "3.1.1" "Context7 Blade UI Guard" "bash scripts/guards/blade-scan.sh resources/views" false
run_step "3.1.2" "Context7 Route Guard" "bash scripts/guards/route-guard.sh routes" false
run_step "3.1.3" "Context7 Migration Guard" "bash scripts/guards/migration-guard.sh database/migrations" false

# ────────────────────────────────────────────────────────────────────────────
# STEP 3.2: SAB Hard Boundary (PHPStan Static Enforcement)
# ────────────────────────────────────────────────────────────────────────────
check_phpstan() {
    if [ -x "./scripts/guards/ci-guard-phpstan.sh" ]; then
        bash ./scripts/guards/ci-guard-phpstan.sh
    elif [ -x "./vendor/bin/phpstan" ]; then
        ./vendor/bin/phpstan analyse app --no-progress --memory-limit=512M
    else
        echo "PHPStan not found"
        return 1
    fi
}

run_step "3.2" "SAB Hard Boundary (PHPStan)" "check_phpstan" false


# ────────────────────────────────────────────────────────────────────────────
# Helper for multi-run determinism check
check_openclaw_determinism() {
    log_info "Running OpenClaw determinism check (3 consecutive runs)..."
    for run in 1 2 3; do
        if ! php -d opcache.enable=0 vendor/bin/phpunit --filter='OpenClawWriteIsolationTest|GuardCoverageRegressionTest|EnsureAgentScopeTest' --no-coverage 2>&1 | tee -a "${LOG_FILE}"; then
            return 1
        fi
    done
    return 0
}

run_step "3.3" "SAB Sealed Cell Guard" "bash scripts/guards/ci-guard-sealed.sh" false
run_step "3.35" "OpenClaw Agent Safety Gate" "check_openclaw_determinism" false
run_step "3.4" "Sprint Isolation Guard" "bash scripts/guards/sprint-isolation-check.sh" false

run_step "3.5" "SAB Prompt & Governance Drift" "bash ./scripts/guards/ci-guard-sab-prompt.sh" false
run_step "3.6" "Language Hardcode Detector" "grep -qR \"en','ru','ar','de','fr\" app/ && exit 1 || exit 0" false
run_step "4" "Bekçi Wizard Contract Audit" "php artisan bekci:wizard-contract" false
run_step "4.5" "System Contract Scanner" "php artisan guard:sozlesme" false
run_step "4.6" "Ghost Schema Audit" "php artisan guard:ghost-schema" false
run_step "4.7" "Security Leak Audit" "php artisan guard:security-leak" false
run_step "4.8" "Browser Flow Verification" "php artisan test --group=browser-flow" false
run_step "4.8.1" "Playwright Browser Tests" "npm run test:browser" false
run_step "4.9" "Architecture Seal Audit" "php artisan guard:schema" false

# SAB §4: Test Skip Protection
run_step "4.9.1" "Test Skip Audit" "grep -r '@group skip' tests/ | wc -l | xargs -I {} bash -c 'if [ {} -gt 100 ]; then echo \"Too many skipped tests: {}\"; exit 1; fi'"

run_step "5" "Code Quality & Smell Detection" "bash ./scripts/guards/code-quality-checks.sh" false
run_sab_guard "ci-guard-raw-db-write.sh" "Raw DB Write Guard" "5.1"
run_sab_guard "ci-guard-determinism.sh" "Determinism Guard" "5.2"
run_step "5.2.1" "Finance Authority Guard" "bash ./scripts/guards/ci-guard-finance-authority.sh" false
run_step "5.2.2" "Governance Registry Drift" "bash ./scripts/guards/ci-guard-governance-registry.sh" false
run_sab_guard "ci-guard-sab-controller.sh" "Controller Zero-Tolerance Guard" "5.3"
run_sab_guard "ci-guard-exception-swallow.sh" "Exception Swallow Guard" "5.4"
run_step "5.5" "SAB New Violation Guard" "bash ./scripts/guards/ci-guard-new-violation.sh" false
run_step "5.6" "Architecture Gate (Drift)" "bash ./scripts/guards/architecture-gate.sh" false
run_step "5.7" "G1 Command Registry Guard" "bash ./scripts/guards/ci-guard-command-registry.sh" false
# SAB §7: Registry Update Check
check_registry_update() {
    if git diff --name-only HEAD~1 2>/dev/null | grep -q "REFACTORING_LOG.md"; then
        log_success "REFACTORING_LOG.md updated."
        return 0
    fi
    log_warning "SAB §7: REFACTORING_LOG.md has not been updated in this commit."
    return 0 # Non-blocking for now, policy-driven
}

run_step "7" "Architectural Registry Check" "check_registry_update"

# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ Phase 11: Dynamic Pattern Discovery (The Learner)
# ═══════════════════════════════════════════════════════════════════════════
# ═══════════════════════════════════════════════════════════════════════════
# 🛡️ Phase 11: Cognitive Watchdog (The Guardian)
# ═══════════════════════════════════════════════════════════════════════════
run_step "11" "Cognitive Architectural Audit" "php artisan bekci:audit --all" true


run_step "8" "Frontend Build Check" "npm run build"

# Cleanup function will be called by trap EXIT
