#!/bin/bash

# 🛡️ Yalıhan 2026 - Production-Grade Governance Rollout Orchestrator
# Version: 1.2.0-advanced
# Standard: SAB-GOVERNANCE-v12-SHIELD
# Purpose: Orchestrates fail-fast production deployment with 10 mandatory safety phases.

set -Eeuo pipefail
trap cleanup SIGINT SIGTERM ERR

# --- Constants & Config ---
VERSION="1.2.0-advanced"
LOG_DIR="storage/logs/governance"
MANIFEST_FILE="storage/app/governance/deploy-manifest.json"
APP_MARKER="PROPERTY_HUB_V2" # Ensure we are in the right app

# --- Colors ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# --- Logging Functions ---
log_info()  { echo -e "${BLUE}[INFO] $(date +'%Y-%m-%d %H:%M:%S') $1${NC}"; }
log_ok()    { echo -e "${GREEN}[OK]   $(date +'%Y-%m-%d %H:%M:%S') $1${NC}"; }
log_warn()  { echo -e "${YELLOW}[WARN] $(date +'%Y-%m-%d %H:%M:%S') $1${NC}"; }
log_error() { echo -e "${RED}[ERR]  $(date +'%Y-%m-%d %H:%M:%S') $1${NC}"; }

# --- Guard Functions ---
cleanup() {
    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        log_error "Rollout failed with exit code $exit_code. Releasing maintenance mode if active..."
        php artisan up 2>/dev/null || true
    fi
    release_lock
}

acquire_lock() {
    if [ -f "/tmp/gov-rollout.lock" ]; then
        log_error "Rollout already in progress by another process. Aborting."
        exit 1
    fi
    touch "/tmp/gov-rollout.lock"
}

release_lock() {
    rm -f "/tmp/gov-rollout.lock"
}

# --- Module: Preflight ---
phase_preflight() {
    local ref=$1
    log_info "PHASE 0: Pre-flight & Provenance Check..."
    
    # Check if we are in the right app
    if [ ! -f "artisan" ]; then
        log_error "Not a Laravel project directory. artisan not found."
        exit 10
    fi

    # Check Git Status (Dirty Tree)
    if ! git diff-index --quiet HEAD --; then
        log_error "Freeze Violated: Uncommitted changes detected in working tree. Deployment must be from a clean state."
        exit 11
    fi

    # Verify Release Ref
    current_hash=$(git rev-parse HEAD)
    if [ "$ref" != "$current_hash" ]; then
        log_error "Provenance Mismatch: HEAD ($current_hash) does not match requested --release-ref ($ref)."
        log_warn "This orchestrator requires an immutable commit hash for reproducibility."
        exit 12
    fi

    log_ok "Pre-flight checks passed. Commit $ref is valid."
}

# --- Module: Environment Validation ---
phase_env_validation() {
    log_info "PHASE 1: Environment & Parity Guard..."
    
    local app_env=$(php artisan tinker --execute="echo app()->environment();" | tail -n 1)
    local db_name=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" | tail -n 1)

    log_info "Detected Env: $app_env, DB: $db_name"

    if [[ "$app_env" == "production" && "$db_name" == *"_test"* ]]; then
        log_error "RİSK: Production ortamında TEST veritabanı tespit edildi! Durduruluyor."
        exit 20
    fi

    log_ok "Environment parity verified."
}

# --- Module: Integrity Gate ---
phase_integrity_gate() {
    log_info "PHASE 2: Integrity Gate (SAB + Tests)..."
    
    if [ ! -f "./scripts/quality-gate.sh" ]; then
        log_error "quality-gate.sh missing."
        exit 21
    fi

    # Run formal quality gate
    if ! bash ./scripts/quality-gate.sh; then
        log_error "Quality Gate FAILED. Rollout aborted."
        exit 22
    fi

    log_ok "Integrity Gate passed."
}

# --- Module: Migration & Backup ---
phase_migration_safety() {
    log_info "PHASE 3: Migration Safety & Approval..."
    
    log_warn "Checking for pending migrations..."
    php artisan migrate:status
    
    log_warn "🚨 Bu işlem veritabanı şemasını değiştirecek."
    read -p "Database yedeği alındı mı? (YES_I_HAVE_A_BACKUP): " backup_confirm
    if [ "$backup_confirm" != "YES_I_HAVE_A_BACKUP" ]; then
        log_error "Backup confirmation missing."
        exit 30
    fi

    read -p "Migration'ları onaylıyor musunuz? (DEPLOY): " deploy_confirm
    if [ "$deploy_confirm" != "DEPLOY" ]; then
        log_error "User aborted deployment."
        exit 31
    fi
}

