#!/usr/bin/env bash
# ==============================================================================
# Yalıhan Governance Engine — Proposal Apply Engine (SAB v16.0 - Active)
# Purpose: Automate governance state transitions via JSON proposals.
# Dependencies: jq >= 1.6
# ==============================================================================

set -e

SAB_DIR=".sab"
PROPOSALS_DIR="$SAB_DIR/proposals"
HISTORY_DIR="$SAB_DIR/history"
APPLIED_DIR="$HISTORY_DIR/proposals"
SNAPSHOTS_DIR="$SAB_DIR/snapshots"
LATEST_DIR="$SAB_DIR/latest"
AUTHORITY_FILE="$SAB_DIR/authority.json"
AUDIT_LOG="$HISTORY_DIR/audit.log"

# --- 0. Initialize Environment ---
mkdir -p "$PROPOSALS_DIR" "$APPLIED_DIR" "$SNAPSHOTS_DIR" "$LATEST_DIR"
touch "$AUDIT_LOG"

log_event() {
    local level=$1
    local msg=$2
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp][$level] $msg" | tee -a "$AUDIT_LOG"
}

# --- 1. Engine Core Logic ---
process_proposal() {
    local proposal_path=$1
    local proposal_file=$(basename "$proposal_path")
    local pid="${proposal_file%.json}"
    
    log_event "INFO" "Processing proposal: $pid"
    
    # A. Pre-flight Snapshot
    local snapshot="$SNAPSHOTS_DIR/authority-pre-$pid-$(date +%s).json"
    cp "$AUTHORITY_FILE" "$snapshot"
    
    # B. Execution Gate (Using JQ)
    # Expected JSON: { "target": "domain.path", "action": "append|update|merge", "value": <any> }
    local target=$(jq -r '.target' "$proposal_path")
    local action=$(jq -r '.action' "$proposal_path")
    local value=$(jq -c '.value' "$proposal_path")
    
    if [[ "$target" == "null" || "$action" == "null" || "$value" == "null" ]]; then
        log_event "ERROR" "Malformed proposal $pid. Skipping."
        return 1
    fi
    
    local tmp_authority=$(mktemp)
    
    case "$action" in
        "append")
            # Append to array if not exists
            jq --argjson val "$value" --arg target_raw "$target" '
               ($target_raw | split(".")) as $path |
               setpath($path; (getpath($path) // []) + [$val] | unique)
            ' "$AUTHORITY_FILE" > "$tmp_authority"
            ;;
        "update")
            # Overwrite value
            jq --argjson val "$value" --arg target_raw "$target" '
               ($target_raw | split(".")) as $path |
               setpath($path; $val)
            ' "$AUTHORITY_FILE" > "$tmp_authority"
            ;;
        "merge")
            # Recursive merge for objects
            jq --argjson val "$value" --arg target_raw "$target" '
               ($target_raw | split(".")) as $path |
               setpath($path; (getpath($path) // {}) * $val)
            ' "$AUTHORITY_FILE" > "$tmp_authority"
            ;;
        *)
            log_event "ERROR" "Unknown action '$action' in $pid."
            rm "$tmp_authority"
            return 1
            ;;
    esac
    
    # C. Critical Path: Commit & Verify
    mv "$tmp_authority" "$AUTHORITY_FILE"
    
    # Run Integrity Checks
    if [ -f "./scripts/policy-check.sh" ]; then
        if ! ./scripts/policy-check.sh > /dev/null 2>&1; then
            log_event "CRITICAL" "Policy violation in $pid! ROLLING BACK."
            cp "$snapshot" "$AUTHORITY_FILE"
            return 1
        fi
    fi
    
    # D. Finalize State
    cp "$AUTHORITY_FILE" "$LATEST_DIR/authority.json"
    mv "$proposal_path" "$APPLIED_DIR/"
    log_event "SUCCESS" "Proposal $pid successfully applied and archived."
}

# --- 2. Main Execution Loop ---
shopt -s nullglob
pending_proposals=("$PROPOSALS_DIR"/*.json)

if [ ${#pending_proposals[@]} -eq 0 ]; then
    echo "[SAB-ENGINE] No pending proposals found."
    exit 0
fi

log_event "ENGINE" "Found ${#pending_proposals[@]} pending proposals. Starting execution..."

for p in "${pending_proposals[@]}"; do
    process_proposal "$p" || echo "[SAB-ENGINE] Failed to process $p"
done

echo "[SAB-ENGINE] Workflow complete."