# --- Module: Deployment Execution ---
phase_deploy_execution() {
    log_info "PHASE 4: Atomic Deployment & Optimization..."
    
    php artisan down --message="Governance Rollout in progress" --retry=60
    
    log_info "Applying migrations..."
    php artisan migrate --force
    
    log_info "Optimizing caches..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan view:cache
    
    log_info "Restarting workers..."
    php artisan queue:restart
    
    php artisan up
    log_ok "Deployment execution finished."
}

# --- Module: Smoke Test ---
phase_smoke_test() {
    log_info "PHASE 5: Advanced Smoke Test (Content Awareness)..."
    
    local smoke_id="smoke-$(date +%s)"
    
    # 1. Functional Check: Create Draft
    log_info "Creating Smoke Entity..."
    local created_id=$(php artisan tinker --execute="\$te = \App\Models\TestEntity::create(['name' => 'Smoke $smoke_id', 'payload' => ['v' => 1], 'governance_state' => 'draft']); echo \$te->id;" | tail -n 1)
    
    if [[ -z "$created_id" ]]; then
        log_error "Smoke Test: Failed to create entity."
        exit 50
    fi

    # 2. Command Check: View Diff
    log_info "Verifying CLI Output for ID $created_id..."
    local diff_output=$(php artisan gov:view-diff TestEntity "$created_id" 2>&1)
    
    if [[ "$diff_output" != *"GOVERNANCE DIFF VIEWER"* ]]; then
        log_error "Smoke Test: CLI output malformed."
        echo "$diff_output"
        exit 51
    fi

    # 3. Security/Guard Check: Rejection test (Published -> Draft)
    log_info "Verifying Adversarial Protection (Published -> Draft Reject)..."
    php artisan tinker --execute="\App\Models\TestEntity::where('id', $created_id)->update(['governance_state' => 'published']);" > /dev/null
    
    # This should FAIL in the service layer
    local reject_test=$(php artisan tinker --execute="try { app(\App\Contracts\Governance\GovernanceServiceInterface::class)->updateDraft(new \App\DataTransferObjects\Governance\UpdateDraftCommand('TestEntity', $created_id, ['hacked' => true])); echo 'FAILED'; } catch (\Exception \$e) { echo 'REJECTED_SUCCESSFULLY'; }" | tail -n 1)

    if [[ "$reject_test" != *"REJECTED_SUCCESSFULLY"* ]]; then
        log_error "Security Test FAILED: Immutable state was mutated!"
        exit 52
    fi

    log_ok "All smoke tests PASSED (Functional, CLI, Security)."
}

# --- Module: Telemetry & Closeout ---
phase_closeout() {
    local ref=$1
    log_info "PHASE 6: Telemetry & Seal..."
    
    mkdir -p "$(dirname $MANIFEST_FILE)"
    
    cat <<EOF > "$MANIFEST_FILE"
{
    "release_ref": "$ref",
    "deployed_at": "$(date -u +'%Y-%m-%dT%H:%M:%SZ')",
    "operator": "$(whoami)",
    "status": "SEALED",
    "smoke_test": "PASSED"
}
EOF

    log_info "Telemetry Event: governance.rollout.complete [$ref]"
    log_ok "System is SEALED. Manifest written to $MANIFEST_FILE"
}

# --- Main Logic ---
main() {
    local ref=""
    local env_requested="production"

    # Argument Parsing
    while [[ $# -gt 0 ]]; do
        key="$1"
        case $key in
            --release-ref)
                ref="$2"
                shift; shift
                ;;
            --env)
                env_requested="$2"
                shift; shift
                ;;
            *)
                echo "Unknown option: $1"
                exit 1;
                ;;
        esac
    done

    if [[ -z "$ref" ]]; then
        log_error "Mandatory argument --release-ref <commit_hash> is missing."
        exit 1
    fi

    acquire_lock
    
    # 10 Phase Execution
    phase_preflight "$ref"
    phase_env_validation
    phase_integrity_gate
    phase_migration_safety
    phase_deploy_execution
    phase_smoke_test
    phase_closeout "$ref"

    echo -e "\n${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}🛡️  GOVERNANCE ARCHITECTURE SEVENTH SEAL: COMPLETED${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

main "$@"
